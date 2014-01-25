<?php defined('SYSPATH') || die('No direct script access allowed.');

$language = array(
	'news' => array(
		'config' => array(
			'cp' => array(
				'news_active' => "Enable news",
				'news_blog' => "Use news as site blog",
				'news_comments' => "Enable comments",
				'news_per_page' => "News per page",
				'news_preview_chars' => "News preview length",
				'news_rating' => "News rating",
				'news_views' => "Count news views"
			),
		),
		'metatags' => array(
			'cp' => array(
				'news_index' => "Browse news"
			),
		),
		'news' => array(
			'ca' => array(
				'no_entries' => "There are no news at this time."
			),
			'cp' => array(
				'entry_delete?' => "Are you sure you want to delete this entry?",
				'entry_deleted' => "News entry has been successfully deleted.",
				'entry_edit' => "Edit entry",
				'entry_new' => "Add entry",
				'entry_saved' => "News entry has been successfully saved.",
				'no_entry' => "News entry does not seem to exist."
			),
		),
		'permissions' => array(
			'cp' => array(
				'news_access' => "Access news",
				'news_manage' => "Manage news",
				'news_search' => "Search news"
			),
		),
	),
	'system' => array(
		'navigation' => array(
			'ca' => array(
				'blog' => "Blog",
				'news' => "News",
				'news_new' => "Latest news"
			),
			'cp' => array(
				'news_manage' => "Manage news"
			),
		),
	)
);

$fields = array(
	0 => array(
		'title' => array(
			'name_english' => "Title",
			'vname_english' => "",
			'sname_english' => "",
			'validate_error_english' => ""
		),
		'body' => array(
			'name_english' => "Page body",
			'vname_english' => "",
			'sname_english' => "",
			'validate_error_english' => ""
		),
		'meta_keywords' => array(
			'name_english' => "Keywords (meta)",
			'vname_english' => "",
			'sname_english' => "",
			'validate_error_english' => ""
		),
		'meta_description' => array(
			'name_english' => "Description (meta)",
			'vname_english' => "",
			'sname_english' => "",
			'validate_error_english' => ""
		)
	)
);

$email_templates = array(

);

$meta_tags = array(
	'news_index' => array(
		'meta_title_english' => "Browse news",
		'meta_description_english' => "Browse news.",
		'meta_keywords_english' => "news"
	)
);