<?php

class Slack_API {

	private $api_url = "https://slack.com/api/";
	private $auth_url = "https://slack.com/oauth/authorize";
	public $app_client_id = "3759810603.4041679803";
	public $app_client_secret = "8750528630d44d3254798ab8e32bd7c8";
	private $auth_token;

	public function __construct()
	{
		$this->auth_token = get_option("slack_for_wp_token");
	}

	public function slack_auth_link() {
		$url = $this->auth_url."?client_id=".$this->app_client_id."&redirect_uri=http://".$_SERVER['SERVER_NAME'].$_SERVER['REQUEST_URI'];
		return $url;
	}

	/*public function slack_authenticate()
	{
		$url = $this->auth_url."?client_id=".$this->app_client_id;

		if(isset($_GET["code"]))
		{
			// If you auth already
			var_dump($_GET["code"]);
		}
		else
		{
			wp_redirect($url); exit;
		}
	}*/

	public function get_channel_list()
	{
		$url = "https://slack.com/api/channels.list";
		$url .= "?exclude_archived=1&token=".$this->get_auth_token();
		$c = file_get_contents($url);
		$result = json_decode($c);

		return $result->channels;
	}

	public function publish_post($msg)
	{
		if($msg == "") $msg = "(no title)";
		$slack_on_publish = get_option("slack_on_publish");
		$slack_on_publish_channel = get_option("slack_on_publish_channel");
		$url = "https://slack.com/api/chat.postMessage";
		$url .= "?token=".$this->get_auth_token();
		$url .= "&channel=".$slack_on_publish_channel;
		$url .= "&text=".urlencode($msg);
		$url .= "&username=WordPress%20BOT";

		file_get_contents($url);

	}

	public function set_auth_token($t)
	{
		$this->auth_token = $t;
	}
	public function get_auth_token()
	{
		return $this->auth_token;
	}
}