<?php

class Slack_API {

	private $api_url = "https://slack.com/api/";
	private $auth_url = "https://slack.com/oauth/authorize";
	public $app_client_id;
	public $app_client_secret;
	private $auth_token;

	public function __construct()
	{
		$this->app_client_id = get_option('slack_app_client_id');
		$this->app_client_secret = get_option('slack_app_client_secret');
		$this->auth_token = get_option("slack_for_wp_token");
	}

	public function slack_auth_link() {

		if(!$this->app_client_id) $this->app_client_id = get_option('slack_app_client_id');
		if(!$this->app_client_secret) $this->app_client_secret = get_option('slack_app_client_secret');
		$url = $this->auth_url."?client_id=".$this->app_client_id."&redirect_uri=http://".$_SERVER['SERVER_NAME'].$_SERVER['REQUEST_URI'];
		return $url;
	}

	public function slack_logout() {
		$this->set_auth_token('');
		delete_option('slack_app_client_id');
		delete_option('slack_app_client_secret');
		delete_option("slack_for_wp_token");
	}

	public function get_channel_list()
	{
		$url = "https://slack.com/api/channels.list";
		$url .= "?exclude_archived=1&token=".$this->get_auth_token();
		$c = Slack_Plugin::make_request($url);
		$result = json_decode($c);

		if(isset($result->channels))
			return $result->channels;
		else
			return false;
	}

	public function get_group_list()
	{
		$url = "https://slack.com/api/groups.list";
		$url .= "?exclude_archived=1&token=".$this->get_auth_token();
		$c = Slack_Plugin::make_request($url);
		$result = json_decode($c);

		if(isset($result->groups))
			return $result->groups;
		else
			return false;
	}

	public function publish_post($channel, $msg)
	{
		if($msg == "") $msg = "(no title)";
		$url = "https://slack.com/api/chat.postMessage";
		$url .= "?token=".$this->get_auth_token();
		$url .= "&channel=".$channel;
		$url .= "&text=".urlencode($msg);
		$url .= "&username=WordPress%20BOT";

		$result = json_decode(Slack_Plugin::make_request($url));
		return $result;

	}

	public function set_auth_token($t)
	{
		$this->auth_token = $t;
		update_option("slack_for_wp_token", $t);
	}
	public function get_auth_token()
	{
		return $this->auth_token;
	}
}