<?php defined('SYSPATH') || die('No direct script access allowed.');

$lists = array(
	'cp_top_nav' => array(
		'content/news' => array(
			'parent' => 'content',
			'uri' => 'content/news',
			'name' => 'news|system_navigation',
			'attr' => '{"class":"news"}',
			'order_id' => 0
		),
		'content/news/manage' => array(
			'parent' => 'content/news',
			'uri' => 'content/news',
			'name' => 'news_manage|system_navigation',
			'attr' => '',
			'order_id' => 1
		),
		'content/news/fields' => array(
			'parent' => 'content/news',
			'uri' => 'system/fields/news',
			'name' => 'system_fields|system_navigation',
			'attr' => '',
			'order_id' => 2
		),
		'content/news/settings' => array(
			'parent' => 'content/news',
			'uri' => 'system/config/news',
			'name' => 'system_settings|system_navigation',
			'attr' => '',
			'order_id' => 3
		)
	),
	'site_bottom_nav' => array(
		'site/news' => array(
			'parent' => '',
			'uri' => 'news',
			'name' => 'news|system_navigation',
			'attr' => '{"class":"news"}',
			'order_id' => 0
		)
	)
);