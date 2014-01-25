<?php

class Captchas_Basic extends Library
{
	protected $config = array();

	public function __construct($config = array())
	{
		parent::__construct();

		$this->config = $config;
		$this->config['driver'] = 'basic';

		loader::library('captcha', $this->config);
	}

	public function getManifest()
	{
		$params = array(
			'name' => 'Basic captcha',
			'description' => 'Basic form protection library.',
			'settings' => array(
				array(
					'name' => 'Complexity',
					'keyword' => 'complexity',
					'type' => 'select',
					'items' => array(
						'3' => '3',
						'4' => '4',
						'5' => '5',
					),
					'value' => '4',
				),
				array(
					'name' => 'Case sensitive',
					'keyword' => 'case',
					'type' => 'boolean',
					'value' => '0',
				),
			),
		);

		return $params;
	}

	public function validateSettings($settings)
	{
		return $settings;
	}

	public function isInput()
	{
		return true;
	}

	public function getCaptcha($name = '')
	{
		$str = form_helper::text($name, '', array('class' => 'text captcha basic'));

		$str .= $this->captcha->create();

		return $str;
	}

	public function reloadCaptcha()
	{
		return $this->captcha->create();
	}

	public function validateCaptcha($value)
	{
		return $this->captcha->verify($value);
	}
}