<?php

class Captchas_Recaptcha extends Library
{
	protected $config = array();

	public function __construct($config = array())
	{
		parent::__construct();

		$this->config = $config;
		$this->config['driver'] = 'recaptcha';

		loader::library('captcha', $this->config);
	}

	public function getManifest()
	{
		$params = array(
			'name' => 'Recaptcha',
			'description' => 'Form protection powered by ' . html_helper::anchor('http://www.google.com/recaptcha', 'reCaptcha', array('target' => '_blank')) . ' service.',
			'settings' => array(
				array(
					'name' => 'Public key',
					'keyword' => 'public_key',
					'type' => 'text',
					'value' => '',
				),
				array(
					'name' => 'Private key',
					'keyword' => 'private_key',
					'type' => 'text',
					'value' => '',
				),
				array(
					'name' => 'Theme',
					'keyword' => 'theme',
					'type' => 'select',
					'items' => array(
						'' => 'Default',
						'blackglass' => 'Black glass',
						'clean' => 'Clean',
						'white' => 'White',
					),
					'value' => 'clean',
				),
			),
		);

		return $params;
	}

	public function validateSettings($settings)
	{
		return $settings;
	}

	public function getCaptcha($name = '')
	{
		return $this->captcha->create();
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