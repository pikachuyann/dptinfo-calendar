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
	iconPath : '', // Should be defined when calling the calendar
	defaultView : 'agendaWeek',
	weekends : false,
	minTime : '08:00:00',
	maxTime : '20:00:00',
	height : 'auto',
	slotEventOverlap : false,
	eventTextColor : 'black',
	allDaySlot : false,
	slotLabelFormat : 'HH:mm',
	timeFormat : 'HH:mm',
	usedTags : [],
	previousView : 'agenda' // by default, it will be agenda.
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
	if (typeof obj.tag_list != 'undefined') event.tag_list=obj.tag_list;
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
	event.tip = makeTips(event.tips);
	calEvents.push(event);
}

function makeTips (list)
{
	if (list.length == 0) return "";
	var text = "<table>";
	jQuery.each(list, function() {
		text+="<tr><td>";
		if (typeof this.icon == 'string')
			text += "<img src='"+dptinfoCalDefaultOptions.iconPath+this.icon+".png' />";
		text+"</td><td>";
		if (typeof this.url == 'string')
			text += "<a href="+this.url+"'>"+this.text+"</a>";
		else
			text += this.text;
		text+="</td></tr>";
	});
	return text+"</table>";
}

/**************************************************************************************************************************/
function advanceDate(d,n)
{
	var date = new Date(d+"T12:00");
	date.setDate(date.getDate()+n);
	return date.toISOString().slice(0,10);
}

function isBlocked (dev)
{
	if (typeof dev.blocked == 'boolean') return dev.blocked;
	if (dev.holidays || dev.holiday || dev.visit) return true;
	return false;
}

function getDateDuration(e)
{
	var val = [];
	if (e.date) { val[0] = val[1] = e.date; }
	if (typeof e.deadline == 'string') { val[0] = val[1] = e.deadline; }
	if (e.start) val[0] = e.start;
	if (e.end) val[1] = e.end;
	val[1] = advanceDate(val[1],1);
	return val;
}

function dateBlocked(data, d)
{
	for (i in data.dates)
	{
		if (!isBlocked(data.dates[i])) continue;
		var dur = getDateDuration(data.dates[i]);
		if (d >= dur[0] && d < dur[1]) return true;
	}
	return false;
}

function dateCancelled(lecture, d)
{
	if (typeof lecture.deletions == 'undefined') return false;
	if (typeof lecture.deletions == 'string') return lecture.deletions == d;
	for (var i in lecture.deletions)
		if (lecture.deletions[i] == d) return true;
	return false;
}

/**************************************************************************************************************************/

function calendarEvent (data, cev)
{
	var event = newEvent([cev.date+"T"+cev.start, cev.date+"T"+cev.end]);

	setEventProperty(cev, event, "speaker", "");
	setEventProperty(cev, event, "name", "");
	setEventProperty(cev, event, "room", "");

	event.title = event.ev_name + "\n" + event.ev_room + "\n" + getEventProperty(event, "speaker");

	addWebPage(data,event,"speaker");
	applySettings(cev, event);
	finalizeEvent(event);
}

/**************************************************************************************************************************/

function getLectureDuration (d, start,end)
{
	var duration = [d,advanceDate(d,1)];
	if (start) duration = [d+"T"+start, d+"T"+end];
	return duration;
}

function setLectureProperties (lecture, event)
{
	setEventProperty(lecture, event, "type", "Lecture");
	setEventProperty(lecture, event, "name", "");
	setEventProperty(lecture, event, "teacher", "");
	setEventProperty(lecture, event, "room", "");

	if (typeof lecture.url == 'string')
		lecture.url = { 'Course webpage': lecture.url };

	event.title = event.ev_name + "\n" + event.ev_room + "\n" + getEventProperty(event, "teacher");
}

function genLectureEvent (data, lecture, d, start, end)
{
	var event = newEvent( getLectureDuration(d, start, end) );
	setLectureProperties(lecture, event);
	applySettings(lecture, event);

	return event;	
}

function finalizeLecture(data,events)
{
	for (var cDate in events)
	{
		addWebPage(data,events[cDate],"teacher");
		finalizeEvent(events[cDate]);
	}
}

function addLectures(data,lecture,events) {
	if (typeof lecture.additions == 'undefined') return;
	for (var i in lecture.additions)
	{
		var add_start = '';
		var add_end = '';
		if (typeof lecture.additions[i].start == 'undefined') add_start = lecture.start
		else add_start = lecture.additions[i].start;
		if (typeof lecture.additions[i].end == 'undefined') add_end = lecture.end
		else add_end = lecture.additions[i].end;
		var d = lecture.additions[i].date;
		events[d] = genLectureEvent(data,lecture,d, add_start, add_end);
		setLectureProperties(lecture.additions[i],events[d]);
		applySettings(lecture.additions[i],events[d]);
	}
}

function applyModifications(events, modif) {
	var event = events[modif.which];
	if (typeof event == 'undefined') { console.log("No event at date "+modif.which+" for "+modif.id+"."); return; }
	var orig_start = event.start;
	var orig_end = event.end;
	var orig_room = event.ev_room;

	setEventProperty(modif, event, "date", modif.which);
	setEventProperty(modif, event, "start", event.start.substr(11));
	setEventProperty(modif, event, "end", event.end.substr(11));
	var dur = getLectureDuration(event.ev_date, event.ev_start, event.ev_end);
	event.start = dur[0]; event.end = dur[1];

	setLectureProperties(modif, event);

	setEventProperty(modif, event, "important", event.end != orig_end || event.start != orig_start || event.ev_room != orig_room);
	if (event.ev_important) event.borderColor = 'red';
	
	applySettings(modif, event);
}

