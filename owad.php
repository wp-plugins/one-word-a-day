<?php
/*
Plugin Name: 	One Word A Day
Plugin URI: 	http://slopjong.de/2009/03/20/one-word-a-day/
Description:  	Displays a new English word every day with a multiple choice quiz.
Author: 		Romain Schmitz
Author URI: 	http://slopjong.de
License:     	GNU General Public License
Last Change: 	18.6.2009
Version: 		0.3

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

define('OWAD_VERSION',"0.3");

require_once(dirname(__FILE__) . '/settings/constants.php');
require_once(dirname(__FILE__) . '/settings/settings.php');
require_once(dirname(__FILE__) . '/classes/class.owad.php');

// xml features
//require_once(dirname(__FILE__) . '/classes/class.array_to_xml.php');
//require_once(dirname(__FILE__) . '/classes/class.xml_element_extended.php');

if( version_compare( $wp_version, "2.8", "<" ) )
	require_once(dirname(__FILE__) . '/classes/class.widget.php');
else
	require_once(dirname(__FILE__) . '/classes/class.widget28.php');

// load language file
load_plugin_textdomain( 'owad', false, OWAD_FOLDER .'/lang' );

// For debugging
//require_once(dirname(__FILE__) . '/krumo/class.krumo.php' );

function krumo( $data )
{
	echo "<pre>". print_r( $data, true) ."</pre>";
}

// Let's go
if ( class_exists('Owad')  && !isset($owad)  )
	$owad = new Owad();


?>
