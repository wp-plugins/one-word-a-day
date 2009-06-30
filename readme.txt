=== One Word A Day ===
Contributors: mcosx
Donate link: 
Tags: english,learn,learning,widget,sidebar
Requires at least: 2.5
Tested up to: 2.8
Stable tag: 0.3.1

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
Change `define ('WPLANG', '');` into `define ('WPLANG', 'xx_XX');` using the desired country code, e.g. de_DE.

= I've changed the country code but the question still appears in English? =

Either you mistyped the code or the plugin wasn't translated into your language yet. If so contact me.

= How do I display the quiz in a post or page? =

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

* PHP5. I don't care about php4 compatibility during the development. So it's possible that the plugin doesn't work
if you use PHP4. There are several ways to activate PHP5:
1. There is a .htaccess file in the plugin. Remove `#` from the line `#AddHandler application/x-httpd-php5 .php`.
If this has no or negative influence add `#` again. Then try number 2.
1. Often the providers offer both. If so you should be able to select which one you prefer in your 
webspace configuration panel. If you're not sure ask your provider how to activate PHP5.

* json. This is a php module that is available by most webhosting providers.

== WordPress compatible versions ==

* 2.7
* 2.8

== Not tested WordPress versions ==

* 1.x
* < 2.7

== History ==

Not listed minor versions ( e.g. 0.2.1 ) include bugfixes or code improvements.

* 0.3: Added localization. This means that the question *What does # mean?* appears in your language. Added a visual editor to edit the comment posted by auto post generator.
* 0.2: Use of the built-in cache. Previous words can be selected.
* 0.1: Initial version just containing the quiz  