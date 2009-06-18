=== One Word A Day ===
Contributors: mcosx
Donate link: 
Tags: english,learn,learning,widget,sidebar
Requires at least: 2.5
Tested up to: 2.8
Stable tag: 0.3

Displays a new English word every day with a multiple choice quiz.

== Description ==

The 'One Word A Day' widget displays a new English word every workday. A quiz is included that shows 
three given choices but only one is correct. The widget's content can also be displayed in a post or page.
Take a look in the FAQ section to see how you do this.

You can also activate an automated post generator which will create a daily post with the quiz. This can
be annoying for your readers so be careful with this function. 

Learning English should make fun. Hopefully you do with this plugin ;-)

== Installation ==

1. Copy plugin contents to /wp-content/plugins/one-word-a-day.
2. Be sure that the folder *cache* has write rights.
3. Activate the plugin through the 'Plugins' menu in WordPress
4. Go to the widgets admin page and drag & drop the widget 'One Word A Day' into your sidebar.

== Frequently Asked Questions ==

= How do I use the translations? =

You have to adopt your wp-config file in your WordPress installation folder. 
Change `define ('WPLANG', '');` into `define ('WPLANG', 'xx_XX');` using the desired country code.

= I've changed the country code but the question still appears in English? =

Either you mistyped the code or the plugin wasn't translated into your language yet. If so contact me.

= How do I display the quiz into a post or page? =

For this the shortcode [owad] was defined. Type one of these shortcode types in your post or page:

**[owad]**
Displays today's word quiz

**[owad date="2009-06-10"]**
Displays the word quiz for the given date.

**[owad date="post_date"]**
Displays the word quiz for the post's date. Changing the the publish date will effect a change of the quiz content.

== Screenshots ==

1. This is how it looks like in the sidebar.
2. By default no daily post is created.
3. If you switch the daily post on the author and post categories can be selected.

== Requirements ==

* PHP5. Often the providers offer the versions 4 and 5 that can be configured by you. There should be
a switch available in your webspace configuration panel. If you're not sure ask your provider to ask
how you can activate PHP5.

* json. This is a php module that is available by most webhosting providers.

== WordPress compatible versions ==

* 2.7
* 2.8

== Not tested WordPress versions ==
Listed version here 
* 1.x
* < 2.7

== History ==

Not listed minor versions ( e.g. 0.2.1 ) include bugfixes or code improvements.

* 0.3: Added localization. This means that the question *What does # mean?* appears in your language. Added a visual editor to edit the comment posted by auto post generator.
* 0.2: Use of the built-in cache. Previous words can be selected.
* 0.1: Initial version just containing the quiz  