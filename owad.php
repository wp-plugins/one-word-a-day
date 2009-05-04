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

require_once(dirname(__FILE__) . '/functions.php');

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

	wp_deregister_script('jquery');
	wp_register_script('jquery', ("http://ajax.googleapis.com/ajax/libs/jquery/1.3.2/jquery.min.js"), false, '1.3.2');
	wp_enqueue_script('jquery');
	
	if ( !function_exists('wp_register_sidebar_widget') )
		return;

	// OWAD FRONTEND START
   function owad($args) 
   {
   	   	
   	extract( $args );
	
	$content = owad_get_data();
	extract( $content );
    
	echo $before_widget;
	echo $before_title .'One Word A Day'. $after_title;

	if ( owad_supported_by_host() )
	{
		?>
		
		<p>
		What does <strong><span id="owad_todays_word"><?= $todays_word ?></span></strong> mean?
		
		<table>
		<tr><td valign="top">a)</td><td> <span id="owad_alt1"> <a href="http://www.owad.de/check.php4?id=<?= $wordid ?>&choice=1" target="_blank"> <?= $alternatives[0] ?> </a> </span> </td></tr>
		<tr><td valign="top">b)</td><td> <span id="owad_alt2"> <a href="http://www.owad.de/check.php4?id=<?= $wordid ?>&choice=3" target="_blank"> <?= $alternatives[1] ?> </a> </span> </td></tr>
		<tr><td valign="top">c)</td><td> <span id="owad_alt3"> <a href="http://www.owad.de/check.php4?id=<?= $wordid ?>&choice=5" target="_blank"> <?= $alternatives[2] ?> </a> </span> </td></tr>
		</table>
		</p>
		
		<?php
		$sets = owad_fetch_archive_words();
		
		$counts = count( $sets );
		if ( $counts > 1 )
		{
			?>
	         

			   <script type="text/javascript">
				 function loadData()
				 {
				 	var dataToBeSent = $("form").serialize();
				 	
					$.getJSON("<?= OWAD_URLPATH ?>word2json.php", dataToBeSent, function(json){
						document.getElementById("owad_todays_word").innerHTML = json.todays_word;

						document.getElementById('owad_alt1').innerHTML = '<a href="http://www.owad.de/check.php4?id=' + json.wordid +'&choice=1" target="_blank">'+ json.alternatives[0] +'</a>';
						document.getElementById('owad_alt2').innerHTML = '<a href="http://www.owad.de/check.php4?id=' + json.wordid +'&choice=3" target="_blank">'+ json.alternatives[1] +'</a>';
						document.getElementById('owad_alt3').innerHTML = '<a href="http://www.owad.de/check.php4?id=' + json.wordid +'&choice=5" target="_blank">'+ json.alternatives[2] +'</a>';

					});
					
				 }
			   </script>
			   

			Older words:
   			<?php
		
			echo '<form id="owad_wordid">';
			echo '<select style="width:100%;" name="wordid" onchange="loadData();">';
			
			
			for ( $i = 0; $i<$counts; $i++ )
			{
				// There's still a bug. Sometimes the words are cached more than once or an empty
				// entry is saved.
				if ( empty( $sets[$i]["wordid"] ) || in_array( $sets[$i]["wordid"], $words )  ) 
					continue;
					
				$words[] = $sets[$i]["wordid"];

				echo  '<option value="'. $sets[$i]["wordid"] .'">'. htmlentities( $sets[$i]["todays_word"] ) .'</option>';			
			}
				
			echo '</select>';
			echo '</form>';
			?>
			
			<div style="max-width:200px;">
			<a style="float:left; margin-right:5px; margin-top:-6px;" href="http://twitter.com/one_word_a_day" target="_blank">
			<img style="height:45px; width:45px;" src="<?= OWAD_URLPATH ?>twitter.png" /></a>
			<span style="line-height:13px; font-size:8pt; margin-top:10px;"><small>
			Get the <a href="http://slopjong.de/2009/03/20/one-word-a-day" target="_blank">'one word a day'
			widget</a> or visit <a href="http://twitter.com/one_word_a_day" target="_blank">OWAD on twitter</a>.
			</small></span>
			</div>
			<br style="clear:left;"/>
			
			<?php
		}
	}
	else
		echo 	'If you can read this text this widget isn\'t supported by this blog\'s host!<br/> 
				<br/>Please leave a comment <a href="http://slopjong.de/2009/03/20/one-word-a-day/" 
				target="_blank">here</a> to help improve this widget.';
	
	echo $after_widget;
	}
	// OWAD FRONTEND ENDE


	// OWAD BACKEND START
	function owad_control() 
	{		
		if ( ! owad_supported_by_host() )
			echo 'If you can read this text this widget isn\'t supported by this blog\'s host!<br/> 
				<br/>Please leave a comment <a href="http://slopjong.de/2009/03/20/one-word-a-day/" 
				target="_blank">here</a> to help improve this widget.';
	}
	// OWAD BACKEND START
	

	// let WP know of this plugin's widget view entry
	wp_register_sidebar_widget('owad', 'One Word A Day','owad');

	// let WP know of this widget's controller entry
	wp_register_widget_control('owad', 'One Word A Day', 'owad_control');
}

add_action('widgets_init', 'owad_init');
    
?>
