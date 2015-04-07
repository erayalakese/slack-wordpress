<?php
/*
Plugin Name: Slack Integration for WordPress
Plugin URI: http://wordpress.org/plugins/slack-wordpress/
Description: Slack Integration for WordPress.
Author: Eray Alakese
Version: 1.1.1
Author URI: http://erayalakese.com/
*/
require_once("inc/api.class.php");
require_once("inc/plugin.class.php");

function activate()
{
	$plugin = new Slack_Plugin();
}
//register_activation_hook(__FILE__, 'activate');

$plugin = new Slack_Plugin();

?>
