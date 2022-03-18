<?php
	if (!defined('PmWiki')) exit();

	$DptinfoCalendarDebugMode = false;

	/***
	 * GOAL : Be able to use wiki markups to fill and display a calendar through a modified version of FullCalendar that is used in the Computer Science department of ENS Paris-Saclay, France
	 ***/

	$RecipeInfo['DptinfoCalendar']['version'] = '0.1.0';

	/** To easily distinguish with other recipes, the global variables contain the (wikified?) name of the recipe, which is long; maybe this is not a good idea. **/

	// Event list:
	$DptinfoCalendarEvents = array();
	$DptinfoCalendarLectures = array();
	if (!isset($DptinfoCalendarGlobalSettings)) {
		$DptinfoCalendarGlobalSettings = array();
	}
	$DptinfoCalendarPeople = array();
	$DptinfoCalendarDates = array();
	$DptinfoCalendarTags = array();
	$DptinfoCalendarDisplayCounter = 0;

	// Default settings
	$DptinfoCalendarGlobalSettings["startindex"]=0;

	// Binds the various js scripts to be loaded to the headers, only if at least one calendar is displayed:
	function DptinfoCalendarHeaders() {
		global $HTMLHeaderFmt, $DptinfoCalendarGlobalSettings;
		static $calls = 0; // Avoids the headers being set multiple times

		if ($calls > 0) { } else {
			$headers = DptinfoCalendarAddHeader("css", "fullcalendar.css");
			if (!isset($DptinfoCalendarGlobalSettings["noJQuery"])) {
				$headers.= DptinfoCalendarAddHeader("js", "jquery.min.js");
			}
			$headers.= DptinfoCalendarAddHeader("js", "moment.min.js");
			$headers.= DptinfoCalendarAddHeader("js", "fullcalendar.min.js");
			$headers.= DptinfoCalendarAddHeader("js", "dptcal.js");
			$headers.= DptinfoCalendarAddHeader("js", "jquery.qtip.min.js");
			$headers.= DptinfoCalendarAddHeader("css", "jquery.qtip.min.css");
			$HTMLHeaderFmt['DptinfoCalendarHDS'] = $headers;
		} $calls++;
	}

	function DptinfoCalendarGetHTTPPath() {
		$http_path = dirname($_SERVER["PHP_SELF"]);
		if ($http_path != "/") { $http_path=$http_path."/"; }
		return $http_path;
	}
	function DptinfoCalendarGetPMWikiPath() {
		$pmwiki_path=dirname(__FILE__); // That is the path to the current recipe.
		$pmwiki_path=dirname($pmwiki_path); // This should be the path to pmwiki main directory
		if ($pmwiki_path != "/") { $pmwiki_path=$pmwiki_path."/"; }
		return $pmwiki_path;
	}

	function DptinfoCalendarAddHeader($type, $file) {
		$http_path=DptinfoCalendarGetHTTPPath();
		$pmwiki_path=DptinfoCalendarGetPMWikiPath();
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
	Markup('DptinfoCalendar', 'directives', '/\\(:dptcal (.*):\\)/', "DptinfoCalendarDisplayHook");
	Markup('DptinfoCalendarNA', 'directives', '/\\(:dptcal:\\)/', "DptinfoCalendarDisplayHook");
	SDVA($MarkupExpr, array('dptcal' => 'DptinfoCalendarDisplay($pagename,$argp)'));
	Markup('DptinfoEvent', 'directives', '/\\(:dptevent (.*):\\)/', "DptinfoCalendarEventHook");
	Markup('DptinfoEventNA', 'directives', '/\\(:dptevent:\\)/', "DptinfoCalendarEventHook");
	SDVA($MarkupExpr, array('dptevent' => 'DptinfoCalendarEvent($pagename,$argp)'));
	Markup('DptinfoLecture', 'directives', '/\\(:dptlecture (.*):\\)/', "DptinfoCalendarLectureHook");
	Markup('DptinfoLectureNA', 'directives', '/\\(:dptlecture:\\)/', "DptinfoCalendarLectureHook");
	SDVA($MarkupExpr, array('dptlecture' => 'DptinfoCalendarLecture($pagename,$argp)'));
	Markup('DptinfoLectureModification', 'directives', '/\\(:dptlecturemodify (.*):\\)/', "DptinfoCalendarLectureModificationHook");
	Markup('DptinfoLectureModificationNA', 'directives', '/\\(:dptlecturemodify:\\)/', "DptinfoCalendarLectureModificationHook");
	SDVA($MarkupExpr, array('dptlecturemodify' => 'DptinfoCalendarLectureModification($pagename,$argp)'));
	Markup('DptinfoLectureAddition', 'directives', '/\\(:dptlectureadd (.*):\\)/', "DptinfoCalendarLectureAdditionHook");
	Markup('DptinfoLectureAdditionNA', 'directives', '/\\(:dptlectureadd:\\)/', "DptinfoCalendarLectureAdditionHook");
	SDVA($MarkupExpr, array('dptlectureadd' => 'DptinfoCalendarLectureAddition($pagename,$argp)'));
	MarkUp('DptinfoLectureDeletion', 'directives', '/\\(:dptlecturedel (.*):\\)/', "DptinfoCalendarLectureDeletionHook");
	MarkUp('DptinfoLectureDeletionNA', 'directives', '/\\(:dptlecturedel:\\)/', "DptinfoCalendarLectureDeletionHook");
	SDVA($MarkupExpr, array('dptlecturedel' => 'DptinfoCalendarLectureDeletion($pagenarme,$argp)'));
	Markup('DptinfoCalendarSetting', 'directives', '/\\(:dptcalset (.*):\\)/', "DptinfoCalendarSettingHook");
	Markup('DptinfoCalendarSettingNA', 'directives', '/\\(:dptcalset:\\)/', "DptinfoCalendarSettingHook");
	SDVA($MarkupExpr, array('dptcalset' => 'DptinfoCalendarSetting($pagename,$argp)'));
	Markup('DptinfoCalendarDate', 'directives', '/\\(:dptdate (.*):\\)/', "DptinfoCalendarDatesHook");
	Markup('DptinfoCalendarDateNA', 'directives', '/\\(:dptdate:\\)/', "DptinfoCalendarDatesHook");
	SDVA($MarkupExpr, array('dptdate' => 'DptinfoCalendarDates($pagename,$argp)'));
	Markup('DptinfoCalendarPerson', 'directives', '/\\(:dptperson (.*):\\)/', "DptinfoCalendarPersonHook");
	Markup('DptinfoCalendarPersonNA', 'directives', '/\\(:dptperson:\\)/', "DptinfoCalendarPersonHook");
	SDVA($MarkupExpr, array('dptperson' => 'DptinfoCalendarPerson($pagename,$argp)'));
	Markup('DptinfoCalendarTag', 'directives', '/\\(:dptcaltag (.*):\\)/', "DptinfoCalendarTagHook");
	Markup('DptinfoCalendarTagNA', 'directives', '/\\(:dptcaltag:\\)/', "DptinfoCalendarTagHook");
	SDVA($MarkupExpr, array('dptcaltag' => 'DptinfoCalendarTag($pagename,$argp)'));

	// Sort of a "hook" to make another PmWiki function actually parse the arguments
	function DptinfoCalendarDisplayHook($arguments) {
		global $pagename;
		return DptinfoCalendarDisplay($pagename, ParseArgs(PSS($arguments[1])));
	}
	function DptinfoCalendarEventHook($arguments) {
		global $pagename;
		return DptinfoCalendarEvent($pagename, ParseArgs(PSS($arguments[1])));
	}
	function DptinfoCalendarLectureHook($arguments) {
		global $pagename;
		return DptinfoCalendarLecture($pagename, ParseArgs(PSS($arguments[1])));
	}
	function DptinfoCalendarLectureModificationHook($arguments) {
		global $pagename;
		return DptinfoCalendarLectureModification($pagename, ParseArgs(PSS($arguments[1])));
	}
	function DptinfoCalendarLectureAdditionHook($arguments) {
		global $pagename;
		return DptinfoCalendarLectureAddition($pagename, ParseArgs(PSS($arguments[1])));
	}
	function DptinfoCalendarLectureDeletionHook($arguments) {
		global $pagename;
		return DptinfoCalendarLectureDeletion($pagename, ParseArgs(PSS($arguments[1])));
	}
	function DptinfoCalendarSettingHook($arguments) {
		global $pagename;
		return DptinfoCalendarSetting($pagename, ParseArgs(PSS($arguments[1])));
	}
	function DptinfoCalendarDatesHook($arguments) {
		global $pagename;
		return DptinfoCalendarDates($pagename, ParseArgs(PSS($arguments[1])));
	}
	function DptinfoCalendarPersonHook($arguments) {
		global $pagename;
		return DptinfoCalendarPerson($pagename, ParseArgs(PSS($arguments[1])));
	}
	function DptinfoCalendarTagHook($arguments) {
		global $pagename;
		return DptinfoCalendarTag($pagename, ParseArgs(PSS($arguments[1])));
	}

	function DptinfoCalendarSpecialChars($string) {
		$newstring = PHSC($string); // PHSC is an equivalent to htmlspecialchars that is defined in PmWiki to deal with the different possible encodings.
		$ns2 = preg_replace("/'/", "&quot;", $newstring);
		$ns3 = preg_replace('/"/', "&dquo;", $ns2);
		return $ns3;
	}

	function DptinfoCalendarJSEscape($string) {
		$newstring = preg_replace("/'/", "\\'", $string);
		return $newstring;
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
				$eventData[$key]=DptinfoCalendarJSEscape($item);
				break;
			case "tag":
				$eventData["tag"]=$item;
				$eventData["tag_list"]=array($item);
				break;
			case "tags":
				if (intval($item) != 0) {
					$eventData[$key]=intval($item);
				} else {
					$splitted_tags=preg_split('/ /',$item);
					$eventData["tag_list"]=$splitted_tags;
				}
				break;
			default:
				if (preg_match('/tag[0-9+]/', $key)) {
					if (!isset($eventData["tag_list"])) { $eventData["tag_list"]=array(); }
					$eventData["tag_list"][] = $item;
					break;
			}
				$displaykey=DptinfoCalendarSpecialChars($key);
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
				$eventData[$key]=DptinfoCalendarJSEscape($item);
				break;
			case "teachers":
				$eventData[$key]=intval($item);
				break;
			default:
				if (preg_match('/teacher[0-9]+/', $key)) {
					$eventData[$key]=DptinfoCalendarJSEscape($item);
				} else {
					$displaykey=DptinfoCalendarSpecialChars($key);
					echo "<font color='red'>[<strong>DptInfoLecture</strong> - Unknown key $displaykey]</font>";
				}
			}
		}

		$eventData["modifications"]=array();
		$eventData["additions"]=array();
		$eventData["deletions"]=array();
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
				$eventData[$key]=DptinfoCalendarJSEscape($item);
				break;
			default:
				$displaykey=DptinfoCalendarSpecialChars($key);
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
				$eventData[$key]=DptinfoCalendarJSEscape($item);
				break;
			case "teachers":
				$eventData[$key]=intval($item);
				break;
			default:
				if (preg_match("/teacher[0-9]+/", $key)) {
					$eventData[$key]=DptinfoCalendarJSEscape($item);
				} else {
					$displaykey=DptinfoCalendarSpecialChars($key);
					echo "<font color='red'>[<strong>DptInfoLectureAdd</strong> - Unknown key $displaykey]</font>";
				}
			}
		}

		if (!isset($DptinfoCalendarLectures[$eventData["id"]])) { echo "<font color='red'>[<strong>DptinfoLectureChange</strong> - Unknown <em>event</em> $eventData[id] ]</font>"; }
		else { $DptinfoCalendarLectures[$eventData["id"]]["additions"][]=$eventData; }
	}

	function DptinfoCalendarLectureDeletion($pagename, $args) {
		global $DptinfoCalendarLectures;

		$a = $args;
		$a = array_change_key_case($a,CASE_LOWER);

		$eventData = array();

		foreach ($a as $key => $item) {
			switch ($key) {
			case "#":
				break;
			case "id":
			case "which":
				$eventData[$key]=DptinfoCalendarJSEscape($item);
				break;
			default:
				$displaykey=DptinfoCalendarSpecialChars($key);
				echo "<font color='red'>[<strong>DptinfoLectureDelete</strong> - Unknown key $displaykey]</font>";
			}
		}

		if (!isset($DptinfoCalendarLectures[$eventData["id"]])) { echo "<font color='red'>[<strong>DptinfoLectureDelete</strong> - Unknown <em>event</em> $eventData[id] ]</font>"; }
		else { $DptinfoCalendarLectures[$eventData["id"]]["deletions"][]=$eventData; }
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
			case "holiday":
			case "exams":
			case "test":
			case "visit":
			case "start":
			case "end":
			case "date":
				$eventData[$key]=DptinfoCalendarJSEscape($item);
				break;
			default:
				$displaykey=DptinfoCalendarSpecialChars($key);
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
				$DptinfoCalendarGlobalSettings[$key]=DptinfoCalendarJSEscape($item);
				break;
			case "startindex":
				$DptinfoCalendarGlobalSettings[$key]=intval($item);
				break;
			default:
				$displaykey=DptinfoCalendarSpecialChars($key);
				echo "<font color='red'>[<strong>DptInfoCalSet</strong> - Unknown key $displaykey]</font>";
			}
		}
	}

	function DptinfoCalendarPerson($pagename, $args) {
		global $DptinfoCalendarPeople;

		$a = $args;
		$a = array_change_key_case($a,CASE_LOWER);

		$personData = array();

		foreach ($a as $key => $item) {
			switch ($key) {
			case "#":
				break;
			case "name":
			case "url":
				$personData[$key]=DptinfoCalendarJSEscape($item);
				break;
			default:
				$displaykey=DptinfoCalendarSpecialChars($key);
				echo "<font color='red'>[<strong>DptinfoPerson</strong> - Unknown key $displaykey]</font>";
			}
		}

		if (!isset($personData["name"])) { echo "<font color='red'>[<strong>DptinfoPerson</strong> - Unspecified name"; }
		elseif (isset($DptinfoCalendarPeople[$personData["name"]])) { echo "<font color='red'>[<strong>DptinfoPersonn</strong> - Had already stored info for $personData[name]</font>"; }
		$DptinfoCalendarPeople[$personData["name"]] = $personData;
	}

	function DptinfoCalendarTag($pagename, $args) {
		global $DptinfoCalendarTags;

		$a = $args;
		$a = array_change_key_case($a,CASE_LOWER);

		$tag_id = "";
		$tag_name = "";

		foreach ($a as $key => $item) {
			switch ($key) {
			case "#":
				break;
			case "id":
				$tag_id=DptinfoCalendarJSEscape($item);
				break;
			case "name":
				$tag_name=DptinfoCalendarSpecialChars($item);
				break;
			default:
				$displaykey=DptinfoCalendarSpecialChars($key);
				echo "<font color='red'>[<strong>DptinfoCalTag</strong> - Unknown key $displaykey]</font>";
			}
		}

		if ($tag_id == "" || $tag_name == "") { echo "<font color='red'>[<strong>DptinfoCalTag</strong> - Incomplete definition of a tag.]</font>"; }
		$DptinfoCalendarTags[$tag_id] = $tag_name;
	}

	function DptinfoCalendarDisplay($pagename, $args) {
		global $DptinfoCalendarEvents, $DptinfoCalendarDisplayCounter, $DptinfoCalendarLectures, $DptinfoCalendarGlobalSettings;
		global $DptinfoCalendarDates, $DptinfoCalendarPeople, $DptinfoCalendarTags;
		global $DptinfoCalendarUseNew;
		global $DptinfoCalendarDebugMode;
		global $HTMLHeaderFmt;

		DptinfoCalendarHeaders();

		$a = $args;
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
		// Create the list of People
		$isFirstEvent = true;
		foreach ($DptinfoCalendarPeople as $name => $person) {
			if (!isset($person["url"])) continue;
			if ($isFirstEvent) {
				$script_dical.=",people: {";
				$isFirstEvent = false;
			} else { $script_dical.=","; }
			$isFirstKey = true;
			$script_dical.="'$name': '$person[url]'";
		}
		if (!$isFirstEvent) { $script_dical.="}\n"; }
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
				if ($cle == "tags" || $cle == "tag") continue;
				if ($isFirstKey) { $isFirstKey = false; } else { $script_dical.=", "; }
				if ($cle == "tag_list") {
					$isFirstTag = true;
					$script_dical.="$cle: [";
					for ($j=0;$j < count($valeur);$j++) {
						if ($isFirstTag) { $isFirstTag = false; } else { $script_dical.=", "; }
						$script_dical.="'".$valeur[$j]."'";
					}
					$script_dical.="]";
					continue;
				}
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
							if (preg_match("/teacher[0-9]+/",$chgkey)) continue;
							if ($chgkey=="id") continue;
							$isFV ? ($isFV = false) : ($script_dical.=",");
							if ($chgkey == "teachers") {
								$nbt = intval($chgvalue);
								$stindx = $DptinfoCalendarGlobalSettings["startindex"];
								$script_dical.=" teacher: [";
								for ($i = $stindx; $i < $stindx + $nbt; $i++) {
									if ($i != $stindx) { $script_dical.=", "; }
									$script_dical.="'".$chgdata["teacher$i"]."'";
								}
								$script_dical.="] "; continue;
							}
							$script_dical.=" $chgkey: '$chgvalue' ";
						}
						$script_dical.=" }, ";
					}
					$script_dical.="]";
				} else if ($cle == "deletions") {
					if (count($valeur)==0) continue;
					if ($isFirstKey) { $isFirstKey = false; } else { $script_dical.=", "; }
					$script_dical.="$cle: ["; $isFV = true;
					foreach ($valeur as $notused => $chgdata) {
						$isFV ? ($isFV = false) : ($script_dical.=",");
						if (!isset($chgdata['which']))
							$script_dical.="''";
						else
							$script_dical.="'$chgdata[which]'";
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
		if (count($DptinfoCalendarTags) > 0) {
			foreach ($DptinfoCalendarTags as $tag => $name) {
				$displayed_html.="<input type='checkbox' id='DptinfoCalendarTag$tag-$DptinfoCalendarDisplayCounter'><label for='DptinfoCalendarTag$tag-$DptinfoCalendarDisplayCounter' class='DptinfoCalendarTagLabel'>$name</label>";
			}
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
			$furtherJS.="  $('#'+tabname).css('display','block'); \n";
			$furtherJS.="  $('#'+tabname).fullCalendar('render'); \n";
			$furtherJS.="  $('#'+tabname).fullCalendar('rerenderEvents'); \n";
			$furtherJS.="};";
		}

		$calCall = "\tfunction(){ \n"; //DptinfoCalendarOptions$DptinfoCalendarDisplayCounter = ";
		if (isset($a["showschedule"])) {
			$calCall.="\t\t$('#DptinfoCalendarInner$DptinfoCalendarDisplayCounter').css('display','none');\n";
		}
		$calCall.= "\t\tgenCalendar('agenda', 'DptinfoCalendarInner$DptinfoCalendarDisplayCounter', dptinfoCalendar$DptinfoCalendarDisplayCounter, ";
		$calCall.= "{"; // options start
		$calCall.=" iconPath:'".DptinfoCalendarJSEscape(DptInfoCalendarGetHTTPPath()."pub/dptinfo-calendar/icons/")."'"; $callvirg=",";
		if (isset($a["startdate"])) { $calCall.="$callvirg startDate:\"".DptinfoCalendarJSEscape($a["startdate"])."\""; $callvirg=","; }
		$calCall.= "} "; //options end
		//		$calCall.= "genCalendar('agenda', 'DptinfoCalendarInner$DptinfoCalendarDisplayCounter', dptinfoCalendar$DptinfoCalendarDisplayCounter, DptinfoCalendarOptions$DptinfoCalendarDisplayCounter); ";
		$calCall.= "); \n";
		if (isset($a["showschedule"])) {
			$calCall.="\t\tgenCalendar('schedule', 'DptinfoCalendarScheduleInner$DptinfoCalendarDisplayCounter', dptinfoCalendar$DptinfoCalendarDisplayCounter, {}); \n";
			$calCall.="\t\t$('#DptinfoCalendarScheduleButton$DptinfoCalendarDisplayCounter').click( function() { $('#DptinfoCalendarInner$DptinfoCalendarDisplayCounter').css('display','none'); dptinfoCalendarDisp$DptinfoCalendarDisplayCounter('DptinfoCalendarScheduleInner$DptinfoCalendarDisplayCounter'); } ); \n";
			$calCall.="\t\t$('#DptinfoCalendarAgendaButton$DptinfoCalendarDisplayCounter').click( function() { $('#DptinfoCalendarScheduleInner$DptinfoCalendarDisplayCounter').css('display','none'); dptinfoCalendarDisp$DptinfoCalendarDisplayCounter('DptinfoCalendarInner$DptinfoCalendarDisplayCounter'); } ); \n";
		}

		$furtherJS.="\nfunction dptinfoCalendarTagUpdate$DptinfoCalendarDisplayCounter() {\n";
		$furtherJS.="\tvar tags = [];\n";
		foreach ($DptinfoCalendarTags as $id => $name) {
			$furtherJS.="\tif ($('#DptinfoCalendarTag$id-$DptinfoCalendarDisplayCounter').is(':checked')) { tags.push('$id'); }\n";
			$calCall.="$('#DptinfoCalendarTag$id-$DptinfoCalendarDisplayCounter').click( dptinfoCalendarTagUpdate$DptinfoCalendarDisplayCounter );";
		}
		$furtherJS.="\tdptinfoCalDefaultOptions.usedTags = tags;\n";
		$furtherJS.="\t$('#DptinfoCalendarInner$DptinfoCalendarDisplayCounter').fullCalendar('rerenderEvents');\n";
		if (isset($a["showschedule"])) {
			$furtherJS.="\t('#DptinfoCalendarScheduleInner$DptinfoCalendarDisplayCounter').fullCalendar('rerenderEvents');\n";
		}
		$furtherJS.="} \n";

		$calCall.="\t\tvar url = window.location.href;";
		$calCall.=" var dMatch = /date=(\d\d\d\d-\d\d-\d\d)/.exec(url); ";
		$calCall.=" if (dMatch != null) { $('#DptinfoCalendarAgendaButton$DptinfoCalendarDisplayCounter').click(); }";
		$calCall.= "\t}\n";

		if (! $DptinfoCalendarUseNew) {
			$hdrscript="<script>$furtherJS\n$(document).ready(\n$calCall); \n </script>";
		} else {
			$hdrscript="<script>$furtherJS\nDptinfoCalendarReady(\n$calCall); \n </script>";
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
