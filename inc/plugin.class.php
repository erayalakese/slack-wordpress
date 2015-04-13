<?php
class Slack_Plugin {
	private $api;
	private $page_hook = 'settings_page_slack-for-wordpress';
	public function __construct() {
		add_action ( 'admin_menu', array (
				$this,
				'register_menu_page' 
		) );
		$this->api = new Slack_API ();
		$this->register_scripts ();
		$this->register_hooks ();
	}
	public function register_menu_page() {
		add_options_page ( "Slack", "Slack", "manage_options", "slack-for-wordpress", array (
				$this,
				'load_page' 
		) );
	}
	public function load_page() {
		if (isset ( $_GET ["code"] )) {
			$qs = "client_id=" . $this->api->app_client_id . "&client_secret=" . $this->api->app_client_secret . "&code=" . $_GET ["code"] . "&redirect_uri=http://" . $_SERVER ['SERVER_NAME'] . $_SERVER ['REQUEST_URI'];
			$c = file_get_contents ( "https://slack.com/api/oauth.access?" . $qs );
			$result = json_decode ( $c );
			update_option ( "slack_for_wp_token", $result->access_token );
			$this->api->set_auth_token ( $result->access_token );
		} elseif ($_GET ["unlink"]) {
			$this->api->slack_logout ();
		}
		
		if ($_POST ["slack_options_submit"]) {
			$this->register_options ( $_POST );
		}
		
		$channels = $this->api->get_channel_list ();
		$ops = $this->get_options ();
		?>
<div class="wrap">
	<div class="bootstrap-wp-wrapper">
		<div class="container-fluid">
			<div class="page-header">
				<h1>
					<img src="<?=plugins_url('img/slack.png', dirname(__FILE__))?>"
						alt=""> <small>integration for WordPress</small>
				</h1>
			</div>
			<div class="row">
				<div class="col-sm-3">SLACK CONNECT</div>
				<div class="col-sm-9">
		        	<?php
		if (! $this->api->get_auth_token ()) {
			if ($_POST ["app_client_id"] && $_POST ["app_client_secret"]) {
				update_option ( "slack_app_client_id", $_POST ["app_client_id"] );
				update_option ( "slack_app_client_secret", $_POST ["app_client_secret"] );
			}
			if (! get_option ( 'slack_app_client_id' )) :
				echo "<a href='https://api.slack.com/applications/new'>Create a new application</a><br />";
				echo "<form action='' method='POST'><label for='app_client_id'>App Client ID</label><input type='text' name='app_client_id' />";
				echo "<label for='app_client_secret'>App Client Secret</label><input type='text' name='app_client_secret' />";
				echo "<input type='submit' class='btn btn-secondary' value='STEP 1 : SAVE'><input type='hidden' name='page' value='slack-for-wordpress' /></form>";
			 else :
				echo "<a href=" . $this->api->slack_auth_link () . " class='btn btn-primary'>STEP 2 : LOGIN TO SLACK</a>";
				echo "<p><a href='?page=slack-for-wordpress&unlink=1' class='btn btn-primary'>UNLINK FROM SLACK</a></p>";
			endif;
		} else {
			
			foreach ( $channels as $channel ) {
				echo "<p>" . $channel->name . " (#" . $channel->id . ")" . "</p>";
			}
			echo "<p><a href='?page=slack-for-wordpress&unlink=1' class='btn btn-primary'>UNLINK FROM SLACK</a></p>";
		}
		?>
		        </div>
			</div>
		    <?php if($this->api->get_auth_token()) : ?>
		    <form action="" method="POST">
				<div class="row">
					<div class="col-sm-3">LOG</div>
					<div class="col-sm-9">
						<input type="checkbox" name="slack_publish_post"
							<?=$ops->slack_publish_post?"checked=checked":""?>
							class="slack_admin_checkbox" /> <label>When a post/page published</label>
						<br />
						<div class="<?=$ops->slack_publish_post?"":"disabled"?>">
							Send notification to this channel : <select
								name="slack_publish_post[channel]"><?=$this->print_channels_options($channels, $ops->slack_publish_post)?></select>
						</div>
						<hr />
						<input type="checkbox" name="slack_trashed_post"
							<?=$ops->slack_trashed_post?"checked=checked":""?>
							class="slack_admin_checkbox" /> <label>When a post/page deleted</label>
						<br />
						<div class="<?=$ops->slack_trashed_post?"":"disabled"?>">
							Send notification to this channel : <select
								name="slack_trashed_post[channel]"><?=$this->print_channels_options($channels, $ops->slack_trashed_post)?></select>
						</div>
						<hr />
						<input type="checkbox" name="slack_user_login"
							<?=$ops->slack_user_login?"checked=checked":""?>
							class="slack_admin_checkbox" /> <label>When a user login</label>
						<br />
						<div class="<?=$ops->slack_user_login?"":"disabled"?>">
							Send notification to this channel : <select
								name="slack_user_login[channel]"><?=$this->print_channels_options($channels, $ops->slack_user_login)?></select>
						</div>
						<hr />
						<input type="checkbox" name="slack_user_logout"
							<?=$ops->slack_user_logout?"checked=checked":""?>
							class="slack_admin_checkbox" /> <label>When a user logout</label>
						<br />
						<div class="<?=$ops->slack_user_logout?"":"disabled"?>">
							Send notification to this channel : <select
								name="slack_user_logout[channel]"><?=$this->print_channels_options($channels, $ops->slack_user_logout)?></select>
						</div>
						<hr />
						<input type="checkbox" name="slack_delete_post"
							<?=$ops->slack_delete_post?"checked=checked":""?>
							class="slack_admin_checkbox" /> <label>When a post deleted</label>
						<br />
						<div class="<?=$ops->slack_delete_post?"":"disabled"?>">
							Send notification to this channel : <select
								name="slack_delete_post[channel]"><?=$this->print_channels_options($channels, $ops->slack_delete_post)?></select>
						</div>
						<hr />
						<input type="checkbox" name="slack_update_post"
							<?=$ops->slack_update_post?"checked=checked":""?>
							class="slack_admin_checkbox" /> <label>When a post updated</label>
						<br />
						<div class="<?=$ops->slack_update_post?"":"disabled"?>">
							Send notification to this channel : <select
								name="slack_update_post[channel]"><?=$this->print_channels_options($channels, $ops->slack_update_post)?></select>
						</div>
					</div>
				</div>





				<div class="row">
					<input type="submit" name="slack_options_submit"
						class="btn btn-primary" value="Submit" />
				</div>
			<?php endif; ?>
		    </form>
		</div>
	</div>
</div>
<?php
	}
	public function print_channels_options($channels, $ops) {
		foreach ( $channels as $channel ) :
			echo '<option value="' . $channel->id . '" ' . ($ops->channel == $channel->id ? "selected=selected" : "") . '>' . $channel->name . '</option>';
		endforeach
		;
	}
	public function publish_post_hook($postID) {
		$hooks = $this->get_options ();
		$post = get_post ( $postID );
		$type = ($post->post_type == 'post') ? '文章' : '頁面'; 
		$author = get_the_author_meta ( 'user_login', $post->post_author );
		$title = $post->post_title;
		$title_link = get_permalink ( $postID );
		$text = substr ( $post->post_content, 0, 30 );
		$date = $post->post_date;
		$color = 'good';
		$attachments = array (
				'fallback' => "新的$type 被提交了，作者是：$author 。",
				'pretext' => "新的$type 被提交了，作者是：$author 。",
				'title' => $title,
				'title_link' => $title_link,
				'text' => $text . '...',
				'color' => $color 
		);
		$this->action_log ( $this->api->publish_post ( $hooks->slack_publish_post->channel, $attachments ) );
	}
	public function trashed_post_hook($postID) {
		$user = wp_get_current_user ();
		$hooks = $this->get_options ();
		$post = get_post ( $postID );
		$type = ($post->post_type == 'post') ? '文章' : '頁面';
		$title = $post->post_title;
		$color = 'danger';
		$attachments = array (
				'fallback' => "$type ：$title 被 $user->user_login 移至垃圾桶了。",
				'title' => "$type ：$title 被 $user->user_login 移至垃圾桶了。",
				'color' => $color 
		);
		$this->action_log ( $this->api->publish_post ( $hooks->slack_trashed_post->channel, $attachments ) );
	}
	public function user_login_hook($user_login, $userID) {
		$hooks = $this->get_options ();
		$color = 'warning';
		$attachments = array (
				'fallback' => $user_login . ' 登入了後台。',
				'title' => $user_login . ' 登入了後台。',
				'color' => $color 
		);
		$this->action_log ( $this->api->publish_post ( $hooks->slack_user_login->channel, $attachments ) );
	}
	public function user_logout_hook() {
		file_put_contents('post_data2.tmp', http_build_query('test'));
		$user = wp_get_current_user ();
		$hooks = $this->get_options ();
		$color = 'warning';
		$attachments = array (
				'fallback' => $user->user_login . ' 登出了後台。',
				'title' => $user->user_login . ' 登出了後台。',
				'color' => $color 
		);
		$this->action_log ( $this->api->publish_post ( $hooks->slack_user_logout->channel, $attachments ) );
	}
	public function delete_post_hook($postID) {
		$post = get_post ( $postID );
		if ($post->post_status == 'trash') {
			$type = ($post->post_type == 'post') ? '文章' : '頁面';
			$title = $post->post_title;
			$user = wp_get_current_user ();
			$hooks = $this->get_options ();
			$color = 'danger';
			$attachments = array (
					'fallback' => "$type ：$title 已被 $user->user_login 刪除了。",
					'title' => "$type ： $title 已被 $user->user_login 刪除了。",
					'color' => $color 
			);
			$this->action_log ( $this->api->publish_post ( $hooks->slack_delete_post->channel, $attachments ) );
		}
	}
	public function update_post_hook($postID) {
		$user = wp_get_current_user ();
		$hooks = $this->get_options ();
		$post = get_post ( $postID );
		$type = ($post->post_type == 'post') ? '文章' : '頁面';
		$title = $post->post_title;
		$color = 'good';
		$attachments = array (
				'fallback' => "$type ：$title 已被$user->user_login 更新了。",
				'title' => "$type ：$title 已被$user->user_login 更新了。",
				'color' => $color 
		);
		$this->action_log ( $this->api->publish_post ( $hooks->slack_update_post->channel, $attachments ) );
	}
	public function register_scripts() {
		add_action ( 'admin_enqueue_scripts', array (
				$this,
				'slack_plugin_admin_scripts' 
		) );
		add_action ( 'admin_print_styles-' . $this->page_hook, array (
				$this,
				'slack_plugin_admin_styles' 
		) );
	}
	public function slack_plugin_admin_scripts($hook) {
		if ($hook == $this->page_hook) :
			wp_enqueue_script ( 'jquery' );
			wp_enqueue_script ( 'bootstrapjs-for-slack', 'https://maxcdn.bootstrapcdn.com/bootstrap/3.3.4/js/bootstrap.min.js', array (
					'jquery' 
			) );
			wp_enqueue_script ( 'slack-script-js', plugins_url ( 'js/script.js', dirname ( __FILE__ ) ), array (
					'bootstrapjs-for-slack',
					'jquery' 
			) );
		
		
		
		
		
		
		
    	endif;
	}
	public function slack_plugin_admin_styles() {
		wp_enqueue_style ( 'bootstrap-for-slack', plugins_url ( 'css/bootstrap-wp.min.css', dirname ( __FILE__ ) ) );
		wp_enqueue_style ( 'slack-opensans-css', 'http://fonts.googleapis.com/css?family=Open+Sans:400,700,300' );
		wp_enqueue_style ( 'slack-style-css', plugins_url ( 'css/style.css', dirname ( __FILE__ ) ) );
	}
	public function register_options($ops) {
		if (is_array ( $ops ))
			$ops = json_encode ( $ops );
		
		update_option ( 'slack_options', $ops );
	}
	public function action_log($string) {
		$log = get_option ( 'slack_action_log', '' );
		if (empty ( $string )) {
			update_option ( 'slack_action_log', $log . '\n' . 'Fail.' );
		}
		update_option ( 'slack_action_log', $log . '\n' . $string );
	}
	public function get_options() {
		$ops = get_option ( 'slack_options' );
		if (! $ops) {
			add_option ( 'slack_options' );
			$ops = get_option ( 'slack_options' );
		}
		$ops_decoded = json_decode ( $ops );
		if (is_null ( $ops_decoded ))
			return $ops;
		else
			return $ops_decoded;
	}
	public function register_hooks() {
		$hooks = $this->get_options ();
		if (is_object ( $hooks )) :
			if ($hooks->slack_publish_post) {
				add_action ( 'draft_to_publish', array (
						$this,
						'publish_post_hook' 
				) );
			}
			if ($hooks->slack_trashed_post) {
				add_action ( 'wp_trash_post', array (
						$this,
						'trashed_post_hook' 
				) );
			}
			if ($hooks->slack_user_login) {
				add_action ( 'wp_login', array (
						$this,
						'user_login_hook' 
				) );
			}
			if ($hooks->slack_user_logout) {
				add_action ( 'wp_logout', array (
						$this,
						'user_logout_hook' 
				) );
			}
			if ($hooks->slack_delete_post) {
				add_action ( 'deleted_post', array (
						$this,
						'delete_post_hook' 
				) );
			}
			if ($hooks->slack_update_post) {
				add_action ( 'publish_to_publish', array (
						$this,
						'update_post_hook' 
				) );
			}
		
		endif;
	}
	public function getApi() {
		return $this->api;
	}
	public function getVersion() {
		return "1.1.0";
	}
}