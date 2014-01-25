<?php

class Authentication_Default extends Library
{
	protected $config = array();
	protected $facebook = array();

	public function __construct($config = array())
	{
		parent::__construct();

		$this->config = $config;
	}

	public function getManifest()
	{
		$params = array(
			'name' => 'Default',
			'description' => 'Default software authentication method using email and password.',
			'settings' => array(),
		);

		return $params;
	}
}