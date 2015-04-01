<?php

class Slack_API {

	private $api_url = "https://slack.com/api/";
	private $auth_url = "https://slack.com/oauth/authorize";
	private $api_method = "chat.postMessage";
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
		$c = file_get_contents($url);
		$result = json_decode($c);

		if(isset($result->channels))
			return $result->channels;
		else
			return false;
	}

/* 	public function publish_post($channel, $msg)
	{
		if($msg == "") $msg = "(no title)";
		$url = "https://slack.com/api/chat.postMessage";
		$url .= "?token=".$this->get_auth_token();
		$url .= "&channel=".$channel;
		$url .= "&text=".urlencode($msg);
		$url .= "&username=WordPress%20BOT";

		$result = json_decode(file_get_contents($url));
		return $result;

	} */
	
	public function publish_post($channel, $msg)
	{
		if (! is_array($msg) || empty($channel)) return false;
		$url = $this->api_url . $this->api_method . '/';
		$attachments = array('fallback' => $msg['author'] . '張貼了一篇新文章：' . $msg['title'] . '。',
						'pretext' =>  $msg['author'] . '張貼了一篇新文章',
						'title' => $msg['title'],
						'title_link' => $msg['title_link'],
						'text' => $msg['text']
						);
		$post_data = array('token' => $this->get_auth_token(),
				'channel' => $channel,
				'username' => 'WordPressBOT',
				'attachments' => json_encode(array($attachments))
		);
		
		$ch = curl_init();
		$setting = array(
				CURLOPT_URL => $url,
				CURLOPT_RETURNTRANSFER => TRUE,
				CURLOPT_POST => TRUE,
				CURLOPT_POSTFIELDS => $post_data,
				CURLOPT_SSL_VERIFYPEER => FALSE
		);
		curl_setopt_array($ch, $setting);
		$response = curl_exec($ch);
		curl_close($ch);
		return $response;
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