<?php

class Messages_Controller extends Controller
{
	public function __construct()
	{
		parent::__construct();

		if ( !config::item('messages_active', 'messages') )
		{
			error::show404();
		}
		elseif ( !users_helper::isLoggedin() )
		{
			router::redirect('users/login');
		}
		elseif ( !session::permission('messages_access', 'messages') )
		{
			view::noAccess();
		}

		// Set trail
		view::setTrail(session::item('slug'), __('my_profile', 'system_navigation'));
		view::setTrail('messages/manage', __('messages', 'system_navigation'));

		loader::model('messages/messages');
	}

	public function index()
	{
		$this->manage();
	}

	public function manage()
	{
		// Does user have permission to browse messages?
		if ( !session::permission('messages_browse', 'messages') )
		{
			view::noAccess();
		}

		// Parameters
		$params = array(
			'join_columns' => array(
				'`r`.`deleted`=0',
				'`r`.`recipient_id`=' . session::item('user_id'),
			),
			'total' => session::item('total_conversations'),
		);

		// Process filters
		$params = $this->parseCounters($params);

		// Process query string
		$qstring = $this->parseQuerystring($params['max']);

		// Get conversations
		$conversations = array();
		if ( $params['total'] )
		{
			$conversations = $this->messages_model->getConversations($params['join_columns'], $qstring['order'], $qstring['limit'], $params);
		}

		// Set pagination
		$config = array(
			'base_url' => config::siteURL('messages/manage?' . $qstring['url']),
			'total_items' => $params['total'],
			'items_per_page' => config::item('messages_per_page', 'messages'),
			'current_page' => $qstring['page'],
			'uri_segment' => 'page',
		);
		$pagination = loader::library('pagination', $config, null);

		// Assign vars
		view::assign(array('conversations' => $conversations, 'pagination' => $pagination));

		// Set title
		view::setTitle(__('my_messages', 'system_navigation'));

		// Assign actions
		//view::setAction('messages/send', __('message_new', 'messages'), array('class' => 'icon-text icon-messages-new'));

		// Load view
		view::load('messages/index');
	}

	public function template()
	{
		// Get URI vars
		$templateID = (int)uri::segment(3);

		// Load templates model
		loader::model('messages/templates', array(), 'messages_templates_model');

		// Get template
		$template = $this->messages_templates_model->getTemplate($templateID, false);

		if ( $template )
		{
			view::ajaxResponse(array('subject' => $template['subject'], 'message' => $template['message']));
		}
		else
		{
			view::ajaxError(__('no_template', 'messages_templates'), 2000);
		}
	}

	public function send()
	{
		// Get URI vars
		$slugID = utf8::trim(urldecode(uri::segment(3)));

		// Do we have a slug ID?
		if ( $slugID )
		{
			if ( !( $user = $this->users_model->getUser($slugID) ) )
			{
				view::setError(__('no_user', 'users'));
				router::redirect('messages/manage');
			}

			// Does user have permission to send messages to this user group?
			if ( !session::permission('messages_send', 'messages') || !in_array($user['group_id'], session::permission('messages_send', 'messages')) )
			{
				view::noAccess();
			}
			// Are we trying to send a message to ourselves?
			elseif ( $user['user_id'] == session::item('user_id') )
			{
				view::setError(__('message_recipients_self', 'messages'));
				router::redirect('messages/manage');
			}
			// Do we require credits to send messages?
			elseif ( config::item('credits_active', 'billing') && session::permission('messages_credits', 'messages') && session::permission('messages_credits', 'messages') > session::item('total_credits') )
			{
				view::setError(__('no_credits', 'system', array(), array('%' => html_helper::anchor('billing/credits', '\1'))));
				router::redirect('messages/manage');
			}
		}
		else
		{
			router::redirect('messages/manage');
		}

		// Did user reach the max messages limit?
		if ( session::permission('messages_limit', 'messages') && session::permission('messages_limit', 'messages') <= session::item('total_conversations') )
		{
			view::setError(__('message_limit_reached', 'messages', array('%limit%' => session::permission('messages_limit', 'messages'))));
			router::redirect('messages/manage');
		}

		// Did we block this user or did they block us?
		if ( config::item('blacklist_active', 'users') && ( $blocked = $this->users_blocked_model->getUser($user['user_id']) ) )
		{
			if ( $blocked['user_id'] == session::item('user_id') )
			{
				view::setError(__('user_blocked', 'users'));
			}
			else
			{
				view::setError(__('user_blocked_self', 'users'));
			}

			router::redirect($user['slug']);
		}

		// Get templates
		$templates = array();
		if ( session::permission('messages_templates', 'messages') )
		{
			loader::model('messages/templates', array(), 'messages_templates_model');
			$templates = $this->messages_templates_model->getTemplates(true, true);
		}

		// Assign vars
		view::assign(array('user' => $slugID ? $user : array(), 'templates' => $templates));

		// Process form values
		if ( input::post('do_save_conversation') )
		{
			$this->_saveConversation($slugID ? $user : array());
		}

		// Set title
		view::setTitle(__('message_send', 'messages'));

		// Load view
		view::load('messages/send');
	}

