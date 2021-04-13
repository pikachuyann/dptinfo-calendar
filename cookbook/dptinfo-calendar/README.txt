== Installation ==
The contents of this archive should be unpacked in the installation folder of
the PmWiki. Then, similarly to other recipes, you include the main file in your
configuration file:
	include_once("cookbook/dptinfo-calendar.php");

== Usage ==
This recipe adds several tags to PmWiki so that an user can construct its
calendar/schedule through several steps. It uses fullCalendar.js to display the
complete schedule, similarly to the way it was done in the computer science
department of the ENS Paris-Saclay ("dptinfo", hence the name).

=== dptcal ===
The (:dptcal …:) tag displays the calendar; the following parameters are accepted:
	showschedule="true"
		defines if the calendar can be displayed as a schedule (for
		repeating events such as lectures or seminars)
	startdate="2021-04-21"
		defines the default display date of the calendar

It can also be used without any parameter ( (:dptcal:) )

=== dptcalset ===
The (:dptcalset …:) tag is used for settings that may apply to all events
	start="2021-01-01"
		defines the default start date for repeating events
	end="2021-08-01"
		defines the default end date for repeating events
	startindex="1"
		defines the starting index for relevant parameters (default is 0)

=== dptevent ===
The (:dptevent …:) tag adds a single-time event to the calendar; the following
parameters are accepted:
	name="A name"
		sets he name of the event
	date="2021-04-21"
		sets the date of the event
	start="19:00"
	end="19:00"
		sets the hours of the event
	color="lightgreen"
		set a background color for the event
	room="Room A"
		sets the place of the event
	speaker="Someone"
		sets the speaker of the event
	url="https://www.example.com/"
	urltext="Example"
		give a url for more information about the event

{(dptevent …)} can also be used with the same parameters.

=== dptlecture ===
The (:dptlecture …:) tag adds a repeating event to the calendar. It repeats
every 7 days after the starting date, until the given end date.
	name="A name"
	start="19:00"
	end="19:00"
	color="lightgreen"
	room="Room A"
	url="https://www.example.com/"
	urltext="Example"
		Similar to (:dptevent:)
	teacher="Someone"
		sets the speaker of the repeating event (i.e. the teacher of
		a lecture)
	teachers="2"
	teacher0="Someone"
	teacher1="Someone else"
		sets the number of teacher then each teacher individually; the
		global setting startindex can be used to start the count at 1
		(and use teacher1= teacher2= instead of teacher0= teacher1=)
	id="id"
		set an identifier to the repeating event (that can be used to
		modify or remove single repeats)

=== dptlecturemodify ===
The (:dptlecturemodify …:) tag is used to modify a single repeat of a repeating
event of the calendar.
	id="id"
		specify the identifier of the repeating event to be modified
	which="2021-04-21"
		specify the date of the repeat to be modified
	start="19:00"
	end="19:00"
	room="Room A"
	teacher="Teacher A"
	url="https://www.example.com/"
	urltext="Example"
		Similar to (:dptlecture:)

=== dptlectureadd ===
The (:dptlectureadd …:) tag is used to add an event linked to a repeating event
that is scheduled outside of the repetition scheme.
	id="id"
		specify the identifier of the repeating event on which we add
		a new date
	date="2021-04-21"
		date of the newly added repeat
	start="19:00"
	end="19:00"
	room="Room A"
	teacher="Teacher"
	url="https://www.example.com/"
		Similar to (:dptlecture:)

=== dptdate ===
The (:dptdate …:) tag is used to define certain periods as "holidays" in which
the repating events can't happen
	holidays="Christmas"
		Sets the given period as an holiday, and gives the name of the
		holidays
	visit="LSV"
		Sets the given period as a lab visit, and give the name of the
		visited lab.
	start="2021-04-10"
	end="2021-04-17"
		The start and end of the holidays.

=== dptperson ===
The (:dptperson …:) tag is used to give additional information about a speaker
or lecturer to be given as a tooltip when hovering the events
	name="Someone"
		used to specify the name of the speaker or lecturer
	url="https://www.example.com/"
		specifies the url of their homepage

