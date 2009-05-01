<?php
define( "USE_CACHE", false );

function owad_get_data()
{
	if ( file_exists( OWAD_CACHE_FILE ) && USE_CACHE )
	{
		$words = simplexml_load_file( OWAD_CACHE_FILE );
		$counts = count( $words );
		
		if ( $counts > 0 )
		{
			$word = $words->word[$counts - 1];
			$attributes = $word->attributes();

			$last_word_date = $attributes[1];
			$now = date( "Y-m-d", mktime() );
			$weekday = date( 'w', mktime() );
			
			// Wenn das heutige Datum nicht mit dem aus dem Set übereinstimmt und kein
			// Wochenende ist, lade das neue Wort
			if ( ( $last_word_date != $now ) && ( $weekday != 0 ) && ( $weekday != 6 ) )
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
	else
		return owad_fetch_todays_word();
}

function owad_get_timestamp($date)
{
	$date = split( '-', $date );
	$ts = mktime( 0, 0, 0, $date[1], $date[2], $date[0] );
	return $ts;
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

function owad_fetch_archive_word()
{
}

function owad_fetch_todays_word()
{	
	// http://owad.de/yesterday.php4?date=2009-03-15
	$file = "http://owad.de/index.php4";
	$page = wp_remote_fopen($file);

	$pattern = "[[:print:]]+";
	
	preg_match( '/wordid=[0-9]{1,4}/', $page, $array );
	$wordid = str_replace( "wordid=", "", $array[0] );
	
	preg_match_all( '/<a href="check.php4[^>]+>'. $pattern .'<\/a>/', $page, $array );
	$alternatives = array( "", "", ""); // if no matches are found
	$alternatives = $array[0];
	
	for( $i=0; $i<3; $i++)
		$alternatives[$i] = strip_tags( $alternatives[$i] );
			
	preg_match( "/See today's word: [^<]+/", $page, $array );
	$todays_word = trim( str_replace( "See today's word:", "", $array[0] ) ); 
	
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
	$date = date( 'Y-m-d' , mktime() - $offset );		
	
	
	$return_value = array(
		"wordid" => $wordid,
		"date" => $date,
		"todays_word" => $todays_word, 
		"alternatives" => $alternatives 
		);
		
	return $return_value;		
}
?>