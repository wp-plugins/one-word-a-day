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