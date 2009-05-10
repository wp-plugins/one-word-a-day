<?php

class Owad_Widget
{
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

	function register_widget()
	{
		wp_register_sidebar_widget('owad', 'One Word A Day', array( &$this, 'frontend') );
		wp_register_widget_control('owad', 'One Word A Day', array( &$this, 'backend') );
	}
	
	
	function frontend( $args )
	{		
		// there is an issue. this function is executed in the backend as well
		if ( ! is_admin() )
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
		}
		
	} // end widget
	
	function backend(  )
	{	
		global $owad;
		
		if ( ! $owad->supported_by_host() )
			echo $owad->no_support_text();
		else
		{			
			global $owad_default_options;
			
			$options = get_option('owad');
			$options = wp_parse_args( $options, $owad_default_options );		
			
				
			if ( isset( $_POST["owad_submit"] ) )
			{
				if ( $_POST["owad_daily_post"] )
					$options['owad_daily_post'] = true;
				else
					$options['owad_daily_post'] = false;
					
				update_option('owad', $options);
			}
			
			echo '<input type="hidden" id="owad_submit" name="owad_submit" value="1" />';				
			echo '
				<p>
				<input type="checkbox" name="owad_daily_post" value="true"';
			
			if ( $options['owad_daily_post'] )
				echo "checked";
				
			echo '> Create a daily post
			
				</p>
				
				<!--
				Create a daily post <br/>
				<input type="radio" name="owad_daily_post" value="yes" /> yes 
				<input type="radio" name="owad_daily_post" value="no" /> no
				-->
			';
		}
			
	} // end widget

}

?>