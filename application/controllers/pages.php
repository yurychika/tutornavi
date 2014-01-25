<?php

class Pages_Controller extends Controller
{
	public function __construct()
	{
		parent::__construct();

		loader::model('pages/pages');
	}

	public function index()
	{
		// Get URI vars
		if ( !( $location = uri::getURI() ) )
		{
			$this->home();
			return;
		}
		elseif ( $location == 'site/offline' )
		{
			$this->offline();
			return;
		}

		// Get page
		if ( !( $page = $this->pages_model->getPage($location, 'in_view', array('replace' => true)) ) || ( !session::permission('site_access_cp', 'system') && !$page['active'] ) )
		{
			error::show404();
		}

		// Set page ID
		$pageID = $page['page_id'];

		// Do we have views enabled?
		if ( config::item('page_views', 'pages') )
		{
			// Update views counter
			$this->pages_model->updateViews($pageID);
		}

		// Load ratings
		if ( config::item('page_rating', 'pages') == 'stars' )
		{
			// Load votes model
			loader::model('comments/votes');

			// Get votes
			$page['user_vote'] = $this->votes_model->getVote('page', $pageID);
		}
		elseif ( config::item('page_rating', 'pages') == 'likes' )
		{
			// Load likes model
			loader::model('comments/likes');

			// Get likes
			$page['user_vote'] = $this->likes_model->getLike('page', $pageID);
		}

		// Assign vars
		view::assign(array('pageID' => $pageID, 'page' => $page));

		// Set title
		view::setTitle($page['data_title']);

		// Set meta tags
		view::setMetaDescription($page['data_meta_description']);
		view::setMetaKeywords($page['data_meta_keywords']);

		// Do we need to build a trail?
		if ( $page['trail'] )
		{
			if ( $page['parent_id'] )
			{
				$parents = $this->pages_model->getParents($page['parent_id']);
				foreach ( $parents as $parent )
				{
					view::setTrail($parent['location'], $parent['data_title']);
				}

				if ( $parents )
				{
					// Set trail
					view::setTrail($location, $page['data_title']);
				}
			}
			else
			{
				// Set trail
				view::setTrail($page['location'], $page['data_title']);
			}
		}

		// Do we have a custom file name?
		if ( $page['file_name'] )
		{
			// Load custom view
			view::load($page['file_name']);
		}
		else
		{
			// Load default view
			view::load('pages/view');
		}
	}

	public function home()
	{
		if ( users_helper::isLoggedin() )
		{
			if ( config::item('homepage_user', 'users') == 'profile' )
			{
				loader::controller('users/profile', array(), 'users_profile');
				$this->users_profile->manage();
				return;
			}
			elseif ( config::item('homepage_user', 'users') == 'timeline_public' )
			{
				loader::controller('timeline', array(), 'timeline');
				$this->timeline->browse();
				return;
			}
			elseif ( config::item('homepage_user', 'users') == 'timeline_user' )
			{
				loader::controller('timeline', array(), 'timeline');
				$this->timeline->manage();
				return;
			}
		}
		elseif ( config::item('homepage_public', 'users') == 'timeline_public' )
		{
			loader::controller('timeline', array(), 'timeline');
			$this->timeline->browse();
			return;
		}

		// Set meta tags
		$this->metatags_model->set('system', 'site_index', array(), false);

		view::load('home');
	}

	public function offline()
	{
		// Get page
		if ( !( $page = $this->pages_model->getPage('site/offline', 'in_view') ) )
		{
			error::show404();
		}

		// Do we have views enabled?
		if ( config::item('page_views', 'pages') )
		{
			// Update views counter
			$this->pages_model->updateViews($page['page_id']);
		}

		// Show offline error
		error::show($page['data_body'], 200, $page['data_title']);
	}
}
