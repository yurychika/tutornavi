<?php

class Report_Controller extends Controller
{
	public function __construct()
	{
		parent::__construct();

		if ( !config::item('reports_active', 'reports') )
		{
			error::show404();
		}
		elseif ( !users_helper::isLoggedin() )
		{
			router::redirect('users/login');
		}

		loader::model('reports/reports');
		loader::model('reports/subjects', array(), 'reports_subjects_model');
	}

	public function index()
	{
		$this->submit();
	}

	public function submit()
	{
		// Does user have permission to submit reports?
		if ( !session::permission('reports_post', 'reports') )
		{
			view::setError(__('no_action', 'system'));
			view::load('system/elements/blank', array('autoclose' => true));
			return false;
		}

		$resource = uri::segment(3);
		$itemID = uri::segment(4);

		if ( !$resource || !( $resourceID = config::item('resources', 'core', $resource, 'resource_id') ) || !config::item('resources', 'core', $resource, 'report') )
		{
			view::setError(__('resource_invalid', 'system'));
			view::load('system/elements/blank', array('autoclose' => true));
			return false;
		}

		if ( !$itemID || !is_numeric($itemID) || $itemID < 0 )
		{
			view::setError(__('item_invalid', 'reports'));
			view::load('system/elements/blank', array('autoclose' => true));
			return false;
		}

		// Does this item exist?
		if ( !( $userID = $this->reports_model->getUserID($resource, $itemID) ) )
		{
			view::setError(__('item_invalid', 'reports'));
			view::load('system/elements/blank', array('autoclose' => true));
			return false;
		}

		// Did we report this already?
		if ( $this->reports_model->isReported($resourceID, $itemID) )
		{
			view::setError(__('report_exists', 'reports'));
			view::load('system/elements/blank', array('autoclose' => true));
			return false;
		}

		// Get subjects
		$subjects = array();
		$data = $this->reports_subjects_model->getSubjects(false, true);
		foreach ( $data as $subject )
		{
			$subjects[$subject['subject_id']] = $subject['name'];
		}
		$subjects = $subjects ? array('' => __('select', 'system')) + $subjects : $subjects;

		// Assign vars
		view::assign(array('subjects' => $subjects));

		// Process form values
		if ( input::post('do_submit_report') )
		{
			$this->_submitReport($resource, $resourceID, $userID, $itemID, $subjects);
		}

		// Set title
		view::setTitle(__('report_submit', 'reports'));

		// Load view
		view::load('report/index');
	}

	protected function _submitReport($resource, $resourceID, $userID, $itemID, $subjects)
	{
		// Extra rules
		$rules = array(
			'subject' => array(
				'rules' => $subjects ? array('required', 'callback__is_valid_subject' => array($subjects)) : array('callback__is_valid_subject' => array($subjects)),
			),
			'message' => array(
				'rules' => array('is_string', 'trim', 'max_length' => 255),
			),
		);

		validate::setRules($rules);

		// Validate form values
		if ( !validate::run($rules) )
		{
			return false;
		}

		// Get values
		$subject = $subjects ? (int)input::post('subject') : 0;
		$message = input::post('message');

		// Send feedback
		if ( !$this->reports_model->saveReport($resourceID, $userID, $itemID, $subject, $message) )
		{
			if ( !validate::getTotalErrors() )
			{
				view::setError(__('save_error', 'system'));
			}
			return false;
		}

		// Success
		router::redirect('report/sent' . ( input::get('modal') ? '?modal=1' : '' ));
	}

	public function sent()
	{
		view::setInfo(__('report_saved', 'reports'));

		view::load('system/elements/blank', array('autoclose' => true));
	}

	public function _is_valid_subject($subjectID, $subjects)
	{
		if ( $subjects && !isset($subjects[$subjectID]) )
		{
			validate::setError('_is_valid_subject', __('subject_invalid', 'reports'));

			return false;
		}

		return true;
	}
}
