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
		global $owad;
		
		if ( $owad->supported_by_host() )
		{
			wp_register_sidebar_widget('owad', 'One Word A Day', array( &$this, 'frontend') );
			wp_register_widget_control('owad', 'One Word A Day', array( &$this, 'backend') );
		}
		else
		{
			wp_register_sidebar_widget('owad', 'One Word A Day', array( &$this, 'frontend_no_support') );
			wp_register_widget_control('owad', 'One Word A Day', array( &$this, 'backend_no_support') );
		}
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
			
			echo $owad->print_word();
			echo $owad->print_archive_words();			
				
			echo $after_widget;
		}
		
	}
	
	function backend()
	{

		
		global $owad_default_options;		
		$options = get_option('owad');
		$options = wp_parse_args( $options, $owad_default_options );		
		
		
		/******************************************************************************/
		
		if ( isset( $_POST["owad-save-widget"] ))
		{
			$set_to_default = true;
			foreach( $_POST["owad_post_category"]  as $el )
			{
				if ( !empty($el))
					$set_to_default = false;
			}
			
			if ( $set_to_default )
				$options["owad_post_category"] = $owad_default_options["owad_post_category"];
			else
				$options["owad_post_category"] = $_POST["owad_post_category"];
			
					
			$options["owad_daily_post"] = (bool) $_POST["owad_daily_post"];
			$options["owad_post_author"] = (int) $_POST["owad_post_author"];
					
			update_option( "owad", $options );
		}
		
		/******************************************************************************/			
		
		echo '
				<script type="text/javascript">
										
					jQuery(document).ready(function(){
						jQuery("input[name=owad_daily_post]").change(function () { 
							jQuery("#owad_post_settings").toggle("slow");
							});	
						});
				</script>
				
				<div style="float:left;">Create a daily post:</div>
				<div style="float:right; width:130px;">
				<input type="hidden" name="owad-save-widget" value"1" />
				<input type="radio" name="owad_daily_post" value="1"'. (($options['owad_daily_post']) ? 'checked="checked"' : '') .'/> yes <br/>
				<input type="radio" name="owad_daily_post" value="0"'. (($options['owad_daily_post']) ? '' : ' checked="checked"') .'/> no
				</div><br style="clear:both;"/>
			';
			
		/******************************************************************************/
		
		// The post settings block
		echo '<div id="owad_post_settings" style="';
		if( ! $options['owad_daily_post'] )	
			echo 'display:none;';
		echo '">';
		
		// The categories
		echo '<hr style="border: 0px; /* Für Firefox und Opera */ border-top: solid 1px #bbbbbb; border-bottom: transparent;" />
		<p> In which categories should be posted? <br/><div style="margin-left:20px;" >';
		
		$cats = get_categories( array( "hide_empty" => false ) );
		foreach( $cats as $cat)			
			echo '<input name="owad_post_category[]" type="checkbox"
			'. (( in_array( $cat->cat_ID, $options["owad_post_category"] )) ? 'checked="checked"' : "" ) 
			.' value="'. $cat->cat_ID .'"/>&nbsp;' . $cat->cat_name ."<br/>";
		echo "</div></p>";
		
		// Which user should be used?
		echo '<hr style="border: 0px; /* Für Firefox und Opera */ border-top: solid 1px #bbbbbb; border-bottom: transparent;"/>
		<p> Who should be the post author?<br/><div style="margin-left:20px;">';
		echo '<select id="owad_post_author" name="owad_post_author">';
		$users = get_users_of_blog();
		foreach( $users as $user)			
			echo '<option value="'. $user->user_id .'"'. (( $user->user_id == $options['owad_post_author']) ? 'selected="selected"' :"" )
			.'>' . $user->user_login .'</option>';
		echo '</select>';
		echo "</div></p>";
		
		echo "<hr/></div>";

	}
	
	function backend_no_support()
	{
		global $owad;
		echo $owad->no_support_text();
	}
	
	// This is just a dummy becuase a sidebar widget has to be registered.
	function frontend_no_support()
	{
	}
	
}

?>