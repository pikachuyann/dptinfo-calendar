<?php
	if (!defined('PmWiki')) exit();

	$DptinfoCalendarDebugMode = false;

	/***
	 * GOAL : Be able to use wiki markups to fill and display a calendar through a modified version of FullCalendar that is used in the Computer Science department of ENS Paris-Saclay, France
	 ***/

	// THIS IS A WORK IN PROGRESS - 2021-01-12

	$RecipeInfo['DptinfoCalendar']['version'] = 'alpha';

	/** To easily distinguish with other recipes, the global variables contain the (wikified?) name of the recipe, which is long; maybe this is not a good idea. **/

	// Event list:
	$DptinfoCalendarEvents = array();
	$DptinfoCalendarDisplayCounter = 0;

	// Binds the various js scripts to be loaded to the headers, only if at least one calendar is displayed:
	function DptinfoCalendarHeaders() {
		global $HTMLHeaderFmt;
		static $calls = 0; // Avoids the headers being set multiple times

		if ($calls > 0) { } else {
			$http_path=dirname($_SERVER["PHP_SELF"]);
			if ($http_path != "/") { $http_path=$http_path."/"; }
			$JSPath=$http_path."pub/dptinfo-calendar/js";
			$CSSPath=$http_path."pub/dptinfo-calendar/css";
			$headers = DptinfoCalendarAddHeader("css", "fullcalendar.css");
			$headers.= DptinfoCalendarAddHeader("js", "jquery.min.js");
			$headers.= DptinfoCalendarAddHeader("js", "moment.min.js");
			$headers.= DptinfoCalendarAddHeader("js", "fullcalendar.min.js");
			$headers.= DptinfoCalendarAddHeader("js", "dptcal.js");
			$HTMLHeaderFmt['DptinfoCalendarHDS'] = $headers;
		} $calls++;
	}

	function DptinfoCalendarAddHeader($type, $file) {
		$http_path=dirname($_SERVER["PHP_SELF"]);
		if ($http_path != "/") { $http_path=$http_path."/"; }
		$pmwiki_path=dirname(__FILE__); // That is the path to the current recipe.
		$pmwiki_path=dirname($pmwiki_path); // This should be the path to pmwiki main directory
		if ($pmwiki_path != "/") { $pmwiki_path=$pmwiki_path."/"; }
		if ($type == "js") { $path="pub/dptinfo-calendar/js/"; }
		else if ($type == "css") { $path="pub/dptinfo-calendar/css/"; }
		else { return ""; } // Unknown file type, do not try further things.
		$stat = stat($pmwiki_path.$path.$file);
		$addstamp = "?m=".$stat["mtime"];
		if ($type == "js") { return "<script src='$http_path$path$file$addstamp'></script>"; }
		else if ($type == "css") { return "<link href='$http_path$path$file$addstamp' rel='stylesheet' />"; }
	}
	// ToDo : minimise the number of javascript and css scripts used

	// Binds the various markups
	Markup('DptinfoCalendar', 'directives', '/\\(:dptcal(.*):\\)/', "DptinfoCalendarDisplayHook");
	SDVA($MarkupExpr, array('dptevent' => 'DptinfoCalendarEvent($pagename,$argp)'));

	// Sort of a "hook" to make another PmWiki function actually parse the arguments
	function DptinfoCalendarDisplayHook($arguments) {
		global $pagename;
		return DptinfoCalendarDisplay($pagename, PSS($arguments[1]));
	}

	function DptInfoCalendarSpecialChars($string) {
		$newstring = PHSC($string); // PHSC is an equivalent to htmlspecialchars that is defined in PmWiki to deal with the different possible encodings.
		$ns2 = preg_replace("/'/", "&quot;", $newstring);
		$ns3 = preg_replace('/"/', "&dquo;", $ns2);
		return $ns3;
	}

	function DptinfoCalendarEvent($pagename, $args) {
		global $DptinfoCalendarEvents;

		// Parse the arguments, rename all keys to lowercase so they're more easily parsed.
		$a = $args;
		$a = array_change_key_case($a,CASE_LOWER);

		$eventData = array();

		foreach ($a as $key => $item) {
			switch ($key) {
			case "#": // Contains an array with even items being keys and (following) odd items being something that is probably their associated item.
				break;
			case "name":
			case "start":
			case "end":
			case "room":
			case "speaker":
			case "date":
				$eventData[$key]=DptInfoCalendarSpecialChars($item);
				break;
			default:
				$displaykey=DptInfoCalendarSpecialChars($key);
				echo "<font color='red'>[<strong>DptInfoEvent</strong> - Unknown key $displaykey]</font>";
			}
		}

		$DptinfoCalendarEvents[] = $eventData;
	}

	function DptinfoCalendarDisplay($pagename, $args) {
		global $DptinfoCalendarEvents, $DptinfoCalendarDisplayCounter;
		global $DptinfoCalendarUseNew;
		global $DptinfoCalendarDebugMode;
		global $HTMLHeaderFmt;

		DptinfoCalendarHeaders();

		$script_dical="<script>\n";
		$script_dical.="function dptinfoCalendar$DptinfoCalendarDisplayCounter() {\n";
		$script_dical.="return {\n";
		// Create the list of Events
		$script_dical.="events : [\n";
		foreach ($DptinfoCalendarEvents as $key => $event) {
			$script_dical.="{";
			if (isset($event["date"])) { $script_dical.=" date: '$event[date]', "; }
			if (isset($event["start"])) { $script_dical.=" start: '$event[start]', "; }
			if (isset($event["end"])) { $script_dical.=" end: '$event[end]', "; }
			if (isset($event["room"])) { $script_dical.=" room: '$event[room]', "; }
			if (isset($event['speaker'])) { $script_dical.=" speaker: '$event[speaker]', "; }
			if (isset($event["name"])) { $script_dical.=" name: '$event[name]', "; }
			$script_dical.=" debug: 'debug'";
			$script_dical.="},\n";
		}
		$script_dical.="], \n";
		// -- List of events ---
		$script_dical.="};\n"; // Ends return
		$script_dical.="};\n"; // Ends the function
		$script_dical.="</script>";

		$displayed_html="<div id='DptinfoCalendar$DptinfoCalendarDisplayCounter'> <div id='DptinfoCalendarInner$DptinfoCalendarDisplayCounter'> </div> </div>";

		// Modify the headers accordingly
		if (! $DptinfoCalendarUseNew) {
			$hdrscript="<script> $(document).ready( function(){ genCalendar('agenda', 'DptinfoCalendarInner$DptinfoCalendarDisplayCounter', dptinfoCalendar$DptinfoCalendarDisplayCounter); }); </script>";
		} else {
			$hdrscript="<script> DptinfoCalendarReady( function(){ genCalendar('agenda', 'DptinfoCalendarInner$DptinfoCalendarDisplayCounter', dptinfoCalendar$DptinfoCalendarDisplayCounter); } ); </script>";
		}
		$HTMLHeaderFmt["DptinfoCalendarDISP$DptinfoCalendarDisplayCounter"]=$hdrscript;

		// Increase the counter for the next calendar to be displayed, if necessary
		$DptinfoCalendarDisplayCounter++;

		if ($DptinfoCalendarDebugMode == true) {
			return htmlspecialchars($hdrscript).htmlspecialchars($script_dical).htmlspecialchars($displayed_html).$script_dical.$displayed_html;
		} else {
			return $script_dical.$displayed_html;
		}
	}
?>
