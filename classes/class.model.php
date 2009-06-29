<?php

if( !defined("OWAD_USE_CACHE") )
	define( "OWAD_USE_CACHE", true );
	
class Owad_Model
{
	/**
	 * Load either today's word from the cache or from the server and cache it.
	 *
	 * @return array today's word
	 */
	function get_data()
	{
		if ( !OWAD_USE_CACHE )
			return self::fetch_todays_word();

		$words = self::get_cache_content();
		
		if ( ! is_null($words) )
		{
			$word = array_pop( $words );
			
			if ( $word['@attributes']['date'] != self::fetch_word_date() )
			{
				$word = self::fetch_todays_word();
				self::cache_word( $word );
			}
		}
		else
		{
			$word = self::fetch_todays_word();
			self::cache_word( $word );
		}

		return $word;	
	}
	
	/**
	 * Retrieves the cached words
	 * 
	 * @return array the words or null if there aren't any
	 */
	public static function get_cache_content()
	{
		if ( !file_exists( OWAD_CACHE_FILE ) )
			file_put_contents( OWAD_CACHE_FILE , '<?xml version="1.0" encoding="UTF-8"?><words></words>');
			
		$words = simplexml_load_file( OWAD_CACHE_FILE );
		$words = self::object_to_array( $words );
		
		if( !isset($words["word"]) )
			return NULL;
		else
			return $words["word"];
	}
	
	/**
	 * Caches the word.
	 *
	 * @param array word to be cached
	 */
	private static function cache_word( $word )
	{
		$words["word"] = self::get_cache_content();
		array_push( $words["word"], $word );
		$words = self::array_to_xml( $words );
		file_put_contents( OWAD_CACHE_FILE, $words->asXML() );
	}

	/**
	 * Load the date of a "today's word".
	 *
	 * @param string given "today's word" (it might be an older one)
	 * @return string a date with the format YYYY-MM-DD
	 */
	private static function fetch_word_date( $word = '' )
	{		
		if( !empty( $word ))
		{
			$first_char = strtoupper( substr( $word, 0, 1 ) );
			$page = wp_remote_fopen( "http://owad.de/owad-archive.php4?char=". $first_char );
			$page = str_replace( "\n", "", $page );
			preg_match( "/<b>". trim($word) ."<\/b> lernen\s+\((\d{4}\-\d{2}\-\d{2})\)\s+<br>/", $page, $array );
			$date = $array[1];
		}
		else
		{
			// Pseudo date
			$date = "1970-01-01";
			
			/*
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
			} while( $this->is_holiday( $date ) );
			*/
		}
		
		return $date;	
	}
	
	/**
	 * Fetches today's word.
	 *
	 * @return array today's word
	 * @see fetch_single_word
	 */
	private static function fetch_todays_word()
	{
		return $this->fetch_single_word( "http://owad.de/index_en.php4" );
	}
	
	/**
	 * Parses the owad page to fetch the desired data for this plugin.
	 *
	 * @param string url where the data should be loaded from
	 * @param string word ID
	 * @return array word
	 */
	private static function fetch_single_word( $url, $id = '')
	{	
		$page = wp_remote_fopen( $url.$id );
	
		$pattern = "[[:print:]]+";
		
		preg_match( '/wordid=[0-9]{1,4}/', $page, $array );
		$wordid = str_replace( "wordid=", "", $array[0] );
		
		// sometimes there are white spaces and a new line at the end of the answers
		preg_match_all( '/<a href="check.php4[^>]+>'. $pattern .'.*?[\n]?<\/a>/', $page, $array );
		$alternatives = array( "", "", "");
		$alternatives = $array[0];
		
		for( $i=0; $i<3; $i++)
		{			
			// remove html tags
			$alternatives[$i] = strip_tags( $alternatives[$i] );
			// remove white spaces
			$alternatives[$i] = trim( $alternatives[$i] );
			// replace ’ by ' ( this does not work )
			//$alternatives[$i] = preg_replace( "/’", "'", $alternatives[$i] );
			// convert into UTF8
			$alternatives[$i] = mb_convert_encoding( $alternatives[$i], "UTF-8" );
		}
				
		if( preg_match( "/See today's word: [^<]+/", $page, $array ) )
			$todays_word = trim( str_replace( "See today's word:", "", $array[0] )); 
		elseif ( preg_match( '/<p align="center" class="word"><br>[^<]+/', $page, $array ) )
			$todays_word = trim( strip_tags( $array[0] ));
		else
			$todays_word = "";
			
		$date = self::fetch_word_date( $todays_word );
		
		$word = array(
			"@attributes" => array(			
				"wordid" => $wordid,
				"date" => mb_convert_encoding( $date ),
				"content" => mb_convert_encoding($todays_word) ),
			"alternative" => $alternatives 
			);
			
		return $word;		
	}

