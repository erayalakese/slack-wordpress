<?php

class Slack_Plugin {

	private $api;

	public function __construct() {
		add_action('admin_menu', array($this, 'register_menu_page'));
		$this->api = new Slack_API();
		$this->register_bootstrap();
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
		?>

		<div class="wrap">
			<table class="widefat">
				<tbody>
					<tr>
						<td>CHANNELS</td>
						<td>
							<?php
							if(!$this->api->get_auth_token())
							{
								echo "<a href=".$this->api->slack_auth_link().">LOGIN TO SLACK</a>";
							}
							else
							{

								$channels = $this->api->get_channel_list();
								foreach($channels as $channel)
								{
									echo "<p>".$channel->name." (#".$channel->id.")"."</p>";
								}
							}
							?>
						</td>
					</tr>
				</tbody>
			</table>
			
		</div>

		<?php


	}

	public function register_bootstrap()
	{
		add_action('admin_print_scripts-settings_page_slack-for-wordpress', array($this, 'slack_plugin_admin_scripts'));
	}
	public function slack_plugin_admin_scripts() {
        wp_enqueue_script( 'bootstrap-for-slack', 'https://maxcdn.bootstrapcdn.com/bootstrap/3.3.4/css/bootstrap.min.css' );
        wp_enqueue_script( 'bootstrapjs-for-slack', 'https://maxcdn.bootstrapcdn.com/bootstrap/3.3.4/js/bootstrap.min.js', array('bootstrap-for-slack', 'jquery') );
    }
    public function getVersion()
    {
    	return "0.0.1";
    }
}