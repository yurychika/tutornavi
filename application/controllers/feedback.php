<?php

class Feedback_Controller extends Controller
{
	public function __construct()
	{
		parent::__construct();

		if ( !config::item('feedback_active', 'feedback') )
		{
			error::show404();
		}
		elseif ( !session::permission('feedback_access', 'feedback') )
		{
			view::noAccess();
		}

		loader::model('feedback/feedback');
	}

	public function index()
	{
		// Process form values
		if ( input::post('do_send_feedback') )
		{
			$this->_sendFeedback();
		}

		// Set meta tags
		$this->metatags_model->set('feedback', 'feedback_index');

		// Set title
		view::setTitle(__('feedback_contact', 'system_navigation'), false);

		// Load view
		view::load('feedback/index');
	}

	protected function _sendFeedback()
	{
		// Check if demo mode is enabled
		if ( input::demo() ) return false;

		// Extra rules
		$rules = array(
			'name' => array(
				'rules' => array('required', 'is_string', 'trim', 'min_length' => 2, 'max_length' => 255),
			),
			'email' => array(
				'rules' => array('required', 'is_string', 'trim', 'valid_email', 'min_length' => 4, 'max_length' => 255),
			),
			'subject' => array(
				'rules' => array('required', 'is_string', 'trim', 'min_length' => 5, 'max_length' => 255),
			),
			'message' => array(
				'rules' => array('required', 'is_string', 'trim', 'min_length' => 10, 'max_length' => 10000),
			),
		);

		if ( config::item('feedback_captcha', 'feedback') == 1 || config::item('feedback_captcha', 'feedback') == 2 && !users_helper::isLoggedin() )
		{
			$rules['captcha'] = array('rules' => array('is_captcha'));
		}

		validate::setRules($rules);

		// Validate form values
		if ( !validate::run($rules) )
		{
			return false;
		}

		// Get values
		$email = input::post('email');
		$subject = input::post('subject');
		$message = input::post('message') . "\n\n--\n" . input::post('name') . ' <' . input::post('email') . '>' . "\n" . input::ipaddress();

		// Send feedback
		if ( !$this->feedback_model->sendFeedback($email, $subject, $message) )
		{
			if ( !validate::getTotalErrors() )
			{
				view::setError(__('send_error', 'system'));
			}
			return false;
		}

		// Success
		view::setInfo(__('message_sent', 'feedback'));
		router::redirect('feedback');
	}
}