	/**
	 * Fetches the word by a given ID from the cache.
	 *
	 * @param int word ID
	 * @return array | NULL word array
	 */	
	function get_cached_word_by_id( $id )
	{
		$words = self::get_cache_content()
		
		foreach ( $words as $word )
			if ( $id == $word["@attributes"]["wordid"] )
				return $word;
		
		return NULL;
	}

	/**
	 * Fetches the word by a given date from the cache.
	 *
	 * @param string Date with the format YYYY-MM-DD
	 * @return array | NULL word array
	 */
	public static function get_cached_word_by_date( $date )
	{
		$words = self::get_cache_content()
		
		foreach ( $words as $word )
			if ( $date == $word["@attributes"]["date"] )
				return $word;
		
		return NULL;
	}
	
	/** 
	 * Return all defect entries
	 * @return array defect entries
	 */
	public static function get_defect_entries()
	{
		$words = simplexml_load_file( OWAD_CACHE_FILE );
		$words = $this->object_to_array( $words );
		
		$defects = array();
		foreach( $words["word"] as $item )
		{				
			$word = $item["@attributes"]["content"];
			$word_id = $item["@attributes"]["wordid"];
			$alternative = $item["alternative"];

			if( empty( $word ) ||
				empty( $alternative[0] ) ||
				empty( $alternative[1] ) ||
				empty( $alternative[2] ) )
				$defects["word"][] = $item;
		}		
		
		return $defects;
	}

	/**
	 * Gets all the word IDs from the defect entries
	 * @param array defect entries
	 * @return array word IDs
	 */
	public static function get_defect_entries_ids( $entries )
	{
		$defects = array();
		foreach( $entries["word"] as $entry )
			$defects[] = $entry["@attributes"]["wordid"];
			
		return $defects;
	}
		
	/**
	 * Checks if the given entry is defect
	 * @param array word
	 * @return bool the result of the check
	 */
	public static function is_entry_defect( $word )
	{		
		if( empty( $word["@attributes"]["wordid"] ) ||
			empty( $word["@attributes"]["content"] ) ||
			empty( $word["alternative"][0] ) ||
			empty( $word["alternative"][1] ) ||
			empty( $word["alternative"][2] ) 
			) return true;
			
		return false;	
	}
	
	/**
	 * Changes a key name
	 * @param string old key name
	 * @param string new key name
	 * @param array reference of the array
	 * @return array array with changed key if it was found 
	 */
	public static function array_change_key_name( $orig, $new, &$array )
	{
		foreach ( $array as $k => $v )
			$return[ ( $k === $orig ) ? $new : $k ] = $v;
		return ( array ) $return;
	}

	/**
	 * Converts an array containg the words into an XML object.
	 * @param array array with all the entries
	 * @return SimpleXMLElement | false XML object or an error
	 */
	private static function array_to_xml( $arr )
	{
		$obj = simplexml_load_string("<?xml version='1.0' encoding='utf-8'?><words />");
		
		// key = 'word', val = word container
		foreach( $arr as $key_word => $val)
		{			
			// key = numeric, val = container with attributes and alternative
			foreach( $val as $key => $val)
			{
				$word = $obj->addChild( $key_word );
			
				foreach( $val["@attributes"] as $key => $val_att )
					$word->addAttribute( $key, trim($val_att) );
					
				foreach( $val["alternative"] as $key => $val_alt )
					$word->addChild( 'alternative', trim($val_alt) );
			}
		}
		
		return $obj;
	}
	
	/**
	 * Converts any object into an array.
	 * @param object any object
	 * @return array array representation of the object
	 * @link http://www.cachedot.net/1034
	 */
	public static function object_to_array( $obj )
	{
		$_arr = is_object($obj) ? get_object_vars($obj) : $obj;
		  
		foreach ($_arr as $key => $val) 
		{
		  	$val = (is_array($val) || is_object($val)) ? self::object_to_array($val) : $val;
			$arr[$key] = $val;
		}
		
    	return $arr; 
	}

}
?>