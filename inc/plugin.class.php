<?php

class Slack_Plugin {

	private $api;
	private $page_hook = 'settings_page_slack-for-wordpress';

	public function __construct() {
		add_action('admin_menu', array($this, 'register_menu_page'));
		$this->api = new Slack_API();
		$this->register_scripts();
		$this->register_hooks();

		add_action('admin_init', array($this, 'http_requests'));
	}
	public function register_menu_page() {
		add_options_page("Slack", "Slack", "manage_options", "slack-for-wordpress", array($this, 'load_page'));
	}
	public function load_page() {

		$channels = $this->api->get_channel_list();
		if(!$channels) $channels = array();
		$groups = $this->api->get_group_list();
		if(!$groups) $groups = array();
		$all_channels = array_merge($channels,$groups);
		$ops = $this->get_options();
		$cpts = get_post_types(array(
				'_builtin' => false,
			), 'names');
		?>
		<div class="wrap">
		<div class="bootstrap-wp-wrapper">
		<div class="slack-notification bg-info">
			<p><strong>Version: </strong><?=$this->getVersion()?></p><p>All bug reports and new feature requests are welcome in <a href="https://github.com/erayalakese/slack-wordpress/issues">here</a>.
			<hr>
			<h5>PREMIUM <img src="<?=plugins_url('img/wordpress.png', dirname(__FILE__))?>" width="100"> PLUGINS <small>FROM AUTHOR</small></h5>
			<a style="" href="http://codecanyon.net/item/wordpress-post-series-ultimate/11334162?ref=erayalakese"><img src="<?=plugins_url('img/thumb.png', dirname(__FILE__))?>" alt=""></a>
			<a style="" href="http://codecanyon.net/item/debug-my-wp/11440759?ref=erayalakese"><img src="<?=plugins_url('img/80x80.jpg', dirname(__FILE__))?>" alt=""></a>
			<a style="" href="http://codecanyon.net/item/enstats-dashboard-widget-for-envato-authors/11950647?ref=erayalakese"><img src="<?=plugins_url('img/enstats.png', dirname(__FILE__))?>" alt=""></a>
			<a style="" href="http://codecanyon.net/item/facebook-elements-for-visual-composer/12026917?ref=erayalakese"><img src="<?=plugins_url('img/vcfe.jpg', dirname(__FILE__))?>" alt=""></a>
			<a style="" href="http://codecanyon.net/item/chart-elements-for-visual-composer/12132158?ref=erayalakese"><img src="<?=plugins_url('img/vcce.jpg', dirname(__FILE__))?>" alt=""></a>
			</p>
		</div>
		<div class="container-fluid">
		    <div class="page-header">
		         <h1><img src="<?=plugins_url('img/slack_svg.svg', dirname(__FILE__))?>" alt="Slack Logo" width="392" height="115"> <small>integration for WordPress</small></h1>
		    </div>
		    <div class="row">
		        <div class="col-sm-3">SLACK CONNECT</div>
		        <div class="col-sm-9">
		        	<?php
					if(!$this->api->get_auth_token())
					{

						if(!get_option('slack_app_client_id')):
						echo "<a href='https://api.slack.com/applications/new'>Create a new application</a><br />";
						echo "<form action='' method='POST'><label for='app_client_id'>App Client ID</label><input type='text' name='app_client_id' />";
						echo "<label for='app_client_secret'>App Client Secret</label><input type='text' name='app_client_secret' />";
						echo "<input type='submit' class='button-primary' value='STEP 1 : SAVE'><input type='hidden' name='page' value='slack-for-wordpress' /></form>";
						else :
						echo "<a href=".$this->api->slack_auth_link()." class='button-secondary'>STEP 2 : LOGIN TO SLACK</a>";
						echo "<p><a href='?page=slack-for-wordpress&unlink=1' class='button-secondary'>UNLINK FROM SLACK</a></p>";
						endif;
					}
					else
					{

						foreach($channels as $channel)
						{
							echo "<p>".$channel->name." (#".$channel->id.")"."</p>";
						}
						foreach($groups as $group)
						{
							echo "<p>".$group->name." (#".$group->id.")"." <span style='color:red;font-weight: bold'>(private)</span></p>";
						}
						echo "<p><a href='?page=slack-for-wordpress&unlink=1' class='button-secondary'>UNLINK FROM SLACK</a></p>";
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
		                <select name="slack_publish_post[channel]"><?=$this->print_channels_options($all_channels, $ops->slack_publish_post)?></select>
		                <br />And add these datas :
		                <br />
		                <input type="checkbox" <?=$ops->slack_publish_post->post_title?"checked=checked":""?> name="slack_publish_post[post_title]" />Post title
		                <input type="checkbox" <?=$ops->slack_publish_post->post_author?"checked=checked":""?> name="slack_publish_post[post_author]" />Post author
		                <input type="checkbox" <?=$ops->slack_publish_post->post_excerpt?"checked=checked":""?> name="slack_publish_post[post_excerpt]" />Post excerpt
		            </div>
		            <hr />
		            <input type="checkbox" name="slack_update_post" <?=$ops->slack_update_post?"checked=checked":""?> class="slack_admin_checkbox" />
		            <label>When a post updated.</label>
		            <br />
		            <div class="<?=$ops->slack_update_post?"":"disabled"?>">Send notification to this channel :
		                <select name="slack_update_post[channel]"><?=$this->print_channels_options($all_channels, $ops->slack_update_post)?></select>
		                <br />And add these datas :
		                <br />
		                <input type="checkbox" <?=$ops->slack_update_post->post_title?"checked=checked":""?> name="slack_update_post[post_title]" />Post title
		                <input type="checkbox" <?=$ops->slack_update_post->post_editor?"checked=checked":""?> name="slack_update_post[post_editor]" />Post editor
		                <input type="checkbox" <?=$ops->slack_update_post->post_excerpt?"checked=checked":""?> name="slack_update_post[post_excerpt]" />Post excerpt
		            </div>
		            <hr />
		            <input type="checkbox" name="slack_trashed_post" <?=$ops->slack_trashed_post?"checked=checked":""?> class="slack_admin_checkbox" />
		            <label>When a post deleted</label>
		            <br />
		            <div class="<?=$ops->slack_trashed_post?"":"disabled"?>">Send notification to this channel :
		                <select name="slack_trashed_post[channel]"><?=$this->print_channels_options($all_channels, $ops->slack_trashed_post)?></select>
		                <br />And add these datas :
		                <br />
		                <input type="checkbox" <?=$ops->slack_trashed_post->post_title?"checked=checked":""?> name="slack_trashed_post[post_title]" />Post title
		                <input type="checkbox" <?=$ops->slack_trashed_post->post_author?"checked=checked":""?> name="slack_trashed_post[post_author]" />Post author
		                <input type="checkbox" <?=$ops->slack_trashed_post->post_excerpt?"checked=checked":""?> name="slack_trashed_post[post_excerpt]" />Post excerpt
		            </div>
		        </div>
		    </div>
		    <div class="row">
		        <div class="col-sm-3">PAGE</div>
		        <div class="col-sm-9">
		            <input type="checkbox" name="slack_publish_page" <?=$ops->slack_publish_page?"checked=checked":""?> class="slack_admin_checkbox" />
		            <label>When a page published</label>
		            <br />
		            <div class="<?=$ops->slack_publish_page?"":"disabled"?>">Send notification to this channel :
		                <select name="slack_publish_page[channel]"><?=$this->print_channels_options($all_channels, $ops->slack_publish_page)?></select>
		                <br />And add these datas :
		                <br />
		                <input type="checkbox" <?=$ops->slack_publish_page->post_title?"checked=checked":""?> name="slack_publish_page[post_title]" />Page title
		                <input type="checkbox" <?=$ops->slack_publish_page->post_author?"checked=checked":""?> name="slack_publish_page[post_author]" />Page author
		                <input type="checkbox" <?=$ops->slack_publish_page->post_excerpt?"checked=checked":""?> name="slack_publish_page[post_excerpt]" />Page excerpt
		            </div>
		            <hr />
		            <input type="checkbox" name="slack_update_page" <?=$ops->slack_update_page?"checked=checked":""?> class="slack_admin_checkbox" />
		            <label>When a page updated.</label>
		            <br />
		            <div class="<?=$ops->slack_update_page?"":"disabled"?>">Send notification to this channel :
		                <select name="slack_update_page[channel]"><?=$this->print_channels_options($all_channels, $ops->slack_update_page)?></select>
		                <br />And add these datas :
		                <br />
		                <input type="checkbox" <?=$ops->slack_update_page->post_title?"checked=checked":""?> name="slack_update_page[post_title]" />Page title
		                <input type="checkbox" <?=$ops->slack_update_page->post_editor?"checked=checked":""?> name="slack_update_page[post_editor]" />Page editor
		                <input type="checkbox" <?=$ops->slack_update_page->post_excerpt?"checked=checked":""?> name="slack_update_page[post_excerpt]" />Page excerpt
		            </div>
		            <hr />
		            <input type="checkbox" name="slack_trashed_page" <?=$ops->slack_trashed_page?"checked=checked":""?> class="slack_admin_checkbox" />
		            <label>When a page deleted</label>
		            <br />
		            <div class="<?=$ops->slack_trashed_page?"":"disabled"?>">Send notification to this channel :
		                <select name="slack_trashed_page[channel]"><?=$this->print_channels_options($all_channels, $ops->slack_trashed_page)?></select>
		                <br />And add these datas :
		                <br />
		                <input type="checkbox" <?=$ops->slack_trashed_page->post_title?"checked=checked":""?> name="slack_trashed_page[post_title]" />Page title
		                <input type="checkbox" <?=$ops->slack_trashed_page->post_author?"checked=checked":""?> name="slack_trashed_page[post_author]" />Page author
		                <input type="checkbox" <?=$ops->slack_trashed_page->post_excerpt?"checked=checked":""?> name="slack_trashed_page[post_excerpt]" />Page excerpt
		            </div>
		        </div>
		    </div>
		    <?php foreach($cpts as $cpt) : ?>
		    <div class="row">
		        <div class="col-sm-3"><?=strtoupper($cpt)?></div>
		        <div class="col-sm-9">
		            <input type="checkbox" name="slack_publish_<?=$cpt?>" <?=$ops->{"slack_publish_$cpt"}?"checked=checked":""?> class="slack_admin_checkbox" />
		            <label>When a <?=$cpt?> published</label>
		            <br />
		            <div class="<?=$ops->{"slack_publish_$cpt"}?"":"disabled"?>">Send notification to this channel :
		                <select name="slack_publish_<?=$cpt?>[channel]"><?=$this->print_channels_options($all_channels, $ops->{"slack_publish_$cpt"})?></select>
		                <br />And add these datas :
		                <br />
		                <input type="checkbox" <?=$ops->{"slack_publish_$cpt"}->post_title?"checked=checked":""?> name="slack_publish_<?=$cpt?>[post_title]" /><?=$cpt?> title
		                <input type="checkbox" <?=$ops->{"slack_publish_$cpt"}->post_author?"checked=checked":""?> name="slack_publish_<?=$cpt?>[post_author]" /><?=$cpt?> author
		                <input type="checkbox" <?=$ops->{"slack_publish_$cpt"}->post_excerpt?"checked=checked":""?> name="slack_publish_<?=$cpt?>[post_excerpt]" /><?=$cpt?> excerpt
		            </div>
		            <hr />
		            <input type="checkbox" name="slack_update_<?=$cpt?>" <?=$ops->{"slack_update_$cpt"}?"checked=checked":""?> class="slack_admin_checkbox" />
		            <label>When a <?=$cpt?> updated.</label>
		            <br />
		            <div class="<?=$ops->{"slack_update_$cpt"}?"":"disabled"?>">Send notification to this channel :
		                <select name="slack_update_<?=$cpt?>[channel]"><?=$this->print_channels_options($all_channels, $ops->{"slack_update_$cpt"})?></select>
		                <br />And add these datas :
		                <br />
		                <input type="checkbox" <?=$ops->{"slack_update_$cpt"}->post_title?"checked=checked":""?> name="slack_update_<?=$cpt?>[post_title]" /><?=$cpt?> title
		                <input type="checkbox" <?=$ops->{"slack_update_$cpt"}->post_editor?"checked=checked":""?> name="slack_update_<?=$cpt?>[post_editor]" /><?=$cpt?> editor
		                <input type="checkbox" <?=$ops->{"slack_update_$cpt"}->post_excerpt?"checked=checked":""?> name="slack_update_<?=$cpt?>[post_excerpt]" /><?=$cpt?> excerpt
		            </div>
		            <hr />
		            <input type="checkbox" name="slack_trashed_<?=$cpt?>" <?=$ops->{"slack_trashed_$cpt"}?"checked=checked":""?> class="slack_admin_checkbox" />
		            <label>When a post deleted</label>
		            <br />
		            <div class="<?=$ops->{"slack_trashed_$cpt"}?"":"disabled"?>">Send notification to this channel :
		                <select name="slack_trashed_<?=$cpt?>[channel]"><?=$this->print_channels_options($all_channels, $ops->{"slack_trashed_$cpt"})?></select>
		                <br />And add these datas :
		                <br />
		                <input type="checkbox" <?=$ops->{"slack_trashed_$cpt"}->post_title?"checked=checked":""?> name="slack_trashed_<?=$cpt?>[post_title]" /><?=$cpt?> title
		                <input type="checkbox" <?=$ops->{"slack_trashed_$cpt"}->post_author?"checked=checked":""?> name="slack_trashed_<?=$cpt?>[post_author]" /><?=$cpt?> author
		                <input type="checkbox" <?=$ops->{"slack_trashed_$cpt"}->post_excerpt?"checked=checked":""?> name="slack_trashed_<?=$cpt?>[post_excerpt]" /><?=$cpt?> excerpt
		            </div>
		        </div>
		    </div>
			<?php endforeach; ?>
		    <div class="row">
		        <div class="col-sm-3">COMMENT</div>
		        <div class="col-sm-9">
		        	<input type="checkbox" <?=$ops->slack_wp_insert_comment?"checked=checked":""?> name="slack_wp_insert_comment" class="slack_admin_checkbox" />
		            <label>When a new comment pending approval</label>
		            <br />
		            <div class="<?=$ops->slack_wp_insert_comment?"":"disabled"?>">Send notification to this channel :
		                <select name="slack_wp_insert_comment[channel]"><?=$this->print_channels_options($all_channels, $ops->slack_wp_insert_comment)?></select>
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
		                <select name="slack_create_category[channel]"><?=$this->print_channels_options($all_channels, $ops->slack_create_category)?></select>
		                <br />And add these datas :
		                <br />
		                <input type="checkbox" <?=$ops->slack_create_category->category_name?"checked=checked":""?> name="slack_create_category[category_name]" />Category name
		            </div>
		            <hr />
		            <input type="checkbox" <?=$ops->slack_delete_category?"checked=checked":""?> name="slack_delete_category" class="slack_admin_checkbox" />
		            <label>When a new category deleted</label>
		            <br />
		            <div class="<?=$ops->slack_create_category?"":"disabled"?>">Send notification to this channel :
		                <select name="slack_delete_category[channel]"><?=$this->print_channels_options($all_channels, $ops->slack_delete_category)?></select>
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
		                <select name="slack_pingback_post[channel]"><?=$this->print_channels_options($all_channels, $ops->slack_pingback_post)?></select>
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
		                <select name="slack_trackback_post[channel]"><?=$this->print_channels_options($all_channels, $ops->slack_trackback_post)?></select>
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
		                <select name="slack_after_switch_theme[channel]"><?=$this->print_channels_options($all_channels, $ops->slack_after_switch_theme)?></select>
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
		                <select name="slack_user_register[channel]"><?=$this->print_channels_options($all_channels, $ops->slack_user_register)?></select>
		                <br />And add these datas :
		                <br />
		                <input type="checkbox" <?=$ops->slack_user_register->username?"checked=checked":""?> name="slack_user_register[username]" />Username
		            </div>
		            <hr />
		            <input type="checkbox" <?=$ops->slack_delete_user?"checked=checked":""?> name="slack_delete_user" class="slack_admin_checkbox" />
		            <label>When a user is removed </label>
		            <br />
		            <div class="<?=$ops->slack_delete_user?"":"disabled"?>">Send notification to this channel :
		                <select name="slack_delete_user[channel]"><?=$this->print_channels_options($all_channels, $ops->slack_delete_user)?></select>
		                <br />And add these datas :
		                <br />
		                <input type="checkbox" <?=$ops->slack_delete_user->username?"checked=checked":""?> name="slack_delete_user[username]" />Username
		            </div>
		            <hr />
		            <input type="checkbox" <?=$ops->slack_login_user?"checked=checked":""?> name="slack_login_user" class="slack_admin_checkbox" />
		            <label>When a user logged in </label>
		            <br />
		            <div class="<?=$ops->slack_login_user?"":"disabled"?>">Send notification to this channel :
		                <select name="slack_login_user[channel]"><?=$this->print_channels_options($all_channels, $ops->slack_login_user)?></select>
		                <br />And add these datas :
		                <br />
		                <input type="checkbox" <?=$ops->slack_login_user->username?"checked=checked":""?> name="slack_login_user[username]" />Username
		                <input type="checkbox" <?=$ops->slack_login_user->siteinfo?"checked=checked":""?> name="slack_login_user[siteinfo]" />Site Info (Name &amp; URL, useful for multi-sites)
		            </div>
		        </div>
		    </div>
		    <div class="row">
		        <input type="submit" name="slack_options_submit" class="button-primary" value="Submit" />
		    </div>
			<?php endif; ?>
		    </form>
		    <hr />
		    <span style="float:right;text-align:right">This plugin developed by a Slack user, <a href="http://www.erayalakese.com">Eray Alakese</a>. It's not created by, affiliated with, or supported by Slack Technologies, Inc.</span>
		</div>
		</div>
		</div>
		<?php
	}

	public function print_channels_options($all_channels, $ops)
	{
		// Warning! $all_channels contains public channels AND private channels (groups)
		foreach($all_channels as $channel) :
		echo '<option value="'.$channel->id.'" '.($ops->channel==$channel->id?"selected=selected":"").'>'.$channel->name.($channel->is_group?" (private)":"").'</option>';
		endforeach;
	}

	public function publish_post_hook($strNewStatus, $strOldStatus, $post)
	{
		if ( (defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE) ) {
			return;
		}

		$post_type = $post->post_type;

		if( ( $strOldStatus == 'draft' || $strOldStatus == 'auto-draft' || $strOldStatus == 'new' ) && $strNewStatus == 'publish' ) :
			// New post/page published
			$hooks = $this->get_options();

			$msg = ($hooks->{"slack_publish_$post_type"}->post_title=='on'?get_the_title($post->ID):'A new '.$post_type);
			$msg .= " published.\n";
			$msg .= ($hooks->{"slack_publish_$post_type"}->post_author=='on'?' Author '.get_the_author_meta('display_name', get_post($post->ID)->post_author)."\n":'');
			$msg .= get_permalink($post->ID);
			$msg .= ($hooks->{"slack_publish_$post_type"}->post_excerpt=='on'?"\nPost excerpt : ".$post->post_excerpt:'');
			$this->api->publish_post($hooks->{"slack_publish_$post_type"}->channel, $msg);

		elseif( $strOldStatus == 'publish' && $strNewStatus == 'publish') :
			// Post/Page updated
			$hooks = $this->get_options();

			// Find real user who edit post, instead of author of post.
			$current_user = wp_get_current_user();
			$msg = ($hooks->{"slack_update_$post_type"}->post_title=='on'?get_the_title($post->ID):'A '.$post_type);
			$msg .= " was updated.\n";
			$msg .= ($hooks->{"slack_update_$post_type"}->post_editor=='on'?"Editor: {$current_user->display_name} \n":'');
			$msg .= get_permalink($post->ID);
			$msg .= ($hooks->{"slack_publish_$post_type"}->post_excerpt=='on'?"\nPost excerpt : ".$post->post_excerpt:'');
			$this->api->publish_post($hooks->{"slack_update_$post_type"}->channel, $msg);
		endif;
	}
	public function trashed_post_hook($postID)
	{
		$hooks = $this->get_options();

		$post_type = get_post_type($postID);

		$msg = ($hooks->{"slack_trashed_$post_type"}->post_title=='on'?get_the_title($postID):'A post');
		$msg .= " deleted.\n";
		$msg .= ($hooks->{"slack_trashed_$post_type"}->post_author=='on'?' Author '.get_the_author_meta('display_name', get_post($postID)->post_author)."\n":'');
		$msg .= get_permalink($postID);
		$this->api->publish_post($hooks->{"slack_trashed_$post_type"}->channel, $msg);
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
	public function login_user_hook($user_login, $user)
	{
		$hooks = $this->get_options();

		$msg = 'User logged in.';
		if($hooks->slack_login_user->username=='on')
			$msg .= "\nUsername : ".$user_login;
		if($hooks->slack_login_user->siteinfo=='on')
			$msg .= "\nSite: ".get_bloginfo( 'name' ).' ('.get_bloginfo( 'wpurl' ).')';
		$this->api->publish_post($hooks->slack_login_user->channel, $msg);
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
	        wp_enqueue_script( 'slack-script-a-js', plugins_url('js/script-a.js', dirname(__FILE__)), array('jquery') );
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
    		add_option('slack_options', json_encode(array()));
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

    	// Using same hook func for post and page actions.
    	if($hooks->slack_publish_post || $hooks->slack_update_post)
    	{
    		add_action('transition_post_status', array($this, 'publish_post_hook'), 10, 3);
    	}
    	if($hooks->slack_trashed_post)
    	{
    		add_action('trashed_post', array($this, 'trashed_post_hook'));
    	}
    	if($hooks->slack_publish_page || $hooks->slack_update_page)
    	{
    		add_action('transition_post_status', array($this, 'publish_post_hook'), 10, 3);
    	}
    	if($hooks->slack_trashed_page)
    	{
    		add_action('trashed_post', array($this, 'trashed_post_hook'));
    	}
    	$cpts = get_post_types(array(
				'_builtin' => false,
			), 'names');
    	foreach($cpts as $cpt)
    	{
    		if($hooks->{"slack_publish_$cpt"} || $hooks->{"slack_update_$cpt"})
	    	{
	    		add_action('transition_post_status', array($this, 'publish_post_hook'), 10, 3);
	    	}
	    	if($hooks->{"slack_trashed_$cpt"})
	    	{
	    		add_action('trashed_post', array($this, 'trashed_post_hook'));
	    	}
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
    	if($hooks->slack_login_user)
    	{
    		add_action('wp_login', array($this, 'login_user_hook'), 10, 2);
    	}
    	endif;
    }

    public function http_requests()
    {
    	if(isset($_GET["page"]) && $_GET["page"] == "slack-for-wordpress" && isset($_GET["code"]))
		{
			$qs = "client_id=".$this->api->app_client_id."&client_secret=".$this->api->app_client_secret."&code=".$_GET["code"]."&redirect_uri=http://".$_SERVER['SERVER_NAME'].$_SERVER['REQUEST_URI'];
			$c = $this->make_request("https://slack.com/api/oauth.access?".$qs);
			$result = json_decode($c);
			update_option("slack_for_wp_token", $result->access_token);
			$this->api->set_auth_token($result->access_token);

			wp_safe_redirect('options-general.php?page=slack-for-wordpress');
			exit;
		}
		elseif ($_GET["page"] == "slack-for-wordpress" && isset($_GET["unlink"])) {
			$this->api->slack_logout();

			wp_safe_redirect('options-general.php?page=slack-for-wordpress');
			exit;
		}
		else if($_GET["page"] == "slack-for-wordpress" && isset($_POST["slack_options_submit"]))
		{
			$this->register_options($_POST);
		}
		else if($_GET["page"] == "slack-for-wordpress" && isset($_POST["app_client_id"]) && isset($_POST["app_client_secret"]))
		{
			update_option("slack_app_client_id", $_POST["app_client_id"]);
			update_option("slack_app_client_secret", $_POST["app_client_secret"]);
		}
    }

    public static function make_request($url)
	{
		if(function_exists('curl_version')) :
			$CURL = curl_init();

			curl_setopt($CURL, CURLOPT_URL, $url);
			curl_setopt($CURL, CURLOPT_HEADER, 0);
			curl_setopt($CURL, CURLOPT_RETURNTRANSFER, 1);

			$data = curl_exec($CURL);

			curl_close($CURL);

			return $data;
		else :
			return file_get_contents($url);
		endif;
	}

    public function getApi()
    {
    	return $this->api;
    }
    public function getVersion()
    {
    	return "1.7.1";
    }
}
