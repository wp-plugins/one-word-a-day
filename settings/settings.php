<?php

// These are the default values of the option 'owad' which is stored in the database
$owad_default_options = array(
		"owad_daily_post"     => 0, // 0 = false, 1 = true
		"owad_post_category"  => array( 1 ),
		"owad_post_author"    => 1,
		"owad_last_word_posted" => 0 // The last word that was posted. 0 means no word posted yet.
		);
		

// Not stored in the database
$owad_default_settings = array(	
		);
		
// On these days no new word appears
$owad_holidays = array(
		/*
		date( "Y-m-d", mktime( 0, 0, 0, 1, 1 ) ),
		date( "Y-m-d", mktime( 0, 0, 0, 5, 1) ),
		date( "Y-m-d", mktime( 0, 0, 0, 5, 21 ) ),
		date( "Y-m-d", mktime( 0, 0, 0, 6, 1 ) ),
		date( "Y-m-d", mktime( 0, 0, 0, 12, 25 ) )
		*/
		
		// Got these data from owad.de
		"2009-01-01",  	  //New Year's Day
		"2009-01-02",     //x-mas-break
		"2009-01-05",     //x-mas-break
		"2009-01-06",     //x-mas-break
		"2009-01-07",     //x-mas-break
		"2009-01-08",     //x-mas-break
		"2009-01-09",     //x-mas-break
		"2009-04-10",     //Karfreitag
		"2009-04-13",     //Ostermontag
		"2009-05-01",     //Maifeiertag
		"2009-05-21",     //Christi Himmelfahrt
		"2009-06-01",     //Pfingstmontag
		"2009-10-03",     //Tag der Deutschen Einheit
		"2009-12-25",     //1. Weihnachtstag
		"2009-12-26",     //2. Weihnachtstag
		
		"2010-01-01",  	  //Neujahr
		"2010-04-02",     //Karfreitag
		"2010-04-05",     //Ostermontag
		"2010-05-01",     //Maifeiertag
		"2010-05-13",     //Christi Himmelfahrt
		"2010-05-24",     //Pfingstmontag
		"2010-10-03",     //Tag der Deutschen Einheit
		"2010-12-25",     //1. Weihnachtstag
		"2010-12-26"      //2. Weihnachtstag
		);
?>