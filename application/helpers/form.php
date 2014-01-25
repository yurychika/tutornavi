<?php defined('SYSPATH') || die('No direct script access allowed.');

class Form_Helper extends CodeBreeder_Form_Helper
{
	public static function captcha($name, $value = '')
	{
		$service = config::item('default_captcha', 'security');
		$settings = config::item('default_captcha_settings', 'security');

		// Load library
		loader::library('captchas/' . $service, $settings, 'captcha_' . $service);

		$str = codebreeder::instance()->{'captcha_' . $service}->getCaptcha($name);

		return $str;
	}
}
