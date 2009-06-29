<?php

/************************************
 If you're a developer stop here. 
 If you're a programmer go on. 
***********************************/

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
		global $wp_version;
		if( version_compare( $wp_version, "2.8", "<") )
		{
			if ( class_exists('Owad_Widget') )
				new Owad_Widget();
		}
		else
			add_action('widgets_init', array( &$this, 'widget_init') );

		if( $this->supported_by_host () )
		{
			add_shortcode( "owad", array( &$this, "shortcode_handler" ) );
	
			add_action( 'wp_head', array( &$this, 'enqueue_resources' ), 1);
			
			global $wp_did_header;
			if ( isset($wp_did_header) )
				add_action('init', array( &$this, 'post_todays_word') );
		}	

		add_filter('screen_layout_columns', array(&$this, 'on_screen_layout_columns'), 10, 2);
		add_action('admin_menu', array(&$this, 'on_admin_menu')); 
		add_action('admin_post_one-word-a-day', array(&$this, 'on_save_changes'));
	}
	
	/*****************************************************************************************/
	/*  ADMIN PAGES   ************************************************************************/
	/*****************************************************************************************/
	
	function get_options()
	{
		global $owad_default_options;		
		$options = get_option('owad');
		$options = wp_parse_args( $options, $owad_default_options );
		
		return $options;
	}
	
	function on_save_changes() 
	{		

		//user permission check
		if ( !current_user_can('manage_options') )
			wp_die( __('Cheatin&#8217; uh?') );			

		
		//cross check the given referer
		check_admin_referer('one-word-a-day');

			
		$options = $this->get_options();
		
		// Check if a category was selected. If not use the default category
		if ( ! isset($_POST['post_category']) )
			$options["owad_post_category"] = $owad_default_options["owad_post_category"];
		else
			$options["owad_post_category"] = $_POST["post_category"];
		
		if (  isset($_POST['owad_post_author']) )		
			$options["owad_post_author"] = (int) $_POST["owad_post_author"];
		
		if (  isset($_POST['owad_daily_post']) )		
			$options["owad_daily_post"] = (bool) $_POST["owad_daily_post"];
			
		if ( isset( $_POST['content'] ))
		{
		  	$options["comment_content"] = stripslashes($_POST['content']);
		  	
		  	// This is needed to avoid multpile comments with the same content
		  	if( !preg_match( "One Word A Day" , $options["comment_content"] ) )
		  		$options["comment_content"] .= "<!-- One Word A Day -->";	
		}
		 	
		update_option( "owad", $options );		

		//lets redirect the post request into get request (you may add additional params at the url, if you need to show save results
		wp_redirect($_POST['_wp_http_referer']);		

	}
	
	function on_screen_layout_columns($columns, $screen) 
	{

		if ($screen == $this->pagehook)
			$columns[$this->pagehook] = 2;			

		return $columns;
	}

	function on_admin_menu() 
	{
		// If the page hook gets changed, don't forget to change the link to this admin page too in the widget form

		$this->pagehook = add_options_page('One Word A Day', "One Word A Day", 'manage_options', 'one_word_a_day', array(&$this, 'on_show_page'));
		$this->pagehook_tools = add_management_page('One Word A Day', "One Word A Day", 'manage_options', 'one_word_a_day', array(&$this, 'on_show_tools'));
		
		//register callback gets call prior your own page gets rendered

		add_action( 'load-'. $this->pagehook, array( &$this, 'on_load_page') );
		add_action( 'admin_print_scripts-'. $this->pagehook, array( &$this, 'my_plugin_init') );
	}

	// Returns an array with the defect entries
	function get_defect_entries()
	{
		$words = simplexml_load_file( OWAD_CACHE_FILE );
		$words = $this->object_to_array( $words );
		
		$defects = array();
		foreach( $words["word"] as $item )
		{				
			$word = $item["@attributes"]["content"];
			$word_id = $item["@attributes"]["wordid"];
			$alternative = $item["alternative"];

			if( empty( $word ) ||
				empty( $alternative[0] ) ||
				empty( $alternative[1] ) ||
				empty( $alternative[2] ) )
				$defects["word"][] = $item;
		}		
		
		return $defects;
	}
	
	// Returns an array with the all the entries
	function get_all_entries()
	{
		$words = simplexml_load_file( OWAD_CACHE_FILE );
		return $words = $this->object_to_array( $words );
	}

	function get_defect_entries_ids( $entries )
	{
		$defects = array();
		foreach( $entries["word"] as $entry )
			$defects[] = $entry["@attributes"]["wordid"];
			
		return $defects;
	}
		
	function is_entry_defect( $word )
	{		
		if( empty( $word["@attributes"]["wordid"] ) ||
			empty( $word["@attributes"]["content"] ) ||
			empty( $word["alternative"][0] ) ||
			empty( $word["alternative"][1] ) ||
			empty( $word["alternative"][2] ) 
			) return true;
			
		return false;	
	}
	
	function array_change_key_name( $orig, $new, &$array )
	{
		foreach ( $array as $k => $v )
			$return[ ( $k === $orig ) ? $new : $k ] = $v;
		return ( array ) $return;
	}

	function repair_word( $id )
	{	
		if( $id )
		{	
			$result[0] = true;
			$fetched_word = $this->fetch_single_word( "http://owad.de/owad-archive-quiz.php4?id=$id" );
			//$word = $this->array_change_key_name( 'alternatives', 'alternative', $fetched_word );
			//krumo( $word );
			
			$repaired_word = array();
			$repaired_word["@attributes"]["wordid"] = mb_convert_encoding( $fetched_word["wordid"], "UTF-8" );
			$repaired_word["@attributes"]["date"] = mb_convert_encoding( $fetched_word["date"], "UTF-8" );
			$repaired_word["@attributes"]["content"] = mb_convert_encoding( $fetched_word["todays_word"], "UTF-8" );
			
			for( $i=0; $i<3; $i++ )
				$repaired_word["alternative"][$i] = mb_convert_encoding( $fetched_word["alternatives"][$i], "UTF-8" );
				
			$result[1] = $repaired_word;
		}
		else
		{
			$result[0] = false;
			$result[1] = null;
		}
		
		return $result;
	}
	
	function action_repair_defects()
	{		
		$entries = $this->get_all_entries();
		
		foreach( $entries["word"] as $key => $entry )
		{
			if( $this->is_entry_defect( $entry ) )
			{
				$word = $this->repair_word( $entry["@attributes"]["wordid"] );
				//krumo( $entry );
				//krumo( $word[1] );
				if( $word[0] )
					$entries["word"][$key] = $word[1];
				
			}
		}
		
		$entries = $this->array_to_xml( $entries );
		file_put_contents( OWAD_CACHE_FILE, $entries->asXML() );
	}
	
	function action_delete_defects()
	{
		$defects = $this->get_defect_entries();
		$defects = $this->get_defect_entries_ids( $defects );
		
		$entries = simplexml_load_file( OWAD_CACHE_FILE );
		$entries = $this->object_to_array( $entries );
		
		$good_entries = array();
		
		foreach( $entries["word"] as $entry )
		{
			if( in_array( $entry["@attributes"]["wordid"], $defects ) )
				continue;
				
			$good_entries["word"][] = $entry;
		}
		
		$good_entries = $this->array_to_xml( $good_entries );
		file_put_contents( OWAD_CACHE_FILE, $good_entries->asXML() );	
	}
	
	function action_delete_duplicates()
	{
		$words = simplexml_load_file( OWAD_CACHE_FILE );
		$words = (array) $words;
		$words = $this->object_to_array( $words );
		$words = $words["word"];
		
		// The new sets to be stored
		$no_duplicates = array();
		$already_handled = array();
		
		foreach( $words as $word )
		{
			$word_id = $word["@attributes"]["wordid"];
			if ( !in_array( $word_id, $already_handled ) )
				$already_handled[] = $word_id;
			else
				continue;
			
			$no_duplicates["word"][] = $word;
		}
		
		$words = $this->array_to_xml( $no_duplicates );
		file_put_contents( OWAD_CACHE_FILE, $words->asXML() );
	}
	
	function array_to_xml( $arr )
	{
		$obj = simplexml_load_string("<?xml version='1.0' encoding='utf-8'?><words />");
		
		// key = 'word', val = word container
		foreach( $arr as $key_word => $val)
		{			
			// key = numeric, val = container with attributes and alternative
			foreach( $val as $key => $val)
			{
				$word = $obj->addChild( $key_word );
			
				//krumo( $val["@attributes"]);
				foreach( $val["@attributes"] as $key => $val_att )
					$word->addAttribute( $key, trim($val_att) );
					
				foreach( $val["alternative"] as $key => $val_alt )
					$word->addChild( 'alternative', trim($val_alt) );
			}
		}
		
		return $obj;
	}
	
	function object_to_array( $obj )
	{
		$_arr = is_object($obj) ? get_object_vars($obj) : $obj;
		  
		foreach ($_arr as $key => $val) 
		{
		  	$val = (is_array($val) || is_object($val)) ? $this->object_to_array($val) : $val;
			$arr[$key] = $val;
		}
    return $arr; 
	}
	
	
	function on_show_tools()	
	{			
		if( isset( $_GET["action"] ) )
		{
			// TODO: filter the variable!
			$action =  $_GET["action"];
			switch( $action )
			{
				case 'repair':
					$this->action_repair_defects();
					break;
					
				case 'delete':
					if( isset( $_GET["object"] ) )
						$object = $_GET["object"];
					else
						break;
					
					switch( $object )
					{
						case 'duplicates':
							$this->action_delete_duplicates();
							break;
						case 'defects':
							$this->action_delete_defects();
							break;
					}
					
					break;
			}
		}
	
		?>
		<div id="one-word-a-day" class="wrap">

		<?php screen_icon('options-general'); ?>

		<h2>One Word A Day</h2>
		<h3>Cache Content</h3>
		
		
		<ul id="owad-cache-menu">
			<li> <a id="owad_show_defects" href="#">Show only defects</a> </li>
			<li> <a id="owad_show_all" href="#">Show all entries</a> </li>
			<li> <a id="owad_repair_defects" href="tools.php?page=one_word_a_day&action=repair"><span style="text-decoration: line-through;">Try to</span> Repair defects</a> </li>
			<li> <a id="owad_delete_defects" href="tools.php?page=one_word_a_day&action=delete&object=defects">Delete defects</a> </li>
			<li> <a id="owad_delete_duplicates" href="tools.php?page=one_word_a_day&action=delete&object=duplicates">Delete duplicates</a> </li>
		</ul>
		<br/>
		
		<table class="widefat fixed" cellspacing="0">
			<thead><tr><th width="200">Word</th><th>Choices</th></tr></thead>
			<tbody><?php
			
				$words = (array) simplexml_load_file( OWAD_CACHE_FILE );
				$words = (array) $words["word"];
				$words = array_reverse( $words );
				
				//echo "<pre>". print_r( $words, true ) ."</pre>";
				$defects = array();
					foreach( $words as $item )
					{
						$word = $item["content"];
						$word_id = $item["wordid"];
						$alternative = $item->alternative;
						
						if( empty( $word ) ||
							empty( $alternative[0] ) ||
							empty( $alternative[1] ) ||
							empty( $alternative[2] ) )
							$defects[] = $word_id; 
						
						echo '<tr id="'. $word_id .'" class="'. (in_array( $word_id, $defects ) ? 'owad_defect' : 'owad_nodefect') .'"><td>'. $word.'</td><td><ul>';
						
						$abc = array( 'a', 'b', 'c' );
						for( $i=0; $i<3; $i++)
							echo '<li>'. $abc[$i] .')&nbsp;&nbsp;&nbsp;'. $alternative[$i] .'</li>';
						
						echo '</ul></td></tr>';
					}
			?></tbody>
		</table>
		
		<script type="text/javascript">
			jQuery(document).ready( function(){
				jQuery('table tr:odd').css("background-color", "#F9F9F9");

				jQuery('#owad_show_defects').bind( "click", function(e){
					jQuery('.owad_nodefect').hide();
					jQuery('tr:visible:odd').css("background-color", "#F9F9F9");
				});
				
				jQuery('#owad_show_all').bind( "click", function(e){
					jQuery('.owad_nodefect').show();
					jQuery('tr:visible:odd').css("background-color", "#F9F9F9");
				});
				
				jQuery('#owad-cache-menu li').css( {
					'display': 'inline',
					'list-style-type': 'none',
					'text-decoration' : 'none',
					'background-color' : '#',
					'margin-right' : '7px'
					});
					
				jQuery('#owad-cache-menu a').css( {
					'text-decoration' : 'none'
					});
				
				/*
				// ask if really delete the entries
				jQuery('#owad_delete_defects').click( function(e){
					//alert("");
					jQuery.post( 'tools.php' );
					});
				//*/
			});
		</script>

		</div>
		<?php
	}
	
	
	function on_show_page() 
	{
		//we need the global screen column value to beable to have a sidebar in WordPress 2.8
		global $screen_layout_columns;
		?>
		

		<div id="one-word-a-day" class="wrap">
		<?php screen_icon('options-general'); ?>
		<h2>One Word A Day</h2>

		<form action="admin-post.php" method="post">
			<?php 
				// this nonce field is used for the referer check
				wp_nonce_field('one-word-a-day');

				wp_nonce_field('closedpostboxes', 'closedpostboxesnonce', false );
				wp_nonce_field('meta-box-order', 'meta-box-order-nonce', false );
			?>		

			<input type="hidden" name="action" value="one-word-a-day" />

			<div id="poststuff" class="metabox-holder<?php echo 2 == $screen_layout_columns ? ' has-right-sidebar' : ''; ?>">

				<div id="side-info-column" class="inner-sidebar">

					<?php do_meta_boxes($this->pagehook, 'side', $data); ?>

				</div>

				<div id="post-body" class="has-sidebar">

					<div id="post-body-content" class="has-sidebar-content">
						

						<?php 
							do_meta_boxes($this->pagehook, 'normal', null); 
						?>
						<p>

							<input type="submit" value="Save Changes" class="button-primary" name="Submit"/>	

						</p>

					</div>

				</div>
												

			</div>	

		</form>

		</div>
		

		<script type="text/javascript">
			//<![CDATA[
			jQuery(document).ready( function($) {
				// close postboxes that should be closed
				$('.if-js-closed').removeClass('if-js-closed').addClass('closed');
				// postboxes setup
				postboxes.add_postbox_toggles('<?php echo $this->pagehook; ?>');
			});
			//]]>
		</script>

		<?php

	}
	
	function my_plugin_init()
	{
		wp_enqueue_script('post');
		if ( user_can_richedit() )
			wp_enqueue_script('editor');
		
		add_thickbox();
		wp_enqueue_script('media-upload');
	}
	
	//will be executed if wordpress core detects this page has to be rendered
	function on_load_page() 
	{
		//ensure, that the needed javascripts been loaded to allow drag/drop, expand/collapse and hide/show of boxes
		wp_enqueue_script('common');
		wp_enqueue_script('wp-lists');
		wp_enqueue_script('postbox');

		add_meta_box('categorydiv', __('Categories'), array( &$this, 'on_post_categories_meta_box'), $this->pagehook, 'side', 'core');

		add_meta_box('post-author-div', __('Post author'), array( &$this, 'on_post_author_meta_box'), $this->pagehook, 'side', 'core');
		add_meta_box('owad-post-generator', 'Auto post generator', array( &$this, 'on_activate_generator_meta_box'), $this->pagehook, 'normal', 'core'); 
		add_meta_box('owad-comment-text', 'Comment text - <a href="http://slopjong.de" target="_blank">Slopjong</a> would be glad if you left him a reference :-)', array( &$this, 'on_comment_text_meta_box'), $this->pagehook, 'normal', 'core'); 

	}
	
	function on_post_categories_meta_box() 
	{
		$options = $this->get_options();
		?>
		<ul id="category-tabs">
			<li class="tabs"><a href="#categories-all" tabindex="3"><?php _e( 'All Categories' ); ?></a></li>
			<li class="hide-if-no-js"><a href="#categories-pop" tabindex="3"><?php _e( 'Most Used' ); ?></a></li>
		</ul>
		
		<div id="categories-pop" class="tabs-panel" style="display: none;">
			<ul id="categorychecklist-pop" class="categorychecklist form-no-clear" >
		<?php $popular_ids = wp_popular_terms_checklist('category'); ?>
			</ul>
		</div>
		
		<div id="categories-all" class="tabs-panel">
			<ul id="categorychecklist" class="list:category categorychecklist form-no-clear">
		<?php wp_category_checklist(0, false, $options['owad_post_category'], $popular_ids ) ?>
			</ul>
		</div>
		
		<?php if ( current_user_can('manage_categories') ) : ?>
		<div id="category-adder" class="wp-hidden-children">
			<h4><a id="category-add-toggle" href="#category-add" class="hide-if-no-js" tabindex="3"><?php _e( '+ Add New Category' ); ?></a></h4>
			<p id="category-add" class="wp-hidden-child">
			<label class="screen-reader-text" for="newcat"><?php _e( 'Add New Category' ); ?></label><input type="text" name="newcat" id="newcat" class="form-required form-input-tip" value="<?php esc_attr_e( 'New category name' ); ?>" tabindex="3" aria-required="true"/>
			<label class="screen-reader-text" for="newcat_parent"><?php _e('Parent category'); ?>:</label><?php wp_dropdown_categories( array( 'hide_empty' => 0, 'name' => 'newcat_parent', 'orderby' => 'name', 'hierarchical' => 1, 'show_option_none' => __('Parent category'), 'tab_index' => 3 ) ); ?>
			<input type="button" id="category-add-sumbit" class="add:categorychecklist:category-add button" value="<?php esc_attr_e( 'Add' ); ?>" tabindex="3" />
		<?php	wp_nonce_field( 'add-category', '_ajax_nonce', false ); ?>
			<span id="category-ajax-response"></span></p>
		</div>
		<?php
		endif;
	
	}
	
	function on_post_author_meta_box()
	{
		$options = $this->get_options();

		echo '<select id="owad_post_author" class="widefat" name="owad_post_author">';
		$users = get_users_of_blog();
		foreach( $users as $user)			
			echo '<option value="'. $user->user_id .'"'. (( $user->user_id == $options['owad_post_author']) ? 'selected="selected"' :"" )
			.'>' . $user->user_login .'</option>';
		echo '</select>';	
	}
	
	function on_comment_text_meta_box()
	{
		$options = $this->get_options();
		if( ! isset($options['comment_content']) || empty($options['comment_content']) )
			$options['comment_content'] = 'Learning English with the WordPress plugin <em>One Word A Day</em>. It displays a new English word in the sidebar every day. Furthermore a quiz is included. <a href="http://slopjong.de/2009/03/20/one-word-a-day/?KeepThis=true&TB_iframe=true&height=540&width=800" class="thickbox" target="_blank">Download it</a> from Slopjong\'s blog.';
		?>							
		<div id="<?php echo user_can_richedit() ? 'postdivrich' : 'postdiv'; ?>" class="postarea">
			<?php 
				// if the id is changed the size of the textarea isn't resized correctly
				the_editor( $options['comment_content'] );//, $id = 'content', $prev_id = 'title', $media_buttons = true, $tab_index = 2); 
			?>
		</div>
			

		<?php
			add_filter( 'teeny_mce_before_init', create_function( '$a', '$a["height"] = "400"; $a["onpageload"] = ""; $a["mode"] = "textareas"; $a["editor_selector"] = "theEditor"; return $a;' ) );
			wp_tiny_mce( true );
	}
	
	function on_activate_generator_meta_box()
	{
		$options = $this->get_options();
		echo '
			<script type="text/javascript">
									
				jQuery(document).ready(function(){
					// adapt the id to the metaboxes
					jQuery("input[name=owad_daily_post]").change(function () { 
						jQuery("#owad_post_settings").toggle("slow");
						});	
					});
					
			</script>
			
			<div style="width:300px;">
				<div style="float:left;">Do you wish a daily post?</div>
				<div style="float:right; width:130px;">
					<input type="radio" name="owad_daily_post" value="1"'. (($options['owad_daily_post']) ? 'checked="checked"' : '') .'/> yes <br/>
					<input type="radio" name="owad_daily_post" value="0"'. (($options['owad_daily_post']) ? '' : ' checked="checked"') .'/> no
				</div>
			</div>
			
			<br style="clear:both;"/>
		';
	}
	
	/*****************************************************************************************/	
	
	function widget_init()
	{
		register_widget('Owad_Widget');
	}
	
	// Load javascript scripts and styles
	function enqueue_resources()
	{
		// scripts
		wp_enqueue_script( 'owad', '/'. PLUGINDIR .'/'. OWAD_FOLDER .'/js/js.php', array('jquery','thickbox') );
		wp_enqueue_script( 'owad-audio-player', '/'. PLUGINDIR .'/'. OWAD_FOLDER .'/audio-player/audio-player.js' );
		//wp_enqueue_script( 'owad-audio-player', '/'. PLUGINDIR .'/'. OWAD_FOLDER .'/audio-player/audio-player-uncompressed.js' );		
		
		// styles
		wp_enqueue_style( 'thickbox' );
	}
	
	// Does what the function name already says
	function shortcode_handler( $atts )
	{
		global $post;
		
		if ( ! $this->supported_by_host() )
			return $this->no_support_text();
			
		if ( isset( $atts["date"] ) )
		{		
			// the tags have to be stripped because the blog could get hacked throuch mail posting
			// controlled by a hacker or whatever.
			$date = strip_tags( $atts["date"] );
			
			if ( $date == "post_date" )
				$date = $post->post_date;
			
			$fields = get_post_custom_values( '_owad_hide_question', $post->ID );
			if( count($fields) == 0 )
				$fields = get_post_custom_values( 'owad_hide_question', $post->ID );

			if( count($fields) != 0 )
				$hide_question = $fields[0];
			else
				$hide_question = false;
			
			if ( preg_match( "/[\d]{4,4}-[\d]{2,2}-[\d]{2,2}/", $date, $date ) )
			{
				$this->post_comment( $post->ID );
				$word = $this->get_word_by_date( $date[0] );
				return $this->print_word( $word, $hide_question );
			}
		}
		else
		{
			$output .= $this->print_word();
			$this->post_comment( $post->ID );
			return $output;
		}	
	}

	// Load either today's word from the cache or from the server and cache it if not done yet.
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
			$last_word_id_xml = $word->attributes()->wordid;
			
			$new_word = $this->fetch_todays_word();
			krumo( $new_word );
			// compare the file's last word date with the last word date
			//if ( ( $last_word_date_xml !=  $this->last_word_date() ) )
			if ( false ) // ( $last_word_date_xml !=  $this->last_word_date() ) )
			//if( $last_word_id_xml != $online_word["wordid"] )
			{
				//$new_word = $this->fetch_todays_word();
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
	
	// Load the date when the last word was published
	function fetch_word_date( $word = '' )
	{
		/*

		//*/
		
		if( !empty( $word ))
		{
			$first_char = strtoupper( substr( $word, 0, 1 ) );
			$page = wp_remote_fopen( "http://owad.de/owad-archive.php4?char=". $first_char );
			$page = str_replace( "\n", "", $page );
			preg_match( "/<b>". trim($word) ."<\/b> lernen\s+\((\d{4}\-\d{2}\-\d{2})\)\s+<br>/", $page, $array );
			$date = $array[1];
		}
		else
		{
			// Pseudo date
			$date = "1970-01-01";
			
			/*
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
			*/
		}
		
		return $date;	
	}
	
	// Checks if the passed date argument is a holiday
	function is_holiday( $date )
	{
		global $owad_holidays;		
		return in_array( $date, $owad_holidays );
	}
	
	// Convert the passed xml word object into an associative array
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
	
	// Cache the word.
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
	
	// Retrieve the cached words
	function fetch_archive_words()
	{	// *
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
		$this->fetch_single_word( "http://owad.de/index_en.php4" );
	}
	
	// Parses the service page to fetch the desired data for this plugin.
	function fetch_single_word( $url, $id = '')
	{	
		$page = wp_remote_fopen( $url.$id );
	
		$pattern = "[[:print:]]+";
		
		preg_match( '/wordid=[0-9]{1,4}/', $page, $array );
		$wordid = str_replace( "wordid=", "", $array[0] );
		
		// sometimes there are white spaces and a new line at the end of the answers
		preg_match_all( '/<a href="check.php4[^>]+>'. $pattern .'.*?[\n]?<\/a>/', $page, $array );
		$alternatives = array( "", "", "");
		$alternatives = $array[0];
		
		for( $i=0; $i<3; $i++)
		{			
			// remove html tags
			$alternatives[$i] = strip_tags( $alternatives[$i] );
			// remove white spaces
			$alternatives[$i] = trim( $alternatives[$i] );
			// replace ’ by ' ( this does not work )
			//$alternatives[$i] = preg_replace( "/’", "'", $alternatives[$i] );
			// convert into UTF8
			//$alternatives[$i] = mb_convert_encoding( $alternatives[$i], "UTF-8", 'ASCII' );
		}
				
		if( preg_match( "/See today's word: [^<]+/", $page, $array ) )
			$todays_word = trim( str_replace( "See today's word:", "", $array[0] ) ); 
		elseif ( preg_match( '/<p align="center" class="word"><br>[^<]+/', $page, $array ) )
			$todays_word = trim( strip_tags( $array[0] ) );
		else
			$todays_word = "";
			
		//$date = $this->last_word_date();
		$date = $this->fetch_word_date( $todays_word );
		
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
		$support = true;
		
		$modules = get_loaded_extensions();
		if ( ! in_array( "json" , $modules ) )
			$support = false;
			
		if ( ! preg_match('/^5/', PHP_VERSION ) )
			$support = false;
		
		return $support;
	}
	
	// Outputs the question 
	function print_word( $word = NULL, $hide_question = false, $widget_id = '' )
	{
		if ( is_null( $word ) )
			$word = $this->get_data();
		
		extract( $word );
		
		$output .= '<div>';
		
		if ( ! $hide_question )
		{
			$question_text = __( 'What does [x] mean', 'owad' );
			$question_text = str_replace( '?', '', $question_text );
			$question_text = str_replace( '[x]', '#', $question_text );
			$question_text = split( '#', $question_text );
			$output .= trim( $question_text[0] );
			$output .= ' <strong><span id="owad_todays_word_'. $widget_id .'">'. $todays_word .'</span></strong> ';
			$output .= trim( $question_text[1] ) .'?';
		}
			
		// If today's word is empty (due to a change of the service web page owad.de) it is set to a dummy text. This is required by the popup.
		if( empty($todays_word) )
			$todays_word = "todays_word";
			
		$player_dir = get_bloginfo('url') .'/'. PLUGINDIR .'/'. OWAD_FOLDER .'/audio-player/player.swf';		
		$sound_file = "What does $todays_word mean.mp3";
		$sound_file = str_replace( ' ', '_', $sound_file );
		$sound_file = str_replace( '"', '', $sound_file );
		$sound_file = str_replace( "'", '', $sound_file );
		$sound_url = "http://slopjong.de/$sound_file";
		
		$output .= '
			
			<table>
			<tr><td valign="top">a)</td><td> <a id="owad_alt1_'. $widget_id .'" href="http://owad.slopjong.de/'. urlencode( str_replace( " ", "_", $todays_word )) .'_1'. $wordid .'.html?KeepThis=true&TB_iframe=true&height=540&width=800" class="thickbox">'. $alternatives[0] .'</a> </td></tr>
			<tr><td valign="top">b)</td><td> <a id="owad_alt2_'. $widget_id .'" href="http://owad.slopjong.de/'. urlencode( str_replace( " ", "_", $todays_word )) .'_3'. $wordid .'.html?KeepThis=true&TB_iframe=true&height=540&width=800" class="thickbox">'. $alternatives[1] .'</a> </td></tr>
			<tr><td valign="top">c)</td><td> <a id="owad_alt3_'. $widget_id .'" href="http://owad.slopjong.de/'. urlencode( str_replace( " ", "_", $todays_word )) .'_5'. $wordid .'.html?KeepThis=true&TB_iframe=true&height=540&width=800" class="thickbox">'. $alternatives[2] .'</a> </td></tr>
			</table>
			</div>
			
			<object type="application/x-shockwave-flash" data="'. $player_dir .'" id="audioplayer-'. $wordid . $widget_id .'" height="24" width="190">
			<param name="movie" value="'. $player_dir .'">
			<param name="FlashVars" value="playerID=1&amp;titles='. urlencode( $todays_word ) .'&amp;animation=yes&amp;soundFile='. $sound_url .'">
			<param name="quality" value="high">
			<param name="menu" value="false">
			<param name="wmode" value="transparent">
			</object>

			<!--
			<p id="owad-sound'. $word_id . $widget_id .'">Listen</p>
			
			<script type="text/javascript">
			//AudioPlayer.embed("owad-sound'. $wordid . $widget_id .'", {soundFile: "'. $sound_url .'"} );
			</script>
			-->
			';

		return $output;
	}
	
	// Outputs the select box with previous words
	function print_archive_words( $widget_id = '' )
	{
		$output = '';
		$sets = $this->fetch_archive_words();
	
		$counts = count( $sets );
		if ( $counts > 1 )
		{
			$output .= __( 'Other words', 'owad' );
			
			$output .='
				<form id="owad_wordid_'. $widget_id .'">
				<select style="width:100%;" name="wordid" onchange="loadData('. $widget_id .');">
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
	
	function no_support_text()
	{
		return 'If you can read this text this widget isn\'t supported by this blog\'s host!<br/> 
				<br/>Please leave a comment <a href="http://slopjong.de/2009/03/20/one-word-a-day/" 
				target="_blank">here</a> to help improve this widget.';
	}
	
	// Create the daily post
	function post_todays_word()
	{
		
		if ( ! is_admin() )
		{
			global $owad_default_options;
			$options = get_option('owad');
			$options = wp_parse_args( $options, $owad_default_options );
			if ( empty( $options["owad_post_category"] ))
				$options["owad_post_category"] = $owad_default_options["owad_post_category"];
			
			$word = $this->get_data();
		
			// check if the word was posted
			if ( $word["wordid"] <= $options["owad_last_word_posted"] )
				return;
				
			$options["owad_last_word_posted"] = $word["wordid"];
			update_option('owad', $options );
			
			// post today's word
			$post_id = wp_insert_post(array(
				'post_title'     => 'What does "'. $word["todays_word"] .'" mean?',
				'post_content'   => '[owad date="'. $word["date"] .'"]',
				'post_status'    => 'publish', // changing it to draft may cause problems
				'post_type'      => 'post', // it's not a page ;-)
				'post_author'    => $options['owad_post_author'],
				'post_category'  => $options['owad_post_category']
				));
			
			if( $post_id )
			{
				add_post_meta( $post_id , '_owad', "One Word A Day");
				add_post_meta( $post_id , '_owad_hide_question', "true");
			}
		}
	}
	
	function post_comment( $post_id )
	{
		$options = $this->get_options();
		
		if( empty($options['comment_content']) )
			$options['comment_content'] = 'Learning English with the WordPress plugin <em>One Word A Day</em>. It displays a new English word in the sidebar every day. Furthermore a quiz is included. <a href="http://slopjong.de/2009/03/20/one-word-a-day/?KeepThis=true&TB_iframe=true&height=540&width=800" class="thickbox" target="_blank">Download it</a> from Slopjong\'s blog.';

		
		$comments = get_comments('post_id='. $post_id );
		$no_comment = true;
		foreach($comments as $comment)
		{
			if ( preg_match( '/One Word A Day/', $comment->comment_content ) && preg_match( '/Romain Schmitz/', $comment->comment_author ) )
			{	
				$no_comment = false;		
			}
		}
		
		// Add a comment
		// I'd be glad if you wouldn't remove this. Consider that yo got this plugin for
		// free. Give other people the chance to get the plugin as well ;-)
		if ( $no_comment )
		{
			$comment_data = array(
				'comment_author'        => "Romain Schmitz",
				//'comment_title'         => 'Learning English with the WordPress plugin <em>One Word A Day</em>',
				'comment_author_url'    => 'http://slopjong.de',
				'comment_author_email'  => '',
				'comment_content'       => $options['comment_content'], //'Learning English with the WordPress plugin <em>One Word A Day</em>. It displays a new English word in the sidebar every day. Furthermore a quiz is included. <a href="http://slopjong.de/2009/03/20/one-word-a-day/?KeepThis=true&TB_iframe=true&height=540&width=800" class="thickbox" target="_blank">Download it</a> from Slopjong\'s blog.',
				//'comment_content'       => '[...] displays a new English word in the sidebar every day. Furthermore a quiz is included [...]',
				'comment_type'          => 'comment',
				'comment_agent'         => 'The Incutio XML-RPC PHP Library -- WordPress/2.7.1',
				'comment_post_ID'       => $post_id 
				);
				
			wp_insert_comment( $comment_data );
		}
	}	
}

?>