<?php

class Owad_Widget extends WP_Widget
{
	// PHP 4 Compatible Constructor
	function Owad_Widget()
	{
		// This leads to an error
		//$this->__construct();
		$this->WP_Widget( 'owad', 'One Word A Day' );
	}
	
	// PHP 5 Constructor	
	/*
	function __construct()
	{  		
		$this->WP_Widget( 'owad', 'One Word A Day' );
	}
	//*/
	
	function widget( $args, $instance )
	{		
		global $owad;
		extract( $args );

		$id = $this->get_field_id('');
		if ( preg_match( '/\d+/', $id, $res ) )
			$id = $res[0];
		else
			$id = '';
		$title = esc_attr($instance['title']);
		
		echo $before_widget;
		echo $before_title . $title . $after_title;
		
		echo $owad->print_word( NULL, false, $id );
		echo $owad->print_archive_words( $id);			
			
		echo $after_widget;		
	}
	
	
	function form( $instance )
	{
		global $wpdb;
		$instance = wp_parse_args((array) $instance, array('title' => 'One Word A Day') );
		$title = esc_attr($instance['title']);
		?>
		<p>
			<label for="<?php echo $this->get_field_id('title'); ?>"> <?php _e('Title:', 'owad'); ?>
			<input class="widefat" id="<?= $this->get_field_id('title') ?>" name="<?= $this->get_field_name('title') ?>" type="text" value="<?= $title; ?>" />
			</label>
		</p>
		
		<p>
			Go to the <a href="<?= get_bloginfo('url'); ?>/wp-admin/options-general.php?page=one_word_a_day">options page</a> for more settings.
		</p>
		
		<input type="hidden" id="<?= $this->get_field_id('submit') ?>" name="<?= $this->get_field_name('submit') ?>" value="1" />
	<?php
	}
	
	function update( $new_instance, $old_instance )
	{
		if (!isset($new_instance['submit']))
			return false;
			
		$instance = $old_instance;
		$instance['title'] = strip_tags($new_instance['title']);
		return $instance;
	}	
}

?>