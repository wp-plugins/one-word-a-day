<?php

// Load the wp funcions if this file isn't included during the plugin loading process
if ( ! isset($wp_did_header) )
{
	// get the wp installation path
	preg_match( '/.+wp\-content/', __FILE__ , $owad_wp_path );
	$owad_wp_path = str_replace( "/wp-content", "", dirname( $owad_wp_path[0] ) );
	
	// load the wp functions 
	require_once( $owad_wp_path .'/wp-load.php' );
}

// define constants for the plugin
define('OWAD_FOLDER', str_replace( "/settings", "", dirname(plugin_basename(__FILE__))));
define('OWAD_URLPATH', get_option('siteurl').'/wp-content/plugins/' . OWAD_FOLDER.'/');
define('OWAD_CACHE_FILE', WP_PLUGIN_DIR .'/'. OWAD_FOLDER . "/cache/words.xml");
define('OWAD_USE_CACHE', true );
define('OWAD_COMMENT_TEXT', 'Learning English with the WordPress plugin <em>One Word A Day</em> which displays a new English word and a quiz in the sidebar every work day. <a rel="follow" href="http://slopjong.de/2009/03/20/one-word-a-day/?KeepThis=true&TB_iframe=true&height=540&width=800" class="thickbox" target="_blank">Get it here</a>!' );
?>