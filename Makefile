default: archive

ZIPFILENAME=PmWikiDptinfoCalendar.zip

archive:
	rm -f $(ZIPFILENAME)
	zip $(ZIPFILENAME) pub pub/dptinfo-calendar pub/dptinfo-calendar/js pub/dptinfo-calendar/css cookbook cookbook/dptinfo-calendar
	zip $(ZIPFILENAME) pub/dptinfo-calendar/js/jquery.min.js pub/dptinfo-calendar/js/moment.min.js pub/dptinfo-calendar/js/fullcalendar.min.js pub/dptinfo-calendar/js/dptcal.js pub/dptinfo-calendar/js/jquery.qtip.min.js
	zip $(ZIPFILENAME) pub/dptinfo-calendar/css/fullcalendar.css pub/dptinfo-calendar/css/jquery.qtip.min.css
	zip $(ZIPFILENAME) cookbook/dptinfo-calendar.php
	zip $(ZIPFILENAME) cookbook/dptinfo-calendar/README.txt cookbook/dptinfo-calendar/example_lectures_L3_2020-21.txt
