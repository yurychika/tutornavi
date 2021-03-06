<?php

class CP_Plugins_Messages_Controller extends Controller
{
	public $messagesPerPage = 30;

	public function __construct()
	{
		parent::__construct();

		// Does user have permission to access this plugin?
		if ( !session::permission('messages_manage', 'messages') )
		{
			view::noAccess();
		}

		view::setCustomParam('section', 'plugins');
		view::setCustomParam('options', config::item('cp_top_nav', 'lists', 'plugins', 'items', 'plugins/messages', 'items'));

		view::setTrail('cp/system/plugins', __('plugins', 'system_navigation'));
		view::setTrail('cp/plugins/messages', __('messages', 'system_navigation'));

		loader::model('messages/messages');
	}

	public function index()
	{
		$this->browse();
	}

	public function browse()
	{
		// Parameters
		$params = array(
			'join_columns' => array(),
		);

		// Process filters
		$params = $this->parseCounters($params);

		// Process query string
		$qstring = $this->parseQuerystring($params['total']);

		// Actions
		$actions = array(
			0 => __('select', 'system'),
			'delete' => __('delete', 'system'),
		);

		// Check form action
		if ( input::post('do_action') )
		{
			// Delete selected messages
			if ( input::post('action') == 'delete' )
			{
				if ( input::post('message_id') && is_array(input::post('message_id')) )
				{
					foreach ( input::post('message_id') as $messageID )
					{
						$messageID = (int)$messageID;
						if ( $messageID && $messageID > 0 )
						{
							$this->delete($messageID);
						}
					}
				}
			}

			// Success
			view::setInfo(__('action_applied', 'system'));
			router::redirect('cp/plugins/messages?' . $qstring['url'] . 'page=' . $qstring['page']);
		}

		// Get messages
		$messages = array();
		if ( $params['total'] )
		{
			$messages = $this->messages_model->getMessages($params['join_columns'], $qstring['order'], $qstring['limit']);
		}

		// Create table grid
		$grid = array(
			'uri' => 'cp/plugins/messages',
			'keyword' => 'messages',
			'header' => array(
				'check' => array(
					'html' => 'message_id',
					'class' => 'check',
				),
				'message' => array(
					'html' => __('message', 'messages'),
					'class' => 'name',
				),
				'user' => array(
					'html' => __('user', 'system'),
					'class' => 'user',
				),
				'post_date' => array(
					'html' => __('post_date', 'system'),
					'class' => 'date',
					'sortable' => true,
				),
				'actions' => array(
					'html' => __('actions', 'system'),
					'class' => 'actions',
				),
			),
			'content' => array()
		);

		// Create grid content
		foreach ( $messages as $message )
		{
			$grid['content'][] = array(
				'check' => array(
					'html' => $message['message_id'],
				),
				'message' => array(
					'html' => html_helper::anchor('cp/plugins/messages/edit/' . $message['message_id'], text_helper::truncate($message['message'], 64)),
				),
				'user' => array(
					'html' => users_helper::anchor($message['user']),
				),
				'post_date' => array(
					'html' => date_helper::formatDate($message['post_date']),
				),
				'actions' => array(
					'html' => array(
						'edit' => html_helper::anchor('cp/plugins/messages/edit/' . $message['message_id'], __('edit', 'system'), array('class' => 'edit')),
						'delete' => html_helper::anchor('cp/plugins/messages/delete/' . $message['message_id'] . '?' . $qstring['url'] . 'page=' . $qstring['page'], __('delete', 'system'), array('data-html' => __('message_delete?', 'messages'), 'data-role' => 'confirm', 'class' => 'delete')),
					),
				),
			);
		}

		// Set pagination
		$config = array(
			'base_url' => config::siteURL('cp/plugins/messages?' . $qstring['url']),
			'total_items' => $params['total'],
			'items_per_page' => $this->messagesPerPage,
			'current_page' => $qstring['page'],
			'uri_segment' => 'page',
		);
		$pagination = loader::library('pagination', $config, null);

		// Filter hooks
		hook::filter('cp/plugins/messages/browse/grid', $grid);
		hook::filter('cp/plugins/messages/browse/actions', $actions);

		// Assign vars
		view::assign(array('grid' => $grid, 'actions' => $actions, 'pagination' => $pagination));

		// Set title
		view::setTitle(__('messages_manage', 'system_navigation'));

		// Set trail
		if ( $qstring['search_id'] )
		{
			view::setTrail('cp/plugins/messages?' . $qstring['url'] . 'page=' . $qstring['page'], __('search_results', 'system'));
		}

		// Assign actions
		view::setAction('#', __('search', 'system'), array('class' => 'icon-text icon-system-search', 'onclick' => '$(\'#messages-search\').toggle();return false;'));

		// Load view
		view::load('cp/plugins/messages/browse');
	}

