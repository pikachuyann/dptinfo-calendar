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
	$DptinfoCalendarLectures = array();
	$DptinfoCalendarGlobalSettings = array();
	$DptinfoCalendarDates = array();
	$DptinfoCalendarDisplayCounter = 0;

	// Default settings
	$DptinfoCalendarGlobalSettings["startindex"]=0;

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
			$headers.= DptinfoCalendarAddHeader("js", "jquery.qtip.min.js");
			$headers.= DptinfoCalendarAddHeader("css", "jquery.qtip.min.css");
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
	SDVA($MarkupExpr, array('dptlecture' => 'DptinfoCalendarLecture($pagename,$argp)'));
	SDVA($MarkupExpr, array('dptlecturemodify' => 'DptinfoCalendarLectureModification($pagename,$argp)'));
	SDVA($MarkupExpr, array('dptlectureadd' => 'DptinfoCalendarLectureAddition($pagename,$argp)'));
	SDVA($MarkupExpr, array('dptcalset' => 'DptinfoCalendarSetting($pagename,$argp)'));
	SDVA($MarkupExpr, array('dptdate' => 'DptinfoCalendarDates($pagename,$argp)'));

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
			case "color":
			case "url":
			case "urltext":
				$eventData[$key]=DptInfoCalendarSpecialChars($item);
				break;
			default:
				$displaykey=DptInfoCalendarSpecialChars($key);
				echo "<font color='red'>[<strong>DptInfoEvent</strong> - Unknown key $displaykey]</font>";
			}
		}

		$DptinfoCalendarEvents[] = $eventData;
	}

	function DptinfoCalendarLecture($pagename, $args) {
		global $DptinfoCalendarLectures;

		$a = $args;
		$a = array_change_key_case($a,CASE_LOWER);

		$eventData = array();

		foreach ($a as $key => $item) {
			switch ($key) {
			case "#":
				break;
			case "name":
			case "start":
			case "end":
			case "room":
			case "speaker":
			case "teacher":
			case "first":
			case "last":
			case "id":
			case "url":
			case "urltext":
			case "color":
				$eventData[$key]=DptinfoCalendarSpecialChars($item);
				break;
			case "teachers":
				$eventData[$key]=intval($item);
				break;
			default:
				if (preg_match('/teacher[0-9]+/', $key)) {
					$eventData[$key]=DptinfoCalendarSpecialChars($item);
				} else {
					$displaykey=DptInfoCalendarSpecialChars($key);
					echo "<font color='red'>[<strong>DptInfoLecture</strong> - Unknown key $displaykey]</font>";
				}
			}
		}

		$eventData["modifications"]=array();
		$eventData["additions"]=array();
		$DptinfoCalendarLectures[$eventData["id"]]=$eventData;
	}

	function DptinfoCalendarLectureModification($pagename, $args) {
		global $DptinfoCalendarLectures;

		$a = $args;
		$a = array_change_key_case($a,CASE_LOWER);

		$eventData = array();

		foreach ($a as $key => $item) {
			switch ($key) {
			case "#":
				break;
			case "id":
			case "start":
			case "end":
			case "room":
			case "teacher":
			case "which":
			case "name":
			case "date":
			case "url":
			case "urltext":
				$eventData[$key]=DptinfoCalendarSpecialChars($item);
				break;
			default:
				$displaykey=DptInfoCalendarSpecialChars($key);
				echo "<font color='red'>[<strong>DptInfoLectureModify</strong> - Unknown key $displaykey]</font>";
			}
		}

		if (!isset($DptinfoCalendarLectures[$eventData["id"]])) { echo "<font color='red'>[<strong>DptinfoLectureChange</strong> - Unknown <em>event</em> $eventData[id] ]</font>"; }
		else { $DptinfoCalendarLectures[$eventData["id"]]["modifications"][]=$eventData; }
	}

	function DptinfoCalendarLectureAddition($pagename, $args) {
		global $DptinfoCalendarLectures;

		$a = $args;
		$a = array_change_key_case($a,CASE_LOWER);

		$eventData = array();

		foreach ($a as $key => $item) {
			switch ($key) {
			case "#":
				break;
			case "id":
			case "start":
			case "end":
			case "room":
			case "teacher":
			case "date":
			case "name":
			case "url":
				$eventData[$key]=DptinfoCalendarSpecialChars($item);
				break;
			default:
				$displaykey=DptInfoCalendarSpecialChars($key);
				echo "<font color='red'>[<strong>DptInfoLectureAdd</strong> - Unknown key $displaykey]</font>";
			}
		}

		if (!isset($DptinfoCalendarLectures[$eventData["id"]])) { echo "<font color='red'>[<strong>DptinfoLectureChange</strong> - Unknown <em>event</em> $eventData[id] ]</font>"; }
		else { $DptinfoCalendarLectures[$eventData["id"]]["additions"][]=$eventData; }
	}

	function DptinfoCalendarDates($pagename, $args) {
		global $DptinfoCalendarDates;

		$a = $args;
		$a = array_change_key_case($a,CASE_LOWER);

		$eventData = array();

		foreach ($a as $key => $item) {
			switch ($key) {
			case "#":
				break;
			case "holidays":
			case "test":
			case "visit":
			case "start":
			case "end":
				$eventData[$key]=DptinfoCalendarSpecialChars($item);
				break;
			default:
				$displaykey=DptInfoCalendarSpecialChars($key);
				echo "<font color='red'>[<strong>DptInfoDate</strong> - Unknown key $displaykey]</font>";
			}
		}

		$DptinfoCalendarDates[]=$eventData;
	}

	function DptinfoCalendarSetting($pagename, $args) {
		global $DptinfoCalendarGlobalSettings;

		$a = $args;
		$a = array_change_key_case($a,CASE_LOWER);

		foreach ($a as $key => $item) {
			switch ($key) {
			case "#":
				break;
			case "start":
			case "end":
				$DptinfoCalendarGlobalSettings[$key]=DptinfoCalendarSpecialChars($item);
				break;
			case "startindex":
				$DptinfoCalendarGlobalSettings[$key]=intval($item);
				break;
			default:
				$displaykey=DptInfoCalendarSpecialChars($key);
				echo "<font color='red'>[<strong>DptInfoCalSet</strong> - Unknown key $displaykey]</font>";
			}
		}
	}

	function DptinfoCalendarDisplay($pagename, $args) {
		global $DptinfoCalendarEvents, $DptinfoCalendarDisplayCounter, $DptinfoCalendarLectures, $DptinfoCalendarGlobalSettings;
		global $DptinfoCalendarDates;
		global $DptinfoCalendarUseNew;
		global $DptinfoCalendarDebugMode;
		global $HTMLHeaderFmt;

		DptinfoCalendarHeaders();

		$a = ParseArgs($args);
		$a = array_change_key_case($a,CASE_LOWER);

		$isFirstEvent = true;
		$isFirstKey = true;

		$script_dical="<script>\n";
		$script_dical.="function dptinfoCalendar$DptinfoCalendarDisplayCounter() {\n";
		$script_dical.="return {\n";
		// Settings
		if (isset($DptinfoCalendarGlobalSettings["start"])) { $script_dical .= " start: '$DptinfoCalendarGlobalSettings[start]', "; }
		if (isset($DptinfoCalendarGlobalSettings["end"])) { $script_dical .= " end: '$DptinfoCalendarGlobalSettings[end]', "; }
		// Create the list of Dates
		$script_dical.="dates : [\n";
		foreach ($DptinfoCalendarDates as $key => $event) {
			if ($isFirstEvent) { $isFirstEvent = false; }
			else { $script_dical.=","; }
			$isFirstKey = true;
			$script_dical.="{";
			foreach ($event as $cle => $valeur) { if ($isFirstKey) { $isFirstKey = false; } else { $script_dical.=", "; } $script_dical.="$cle: '$valeur'"; }
			$script_dical.="}\n";
		}
		$script_dical.="]\n";
		// Create the list of Events
		$isFirstEvent = true;
		$script_dical.=", events : [\n";
		foreach ($DptinfoCalendarEvents as $key => $event) {
			if ($isFirstEvent) { $isFirstEvent = false; }
			else { $script_dical.=","; }
			$isFirstKey = true;
			$script_dical.="{";
			foreach ($event as $cle => $valeur) {
				if ($cle == "urltext") continue;
				if ($isFirstKey) { $isFirstKey = false; } else { $script_dical.=", "; }
				if ($cle == "url" && isset($event["urltext"])) {
					$script_dical.="$cle: {'$event[urltext]': '$valeur'}";
					continue;
				}
				$script_dical.="$cle: '$valeur'";
			}
			$script_dical.="}\n";
		}
		$script_dical.="]\n";
		// -- List of events ---
		// Create the list of Lectures
		$isFirstEvent = true;
		$script_dical.=", lectures : [\n";
		foreach ($DptinfoCalendarLectures as $key => $event) {
			if ($isFirstEvent) { $isFirstEvent = false; }
			else { $script_dical.=","; }
			$script_dical.="{";
			$isFirstKey = true;
			foreach ($event as $cle => $valeur) {
				if ($cle == "modifications" || $cle == "additions") {
					if (count($valeur)==0) continue;
					if ($isFirstKey) { $isFirstKey = false; } else { $script_dical.=", "; }
					$script_dical.="$cle: [";
					foreach ($valeur as $notused => $chgdata) { 
						$script_dical.=" {"; $isFV = true;
						foreach ($chgdata as $chgkey => $chgvalue) { 
							if ($chgkey=="id") continue;
							$isFV ? ($isFV = false) : ($script_dical.=",");
							$script_dical.=" $chgkey: '$chgvalue' ";
						}
						$script_dical.=" }, ";
					}
					$script_dical.="]";
				} else {
					if (preg_match('/teacher.*/',$cle)) { /* */ }
					else {
						if ($cle == "urltext") continue;
						if ($isFirstKey) { $isFirstKey = false; } else { $script_dical.=", "; }
						if ($cle == "url" && isset($event["urltext"])) {
							$script_dical.="$cle: {'$event[urltext]': '$valeur'}";
							continue;
						}
						$script_dical.="$cle: '$valeur'";
					}
				}
			}
			// The "teacher" key(s) are dealt with separately to deal with the case of multiple teachers
			if (isset($event["teachers"])) {
				if ($isFirstKey) { $isFirstKey = false; } else { $script_dical.=", "; }
				$script_dical.= "teacher: ["; $nbt = intval($event["teachers"]);
				$stindx = $DptinfoCalendarGlobalSettings["startindex"];
				for ($i = $stindx; $i < $stindx + $nbt; $i++) {
					if (isset($event["teacher$i"])) {
						if ($i!=$stindx) { $script_dical.=", "; }
						$script_dical.="'".$event["teacher$i"]."'";
					}
				}
				$script_dical.="]";
			} else {
				if (isset($event["teacher"])) {
					if ($isFirstKey) { $isFirstKey = false; } else { $script_dical.=", "; }
					$script_dical.="teacher: '$event[teacher]'";
				}
			}
			$script_dical.="}\n";
		}
		$script_dical.="]";
		// -- List of lectures --
		$script_dical.="};\n"; // Ends return
		$script_dical.="};\n"; // Ends the function
		$script_dical.="</script>";

		$displayed_html="<div id='DptinfoCalendar$DptinfoCalendarDisplayCounter'>";
		if (isset($a["showschedule"])) {
			$displayed_html.="<button id='DptinfoCalendarScheduleButton$DptinfoCalendarDisplayCounter'>Schedule</button>";
			$displayed_html.="<button id='DptinfoCalendarAgendaButton$DptinfoCalendarDisplayCounter'>Agenda</button>";
		}
		$displayed_html.="<div id='DptinfoCalendarInner$DptinfoCalendarDisplayCounter'> </div> ";
		if (isset($a["showschedule"])) {
			$displayed_html.="<div id='DptinfoCalendarScheduleInner$DptinfoCalendarDisplayCounter'> </div>";
		}
		$displayed_html.="</div>";

		// Modify the headers accordingly
		$calvirg="";

		$furtherJS="";
		if (isset($a["showschedule"])) {
			$furtherJS.="function dptinfoCalendarDisp$DptinfoCalendarDisplayCounter(tabname) {\n";
			$furtherJS.="  console.log(tabname); \n";
			$furtherJS.="  $('#'+tabname).css('display','block'); \n";
			$furtherJS.="  $('#'+tabname).fullCalendar('render'); \n";
			$furtherJS.="  $('#'+tabname).fullCalendar('rerenderEvents'); \n";
			$furtherJS.="};";
		}

		$calCall = "function(){ "; //DptinfoCalendarOptions$DptinfoCalendarDisplayCounter = ";
		if (isset($a["showschedule"])) {
			$calCall.="$('#DptinfoCalendarInner$DptinfoCalendarDisplayCounter').css('display','none');";
		}
		$calCall.= "genCalendar('agenda', 'DptinfoCalendarInner$DptinfoCalendarDisplayCounter', dptinfoCalendar$DptinfoCalendarDisplayCounter, ";
		$calCall.= "{"; // options start
		if (isset($a["startdate"])) { $calCall.="$callvirg startDate:\"".DptInfoCalendarSpecialChars($a["startdate"])."\""; $callvirg=","; }
		$calCall.= "} "; //options end
		//		$calCall.= "genCalendar('agenda', 'DptinfoCalendarInner$DptinfoCalendarDisplayCounter', dptinfoCalendar$DptinfoCalendarDisplayCounter, DptinfoCalendarOptions$DptinfoCalendarDisplayCounter); ";
		$calCall.= "); ";
		if (isset($a["showschedule"])) {
			$calCall.=" genCalendar('schedule', 'DptinfoCalendarScheduleInner$DptinfoCalendarDisplayCounter', dptinfoCalendar$DptinfoCalendarDisplayCounter, {}); ";
			$calCall.="$('#DptinfoCalendarScheduleButton$DptinfoCalendarDisplayCounter').click( function() { $('#DptinfoCalendarInner$DptinfoCalendarDisplayCounter').css('display','none'); dptinfoCalendarDisp$DptinfoCalendarDisplayCounter('DptinfoCalendarScheduleInner$DptinfoCalendarDisplayCounter'); } ); \n";
			$calCall.="$('#DptinfoCalendarAgendaButton$DptinfoCalendarDisplayCounter').click( function() { $('#DptinfoCalendarScheduleInner$DptinfoCalendarDisplayCounter').css('display','none'); dptinfoCalendarDisp$DptinfoCalendarDisplayCounter('DptinfoCalendarInner$DptinfoCalendarDisplayCounter'); } ); \n";
		}
		$calCall.= "} ";

		if (! $DptinfoCalendarUseNew) {
			$hdrscript="<script> $furtherJS $(document).ready( $calCall ); </script>";
		} else {
			$hdrscript="<script> $furtherJS DptinfoCalendarReady( $calCall ); </script>";
		}
		$HTMLHeaderFmt["DptinfoCalendarDISP$DptinfoCalendarDisplayCounter"]=$hdrscript;
		$HTMLHeaderFmt["DptinfoCalendarDATA$DptinfoCalendarDisplayCounter"]=$script_dical;

		// Increase the counter for the next calendar to be displayed, if necessary
		$DptinfoCalendarDisplayCounter++;

		if ($DptinfoCalendarDebugMode == true) {
			return htmlspecialchars($hdrscript).htmlspecialchars($script_dical).htmlspecialchars($displayed_html).$displayed_html;
		} else {
			return $displayed_html;
		}
	}
?>
