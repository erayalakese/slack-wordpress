<?php

class SlackPlugin_Test extends WP_UnitTestCase {

	public $plugin;

	public function setUp()
	{
		$this->plugin = new Slack_Plugin();
	}

	public function testVersion()
	{
		$this->assertEquals($this->plugin->getVersion(), "0.0.1");
	}

	public function test_waiting_comment_hook()
	{
		
	}

}