function calendarLecture (data, lecture) {
	var firstCours = '';
	var lastCours = '';
	var events = [];

	if (typeof data.start == 'string') firstCours = data.start;
	if (typeof data.end == 'string') lastCours = data.end;
	if (typeof lecture.first == 'string') firstCours = lecture.first;
	if (typeof lecture.last == 'string') lastCours = lecture.last;

	if (firstCours == '') return;
	if (lastCours == '') return;

	var cDate=advanceDate(firstCours,-7);

	while (true) {
		cDate = advanceDate(cDate,7);
		if (cDate > lastCours) break;
		if (dateBlocked(data,cDate)) continue;
		if (dateCancelled(lecture,cDate)) continue;
		events[cDate] = genLectureEvent(data,lecture,cDate,lecture.start,lecture.end);
	}

	addLectures(data, lecture, events);
	if (typeof lecture.modifications != 'undefined')
		for (var m in lecture.modifications) applyModifications(events,lecture.modifications[m]);

	finalizeLecture(data,events);
}

function calendarLectureSummary (data, lecture) {
	var cDate=advanceDate( data.start, new Date(lecture.first).getDay() - new Date(data.start).getDay() );

	var event = newEvent(getLectureDuration(cDate, lecture.start, lecture.end));
	setLectureProperties(lecture, event);
	applySettings(lecture, event);

	addWebPage(data,event,"teacher");

	finalizeEvent(event);
}

function setDateTitle(dev, event)
{
	if (typeof dev.vacances == "string") event.title="Vacances ("+dev.vacances+")";
	if (typeof dev.holidays == "string") event.title="Holidays ("+dev.holidays+")";
	if (typeof dev.ferie == "string") event.title=dev.ferie;
	if (typeof dev.holiday == "string") event.title="Holiday ("+dev.holiday+")";
	if (typeof dev.visit == "string") event.title="Lab Visit ("+dev.visit+")";
	if (typeof dev.visite == "string") event.title="Visite de Labo ("+dev.visite+")";
	if (typeof dev.epreuves == "string") event.title="Épreuves ("+dev.epreuves+")";
	if (typeof dev.exams == "string") event.title="Exams ("+dev.exams+")";
}

function setDateColor(dev, event)
{
	event.color = "beige";
	if (dev.vacances || dev.holidays) event.color="greenyellow";
	if (dev.ferie || dev.holiday) event.color="greenyellow";
	if (dev.visit || dev.visite) event.color="lightpink";
}

function calendarDate() {
	var blocked = isBlocked(this);

	var event1 = newEvent(getDateDuration(this));
	setDateTitle(this,event1);
	setDateColor(this,event1);
	applySettings(this,event1);
	var event2 = new jQuery.extend({}, event1);

	if (blocked) event1.color = "transparent";
	finalizeEvent(event1);
	if (!blocked) return;

	event2.rendering='background';
	finalizeEvent(event2);

	var event3 = new jQuery.extend({}, event2);
	event2.start += "T00:00";
	event2.end += "T00:00";
	finalizeEvent(event3);
}

/**************************************************************************************************************************/
function addWebPage(data, event, key) {
	jQuery.each(getEventProperty(event,key).split("\n"),function() {
		console.log(this);
		console.log(data.people[this]);
		if (data.people[this])
			event.tips.push( { icon: 'person', url: data.people[this], text: this + " webpage" } );
	});
}

function calApplyTooltip(event, element, view) {
	if (typeof event.tip == "undefined") return;
	if (typeof event.tip != "string") return;
	if (event.tip == "") return;

	element.qtip({
		content: event.tip,
		position: {target: 'mouse', adjust: {mouse: false, method: 'shift'}},
		show: {solo: true}
	});
}

function calEventRender(event, element, view)
{
	calApplyTooltip(event, element, view);

	if (typeof event.tag_list == "undefined") { return true; }
	if (event.tag_list.length == 0) { return true; }
	if (dptinfoCalDefaultOptions.usedTags.length == 0) { return true; }

	return false;
}

/**************************************************************************************************************************/

function genCalendar(style,name,callback,addoptions)
{
	if (style == 'previous')
	{
		style = dptinfoCalDefaultOptions.previousView;
	}
	dptinfoCalDefaultOptions.previousView = style;

	calEvents = [];
	if (addoptions.hasOwnProperty('iconPath'))
		dptinfoCalDefaultOptions.iconPath = addoptions.iconPath;
	var options = dptinfoCalDefaultOptions;

	options['eventRender'] = calEventRender;

	if (style == 'agenda')
	{
		options.header = { left:"", right: "prev, today, next" };
		options.allDaySlot = true;
		options.columnFormat = "dddd D/M";
	}
	else if (style == "schedule" || style == "hebdo" || style == "semaine")
	{
		options.header = false;
		options.allDaySlot = false;
		options.columnFormat = "dddd";
		if (style == "semaine")
			options.columnFormat = "dddd D/M";
	}
	
	data = callback();
	if (! data.hasOwnProperty('people')) data.people={};

	if (style == 'agenda')
	{
		jQuery.each(data.dates,calendarDate);
		jQuery.each(data.events,function() { calendarEvent(data,this); });
		jQuery.each(data.lectures,function() { calendarLecture(data,this); });
	} else if (style == 'schedule') {
		jQuery.each(data.lectures,function() { calendarLectureSummary(data,this); });
	}

	jQuery.extend(options,data.options);
	calEvents.forEach(function(e){ e.calendar=name; });
	options['events'] = calEvents;

	// Actually display the calendar:
	$("#"+name).fullCalendar(options);

	if (addoptions.hasOwnProperty('startDate'))
		$("#"+name).fullCalendar('gotoDate', addoptions.startDate);

	if (style == 'schedule')
		$("#"+name).fullCalendar('gotoDate', data.start);
}