	public function edit()
	{
		// Get URI vars
		$messageID = (int)uri::segment(5);

		// Get message
		if ( !$messageID || !( $message = $this->messages_model->getMessage($messageID) ) )
		{
			view::setError(__('no_message', 'messages'));
			router::redirect('cp/plugins/messages');
		}

		// Assign vars
		view::assign(array('messageID' => $messageID, 'message' => $message));

		// Process form values
		if ( input::post('do_save_message') )
		{
			$this->_saveMessage($messageID, $message);
		}

		// Set title
		view::setTitle(__('message_edit', 'messages'));

		// Set trail
		view::setTrail('cp/plugins/messages/edit/' . $messageID, __('message_edit', 'messages') . ' - ' . text_helper::entities($message['message']));

		// Load view
		view::load('cp/plugins/messages/edit');
	}

	protected function _saveMessage($messageID, $message)
	{
		// Check if demo mode is enabled
		if ( input::demo() ) return false;

		// Create rules
		$rules = array(
			'message' => array(
				'label' => __('message', 'messages'),
				'rules' => array('trim', 'required')
			),
		);

		// Assign rules
		validate::setRules($rules);

		// Validate fields
		if ( !validate::run() )
		{
			return false;
		}

		// Get input data
		$body = input::post('message');

		// Save message
		if ( !( $messageID = $this->messages_model->saveMessage($messageID, $message['conversation_id'], $body) ) )
		{
			if ( !validate::getTotalErrors() )
			{
				view::setError(__('save_error', 'system'));
			}
			return false;
		}

		// Success
		view::setInfo(__('message_saved', 'messages'));
		router::redirect('cp/plugins/messages/edit/' . $messageID);
	}

	public function delete($actionID = false)
	{
		// Check if demo mode is enabled
		if ( input::demo(1, 'cp/plugins/messages') ) return false;

		// Get URI vars
		$messageID = $actionID ? $actionID : (int)uri::segment(5);

		// Get message
		if ( !$messageID || !( $message = $this->messages_model->getMessage($messageID) ) )
		{
			view::setError(__('no_message', 'messages'));
			router::redirect('cp/plugins/messages');
		}

		// Get conversation
		if ( !( $conversation = $this->messages_model->getConversation($message['conversation_id'], $message['user_id'], array('messages' => false, 'recipients' => false)) ) )
		{
			view::setError(__('no_conversation', 'messages'));
			router::redirect('cp/plugins/messages');
		}

		// Delete message
		$this->messages_model->deleteMessage($messageID, $message['conversation_id'], $message, $conversation);

		// Is this an action call?
		if ( $actionID ) return;

		// Process query string
		$qstring = $this->parseQuerystring();

		// Success
		view::setInfo(__('message_deleted', 'messages'));
		router::redirect('cp/plugins/messages?' . $qstring['url'] . 'page=' . $qstring['page']);
	}

