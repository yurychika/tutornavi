<?php

class Captcha_Controller extends Controller
{
	public function __construct()
	{
		parent::__construct();
	}

	public function index()
	{
		$service = config::item('default_captcha', 'security');
		$settings = config::item('default_captcha_settings', 'security');

		// Load library
		$captcha = loader::library('captcha', $settings, null);

		if ( uri::segment(3) == 'reload' )
		{
			$captcha->create();
		}

		echo $captcha->render();
		exit;
	}
}