	protected function _saveConversation($user)
	{
		// Create rules
		$rules = array();

		if ( !$user )
		{
			$rules['recipients'] = array(
				'label' => __('message_send_to', 'messages'),
				'rules' => array('trim', 'required', 'callback__is_valid_recipients')
			);
		}

		$rules['subject'] = array(
			'label' => __('message_subject', 'messages'),
			'rules' => array('trim', 'required', 'max_length' => 255)
		);
		$rules['message'] = array(
			'label' => __('message', 'messages'),
			'rules' => array('trim', 'required', 'callback__is_messages_delay')
		);

		// Do we have character limit?
		if ( session::permission('messages_characters_limit', 'messages') )
		{
			$rules['message']['rules']['max_length'] = session::permission('messages_characters_limit', 'messages');
		}

		// Assign rules
		validate::setRules($rules);

		// Validate fields
		if ( !validate::run() )
		{
			return false;
		}

		// Get input data
		$recipients = $user ? array($user['user_id']) : explode(',', input::post('recipients'));
		$subject = input::post('subject');
		$message = input::post('message');

		// One recipient
		if ( $user )
		{
			$users = array($user);
		}
		// Multiple recipients
		else
		{
			// Parameters
			/*
			$params = array(
				'limit' => count($recipients),
				'max' => count($recipients),
				'fields' => '',
				'profiles' => true,
				'config' => true,
				'escape' => true,
				'joincolumns' => array(
					'`u`.`user_id` IN (' . implode(',', $recipients) . ')',
					'`u`.`verified`=1',
					'`u`.`active`=1',
				),
			);
			$users = $this->users_model-> getUsers($params);
			*/
		}

		// Save conversation
		if ( !( $conversationID = $this->messages_model->saveConversation(0, $subject, $message, $recipients) ) )
		{
			if ( !validate::getTotalErrors() )
			{
				view::setError(__('save_error', 'system'));
			}
			return false;
		}

		// Create email replacement tags
		$tags = array();
		foreach ( session::section('session') as $key => $value )
		{
			$tags['from.' . $key] = $value;
		}
		$tags['conversation_link'] = config::siteURL('messages/view/' . $conversationID);

		loader::library('email');
		foreach ( $users as $user )
		{
			// Send new private message email
			if ( !isset($user['config']['notify_messages']) || $user['config']['notify_messages'] )
			{
				$this->email->sendTemplate('messages_new', $user['email'], array_merge($tags, $user), $user['language_id']);
			}
		}

		// Success
		view::setInfo(__('message_sent', 'messages'));
		router::redirect('messages/view/' . $conversationID);
	}

