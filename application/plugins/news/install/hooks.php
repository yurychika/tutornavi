<?php defined('SYSPATH') || die('No direct script access allowed.');

$hooks = array(
	'action' => array(
		'system/languages/install' => array(
			array(
				'path' => 'news',
				'object' => 'languages',
				'function' => 'install'
			)
		),
		'system/languages/uninstall' => array(
			array(
				'path' => 'news',
				'object' => 'languages',
				'function' => 'uninstall'
			)
		)
	)
);