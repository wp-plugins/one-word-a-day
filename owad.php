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

/* TODO
- Trackbacks einfügen
- den Custom Value 'owad_hide_question' berücksichtigen und nicht nur die versteckte Variante
*/

define('OWAD_VERSION',"0.2.1");

require_once(dirname(__FILE__) . '/settings/constants.php');
require_once(dirname(__FILE__) . '/settings/settings.php');
require_once(dirname(__FILE__) . '/classes/class.owad.php');
require_once(dirname(__FILE__) . '/classes/class.widget.php');

// For debugging
//require_once(dirname(__FILE__) . '/krumo/class.krumo.php' );

// Let's go
if ( class_exists('Owad') )
	$owad = new Owad();

?>
