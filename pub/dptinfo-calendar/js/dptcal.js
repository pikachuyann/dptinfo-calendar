/* Call callback() whenever the document is fully ready to be displayed
 * This is a code reused from StackOverflow - https://stackoverflow.com/a/7053197
 * which uses the code from plainjs - https://plainjs.com/javascript/events/running-code-when-the-document-is-ready-15/
 */
// Modified to check whether jQuery is loaded and fallback to jQuery if jQuery is loaded.
function DptinfoCalendarReady (callback) {
	if (window.jQuery) {
		$(document).ready( callback );
	} else {
		// In the case the document is already rendered:
		if (document.readyState!='loading') callback();
		// For modern browsers:
		else if (document.addEventListener) document.addEventListener('DOMContentLoaded', callback);
		// For older versions of Internet Explorer (<= 8):
		else document.attachEvent('onreadystatechange', function(){
			if (document.readyState=='complete') callback();
		});
	}
}

/* Now, I reuse code that was used for the computer science department of ENS Paris-Saclay website
 * More specifically, the code reused is from http://dptinfo.ens-cachan.fr/dptCalendar/dptCalendar.js?2
 */

var dptinfoCalDefaultOptions = {
	defaultView : 'agendaWeek',
	weekends : false,
	minTime : '08:00:00',
	maxTime : '20:00:00',
	height : 'auto',
	slotEventOverlap : false,
	eventTextColor : 'black',
	allDaySlot : false,
	slotLabelFormat : 'HH:mm'
};

var dows = ['lundi', 'mardi', 'mercredi', 'jeudi', 'vendredi'];
var simpleExams = ['partiel', 'examens', 'soutenance' ];
var mois = ['', 'janvier', 'février', 'mars', 'avril', 'mai', 'juin', 'juillet', 'août', 'septembre', 'octobre', 'novembre', 'decembre'];
var calEvents;

function newEvent (when)
{
	return { start: when[0], end: when[1], tips: [] };
}

function setEventProperty (cours, event, key, value)
{
	var ck = "ev_"+key;
	if (typeof event[ck] == 'undefined') event[ck]=value;
	if (typeof cours[key] != 'undefined') event[ck]=cours[key];
}

function getEventProperty (event, key)
{
	var ck = "ev_"+key;
	if (typeof event[ck] == 'string') return event[ck];
	if (typeof event[ck] != 'object') return "";
	return event[ck].join("\n");
}

function applySettings(obj, event)
{
	if (typeof obj.tag == 'string') event.tag=obj.tag;
	if (typeof obj.vue == 'string') event.vue = obj.vue;
	if (typeof obj.title == 'string') event.title = obj.title;
	if (typeof obj.color == 'string') event.color = obj.color;
	if (typeof obj.textColor == 'string')
		event.textColor = obj.textColor;
	if (typeof obj.borderColor == 'string')
		event.borderColor = obj.borderColor;
	if (typeof obj.properties == 'object')
		jQuery.extend(event,obj.properties);

	var linktext = "Plus d'information";
	if (typeof obj.info == 'string') event.tips.push(
		{ icon: 'info', text: obj.info });
	if (typeof obj.url == 'string') event.tips.push(
		{ icon: 'link', text: linktext, url: obj.url });
	if (typeof obj.url == 'object')
		for (var k in obj.url)
			event.tips.push({icon:'link', text:k, url:obj.url[k]});
}

function finalizeEvent (event)
{
//	even.tip = makeTips(event.tips);	// ToDo: See if makeTips is necessary and implement it if needed
	calEvents.push(event);
}

/**************************************************************************************************************************/

function calendarEvent (data, cev)
{
	var event = newEvent([cev.date+"T"+cev.start, cev.date+"T"+cev.end]);

	setEventProperty(cev, event, "speaker", "");
	setEventProperty(cev, event, "name", "");
	setEventProperty(cev, event, "room", "");

	event.title = event.ev_name + "\n" + event.ev_room + "\n" + getEventProperty(event, "speaker");

	applySettings(cev, event);
	finalizeEvent(event);
}

/**************************************************************************************************************************/

function genCalendar(style,name,callback)
{
	calEvents = [];
	var options = dptinfoCalDefaultOptions;

//	option['eventRender'] = calEventRender;
	if (style == 'agenda')
	{
		options.header = { left:"", right: "prev, today, next" };
		options.columnFormat = "dddd D/M";
	}
	else if (style == "hebdo" || style == "semaine")
	{
		options.header = false;
		options.allDaySlot = false;
		options.columnFormat = "dddd";
		if (style == "semaine")
			options.columnFormat = "dddd D/M";
	}
	
	data = callback();
	
	if (style == 'agenda')
	{
		jQuery.each(data.events,function() { calendarEvent(data,this); });
	}

	jQuery.extend(options,data.options);
	calEvents.forEach(function(e){ e.calendar=name; });
	options['events'] = calEvents;

	// Actually display the calendar:
	$("#"+name).fullCalendar(options);
}
