<?php

class Owad_Widget
{

	function register_widget()
	{
		wp_register_sidebar_widget('owad', 'One Word A Day', array( &$this, 'frontend') );
		wp_register_widget_control('owad', 'One Word A Day', array( &$this, 'backend') );
	}
	
	
	function frontend( $args )
	{		
		global $owad;
		extract( $args );

		echo $before_widget;
		echo $before_title .'One Word A Day'. $after_title;
	
		if ( $owad->supported_by_host() )
		{
		
			echo $owad->print_word();
			echo $owad->print_archive_words();			

		}
		else
			echo $owad->no_support_text();
		
		?>
		
		<!-- I'd be glad if you wouldn't remove this :-D -->
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
		echo $after_widget;
		
		
		
	} // end widget
	
	function backend()
	{	
		global $owad;
		
		if ( ! $owad->supported_by_host() )
			echo $owad->no_support_text();
			
	} // end widget

	
	/**
	* PHP 4 Compatible Constructor
	*/
	function Owad_Widget()
	{
		$this->__construct();
	}
	
	/**
	* PHP 5 Constructor
	*/		
	function __construct()
	{  		
		add_action('widgets_init', array(&$this, 'register_widget'));
	}
}

?>