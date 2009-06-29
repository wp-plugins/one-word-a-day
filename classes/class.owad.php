<?php

/************************************
 If you're a developer stop here. 
***********************************/

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
			//add_shortcode( "owad", array( &$this, "shortcode_handler" ) );
	
			//add_action( 'wp_head', array( &$this, 'enqueue_resources' ), 1);
			
			/*
			global $wp_did_header;
			if ( isset($wp_did_header) )
				add_action('init', array( &$this, 'post_todays_word') );
			*/
		}	

		/*
		add_filter('screen_layout_columns', array(&$this, 'on_screen_layout_columns'), 10, 2);
		add_action('admin_menu', array(&$this, 'on_admin_menu')); 
		add_action('admin_post_one-word-a-day', array(&$this, 'on_save_changes'));
		*/
	}
	
	/**
	 * Load the options. If not found in the database the default settings are loaded.
	 * @return array the options
	 */
	function get_options()
	{
		global $owad_default_options;		
		$options = get_option('owad');
		$options = wp_parse_args( $options, $owad_default_options );
		
		return $options;
	}
	
	/**
	 * Updates the options sent by the form of the admin page 'Settings->One Word A Day'.
	 */
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
	
	/**
	 *
	 */
	function on_screen_layout_columns($columns, $screen) 
	{

		if ($screen == $this->pagehook)
			$columns[$this->pagehook] = 2;			

		return $columns;
	}

	/**
	 * Adds admin pages and registers some callbacks.
	 */
	function on_admin_menu() 
	{
		// If the page hook gets changed, don't forget to change the link to this admin page too in the widget form
		$this->pagehook = add_options_page('One Word A Day', "One Word A Day", 'manage_options', 'one_word_a_day', array(&$this, 'on_show_page'));
		$this->pagehook_tools = add_management_page('One Word A Day', "One Word A Day", 'manage_options', 'one_word_a_day', array(&$this, 'on_show_tools'));
		
		//register callback gets call prior your own page gets rendered
		add_action( 'load-'. $this->pagehook, array( &$this, 'on_load_page') );
		add_action( 'admin_print_scripts-'. $this->pagehook, array( &$this, 'my_plugin_init') );
	}

	/**
	 * Reloads the word data if possible.
	 * @param array word
	 */
	function repair_word( &$word )
	{	
		if( $word["@attributes"]["wordid"] )
			$word = Owad_Data::fetch_single_word( "http://owad.de/owad-archive-quiz.php4?id=$id" );
	}
	
	/**
	 * Repairs the defect entries.
	 */
	function action_repair_defects()
	{		
		$cached_words = Owad_Data::get_cache_content();
		
		foreach( $cached_words as $key => $cached_word )
		{
			if( Owad_Data::is_entry_defect( $cached_word ) )
				$this->repair_word( $cached_word );
		}
		
		$modified_words = $this->array_to_xml( $cached_words );
		file_put_contents( OWAD_CACHE_FILE, $modified_words->asXML() );
	}
	
	/**
	 * Deletes the defect entries.
	 */
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
	
	/**
	 * Deletes the multiple entries.
	 */
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
	
	/**
	 * This method displays the admin page "Tools->Owad Cache"
	 * It also triggers the repair or delete functions if the action is set in $_GET
	 */
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
	
	/**
	 * This method displays the admin page "Settings->One Word A Day"
	 */	
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
	
	/**
	 * Enqueues script and style resources needed by this plugin.
	 */
	function my_plugin_init()
	{
		wp_enqueue_script('post');
		if ( user_can_richedit() )
			wp_enqueue_script('editor');
		
		add_thickbox();
		wp_enqueue_script('media-upload');
	}
	
	/**
	 * Adds metaboxes and enqeues the required script resources.
	 */
	//This method is executed if wordpress core detects this page has to be rendered
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
	
	/**
	 * Lists categories that the post ca be assigned to.
	 */
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
	
	
	/**
	 * Lists the authors that the post can be assigned to.
	 */
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
	
	/**
	 * Shows an editor with the comment text that should appear in every automated post.
	 */
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
	
	/**
	 * Shows a radio button to activate the auto post generator.
	 */
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
	
	/**
	 * Registers the widget
	 */
	function widget_init()
	{
		register_widget('Owad_Widget');
	}
	
	/**
	 * Enqueues script and style resources needed by this plugin.
	 */
	function enqueue_resources()
	{
		// scripts
		wp_enqueue_script( 'owad', '/'. PLUGINDIR .'/'. OWAD_FOLDER .'/js/js.php', array('jquery','thickbox') );
		wp_enqueue_script( 'owad-audio-player', '/'. PLUGINDIR .'/'. OWAD_FOLDER .'/audio-player/audio-player.js' );
		
		// styles
		wp_enqueue_style( 'thickbox' );
	}
	
	/**
	 * Handles the shortcodes.
	 *
	 * @param array shortcode attributes
	 * @return string the output of the specific shortcode
	 */
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
				$word = Owad_Data::get_cached_word_by_date( $date[0] );
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

	
	/**
	 * Checks if the passed date is a holiday
	 * 
	 * @param string date of the format YYYY-MM-DD
	 * @return bool the result of the check
	 */
	function is_holiday( $date )
	{
		global $owad_holidays;		
		return in_array( $date, $owad_holidays );
	}	
	
	/**
	 * Checks if the required php modules are loaded.
	 *
	 * @return bool support
	 */
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
	
	/**
	 * Generates the shortcode handler output.
	 *
	 * @param SimpleXMLElement word object
	 * @param bool control flag for hiding the question
	 * @param int the widget ID
	 * @return string the shortcode handler output
	 */
	function print_word( $word = NULL, $hide_question = false, $widget_id = '' )
	{
		if ( is_null( $word ) )
			$word = Owad_Model::get_data();
		
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
	
	/**
	 * Outputs the select box with previous words.
	 *
	 * @param int widget ID
	 * @return string a form with the select box
	 */
	function print_archive_words( $widget_id = '' )
	{
		$output = '';
		$words = Owad_Model::fetch_archive_words();
	
		// outputs the select box with other words only if there are any
		if ( !is_null( $sets ) )
		{
			$output .= __( 'Other words', 'owad' );
			
			$output .='
				<form id="owad_wordid_'. $widget_id .'">
				<select style="width:100%;" name="wordid" onchange="loadData('. $widget_id .');">
				';
			
			foreach ( $words["word"] as $word )
				$output .=  '<option value="'. $word["wordid"] .'">'. htmlentities( $word["todays_word"] ) .'</option>';			
				
			$output .= '</select>';
			$output .= '</form>';
		}
		
		return $output;
	}
	
	/**
	 * Returns a string with a notifying text about no host support
	 *
	 * @return string no support text
	 */
	function no_support_text()
	{
		return 'If you can read this text this widget isn\'t supported by this blog\'s host!<br/> 
				<br/>Please leave a comment <a href="http://slopjong.de/2009/03/20/one-word-a-day/" 
				target="_blank">here</a> to help improve this widget.';
	}
	
	/**
	 * Posts today's word. The post body contains a shortcode with the attribute "date"
	 */
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
	
	/**
	 * Posts the comment.
	 *
	 * @param int post ID
	 */
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