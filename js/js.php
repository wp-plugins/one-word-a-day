<?php
header('Content-type: application/x-javascript');

require_once( '../settings/constants.php' );

$url_path = OWAD_URLPATH;
$inc_path = get_option('siteurl') .'/'. WPINC;

echo <<<JAVASCRIPT

		// set the paths to the thickbox images
		var tb_pathToImage = "$inc_path/js/thickbox/loadingAnimation.gif";
		var tb_closeImage = "$inc_path/js/thickbox/tb-close.png"

		// Loads the word in the json format		
		function loadData( widget_id )
		{
			// If an older WordPress version than 2.8 is used the argument is missing which has to be created then
			if( !widget_id )
				var widget_id = '';
				
			var dataToBeSent = jQuery('#owad_wordid_' + widget_id ).serialize();

			jQuery.getJSON("${url_path}word2json.php", dataToBeSent, function(json){
				var todays_word = json.todays_word;
			
				jQuery("#owad_todays_word_" + widget_id )[0].innerHTML = json.todays_word;
								
				jQuery("#owad_alt1_" + widget_id )[0].innerHTML = json.alternatives[0];
				jQuery("#owad_alt1_" + widget_id )[0].href = 'http://owad.slopjong.de/'+ escape( todays_word.replace( / /g, "_") ) +'_1' + json.wordid +'.html?KeepThis=true&TB_iframe=true&height=540&width=800';
								
				jQuery("#owad_alt2_" + widget_id )[0].innerHTML = json.alternatives[1];
				jQuery("#owad_alt2_" + widget_id )[0].href = 'http://owad.slopjong.de/'+ escape( todays_word.replace( / /g, "_") ) +'_3' + json.wordid +'.html?KeepThis=true&TB_iframe=true&height=540&width=800';
				
				jQuery("#owad_alt3_" + widget_id )[0].innerHTML = json.alternatives[2];
				jQuery("#owad_alt3_" + widget_id )[0].href = 'http://owad.slopjong.de/'+ escape( todays_word.replace( / /g, "_") ) +'_5' + json.wordid +'.html?KeepThis=true&TB_iframe=true&height=540&width=800';

			});
			
		 }

JAVASCRIPT;

?>