	protected function parseCounters($params)
	{
		// Set filter fields
		$filters = array(
			array(
				'name' => __('keyword', 'system'),
				'type' => 'text',
				'keyword' => 'q',
			),
			array(
				'name' => __('user', 'system'),
				'type' => 'text',
				'keyword' => 'user',
			),
		);

		// Assign vars
		view::assign(array('filters' => $filters, 'values' => array()));

		// Did user submit the filter form?
		if ( input::post_get('do_search') )
		{
			$values = array();

			// Check extra keyword
			$keyword = utf8::trim(input::post_get('q'));
			if ( $keyword )
			{
				$params['join_columns'][] = $this->search_model->prepareValue($keyword, 'm', 'message');
				$values['q'] = $keyword;
			}

			// Check extra user field
			$user = utf8::trim(input::post_get('user'));
			if ( $user )
			{
				$params['join_columns'][] = $this->search_model->prepareValue($user, 'u', 'user');
				$values['user'] = $user;
			}

			// Search messages
			$searchID = $this->search_model->searchData('message', $filters, $params['join_columns'], $values);

			// Do we have any search terms?
			if ( $searchID == 'no_terms' )
			{
				view::setError(__('search_no_terms', 'system'));
			}
			// Do we have any results?
			elseif ( $searchID == 'no_results' )
			{
				view::setError(__('search_no_results', 'system'));
				$params['total'] = 0;
				return $params;
			}
			// Redirect to search results
			else
			{
				router::redirect('cp/plugins/messages?search_id=' . $searchID);
			}
		}

		// Do we have a search ID?
		if ( !input::post_get('do_search') && input::get('search_id') )
		{
			// Get search
			if ( !( $search = $this->search_model->getSearch(input::get('search_id')) ) )
			{
				view::setError(__('search_expired', 'system'));
				router::redirect('cp/plugins/messages');
			}

			// Combine results
			$params['join_columns'] = $search['conditions']['columns'];
			$params['values'] = $search['values'];
			$params['total'] = $search['results'];

			// Assign vars
			view::assign(array('values' => $search['values']));
		}
		else
		{
			// Count messages
			if ( !( $params['total'] = $this->counters_model->countData('message', 0, 0, $params['join_columns'], array(), $params) ) )
			{
				view::setInfo(__('no_messages', 'messages'));
			}
		}

		return $params;
	}

	protected function parseQuerystring($max = 0)
	{
		$qstring = array();

		// Set max page
		$maxpage = $max ? ceil($max / $this->messagesPerPage) : 0;

		// Get current page
		$qstring['page'] = (int)input::get('page', 1);
		$qstring['page'] = $qstring['page'] > 0 ? ( !$maxpage || $qstring['page'] <= $maxpage ? $qstring['page'] : $maxpage ) : 1;

		// Get search id
		$qstring['search_id'] = input::get('search_id');

		// Get order field and direction
		$qstring['orderby'] = input::post_get('o') && in_array(input::post_get('o'), array('post_date')) ? input::post_get('o') : 'post_date';
		$qstring['orderdir'] = input::post_get('d') && in_array(input::post_get('d'), array('asc', 'desc')) ? input::post_get('d') : 'desc';
		$qstring['order'] = $qstring['orderby'] ? array($qstring['orderby'] => $qstring['orderdir']) : array();

		// Create url string
		$qstring['url'] = ( $qstring['search_id'] ? 'search_id=' . $qstring['search_id'] . '&' : '' ) .
			( $qstring['orderby'] ? 'o=' . $qstring['orderby'] . '&' : '' ) .
			( $qstring['orderby'] && $qstring['orderdir'] ? 'd=' . $qstring['orderdir'] . '&' : '' );

		// Set limit
		$from = ( $qstring['page'] - 1 ) * $this->messagesPerPage;
		$qstring['limit'] = ( !$max || $from <= $max ? $from : $max ) . ', ' . $this->messagesPerPage;

		// Assign vars
		view::assign(array('qstring' => $qstring));

		return $qstring;
	}
}
