<?php

class Timeline_Notices_Controller extends Controller
{
	public function __construct()
	{
		parent::__construct();

		if ( !config::item('timeline_active', 'timeline') || !users_helper::isLoggedin() )
		{
			error::show404();
		}

		loader::model('timeline/notices', array(), 'timeline_notices_model');
	}

	public function index()
	{
		$this->browse();
	}

	public function browse()
	{
		// Get user and last notice ID
		$lastID = (int)input::post_get('last_id', 0);

		// Get notices
		$notices = $this->timeline_notices_model->getNotices(session::item('user_id'), $lastID, config::item('notices_per_page', 'timeline'));

		// Set title
		view::setTitle(__('my_timeline_notifications', 'system_navigation'));

		// Dow we have new notifications?
		if ( session::item('total_notices_new') )
		{
			// Reset new notifications counter
			$this->timeline_notices_model->resetCounter();
		}

		// Load view
		if ( input::isAjaxRequest() )
		{
			$output = view::load('timeline/notices/items', array('notices' => $notices), true);

			view::ajaxResponse($output);
		}
		else
		{
			view::load('timeline/notices/index', array('notices' => $notices));
		}
	}

	public function recent()
	{
		// Load view
		if ( input::isAjaxRequest() )
		{
			// Get notices
			$notices = $this->timeline_notices_model->getNotices(session::item('user_id'), 0, 5);

			$output = view::load('timeline/notices/recent', array('notices' => $notices), true);

			// Dow we have new notifications?
			if ( session::item('total_notices_new') )
			{
				// Reset new notifications counter
				$this->timeline_notices_model->resetCounter();
			}

			view::ajaxResponse($output);
		}
	}
}
