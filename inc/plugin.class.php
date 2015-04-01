<?php

class Slack_Plugin {

	private $api;
	private $page_hook = 'settings_page_slack-for-wordpress';

	public function __construct() {
		add_action('admin_menu', array($this, 'register_menu_page'));
		$this->api = new Slack_API();
		$this->register_scripts();
		$this->register_hooks();
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
			$this->api->set_auth_token($result->access_token);
		}
		elseif ($_GET["unlink"]) {
			$this->api->slack_logout();
		}

		if($_POST["slack_options_submit"])
		{
			$this->register_options($_POST);
		}

		$channels = $this->api->get_channel_list();
		$ops = $this->get_options();
		?>
		<div class="wrap">
		<div class="bootstrap-wp-wrapper">
		<div class="container-fluid">
		    <div class="page-header">
		         <h1><img src="<?=plugins_url('img/slack.png', dirname(__FILE__))?>" alt=""> <small>integration for WordPress</small></h1>
		    </div>
		    <div class="row">
		        <div class="col-sm-3">SLACK CONNECT</div>
		        <div class="col-sm-9">
		        	<?php
					if(!$this->api->get_auth_token())
					{
						if($_POST["app_client_id"] && $_POST["app_client_secret"])
						{
							update_option("slack_app_client_id", $_POST["app_client_id"]);
							update_option("slack_app_client_secret", $_POST["app_client_secret"]);
						}
						if(!get_option('slack_app_client_id')):
						echo "<a href='https://api.slack.com/applications/new'>Create a new application</a><br />";
						echo "<form action='' method='POST'><label for='app_client_id'>App Client ID</label><input type='text' name='app_client_id' />";
						echo "<label for='app_client_secret'>App Client Secret</label><input type='text' name='app_client_secret' />";
						echo "<input type='submit' class='btn btn-secondary' value='STEP 1 : SAVE'><input type='hidden' name='page' value='slack-for-wordpress' /></form>";
						else :
						echo "<a href=".$this->api->slack_auth_link()." class='btn btn-primary'>STEP 2 : LOGIN TO SLACK</a>";
						echo "<p><a href='?page=slack-for-wordpress&unlink=1' class='btn btn-primary'>UNLINK FROM SLACK</a></p>";
						endif;
					}
					else
					{

						foreach($channels as $channel)
						{
							echo "<p>".$channel->name." (#".$channel->id.")"."</p>";
						}
						echo "<p><a href='?page=slack-for-wordpress&unlink=1' class='btn btn-primary'>UNLINK FROM SLACK</a></p>";
					}
					?>
		        </div>
		    </div>
		    <?php if($this->api->get_auth_token()) : ?>
		    <form action="" method="POST">
		    <div class="row">
		        <div class="col-sm-3">POST</div>
		        <div class="col-sm-9">
		            <input type="checkbox" name="slack_publish_post" <?=$ops->slack_publish_post?"checked=checked":""?> class="slack_admin_checkbox" />
		            <label>When a post published</label>
		            <br />
		            <div class="<?=$ops->slack_publish_post?"":"disabled"?>">Send notification to this channel :
		                <select name="slack_publish_post[channel]"><?=$this->print_channels_options($channels, $ops->slack_publish_post)?></select>
		                <br />And add these datas :
		                <br />
		                <input type="checkbox" <?=$ops->slack_publish_post->post_title?"checked=checked":""?> name="slack_publish_post[post_title]" />Post title
		                <input type="checkbox" <?=$ops->slack_publish_post->post_author?"checked=checked":""?> name="slack_publish_post[post_author]" />Post author
		            </div>
		            <hr />
		            <input type="checkbox" name="slack_trashed_post" <?=$ops->slack_trashed_post?"checked=checked":""?> class="slack_admin_checkbox" />
		            <label>When a post deleted</label>
		            <br />
		            <div class="<?=$ops->slack_trashed_post?"":"disabled"?>">Send notification to this channel :
		                <select name="slack_trashed_post[channel]"><?=$this->print_channels_options($channels, $ops->slack_trashed_post)?></select>
		                <br />And add these datas :
		                <br />
		                <input type="checkbox" <?=$ops->slack_trashed_post->post_title?"checked=checked":""?> name="slack_trashed_post[post_title]" />Post title
		                <input type="checkbox" <?=$ops->slack_trashed_post->post_author?"checked=checked":""?> name="slack_trashed_post[post_author]" />Post author
		            </div>
		        </div>
		    </div>
		    <div class="row">
		        <div class="col-sm-3">COMMENT</div>
		        <div class="col-sm-9">
		        	<input type="checkbox" <?=$ops->slack_wp_insert_comment?"checked=checked":""?> name="slack_wp_insert_comment" class="slack_admin_checkbox" />
		            <label>When a new comment pending approval</label>
		            <br />
		            <div class="<?=$ops->slack_wp_insert_comment?"":"disabled"?>">Send notification to this channel :
		                <select name="slack_wp_insert_comment[channel]"><?=$this->print_channels_options($channels, $ops->slack_wp_insert_comment)?></select>
		                <br />And add these datas :
		                <br />
		                <input type="checkbox" <?=$ops->slack_wp_insert_comment->post_title?"checked=checked":""?> name="slack_wp_insert_comment[post_title]" />Post title
		            </div>
		        </div>
		    </div>
		    <div class="row">
		        <div class="col-sm-3">CATEGORY</div>
		        <div class="col-sm-9">
		        	<input type="checkbox" <?=$ops->slack_create_category?"checked=checked":""?> name="slack_create_category" class="slack_admin_checkbox" />
		            <label>When a new category created</label>
		            <br />
		            <div class="<?=$ops->slack_create_category?"":"disabled"?>">Send notification to this channel :
		                <select name="slack_create_category[channel]"><?=$this->print_channels_options($channels, $ops->slack_create_category)?></select>
		                <br />And add these datas :
		                <br />
		                <input type="checkbox" <?=$ops->slack_create_category->category_name?"checked=checked":""?> name="slack_create_category[category_name]" />Category name
		            </div>
		            <hr />
		            <input type="checkbox" <?=$ops->slack_delete_category?"checked=checked":""?> name="slack_delete_category" class="slack_admin_checkbox" />
		            <label>When a new category deleted</label>
		            <br />
		            <div class="<?=$ops->slack_create_category?"":"disabled"?>">Send notification to this channel :
		                <select name="slack_delete_category[channel]"><?=$this->print_channels_options($channels, $ops->slack_delete_category)?></select>
		                <br />And add these datas :
		                <br />
		                <input type="checkbox" <?=$ops->slack_delete_category->category_name?"checked=checked":""?> name="slack_delete_category[category_name]" />Category name
		            </div>
		        </div>
		    </div>
		    <div class="row">
		        <div class="col-sm-3">PING</div>
		        <div class="col-sm-9">
		        	<input type="checkbox" <?=$ops->slack_pingback_post?"checked=checked":""?> name="slack_pingback_post" class="slack_admin_checkbox" />
		            <label>When a new ping received</label>
		            <br />
		            <div class="<?=$ops->slack_pingback_post?"":"disabled"?>">Send notification to this channel :
		                <select name="slack_pingback_post[channel]"><?=$this->print_channels_options($channels, $ops->slack_pingback_post)?></select>
		            </div>
		        </div>
		    </div>
		    <div class="row">
		        <div class="col-sm-3">TRACKBACK</div>
		        <div class="col-sm-9">
		        	<input type="checkbox" <?=$ops->slack_trackback_post?"checked=checked":""?> name="slack_trackback_post" class="slack_admin_checkbox" />
		            <label>When a new trackback received</label>
		            <br />
		            <div class="<?=$ops->slack_trackback_post?"":"disabled"?>">Send notification to this channel :
		                <select name="slack_trackback_post[channel]"><?=$this->print_channels_options($channels, $ops->slack_trackback_post)?></select>
		            </div>
		        </div>
		    </div>
		    <div class="row">
		        <div class="col-sm-3">THEME</div>
		        <div class="col-sm-9">
		        	<input type="checkbox" <?=$ops->slack_after_switch_theme?"checked=checked":""?> name="slack_after_switch_theme" class="slack_admin_checkbox" />
		            <label>When theme switched </label>
		            <br />
		            <div class="<?=$ops->slack_after_switch_theme?"":"disabled"?>">Send notification to this channel :
		                <select name="slack_after_switch_theme[channel]"><?=$this->print_channels_options($channels, $ops->slack_after_switch_theme)?></select>
		                <br />And add these datas :
		                <br />
		                <input type="checkbox" <?=$ops->slack_after_switch_theme->theme_name?"checked=checked":""?> name="slack_after_switch_theme[theme_name]" />Theme name
		            </div>
		        </div>
		    </div>
		    <div class="row">
		        <div class="col-sm-3">USER</div>
		        <div class="col-sm-9">
		        	<input type="checkbox" <?=$ops->slack_user_register?"checked=checked":""?> name="slack_user_register" class="slack_admin_checkbox" />
		            <label>When a user registered </label>
		            <br />
		            <div class="<?=$ops->slack_user_register?"":"disabled"?>">Send notification to this channel :
		                <select name="slack_user_register[channel]"><?=$this->print_channels_options($channels, $ops->slack_user_register)?></select>
		                <br />And add these datas :
		                <br />
		                <input type="checkbox" <?=$ops->slack_user_register->username?"checked=checked":""?> name="slack_user_register[username]" />Username
		            </div>
		            <hr />
		            <input type="checkbox" <?=$ops->slack_delete_user?"checked=checked":""?> name="slack_delete_user" class="slack_admin_checkbox" />
		            <label>When a user is removed </label>
		            <br />
		            <div class="<?=$ops->slack_delete_user?"":"disabled"?>">Send notification to this channel :
		                <select name="slack_delete_user[channel]"><?=$this->print_channels_options($channels, $ops->slack_delete_user)?></select>
		                <br />And add these datas :
		                <br />
		                <input type="checkbox" <?=$ops->slack_delete_user->username?"checked=checked":""?> name="slack_delete_user[username]" />Username
		            </div>
		        </div>
		    </div>
		    <div class="row">
		        <input type="submit" name="slack_options_submit" class="btn btn-primary" value="Submit" />
		    </div>
			<?php endif; ?>
		    </form>
		</div>
		</div>
		</div>
		<?php
	}

	public function print_channels_options($channels, $ops)
	{
		foreach($channels as $channel) :
		echo '<option value="'.$channel->id.'" '.($ops->channel==$channel->id?"selected=selected":"").'>'.$channel->name.'</option>';
		endforeach;
	}

	public function publish_post_hook($postID)
	{
		$hooks = $this->get_options();
		$post = get_post($postID);
		$msg = array('author' => get_the_author_meta('display_name', $post->post_author),
				'title' => $post->post_title,
				'title_link' => get_permalink($postID),
				'text' => substr($post->post_content, 0, 30),
				'color' => 'good',
				'date' => $post->post_date,
				);
		$this->api->publish_post($hooks->slack_publish_post->channel, $msg);
	}
	public function trashed_post_hook($postID)
	{
		$hooks = $this->get_options();
		$msg = ($hooks->slack_trashed_post->post_title=='on'?get_the_title($postID):'A post');
		$msg .= " deleted.\n";
		$msg .= ($hooks->slack_trashed_post->post_author=='on'?' Author '.get_the_author_meta('display_name', get_post($postID)->post_author)."\n":'');
		$msg .= get_permalink($postID);
		$this->api->publish_post($hooks->slack_trashed_post->channel, $msg);
	}
	public function wp_insert_comment_hook($comment_id, $comment_object)
	{
		$hooks = $this->get_options();

		if($comment_object->comment_approved == '0')
		{
			$msg = "There is a new comment pending for approval.\n";
			$msg .= ($hooks->slack_wp_insert_comment->post_title=='on'?"*Post Name* : ".get_the_title($comment_object->comment_post_ID):"");
			$this->api->publish_post($hooks->slack_wp_insert_comment->channel, $msg);
		}
	}
	public function create_category_hook($catID)
	{
		$hooks = $this->get_options();

		$msg = 'A new category';
		$msg .= " created.\n";
		$msg .= ($hooks->slack_create_category->category_name=='on'?' *Category name* : '.get_cat_name($catID)."\n":'');
		$this->api->publish_post($hooks->slack_create_category->channel, $msg);
	}
	public function delete_category_hook($catID)
	{
		$hooks = $this->get_options();

		$msg = 'A category';
		$msg .= " deleted.\n";
		$msg .= ($hooks->slack_delete_category->category_name=='on'?' *Category name* : '.get_cat_name($catID)."\n":'');
		$this->api->publish_post($hooks->slack_delete_category->channel, $msg);
	}
	public function pingback_post_hook($catID)
	{
		$hooks = $this->get_options();

		$msg = 'A pingback';
		$msg .= " received.\n";
		$this->api->publish_post($hooks->slack_pingback_post->channel, $msg);
	}
	public function trackback_post_hook($commentID)
	{
		$hooks = $this->get_options();

		$msg = 'A trackback';
		$msg .= " received.\n";
		$this->api->publish_post($hooks->slack_trackback_post->channel, $msg);
	}
	public function after_switch_theme_hook($name)
	{
		$hooks = $this->get_options();

		if($hooks->slack_after_switch_theme->theme_name=='on')
			$msg = 'Active theme changed to : '.$name;
		else
			$msg = 'Active theme changed';
		$this->api->publish_post($hooks->slack_after_switch_theme->channel, $msg);
	}
	public function user_register_hook($userID)
	{
		$hooks = $this->get_options();

		if($hooks->slack_user_register->username=='on')
			$msg = 'A new user registered. Username : '.get_userdata($userID)->display_name;
		else
			$msg = 'A new user registered.';
		$this->api->publish_post($hooks->slack_user_register->channel, $msg);
	}
	public function delete_user_hook($userID)
	{
		$hooks = $this->get_options();

		if($hooks->slack_delete_user->username=='on')
			$msg = 'User deleted. Username : '.get_userdata($userID)->display_name;
		else
			$msg = 'User deleted.';
		$this->api->publish_post($hooks->slack_delete_user->channel, $msg);
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

    public function register_options($ops)
    {
    	if(is_array($ops)) $ops = json_encode($ops);

    	update_option('slack_options', $ops);
    }

    public function get_options()
    {
    	$ops = get_option('slack_options');
    	if(!$ops) {
    		add_option('slack_options');
    		$ops = get_option('slack_options');
    	}
    	$ops_decoded = json_decode($ops);
    	if(is_null($ops_decoded))
    		return $ops;
    	else
    		return $ops_decoded;
    }

    public function register_hooks()
    {
    	$hooks = $this->get_options();

    	if(is_object($hooks)) :
    	if($hooks->slack_publish_post)
    	{
    		add_action('publish_post', array($this, 'publish_post_hook'));
    	}
    	if($hooks->slack_trashed_post)
    	{
    		add_action('trashed_post', array($this, 'trashed_post_hook'));
    	}
    	if($hooks->slack_wp_insert_comment)
    	{
    		add_action('wp_insert_comment', array($this, 'wp_insert_comment_hook'), 10, 2);
    	}
    	if($hooks->slack_create_category)
    	{
    		add_action('create_category', array($this, 'create_category_hook'));
    	}
    	if($hooks->slack_delete_category)
    	{
    		add_action('delete_category', array($this, 'delete_category_hook'));
    	}
    	if($hooks->slack_pingback_post)
    	{
    		add_action('pingback_post', array($this, 'pingback_post_hook'));
    	}
    	if($hooks->slack_trackback_post)
    	{
    		add_action('trackback_post', array($this, 'trackback_post_hook'));
    	}
    	if($hooks->slack_after_switch_theme)
    	{
    		add_action('after_switch_theme', array($this, 'after_switch_theme_hook'));
    	}
    	if($hooks->slack_user_register)
    	{
    		add_action('user_register', array($this, 'user_register_hook'));
    	}
    	if($hooks->slack_delete_user)
    	{
    		add_action('delete_user', array($this, 'delete_user_hook'));
    	}
    	endif;
    }

    public function getApi()
    {
    	return $this->api;
    }
    public function getVersion()
    {
    	return "1.1.0";
    }
}