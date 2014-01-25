<?php

$config['configs'] = array();

$config['helpers'] = array(
	'array' => true,
	'text' => true,
	'html' => true,
	'form' => true,
	'date' => true,
	'users/users' => true,
	'system/storage' => true,
	'system/geo' => true,
	'banners/banners' => true,
	'pages/pages' => true,
	'timeline/timeline' => true,
);

$config['libraries'] = array(
	'cache' => true,
	'db' => true,
	'bootstrap' => true,
);

$config['models'] = array(
	'system/fields' => true,
	'system/storage' => true,
	'system/metatags' => true,
	'system/counters' => true,
	'system/hooks' => true,
	'system/search' => true,
	'system/geo' => true,
	'users/users' => true,
	'users/friends' => 'users_friends_model',
	'users/blocked' => 'users_blocked_model',
);
