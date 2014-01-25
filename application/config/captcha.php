<?php defined('SYSPATH') || die('No direct script access allowed.');

$config['driver'] = 'basic';

$config['basic'] = array(
	'width' => 150,
	'height' => 28,
	'complexity' => 4,
	'case' => true,
	'fontpath' => SYSPATH . 'fonts/',
	'fonts' => array('DejaVuSerif.ttf'),
);

$config['recaptcha'] = array(
	'public_key' => '',
	'private_key' => '',
	'theme' => '',
);