	public function view()
	{
		// Get URI vars
		$conversationID = (int)uri::segment(3);

		// Get conversation
		if ( !$conversationID || !( $conversation = $this->messages_model->getConversation($conversationID, session::item('user_id')) ) || $conversation['deleted'] )
		{
			view::setError(__('no_conversation', 'messages'));
			router::redirect('messages/manage');
		}

		if ( $conversation['user_id'] != session::item('user_id') && !in_array($conversation['users'][$conversation['user_id']]['group_id'], session::permission('messages_view', 'messages')) )
		{
			view::noAccess();
		}

		// Mark conversation as read
		if ( $conversation['new'] )
		{
			$this->messages_model->markRead($conversationID, session::item('user_id'));
		}

		// Assign vars
		view::assign(array('conversationID' => $conversationID, 'conversation' => $conversation));

		// Process form values
		if ( input::post('do_save_message') )
		{
			$this->_saveMessage($conversationID, $conversation);
		}

		// Set title
		view::setTitle($conversation['subject']);

		// Do we have more than 1 recipient?
		if ( $conversation['total_recipients'] > 1 )
		{
			// Assign actions
			view::setAction('messages/people/' . $conversationID, __('conversation_participants', 'messages'), array('class' => 'icon-text icon-messages-people'));
		}

		// Load view
		view::load('messages/view');
	}

	protected function _saveMessage($conversationID, $conversation)
	{
		// Are we allowed to reply?
		if ( $conversation['user_id'] == session::item('user_id') && !session::permission('messages_reply', 'messages') || !in_array($conversation['users'][$conversation['user_id']]['group_id'], session::permission('messages_reply', 'messages')) )
		{
			view::setError(__('no_action', 'system'));
			return false;
		}
		// Do we require credits to send messages?
		elseif ( config::item('credits_active', 'billing') && session::permission('messages_credits', 'messages') && session::permission('messages_credits', 'messages') > session::item('total_credits') )
		{
			view::setError(__('no_credits', 'system', array(), array('%' => html_helper::anchor('billing/credits', '\1'))));
			return false;
		}

		// Create rules
		$rules = array(
			'message' => array(
				'label' => __('message', 'messages'),
				'rules' => array('trim', 'required', 'callback__is_messages_delay')
			),
		);

		// Do we have character limit?
		if ( session::permission('messages_characters_limit', 'messages') )
		{
			$rules['message']['rules']['max_length'] = session::permission('messages_characters_limit', 'messages');
		}

		// Assign rules
		validate::setRules($rules);

		// Validate fields
		if ( !validate::run() )
		{
			return false;
		}

		// Get input data
		$message = input::post('message');

		// Save message
		if ( !( $messageID = $this->messages_model->saveMessage(0, $conversationID, $message, $conversation['recipients']) ) )
		{
			if ( !validate::getTotalErrors() )
			{
				view::setError(__('save_error', 'system'));
			}
			return false;
		}

		// Create email replacement tags
		$tags = array();
		foreach ( session::section('session') as $key => $value )
		{
			$tags['from.' . $key] = $value;
		}
		$tags['conversation_link'] = config::siteURL('messages/view/' . $conversationID);

		// Send new private message email
		loader::library('email');
		foreach ( $conversation['users'] as $user )
		{
			if ( $user['user_id'] != session::item('user_id') && ( !isset($user['config']['notify_messages']) || $user['config']['notify_messages'] ) )
			{
				$this->email->sendTemplate('messages_new', $user['email'], array_merge($tags, $user), $user['language_id']);
			}
		}

		// Success
		view::setInfo(__('message_sent', 'messages'));
		router::redirect('messages/view/' . $conversationID);
	}

	public function people()
	{
		// Get URI vars
		$conversationID = (int)uri::segment(3);

		// Get conversation
		if ( !$conversationID || !( $conversation = $this->messages_model->getConversation($conversationID, session::item('user_id'), array('messages' => false, 'recipients' => false)) ) || $conversation['deleted'] )
		{
			view::setError(__('no_conversation', 'messages'));
			router::redirect('messages/manage');
		}

		// Get fields
		$fields = array();
		foreach ( config::item('usertypes', 'core', 'keywords') as $categoryID => $keyword )
		{
			$fields[$categoryID] = $this->fields_model->getFields('users', $categoryID, 'view', 'in_list');
		}

		// Get participants
		$participants = $this->messages_model->getPeople($conversationID, $conversation['total_recipients']);

		// Assign vars
		view::assign(array('conversationID' => $conversationID, 'fields' => $fields, 'conversation' => $conversation, 'participants' => $participants));

		// Set title
		view::setTitle(__('conversation_participants', 'messages'));

		// Load view
		view::load('messages/people');
	}

