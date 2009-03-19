<?php
/*
Plugin Name: 	One Word A Day
Plugin URI: 	http://slopjong.de/2009/03/20/one-word-a-day/
Description:  	Displays a new English word every day with a multiple choice quiz.
Author: 		Romain Schmitz
Author URI: 	http://slopjong.de
License:     	GNU General Public License
Last Change: 	20.3.2009
Version: 		0.1

This program is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation; either version 2 of the License, or
(at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program; if not, write to the Free Software
Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA

*/

define('OWAD_VERSION',"0.1");

define('OWAD_FOLDER', dirname(plugin_basename(__FILE__)));
define('OWAD_URLPATH', get_option('siteurl').'/wp-content/plugins/' . OWAD_FOLDER.'/');
define('OWAD_CACHE_FILE', "wp-content/plugins/" . OWAD_FOLDER . "/cache/words.xml");

//require_once(dirname(__FILE__) . '/frontend.php');

// load language file
//load_plugin_textdomain( 'owad', false, OWAD_FOLDER .'/lang' );

/*
if ( is_admin() )
{
	if ( class_exists('Owad_Backend') )
	{
		$backend = new Owad_Backend();
		register_activation_hook( __FILE__, array( &$backend, 'activate') );
	}
}
else
{
	if ( class_exists('Owad_Frontend') )
		$frontend = new Owad_Frontend();
}
*/

function owad_init() {	// Check for the required WP functions, die silently for pre-2.2 WP.
	if ( !function_exists('wp_register_sidebar_widget') )
		return;

	// OWAD FRONTEND START
   function owad($args) 
   {
   	extract( $args );
	
	$content = owad_get_data();
	extract( $content );
    
	echo $before_widget;
	echo $before_title .'One Word A Day'. $after_title; ?>

	<div>
	What does <strong><?= $todays_word ?></strong> mean?
	
	<table>
	<tr><td>a)</td><td> <a href="http://www.owad.de/check.php4?id=<?= $wordid ?>&choice=1" target="_blank"> <?= $alternatives[0] ?> </a> </td></tr>
	<tr><td>b)</td><td> <a href="http://www.owad.de/check.php4?id=<?= $wordid ?>&choice=3" target="_blank"> <?= $alternatives[1] ?> </a> </td></tr>
	<tr><td>c)</td><td> <a href="http://www.owad.de/check.php4?id=<?= $wordid ?>&choice=5" target="_blank"> <?= $alternatives[2] ?> </a> </td></tr>
	</table>
	
	</div>
	
	<?php
	echo $after_widget;
	}
	// OWAD FRONTEND ENDE


	// OWAD BACKEND START
	function owad_control() 
	{

	}
	// OWAD BACKEND START
	
	function owad_get_timestamp($date)
	{
		$date = split( '-', $date );
		$ts = mktime( 0, 0, 0, $date[1], $date[2], $date[0] );
		return $ts;
	}
	
	function owad_get_data()
	{
		if ( file_exists( OWAD_CACHE_FILE ) )
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
	
		$opened_file = wp_remote_fopen($file);
	
		// fetch the content of interest
		$pos_start 	= strpos( $opened_file, "See today's word:") +  strlen( "See today's word:" );
		$pos_end 	= strpos( $opened_file, "<!--werbung-->");
		$len = $pos_end - $pos_start;
		
		$opened_file = substr( $opened_file, $pos_start, $len );

		// get word ID
		$pos_start  = strpos( $opened_file, "wordid=" ) + strlen( "wordid=" );
		$pos_end	= strpos( $opened_file, "&choice" );
		$len = $pos_end - $pos_start;
		
		$wordid = substr( $opened_file, $pos_start, $len );

		// remove all tags
		$opened_file = strip_tags( $opened_file );
		
		// get today's word
		$pos = strpos( $opened_file, "Now choose" );
		$todays_word = substr( $opened_file, 0, $pos );
	
		// get the alternatives
		$pos_alt_1 = strpos( $opened_file, 'a)' ) + 2;
		$pos_alt_2 = strpos( $opened_file, 'b)' ) + 2;
		$pos_alt_3 = strpos( $opened_file, 'c)' ) + 2;
		
		$len_1 = $pos_alt_2 - $pos_alt_1 -2;
		$len_2 = $pos_alt_3 - $pos_alt_2 -2;
		
		$alternatives[] = substr( $opened_file, $pos_alt_1, $len_1 );
		$alternatives[] = substr( $opened_file, $pos_alt_2, $len_2 );
		$alternatives[] = substr( $opened_file, $pos_alt_3 );
		
		// calculate the date, on the weekend there's no new word, so the date has to calculated
		// with an offset of either one or two days
		$multiplicator = 0;
		switch ( date("w") )
		{
			case 0: 	$multiplicator = 2;
						break;
			case 6: 	$multiplicator = 1;
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

	// let WP know of this plugin's widget view entry
	wp_register_sidebar_widget('owad', 'One Word A Day','owad');

	// let WP know of this widget's controller entry
	wp_register_widget_control('owad', 'One Word A Day', 'owad_control');
} 

add_action('widgets_init', 'owad_init');
    
?>
