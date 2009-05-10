<?php

define( "OWAD_USE_CACHE", true );

class Owad
{

	/**
	* PHP 4 Compatible Constructor
	*/
	function Owad()
	{
		$this->__construct();
	}
	
	/**
	* PHP 5 Constructor
	*/		
	function __construct()
	{
		wp_deregister_script('jquery');
		wp_register_script('jquery', ("http://ajax.googleapis.com/ajax/libs/jquery/1.3.2/jquery.min.js"), false, '1.3.2');
		wp_enqueue_script('jquery');
		
		if ( class_exists('Owad_Widget') )
			$widget = new Owad_Widget();
			
		add_shortcode( "owad", array( &$this, "shortcode_handler" ) );
		add_action( 'wp_head', array( &$this, 'header'), 1);
	
	}
	
	function shortcode_handler( $atts )
	{
		if ( ! $this->supported_by_host() )
			return $this->no_support_text();
			
		if ( isset( $atts["date"] ) )
		{	
			// the tags have to be stripped because the blog could get hacked throuch mail posting
			// controlled by a hacker or whatever.
			$date = strip_tags( $atts["date"] );
			
			if ( $date == "post_date" )
			{	
				global $post;
				$date = $post->post_date;
			}
			
			if ( preg_match( "/[\d]{4,4}-[\d]{2,2}-[\d]{2,2}/", $date, $date ) )
			{
				$word = $this->get_word_by_date( $date[0] );	
				return $this->print_word( $word );
			}
		}
		else
		{
			$output .= $this->print_word();
			//$output .= $this->print_archive_words();
			
			return $output;
		}
		
	}

	function get_data()
	{
		if ( !OWAD_USE_CACHE )
			return $this->fetch_todays_word();
			
		if ( !file_exists( OWAD_CACHE_FILE ) )
			file_put_contents( OWAD_CACHE_FILE , '<?xml version="1.0" encoding="UTF-8"?><words></words>');
	
		$words = simplexml_load_file( OWAD_CACHE_FILE );
		$counts = count( $words );
		
		if ( $counts > 0 )
		{
			$word = $words->word[$counts - 1];
			$last_word_date_xml = $word->attributes()->date;
			
			// compare the file's last word date with the last word date
			if ( ( $last_word_date_xml != $this->last_word_date() ) )
			{
				$new_word = $this->fetch_todays_word();
				$this->save_set( $new_word, $words );
				return $new_word;
			}	
			
			$last_word = $this->extract_set( $word );
			return $last_word;
		}
		else
		{
			$set = $this->fetch_todays_word();
			$this->save_set( $set, $words );
			
			return $set;
		}
	
	}
	
	function last_word_date()
	{
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
		
		return $date;	
	}
	
	function is_holiday( $date )
	{
		global $owad_holidays;		
		return in_array( $date, $owad_holidays );
	}
	
	function extract_set( $word )
	{
		$attributes = $word->attributes();
		
		// in the first version of this widget the data in the xml file had white spaces.
		// that's why trim is used here
		$set = array(
		  "wordid" => "". trim( $attributes->wordid ),
		  "date" => "". trim ( $attributes->date ),
		  "todays_word" => "". trim( $attributes->content ),
		  "alternatives" => array( 
			"". trim( $word->alternative[0] ),
			"". trim( $word->alternative[1] ),
			"". trim( $word->alternative[2] )
			)
		  );
	
		return $set;
	}
	
	function save_set( $set, $words )
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
	
	function fetch_archive_words()
	{	//*
		if ( file_exists( OWAD_CACHE_FILE ) )
		{
			$sets = array();
			
			$words = simplexml_load_file( OWAD_CACHE_FILE );
			$counts = count( $words );
			
			if ( $counts >= 2)
			{
				for ( ; $counts > 0; $counts-- )
				{
					$word = $words->word[$counts-1];
					$att = $word->attributes();
					$sets[] = $this->extract_set( $word, $att );				
				}
			}
			
			return $sets;
		}
		//*/
	}
	
	function fetch_todays_word()
	{	
		$file = "http://owad.de/index.php4";
		$page = wp_remote_fopen($file);
	
		$pattern = "[[:print:]]+";
		
		preg_match( '/wordid=[0-9]{1,4}/', $page, $array );
		$wordid = str_replace( "wordid=", "", $array[0] );
		
		preg_match_all( '/<a href="check.php4[^>]+>'. $pattern .'<\/a>/', $page, $array );
		$alternatives = array( "", "", "");
		$alternatives = $array[0];
		
		for( $i=0; $i<3; $i++)
			$alternatives[$i] = strip_tags( $alternatives[$i] );
				
		preg_match( "/See today's word: [^<]+/", $page, $array );
		$todays_word = trim( str_replace( "See today's word:", "", $array[0] ) ); 
		
		$date = $this->last_word_date();
		
		$return_value = array(
			"wordid" => $wordid,
			"date" => $date,
			"todays_word" => $todays_word, 
			"alternatives" => $alternatives 
			);
			
		return $return_value;		
	}
	
