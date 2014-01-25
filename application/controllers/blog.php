<?php

class Blog_Controller extends News_Controller
{
	public function __construct()
	{
		parent::__construct();

		if ( !config::item('news_blog', 'news') && uri::segment(1) != 'news' )
		{
			router::redirect('news/' . utf8::substr(uri::getURI(), 5));
		}
	}
}
