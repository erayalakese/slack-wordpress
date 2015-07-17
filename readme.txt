=== SLACK Integration for WordPress ===
Contributors: erayalakese
Donate link: http://goo.gl/KTmqNL
Tags: slack,integration,chat,api,notification,team
Requires at least: 3.6.1
Tested up to: 4.2.2
Stable tag: 1.7.1
License: The MIT License
License URI: http://opensource.org/licenses/MIT

This plugin sends notifications to any Slack channel (public or private) when an event triggered in WordPress.

== Description ==

You can contribute to this project on [GitHub](https://github.com/erayalakese/slack-wordpress "Slack WordPress Integration"). **And yes you can send your new feature requests to [here](https://github.com/erayalakese/slack-wordpress/issues)**.    

This plugin sends notifications when

 1. a new post/page/custom post type published
 2. a post/page/custom post type updated
 3. a post/page/custom post type deleted
 4. a new comment pending approval 
 5. a new category created 
 6. a new category deleted
 7. a new ping received 
 8. a new trackback received 
 9. theme switched
 10. a new user registered
 11. a user is removed

 = FOR DEVELOPERS =
 You can send custom Slack notifications within your theme or plugin . To achieve this, **slack-wordpress** declares a global variable `$slack_plugin` for you. You can send notification like this :

     <?php
     global $slack_plugin;
     $channel_to_post = 'CXXXXXXXX';
     $msg = 'test';
     $slack_plugin->getApi()->publish_post($channel_to_post, $msg);

 `publish_post()` returns response of [chat.postMessage](https://api.slack.com/methods/chat.postMessage) , you can look at **Response** section. Also you can check **Formatting** section to formatting your message.

 = AUTHOR =
 * [Eray Alakese](http://eray.rocks)

 = CONTRIBUTORS =
 * [wormeyman](https://github.com/wormeyman)
 * [Cartor](https://github.com/Cartor)
 * [likol](https://github.com/likol)

== Installation ==

1. Create a new Slack Application on [here](https://api.slack.com/applications/new "New Slack Application")
2. Get **Client ID** and **Client Secret** codes from **My Apps** page.
3. Activate the plugin through the 'Plugins' menu in WordPress
4. Add **Client ID** and **Client Secret** codes on *Settings > Slack* page. Click **Step 1 : SAVE** button.
5. Click **Step 2:LINK TO SLACK** button.
6. Now you can configure Slack on *Settings > Slack* page.

== Changelog ==

= 1.7.1 =
* Bug #26 fixed

= 1.7.0 =
* Post excerpt bug fixed. Bug #21
* Now sending notifications when a user logged in.
* Some little design problems solved.

= 1.6.1 =
* Fix for bug #18 . Wordpress Jetpack plugin conflict solved.

= 1.6.0 =
* Post excerpt support for all post types

= 1.5.0 =
* Major file_get_contents() bug fixed
* Custom Post Type support

= 1.4.1 =
* Ready for WordPress 4.2

= 1.4.0 =
* Critical bug fixed
* 'Page' post type support

= 1.3.0 =
* Support for private channels (groups)
* Different notifications to different channels for new post publish and post update

= 1.2.0 =
* Provided a global variable so developers can send custom Slack notifications within their plugin / theme

= 1.1.1 =
* Critical bug fix

= 1.0.1 =
* Version number problem solved.

= 1.0 =
* First stable tag. Hello World !