	function get_word_by_id( $id )
	{
		$words = simplexml_load_file( OWAD_CACHE_FILE );
		$counts = count( $words );
		
		for ( $i=0; $i<$counts; $i++)
		{
			$word = $words->word[$i];
			$attributes = $word->attributes();
			if ( $id == $attributes[0] )
				return Owad::extract_set( $word, $attributes );
		}
		
		return NULL;
	}

	function get_word_by_date( $date )
	{
		$words = simplexml_load_file( OWAD_CACHE_FILE );
		$counts = count( $words );
		
		for ( $i=0; $i<$counts; $i++)
		{
			$word = $words->word[$i];
			
			if ( $date == $word->attributes()->date )
				return $this->extract_set( $word, $attributes );
		}
		
		return NULL;
	}
	
	function supported_by_host()
	{
		$modules = get_loaded_extensions();
		return in_array( "json" , $modules );
	}
	
	function print_word( $word = NULL )
	{

		if ( NULL == $word )
		{
			$word = $this->get_data();
		}
		
		extract( $word );
		
		$output = '
			<div>
			What does <strong><span id="owad_todays_word">'. $todays_word .'</span></strong> mean?
			
			<table>
			<tr><td valign="top">a)</td><td> <span id="owad_alt1"> <a href="http://owad.slopjong.de/'. str_replace( " ", "_", $todays_word ) .'_1'. $wordid .'.html" target="_blank">'. $alternatives[0] .'</a> </span> </td></tr>
			<tr><td valign="top">b)</td><td> <span id="owad_alt2"> <a href="http://owad.slopjong.de/'. str_replace( " ", "_", $todays_word ) .'_3'. $wordid .'.html" target="_blank">'. $alternatives[1] .'</a> </span> </td></tr>
			<tr><td valign="top">c)</td><td> <span id="owad_alt3"> <a href="http://owad.slopjong.de/'. str_replace( " ", "_", $todays_word ) .'_5'. $wordid .'.html" target="_blank">'. $alternatives[2] .'</a> </span> </td></tr>
			</table>
			</div>
			';

		return $output;
	}
	
	function print_archive_words()
	{
		$output = '';
		$sets = $this->fetch_archive_words();
	
		$counts = count( $sets );
		if ( $counts > 1 )
		{
			$output .= '

			Older words:
		
			<form id="owad_wordid">
			<select style="width:100%;" name="wordid" onchange="loadData();">
			';
			
			$words = array();
			for ( $i = 0; $i<$counts; $i++ )
			{
				// There's still a bug. Sometimes the words are cached more than once or an empty
				// entry is saved.
				if ( empty( $sets[$i]["wordid"] ) || in_array( $sets[$i]["wordid"], $words )  ) 
					continue;
					
				$words[] = $sets[$i]["wordid"];

				$output .=  '<option value="'. $sets[$i]["wordid"] .'">'. htmlentities( $sets[$i]["todays_word"] ) .'</option>';			
			}
			
				
			$output .= '</select>';
			$output .= '</form>';
		}
		
		return $output;
	}
	
	function header()
	{
		$this->javascript();
	}
	
	function javascript()
	{
		?>
		<script type="text/javascript">
		 function loadData()
		 {
			var dataToBeSent = $('#owad_wordid').serialize();
			
			$.getJSON("<?= constant('OWAD_URLPATH') ?>word2json.php", dataToBeSent, function(json){
				var todays_word = json.todays_word;
			
				$("#owad_todays_word")[0].innerHTML = json.todays_word;

				$("#owad_alt1")[0].innerHTML = '<a href="http://owad.slopjong.de/'+ escape( todays_word.replace( / /g, "_") ) +'_1' + json.wordid +'.html" target="_blank">'+ json.alternatives[0] +'</a>';
				$("#owad_alt2")[0].innerHTML = '<a href="http://owad.slopjong.de/'+ escape( todays_word.replace( / /g, "_") ) +'_3' + json.wordid +'.html" target="_blank">'+ json.alternatives[1] +'</a>';
				$("#owad_alt3")[0].innerHTML = '<a href="http://owad.slopjong.de/'+ escape( todays_word.replace( / /g, "_") ) +'_5' + json.wordid +'.html" target="_blank">'+ json.alternatives[2] +'</a>';

			});
			
		 }
	   </script>
		<?php
	}
	
	function no_support_text()
	{
		return 'If you can read this text this widget isn\'t supported by this blog\'s host!<br/> 
				<br/>Please leave a comment <a href="http://slopjong.de/2009/03/20/one-word-a-day/" 
				target="_blank">here</a> to help improve this widget.';
	}
	
}

?>