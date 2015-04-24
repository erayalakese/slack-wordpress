<?php

class SlackPlugin_Test extends WP_UnitTestCase {

	public $plugin;

	public function setUp()
	{
		$this->plugin = new Slack_Plugin();
	}

	public function testVersion()
	{
		$this->assertEquals($this->plugin->getVersion(), "1.4.1");
	}

	public function test_waiting_comment_hook()
	{
		
	}

	public function test_register_options()
	{
		$this->plugin->register_options(array('test'=>'data'));
		$result = json_decode(get_option('slack_options'));
		$this->assertObjectHasAttribute('test', $result);
		$result = null;

		$this->plugin->register_options('data');
		$result = get_option('slack_options');
		$this->assertEquals('data', $result);

	}

	public function test_get_options()
	{
		$this->plugin->register_options(array('test'=>'data'));
		$result = $this->plugin->get_options();
		$this->assertObjectHasAttribute('test', $result);
		$result = null;

		$this->plugin->register_options('data');
		$result = $this->plugin->get_options();
		$this->assertEquals('data', $result);
	}

}