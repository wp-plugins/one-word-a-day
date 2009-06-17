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
		  	$options["comment_content"] = stripslashes($_POST['content']);
		 	
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
		$this->pagehook = add_posts_page('One Word A Day', "One Word A Day", 'manage_options', 'one_word_a_day', array(&$this, /*'admin_subpage'*/ 'on_show_page'));
		
		//register callback gets call prior your own page gets rendered
		add_action( 'load-'. $this->pagehook, array( &$this, 'on_load_page') );
		add_action( 'admin_print_scripts-'. $this->pagehook, array( &$this, 'my_plugin_init') );
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
		$options = $this->get_options();		?>		<ul id="category-tabs">			<li class="tabs"><a href="#categories-all" tabindex="3"><?php _e( 'All Categories' ); ?></a></li>			<li class="hide-if-no-js"><a href="#categories-pop" tabindex="3"><?php _e( 'Most Used' ); ?></a></li>		</ul>				<div id="categories-pop" class="tabs-panel" style="display: none;">			<ul id="categorychecklist-pop" class="categorychecklist form-no-clear" >		<?php $popular_ids = wp_popular_terms_checklist('category'); ?>			</ul>		</div>				<div id="categories-all" class="tabs-panel">			<ul id="categorychecklist" class="list:category categorychecklist form-no-clear">		<?php wp_category_checklist(0, false, $options['owad_post_category'], $popular_ids ) ?>			</ul>		</div>				<?php if ( current_user_can('manage_categories') ) : ?>		<div id="category-adder" class="wp-hidden-children">			<h4><a id="category-add-toggle" href="#category-add" class="hide-if-no-js" tabindex="3"><?php _e( '+ Add New Category' ); ?></a></h4>			<p id="category-add" class="wp-hidden-child">			<label class="screen-reader-text" for="newcat"><?php _e( 'Add New Category' ); ?></label><input type="text" name="newcat" id="newcat" class="form-required form-input-tip" value="<?php esc_attr_e( 'New category name' ); ?>" tabindex="3" aria-required="true"/>			<label class="screen-reader-text" for="newcat_parent"><?php _e('Parent category'); ?>:</label><?php wp_dropdown_categories( array( 'hide_empty' => 0, 'name' => 'newcat_parent', 'orderby' => 'name', 'hierarchical' => 1, 'show_option_none' => __('Parent category'), 'tab_index' => 3 ) ); ?>			<input type="button" id="category-add-sumbit" class="add:categorychecklist:category-add button" value="<?php esc_attr_e( 'Add' ); ?>" tabindex="3" />		<?php	wp_nonce_field( 'add-category', '_ajax_nonce', false ); ?>			<span id="category-ajax-response"></span></p>		</div>		<?php		endif;		}
	
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

	function admin_page()
	{
		if (function_exists('add_submenu_page'))
	        add_posts_page( 'One Word A Day', 'One Word A Day', 10, "one-word-a-day", array( &$this, 'admin_subpage') );

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
	
	// Load the date when the last word was published
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
	
	
	// TODO: Den Rest 'morgen' dokumentieren.
	
	
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
		$support = true;
		
		$modules = get_loaded_extensions();
		if ( ! in_array( "json" , $modules ) )
			$support = false;
			
		if ( ! preg_match('/^5/', PHP_VERSION ) )
			$support = false;
		
		return $support;
	}
	
	function print_word( $word = NULL, $hide_question = false, $widget_id = '' )
	{

		if ( NULL == $word )
		{
			$word = $this->get_data();
		}
		
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
			
		$output .= '

			<table>
			<tr><td valign="top">a)</td><td> <a id="owad_alt1_'. $widget_id .'" href="http://owad.slopjong.de/'. urlencode( str_replace( " ", "_", $todays_word )) .'_1'. $wordid .'.html?KeepThis=true&TB_iframe=true&height=540&width=800" class="thickbox">'. $alternatives[0] .'</a> </td></tr>
			<tr><td valign="top">b)</td><td> <a id="owad_alt2_'. $widget_id .'" href="http://owad.slopjong.de/'. urlencode( str_replace( " ", "_", $todays_word )) .'_3'. $wordid .'.html?KeepThis=true&TB_iframe=true&height=540&width=800" class="thickbox">'. $alternatives[1] .'</a> </td></tr>
			<tr><td valign="top">c)</td><td> <a id="owad_alt3_'. $widget_id .'" href="http://owad.slopjong.de/'. urlencode( str_replace( " ", "_", $todays_word )) .'_5'. $wordid .'.html?KeepThis=true&TB_iframe=true&height=540&width=800" class="thickbox">'. $alternatives[2] .'</a> </td></tr>
			</table>
			</div>
			';

		return $output;
	}
	
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
				'comment_content'       => 'Learning English with the WordPress plugin <em>One Word A Day</em>. It displays a new English word in the sidebar every day. Furthermore a quiz is included. <a href="http://slopjong.de/2009/03/20/one-word-a-day/?KeepThis=true&TB_iframe=true&height=540&width=800" class="thickbox" target="_blank">Download it</a> from Slopjong\'s blog.',
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