	public function delete()
	{
		// Get URI vars
		$conversationID = (int)uri::segment(3);

		// Get conversation
		if ( !$conversationID || !( $conversation = $this->messages_model->getConversation($conversationID, session::item('user_id'), array('messages' => false, 'recipients' => false)) ) || $conversation['deleted'] )
		{
			view::setError(__('no_conversation', 'messages'));
			router::redirect('messages/manage');
		}

		// Delete conversation
		$this->messages_model->deleteConversation($conversationID, session::item('user_id'), $conversation);

		// Process query string
		$qstring = $this->parseQuerystring();

		// Success
		view::setInfo(__('conversation_deleted', 'messages'));
		router::redirect('messages/manage?' . $qstring['url'].'page=' . $qstring['page']);
	}

	protected function parseCounters($params)
	{
		// Do we have any conversations?
		if ( !$params['total'] )
		{
			view::setInfo(__('no_messages_self', 'messages'));
		}

		$params['max'] = $params['total'];

		return $params;
	}

	protected function parseQuerystring($max = 0)
	{
		$qstring = array();

		// Set max page
		$maxpage = $max ? ceil($max / config::item('messages_per_page', 'messages')) : 0;

		// Get current page
		$qstring['page'] = (int)input::get('page', 1);
		$qstring['page'] = $qstring['page'] > 0 ? ( !$maxpage || $qstring['page'] <= $maxpage ? $qstring['page'] : $maxpage ) : 1;

		// Get search id
		$qstring['search_id'] = input::get('search_id');

		// Get order field and direction
		$qstring['orderby'] = input::post_get('o') && in_array(input::post_get('o'), array('last_post_date')) ? input::post_get('o') : 'last_post_date';
		$qstring['orderdir'] = input::post_get('d') && in_array(input::post_get('d'), array('asc', 'desc')) ? input::post_get('d') : 'desc';
		$qstring['order'] = $qstring['orderby'] ? array($qstring['orderby'] => $qstring['orderdir']) : array();

		// Create url string
		$qstring['url'] = ( $qstring['search_id'] ? 'search_id=' . $qstring['search_id'] . '&' : '' ) .
			( $qstring['orderby'] ? 'o=' . $qstring['orderby'] . '&' : '' ) .
			( $qstring['orderby'] && $qstring['orderdir'] ? 'd=' . $qstring['orderdir'] . '&' : '' );

		// Set limit
		$from = ( $qstring['page'] - 1 ) * config::item('messages_per_page', 'messages');
		$qstring['limit'] = ( !$max || $from <= $max ? $from : $max ) . ', ' . config::item('messages_per_page', 'messages');

		// Assign vars
		view::assign(array('qstring' => $qstring));

		return $qstring;
	}

	public function _is_valid_recipients($recipients)
	{
		$recipients = array_unique(explode(',', preg_replace('/[^0-9\,]/', '', $recipients)));

		if ( !$recipients )
		{
			validate::setError('_is_valid_recipients', __('no_recipients', 'messages'));
			return false;
		}

		if ( in_array(session::item('user_id'), $recipients) )
		{
			validate::setError('_is_valid_recipients', __('message_recipients_self', 'messages'));
			return false;
		}
		elseif ( $this->messages_model->verifyRecipients($recipients) != count($recipients) )
		{
			validate::setError('_is_valid_recipients', __('message_recipients_mismatch', 'messages'));
			return false;
		}

		return implode(',', $recipients);
	}

	public function _is_messages_delay()
	{
		if ( session::permission('messages_delay_limit', 'messages') )
		{
			$messages = $this->messages_model->countRecentMessages();

			if ( $messages >= session::permission('messages_delay_limit', 'messages') )
			{
				validate::setError('_is_messages_delay', __('messages_delay_reached', 'messages', array(
					'%messages' => session::permission('messages_delay_limit', 'messages'),
					'%time' => session::permission('messages_delay_time', 'messages'),
					'%type' => utf8::strtolower(__(( session::permission('messages_delay_type', 'messages') == 'minutes' ? 'minute' : 'hour' ) . ( session::permission('messages_delay_time', 'messages') > 1 ? 's' : '' ), 'date'))
					)));
				return false;
			}
		}

		return true;
	}
}
