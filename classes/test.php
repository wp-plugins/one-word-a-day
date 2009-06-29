<?php

/*******************
 * TEST CASES
 ******************/
 
if( !defined( "OWAD_CACHE_FILE" ) )
	define( "OWAD_CACHE_FILE", "../cache/words.xml");

// Load the wp funcions if this file isn't included during the plugin loading process
if ( ! isset($wp_did_header) ) {
	
	// get the wp installation path
	preg_match( '/.+wp\-content/', __FILE__ , $owad_wp_path );
	$owad_wp_path = str_replace( "/wp-content", "", dirname( $owad_wp_path[0] ) );
	
	// load the wp functions 
	require_once( $owad_wp_path .'/wp-load.php' );
}


if( ! function_exists( "krumo" ) ) {
	function krumo( $mixed ) {
		echo "<pre>". print_r( $mixed, true ) ."</pre>";
	}
}

if( ! class_exists( "Owad_Model" ))
	include( "class.model.php" );


class Test extends Owad_Model
{
	// the parent is protected
	public static function fetch_word_date() {
		return parent::fetch_word_date();
	}

	// the parent is already public so this helper function isn't needed
	public static function get_newest_word() {
		return parent::get_newest_word();
	}
	
	public static function cache_word() {
	}
	
}

/*** Successfully tested
krumo( Test::get_newest_word() );
krumo( Test::fetch_word_date() );
krumo( Test::fetch_single_word() );
krumo( Test::get_cached_word_by_id(2260) );
krumo( Test::get_cached_word_by_date("2009-06-05") );
krumo( Test::get_defect_entries() );
krumo( Test::get_defect_entries_ids( Test::get_defect_entries() ) );
krumo( Test::is_entry_defect( Test::get_cached_word_by_id(2319) )); // ID(2319) was defect
//*/

?>