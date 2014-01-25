<?php defined('SYSPATH') || die('No direct script access allowed.');

$permissions = array(
	'ca' => array(
		'news_access' => array(
			'type' => 'boolean',
			'guests' => '1',
			'group_admin' => '1',
			'group_guests' => '1',
			'group_default' => '1',
			'group_cancelled' => '0'
		),
		'news_search' => array(
			'type' => 'boolean',
			'guests' => '1',
			'group_admin' => '1',
			'group_guests' => '1',
			'group_default' => '1',
			'group_cancelled' => '0'
		)
	),
	'cp' => array(
		'news_manage' => array(
			'type' => 'boolean',
			'guests' => '0',
			'group_admin' => '1',
			'group_guests' => '0',
			'group_default' => '0',
			'group_cancelled' => '0'
		),
		'fields_manage' => array(
			'type' => 'boolean',
			'guests' => '0',
			'group_admin' => '1',
			'group_guests' => '0',
			'group_default' => '0',
			'group_cancelled' => '0'
		),
		'settings_manage' => array(
			'type' => 'boolean',
			'guests' => '0',
			'group_admin' => '1',
			'group_guests' => '0',
			'group_default' => '0',
			'group_cancelled' => '0'
		)
	)
);