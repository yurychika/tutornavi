<?php defined('SYSPATH') || die('No direct script access allowed.');

$config = array(
	'general' => array(
		'news_active' => array(
			'type' => 'boolean',
			'value' => 1
		),
		'news_blog' => array(
			'type' => 'boolean',
			'value' => 0
		),
		'news_per_page' => array(
			'type' => 'number',
			'value' => 20,
			'required' => 1
		),
		'news_rating' => array(
			'type' => 'select',
			'value' => "likes",
			'items' => array(
				'' => "rating_none",
				'likes' => "rating_likes",
				'stars' => "rating_stars"
			)
		),
		'news_views' => array(
			'type' => 'boolean',
			'value' => 1
		),
		'news_preview_chars' => array(
			'type' => 'number',
			'value' => 500
		)
	),
	'comments' => array(
		'news_comments' => array(
			'type' => 'boolean',
			'value' => 1
		)
	)
);