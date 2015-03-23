<?php

class Slack_Plugin {

	private $api;
	private $page_hook = 'settings_page_slack-for-wordpress';

	public function __construct() {
		add_action('admin_menu', array($this, 'register_menu_page'));
		$this->api = new Slack_API();
		$this->register_scripts();

		if(get_option("slack_on_publish"))
			add_action('publish_post', array($this, 'publish_post_hook'));

		if(get_option("slack_on_waitingcomment"))
			add_action('wp_insert_comment', array($this, 'waiting_comment_hook'), 10, 2);
	}
	public function register_menu_page() {
		add_options_page("Slack", "Slack", "manage_options", "slack-for-wordpress", array($this, 'load_page'));
	}
	public function load_page() {
		?>
		<div class="wrap">
		<div class="bootstrap-wp-wrapper">
		<div class="container-fluid">
		    <div class="page-header">
		         <h1><img src="https://slack.global.ssl.fastly.net/895d/img/landing_slack_hash_wordmark_logo.png" alt=""> <small>integration for WordPress</small></h1>
		    </div>
		    <div class="row">
		        <div class="col-sm-3">POST</div>
		        <div class="col-sm-9">
		            <input type="checkbox" class="slack_admin_checkbox" />
		            <label>When a post published</label>
		            <br />
		            <div class="disabled">Send notification to this channel :
		                <select></select>
		                <br />And add these datas :
		                <br />
		                <input type="checkbox" />Post title
		                <input type="checkbox" />Post author
		            </div>
		            <hr />
		            <input type="checkbox" class="slack_admin_checkbox" />
		            <label>When a post deleted</label>
		            <br />
		            <div class="disabled">Send notification to this channel :
		                <select></select>
		                <br />And add these datas :
		                <br />
		                <input type="checkbox" />Post title
		                <input type="checkbox" />Post author
		            </div>
		        </div>
		    </div>
		    <div class="row">
		        <div class="col-sm-3">COMMENT</div>
		        <div class="col-sm-9">
		        	<input type="checkbox" class="slack_admin_checkbox" />
		            <label>When a new comment pending approval</label>
		            <br />
		            <div class="disabled">Send notification to this channel :
		                <select></select>
		                <br />And add these datas :
		                <br />
		                <input type="checkbox" />Post title
		                <input type="checkbox" />Post author
		            </div>
		        </div>
		    </div>
		    <div class="row">
		        <div class="col-sm-3">CATEGORY</div>
		        <div class="col-sm-9">
		        	<input type="checkbox" class="slack_admin_checkbox" />
		            <label>When a new category created</label>
		            <br />
		            <div class="disabled">Send notification to this channel :
		                <select></select>
		                <br />And add these datas :
		                <br />
		                <input type="checkbox" />Category name
		            </div>
		            <hr />
		            <input type="checkbox" class="slack_admin_checkbox" />
		            <label>When a new category deleted</label>
		            <br />
		            <div class="disabled">Send notification to this channel :
		                <select></select>
		                <br />And add these datas :
		                <br />
		                <input type="checkbox" />Category name
		            </div>
		        </div>
		    </div>
		    <div class="row">
		        <div class="col-sm-3">PING</div>
		        <div class="col-sm-9">
		        	<input type="checkbox" class="slack_admin_checkbox" />
		            <label>When a new ping received</label>
		            <br />
		            <div class="disabled">Send notification to this channel :
		                <select></select>
		            </div>
		        </div>
		    </div>
		    <div class="row">
		        <div class="col-sm-3">TRACKBACK</div>
		        <div class="col-sm-9">
		        	<input type="checkbox" class="slack_admin_checkbox" />
		            <label>When a new trackback received</label>
		            <br />
		            <div class="disabled">Send notification to this channel :
		                <select></select>
		            </div>
		        </div>
		    </div>
		    <div class="row">
		        <div class="col-sm-3">THEME</div>
		        <div class="col-sm-9">
		        	<input type="checkbox" class="slack_admin_checkbox" />
		            <label>after_switch_theme </label>
		            <br />
		            <div class="disabled">Send notification to this channel :
		                <select></select>
		                <br />And add these datas :
		                <br />
		                <input type="checkbox" />Theme name
		            </div>
		        </div>
		    </div>
		    <div class="row">
		        <div class="col-sm-3">USER</div>
		        <div class="col-sm-9">
		        	<input type="checkbox" class="slack_admin_checkbox" />
		            <label>user_register </label>
		            <br />
		            <div class="disabled">Send notification to this channel :
		                <select></select>
		                <br />And add these datas :
		                <br />
		                <input type="checkbox" />Username
		            </div>
		            <hr />
		            <input type="checkbox" class="slack_admin_checkbox" />
		            <label>delete_user </label>
		            <br />
		            <div class="disabled">Send notification to this channel :
		                <select></select>
		                <br />And add these datas :
		                <br />
		                <input type="checkbox" />Username
		            </div>
		        </div>
		    </div>
		    <div class="row">
		        <button type="button" class="btn btn-primary">Primary</button>
		    </div>
		</div>
		</div>
		</div>
		<?php
	}
	public function load_page_bak() { 
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
		if($_POST["slack_on_waitingcomment"])
		{
			update_option("slack_on_waitingcomment", $_POST["slack_on_waitingcomment"]);
			update_option("slack_on_waitingcomment_channel", $_POST["slack_on_waitingcomment_channel"]);
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

					<?php
						$slack_on_waitingcomment = get_option("slack_on_waitingcomment");
						$slack_on_waitingcomment_channel = get_option("slack_on_waitingcomment_channel")
					?>
					<tr>
						<th>On Pending Comment</th>
						<td>
							<input type="checkbox" name="slack_on_waitingcomment" <?=$slack_on_waitingcomment?"checked=checked":""?>>
							<select name="slack_on_waitingcomment_channel" id="">
								<?php foreach($channels as $channel) : ?>
								<option value="<?=$channel->id?>" <?=$slack_on_waitingcomment_channel?"selected=selected":""?>><?=$channel->name?></option>
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

	public function waiting_comment_hook($comment_id, $comment_object)
	{
		if($comment_object->comment_approved == '0')
		{
			$this->api->publish_post("There is a new comment pending for approval.\n*Post Name* : ".get_the_title($comment_object->comment_post_ID));
		}
	}

	public function register_scripts()
	{
		add_action('admin_enqueue_scripts', array($this, 'slack_plugin_admin_scripts'));
		add_action('admin_print_styles-'.$this->page_hook, array($this, 'slack_plugin_admin_styles'));
	}
	public function slack_plugin_admin_scripts($hook) {
		if($hook == $this->page_hook):
			wp_enqueue_script('jquery');
	        wp_enqueue_script( 'bootstrapjs-for-slack', 'https://maxcdn.bootstrapcdn.com/bootstrap/3.3.4/js/bootstrap.min.js', array('jquery') );
	        wp_enqueue_script( 'slack-script-js', plugins_url('js/script.js', dirname(__FILE__)), array('bootstrapjs-for-slack', 'jquery') );
    	endif;
    }
    public function slack_plugin_admin_styles() {
        wp_enqueue_style( 'bootstrap-for-slack', plugins_url('css/bootstrap-wp.min.css', dirname(__FILE__)) );
        wp_enqueue_style( 'slack-opensans-css', 'http://fonts.googleapis.com/css?family=Open+Sans:400,700,300' );
        wp_enqueue_style( 'slack-style-css', plugins_url('css/style.css', dirname(__FILE__)) );

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