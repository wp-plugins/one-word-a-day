<?php
/*
Plugin Name: 	One Word A Day
Plugin URI: 	http://slopjong.de/2009/03/20/one-word-a-day/
Description:  	Displays a new English word every day with a multiple choice quiz.
Author: 		Romain Schmitz
Author URI: 	http://slopjong.de
License:     	GNU General Public License
Last Change: 	4.5.2009
Version: 		0.2.1

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

define('OWAD_VERSION',"0.2.1");

define('OWAD_FOLDER', dirname(plugin_basename(__FILE__)));
define('OWAD_URLPATH', get_option('siteurl').'/wp-content/plugins/' . OWAD_FOLDER.'/');
define('OWAD_CACHE_FILE', "wp-content/plugins/" . OWAD_FOLDER . "/cache/words.xml");

//require_once(dirname(__FILE__) . '/functions.php');
require_once(dirname(__FILE__) . '/owad.class.php');
require_once(dirname(__FILE__) . '/widget.class.php');

if ( class_exists('Owad') )
	$owad = new Owad();

?>
