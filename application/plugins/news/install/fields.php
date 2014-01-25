<?php defined('SYSPATH') || die('No direct script access allowed.');

$fields = array(
	0 => array(
		'title' => array(
			'type' => 'text',
			'required' => 1,
			'system' => 1,
			'multilang' => 1,
			'config' => array(
				'in_view' => 1,
				'in_list' => 1,
				'min_length' => 3,
				'max_length' => 255,
				'in_search' => 0,
				'in_search_advanced' => 0
			)
		),
		'body' => array(
			'type' => 'textarea',
			'required' => 1,
			'system' => 1,
			'multilang' => 1,
			'config' => array(
				'in_view' => 1,
				'in_list' => 1,
				'min_length' => 0,
				'max_length' => 65535,
				'html' => 1,
				'in_search' => 0,
				'in_search_advanced' => 0
			)
		),
		'meta_keywords' => array(
			'type' => 'text',
			'required' => 0,
			'system' => 1,
			'multilang' => 1,
			'config' => array(
				'in_view' => 1,
				'in_list' => 1,
				'min_length' => 0,
				'max_length' => 255,
				'in_search' => 0,
				'in_search_advanced' => 0
			)
		),
		'meta_description' => array(
			'type' => 'text',
			'required' => 0,
			'system' => 1,
			'multilang' => 1,
			'config' => array(
				'in_view' => 1,
				'in_list' => 1,
				'min_length' => 0,
				'max_length' => 255,
				'in_search' => 0,
				'in_search_advanced' => 0
			)
		)
	)
);