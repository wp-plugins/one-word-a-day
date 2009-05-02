<?php
define( "USE_CACHE", true );

function owad_get_data()
{
	if ( !USE_CACHE )
		return owad_fetch_todays_word();
		
	if ( file_exists( OWAD_CACHE_FILE ) )
	{
		$words = simplexml_load_file( OWAD_CACHE_FILE );
		$counts = count( $words );
		
		if ( $counts > 0 )
		{
			$word = $words->word[$counts - 1];
			$attributes = $word->attributes();

			$last_word_date = $attributes[1];
			
			// load the cache date from the server to handle timezone issues
			$now = wp_remote_fopen("http://owad.slopjong.de/cache_time.php?now");
			$time = split( '-', $now );
			$weekday = date( 'w', mktime( 0, 0, 0, $time[1], $time[2], $time[0]) );
			
			// Wenn das heutige Datum nicht mit dem aus dem Set �bereinstimmt und kein
			// Wochenende ist, lade das neue Wort
			if ( ( $last_word_date != $now ) && ( $weekday != 0 ) && ( $weekday != 6 ) && !owad_is_holiday( $now ) )
			{
				$new_word = owad_fetch_todays_word();
				owad_save_set( $new_word, $words );
				return $new_word;
			}	
			
			$last_word = owad_extract_set( $word, $attributes );
			return $last_word;
		}
		else
		{
			$set = owad_fetch_todays_word();
			owad_save_set( $set, $words );
			
			return $set;
		}
	}
}

// This can be optimized a bit
// a timestamp should be used instaed of a date
function owad_is_holiday( $date )
{
	$holidays[] = array(
	date( "Y-m-d", mktime( 0, 0, 0, 1, 1 ) ),
	date( "Y-m-d", mktime( 0, 0, 0, 5, 1) ),
	date( "Y-m-d", mktime( 0, 0, 0, 5, 21 ) ),
	date( "Y-m-d", mktime( 0, 0, 0, 6, 1 ) ),
	date( "Y-m-d", mktime( 0, 0, 0, 12, 25 ) )
	);
	
	return in_array( $date, $holidays );
}

function owad_extract_set( $word, $attributes )
{
	$set = array(
	  "wordid" => $attributes[0],
	  "date" => $attributes[1],
	  "todays_word" => $attributes[2],
	  "alternatives" => array( 
		$word->alternative[0],
		$word->alternative[1],
		$word->alternative[2]
		)
	  );

	return $set;
}

function owad_save_set( $set, $words )
{
	extract( $set );

	$new_word = $words->addChild('word');
	$new_word->addAttribute( 'wordid' , $wordid );
	$new_word->addAttribute( 'date' , $date );
	$new_word->addAttribute( 'content' , $todays_word );
	
	for( $i = 0; $i < 3 ; $i++)
		$new_word->addChild( 'alternative', $alternatives[$i] );
	
	$file = fopen( OWAD_CACHE_FILE, 'w' );
	fwrite( $file , $words->asXML() );
}

function owad_fetch_archive_words()
{	//*
	if ( file_exists( OWAD_CACHE_FILE ) )
 	{
 		$sets = array();
 		
		$words = simplexml_load_file( OWAD_CACHE_FILE );
		$counts = count( $words );
		
		if ( $counts >= 2)
		{
			for ( ; $counts > 0; $counts-- )
			{
				$word = $words->word[$counts-1];
				$att = $word->attributes();
				$sets[] = owad_extract_set( $word, $att );				
			}
		}
		
		return $sets;
	}
	//*/
}

function owad_fetch_todays_word()
{	
	/*
		OWAD API
		- owad.de/yesterday.php4?date=2009-03-15
			=> Get the latest words
		- owad.de/check.php4?wordid=1&choice=1
			=> Check the choice
		- owad.de/repeat.php4?date=2009-04-06
			=> Quiz for the latest words
	*/

	$file = "http://owad.de/index.php4";
	$page = wp_remote_fopen($file);

	$pattern = "[[:print:]]+";
	
	preg_match( '/wordid=[0-9]{1,4}/', $page, $array );
	$wordid = str_replace( "wordid=", "", $array[0] );
	
	preg_match_all( '/<a href="check.php4[^>]+>'. $pattern .'<\/a>/', $page, $array );
	$alternatives = array( "", "", "");
	$alternatives = $array[0];
	
	for( $i=0; $i<3; $i++)
		$alternatives[$i] = strip_tags( $alternatives[$i] );
			
	preg_match( "/See today's word: [^<]+/", $page, $array );
	$todays_word = trim( str_replace( "See today's word:", "", $array[0] ) ); 
	
	// holidays are not considered yet
	$now = wp_remote_fopen("http://owad.slopjong.de/cache_time.php");
	$time = split( '-', $now );
	
	// calculate the date, on the weekend there's no new word, so the date has to calculated
	// with an offset of either one or two days
	$multiplicator = 0;
	switch ( date("w") )
	{
		case 0: 	$multiplicator = 2;
					break;
		case 6: 	$multiplicator = 1;
		default:	break;
	};
	
	// Wenn Samstag, dann $offset = Sekunden eines Tags, 
	// wenn Sonntag, dann $offset = Sekunden von 2 Tagen
	$offset = 60 * 60 * 24 * $multiplicator;
	
	//do
	{
		$date = date( 'Y-m-d' , mktime( 0, 0, 0, $time[1], $time[2]) - $offset );
	} 
	//while( owad_is_holiday( $date ) )
	
	$return_value = array(
		"wordid" => $wordid,
		"date" => $date,
		"todays_word" => $todays_word, 
		"alternatives" => $alternatives 
		);
		
	return $return_value;		
}

// This only checks if cURL is installed on the server. It's returns true all time
// because cURL isn't used.
function owad_supported_by_host()
{
	$modules = get_loaded_extensions();
	//return in_array( "curl" , $modules );
	return true;
}
?>