<?php

class Slack_Plugin {

	private $api;

	public function __construct() {
		add_action('admin_menu', array($this, 'register_menu_page'));
		$this->api = new Slack_API();
		$this->register_bootstrap();

		if(get_option("slack_on_publish"))
			add_action('publish_post', array($this, 'publish_post_hook'));
	}
	public function register_menu_page() {
		add_options_page("Slack", "Slack", "manage_options", "slack-for-wordpress", array($this, 'load_page'));
	}
	public function load_page() { 
		if(isset($_GET["code"]))
		{
			$qs = "client_id=".$this->api->app_client_id."&client_secret=".$this->api->app_client_secret."&code=".$_GET["code"]."&redirect_uri=http://".$_SERVER['SERVER_NAME'].$_SERVER['REQUEST_URI'];
			$c = file_get_contents("https://slack.com/api/oauth.access?".$qs);
			$result = json_decode($c);
			update_option("slack_for_wp_token", $result->access_token);
			$this->api->set_auth_token($_GET["code"]);
		}

		if($_POST["slack_on_publish"])
		{
			update_option("slack_on_publish", $_POST["slack_on_publish"]);
			update_option("slack_on_publish_channel", $_POST["slack_on_publish_channel"]);
		}

		$channels = $this->api->get_channel_list();
		?>

		<div class="wrap">
		<form action="" method="post">
			<table class="widefat">
				<tbody>
					<tr>
						<th>CHANNELS</th>
						<td>
							<?php
							if(!$this->api->get_auth_token())
							{
								echo "<a href=".$this->api->slack_auth_link().">LOGIN TO SLACK</a>";
							}
							else
							{

								foreach($channels as $channel)
								{
									echo "<p>".$channel->name." (#".$channel->id.")"."</p>";
								}
							}
							?>
						</td>
					</tr>
					<?php if($this->api->get_auth_token()) : ?>
					<?php
						$slack_on_publish = get_option("slack_on_publish");
						$slack_on_publish_channel = get_option("slack_on_publish_channel")
					?>
					<tr>
						<th>On Post Publish</th>
						<td>
							<input type="checkbox" name="slack_on_publish" <?=$slack_on_publish?"checked=checked":""?>>
							<select name="slack_on_publish_channel" id="">
								<?php foreach($channels as $channel) : ?>
								<option value="<?=$channel->id?>" <?=$slack_on_publish_channel?"selected=selected":""?>><?=$channel->name?></option>
								<?php endforeach; ?>
							</select>
						</td>
					</tr>
					<?php endif; ?>
					<tr>
						<td><input type="submit" class="button-primary"></td>
					</tr>
				</tbody>
			</table>
		</form>	
		</div>

		<?php


	}

	public function publish_post_hook($postID)
	{
		$this->api->publish_post(get_the_title($postID)." published. ".get_permalink($postID));
	}

	public function register_bootstrap()
	{
		add_action('admin_print_scripts-settings_page_slack-for-wordpress', array($this, 'slack_plugin_admin_scripts'));
	}
	public function slack_plugin_admin_scripts() {
        wp_enqueue_script( 'bootstrap-for-slack', 'https://maxcdn.bootstrapcdn.com/bootstrap/3.3.4/css/bootstrap.min.css' );
        wp_enqueue_script( 'bootstrapjs-for-slack', 'https://maxcdn.bootstrapcdn.com/bootstrap/3.3.4/js/bootstrap.min.js', array('bootstrap-for-slack', 'jquery') );
    }
    public function getApi()
    {
    	return $this->api;
    }
    public function getVersion()
    {
    	return "0.0.1";
    }
}