<?php defined('SYSPATH') || die('No direct script access allowed.');

$resources = array(
	'news' => array(
		'model' => 'news',
		'items' => 'entries',
		'prefix' => 'n',
		'table' => 'news_data',
		'column' => 'news_id',
		'user' => '',
		'orderby' => 'post_date',
		'orderdir' => 'desc',
		'report' => 0
	)
);