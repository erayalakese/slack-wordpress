<?php
class SlackAPI_Test extends WP_UnitTestCase {

	public $plugin;

	public function setUp()
	{
		$this->plugin = new Slack_Plugin();
		
	}

	public function set_auth_token()
	{
		$this->plugin->getApi()->set_auth_token('xoxp-3759810603-3759810611-4068387830-82c423');
	}
	public function remove_auth_token()
	{
		$this->plugin->getApi()->set_auth_token(null);
	}

	public function test_getApi()
	{
		$this->assertInstanceOf('Slack_API', $this->plugin->getApi());
	}
	public function test_get_channel_list()
	{ 
		$this->remove_auth_token();
		$channels = $this->plugin->getApi()->get_channel_list();
		$this->assertFalse($channels);


		$this->set_auth_token();
		$channels = $this->plugin->getApi()->get_channel_list();
		foreach($channels as $channel)
		{
			$this->assertObjectHasAttribute("is_channel", $channel);
		}
	}
	public function test_get_group_list()
	{ 
		$this->remove_auth_token();
		$groups = $this->plugin->getApi()->get_group_list();
		$this->assertFalse($groups);


		$this->set_auth_token();
		$groups = $this->plugin->getApi()->get_group_list();
		foreach($groups as $group)
		{
			$this->assertObjectHasAttribute("is_group", $group);
		}
	}
	public function test_publish_post()
	{
		$this->remove_auth_token();
		$result = $this->plugin->getApi()->publish_post("C0422E4PG", "Dump text");
		$this->assertFalse($result->ok);
		$this->assertEquals($result->error, 'not_authed');

		$this->set_auth_token();
		$result = $this->plugin->getApi()->publish_post("xyz", "Dump text");
		$this->assertFalse($result->ok);
		$this->assertEquals($result->error, 'channel_not_found');
	}


}