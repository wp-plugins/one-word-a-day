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
			$last_word_date_xml = $word->attributes()->date;
			
			// compare the file's last word date with the last word date
			if ( ( $last_word_date_xml != owad_last_word_date() ) )
			{
				$new_word = owad_fetch_todays_word();
				owad_save_set( $new_word, $words );
				return $new_word;
			}	
			
			$last_word = owad_extract_set( $word );
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

function owad_last_word_date()
{
	// holidays are not considered yet
	$now = wp_remote_fopen("http://owad.slopjong.de/cache_time.php");
	$time = split( '-', $now );
	
	// calculate the date, on the weekend there's no new word, so the date has to calculated
	// with an offset of either one or two days
	$offset = 0;
	switch ( date("w", mktime( 0, 0, 0, $time[1], $time[2]) ))
	{
		case 0: 	$offset = 2;
					break;
		case 6: 	$offset = 1;
		default:	break;
	};
	
	do
	{
		$date = date( 'Y-m-d' , mktime( 0, 0, 0, $time[1], $time[2] - $offset ) );
		$offset++;
	} while( owad_is_holiday( $date ) );
	
	return $date;	
}

function owad_is_holiday( $date )
{
	$holidays = array(
	date( "Y-m-d", mktime( 0, 0, 0, 1, 1 ) ),
	date( "Y-m-d", mktime( 0, 0, 0, 5, 1) ),
	date( "Y-m-d", mktime( 0, 0, 0, 5, 21 ) ),
	date( "Y-m-d", mktime( 0, 0, 0, 6, 1 ) ),
	date( "Y-m-d", mktime( 0, 0, 0, 12, 25 ) )
	);
	
	return in_array( $date, $holidays );
}

function owad_extract_set( $word )
{
	$attributes = $word->attributes();
	
	// in the first version of this widget the data in the xml file had white spaces.
	// that's why trim is used here
	$set = array(
	  "wordid" => "". trim( $attributes->wordid ),
	  "date" => "". trim ( $attributes->date ),
	  "todays_word" => "". trim( $attributes->content ),
	  "alternatives" => array( 
		"". trim( $word->alternative[0] ),
		"". trim( $word->alternative[1] ),
		"". trim( $word->alternative[2] )
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
	
	$date = owad_last_word_date();
	
	$return_value = array(
		"wordid" => $wordid,
		"date" => $date,
		"todays_word" => $todays_word, 
		"alternatives" => $alternatives 
		);
		
	return $return_value;		
}

function owad_get_word_by_id( $id )
{
	$words = simplexml_load_file( OWAD_CACHE_FILE );
	$counts = count( $words );
	
	for ( $i=0; $i<$counts; $i++)
	{
		$word = $words->word[$i];
		$attributes = $word->attributes();
		if ( $id == $attributes[0] )
			return owad_extract_set( $word, $attributes );
	}
	
	return NULL;
}

function owad_supported_by_host()
{
	$modules = get_loaded_extensions();
	return in_array( "json" , $modules );
}
?>