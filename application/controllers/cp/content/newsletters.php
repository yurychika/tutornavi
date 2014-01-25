<?php

class CP_Content_Newsletters_Controller extends Controller
{
	public function __construct()
	{
		parent::__construct();

		// Does user have permission to access this plugin?
		if ( !session::permission('newsletters_manage', 'newsletters') )
		{
			view::noAccess();
		}

		view::setCustomParam('section', 'content');
		view::setCustomParam('options', config::item('cp_top_nav', 'lists', 'content', 'items', 'content/newsletters', 'items'));

		view::setTrail('cp/system/plugins', __('content', 'system_navigation'));
		view::setTrail('cp/content/newsletters', __('newsletters', 'system_navigation'));

		loader::model('newsletters/newsletters', array(), 'newsletters_model');
	}

	public function index()
	{
		$this->browse();
	}

	public function browse()
	{
		// Get newsletters
		if ( !( $newsletters = $this->newsletters_model->getNewsletters() ) )
		{
			view::setInfo(__('no_newsletters', 'newsletters'));
		}

		// Create table grid
		$grid = array(
			'uri' => 'cp/content/newsletters/browse',
			'keyword' => 'newsletters',
			'header' => array(
				'name' => array(
					'html' => __('newsletter_subject', 'newsletters'),
					'class' => 'name',
				),
				'status' => array(
					'html' => __('status', 'system'),
					'class' => 'status',
				),
				'actions' => array(
					'html' => __('actions', 'system'),
					'class' => 'actions',
				),
			),
			'content' => array()
		);

		// Create grid content
		foreach ( $newsletters as $newsletter )
		{
			$grid['content'][] = array(
				'name' => array(
					'html' => html_helper::anchor('cp/content/newsletters/edit/' . $newsletter['newsletter_id'], text_helper::truncate($newsletter['subject'], 64)),
				),
				'status' => array(
					'html' => $newsletter['total_sent'] ? '<span class="label small info">' . __('pending', 'system') . '</span>' : '<span class="label small success">' . __('active', 'system') . '</span>',
				),
				'actions' => array(
					'html' => array(
						'send' => html_helper::anchor('cp/content/newsletters/review/' . $newsletter['newsletter_id'], __('newsletter_review', 'newsletters'), array('class' => 'review')),
						'edit' => html_helper::anchor('cp/content/newsletters/edit/' . $newsletter['newsletter_id'], __('edit', 'system'), array('class' => 'edit')),
						'delete' => html_helper::anchor('cp/content/newsletters/delete/' . $newsletter['newsletter_id'], __('delete', 'system'), array('data-html' => __('newsletter_delete?', 'newsletters'), 'data-role' => 'confirm', 'class' => 'delete')),
					),
				),
			);
		}

		// Filter hooks
		hook::filter('cp/content/newsletters/browse/grid', $grid);

		// Assign vars
		view::assign(array('grid' => $grid));

		// Set title
		view::setTitle(__('newsletters_manage', 'system_navigation'));

		// Set action
		view::setAction('cp/content/newsletters/edit', __('newsletter_new', 'newsletters'), array('class' => 'icon-text icon-newsletters-new'));

		// Load view
		view::load('cp/content/newsletters/browse');
	}

	public function edit()
	{
		// Get URI vars
		$newsletterID = (int)uri::segment(5);

		// Get newsletter
		$newsletter = array();
		if ( $newsletterID && !( $newsletter = $this->newsletters_model->getNewsletter($newsletterID, false) ) )
		{
			view::setError(__('no_newsletter', 'newsletters'));
			router::redirect('cp/content/newsletters');
		}

		// Assign vars
		view::assign(array('newsletterID' => $newsletterID, 'newsletter' => $newsletter));

		// Process form values
		if ( input::post('do_save_newsletter') )
		{
			$this->_saveNewsletter($newsletterID);
		}

		// Set title
		view::setTitle($newsletterID ? __('newsletter_edit', 'newsletters') : __('newsletter_new', 'newsletters'));

		// Set trail
		view::setTrail('cp/content/newsletters/edit/' . ( $newsletterID ? $newsletterID : '' ) . ( uri::segment(6) == 'review' ? '/review' : '' ), ( $newsletterID ? __('newsletter_edit', 'newsletters') . ' - ' . text_helper::entities($newsletter['subject']) : __('newsletter_new', 'newsletters') ));

		// Load ckeditor
		view::includeJavascript('externals/ckeditor/ckeditor.js');

		// Load view
		view::load('cp/content/newsletters/edit');
	}

	protected function _saveNewsletter($newsletterID)
	{
		// Check if demo mode is enabled
		if ( input::demo() ) return false;

		// Create rules
		$rules = array(
			'category_id' => array(
				'label' => __('gift_category', 'gifts'),
				'rules' => array('intval')
			),
		);

		// Get newsletter data
		$newsletterData = $input = array();
		$rules['subject'] = array(
			'label' => __('newsletter_subject', 'newsletters'),
			'rules' => array('trim', 'required', 'max_length' => 255)
		);
		$rules['message_html'] = array(
			'label' => __('newsletter_message_html', 'newsletters'),
			'rules' => array('trim', 'required')
		);
		$rules['message_text'] = array(
			'label' => __('newsletter_message_text', 'newsletters'),
			'rules' => array('trim', 'required')
		);
		$input = array('subject', 'message_html', 'message_text');

		// Assign rules
		validate::setRules($rules);

		// Validate fields
		if ( !validate::run() )
		{
			return false;
		}

		// Get newsletter data
		$newsletter = input::post($input);

		// Save newsletter
		if ( !( $newsletterID = $this->newsletters_model->saveNewsletter($newsletterID, $newsletter) ) )
		{
			view::setError(__('save_error', 'system'));
			return false;
		}

		router::redirect('cp/content/newsletters/' . ( uri::segment(6) == 'review' ? 'review' : 'recipients' ) . '/' . $newsletterID);
	}

	public function recipients()
	{
		// Get URI vars
		$newsletterID = (int)uri::segment(5);

		// Get newsletter
		if ( !$newsletterID || !( $newsletter = $this->newsletters_model->getNewsletter($newsletterID, false) ) )
		{
			view::setError(__('no_newsletter', 'newsletters'));
			router::redirect('cp/content/newsletters');
		}

		// Do we need to display recipients?
		if ( uri::segment(6) == 'view' && isset($newsletter['params']['conditions']) )
		{
			// Search users
			$searchID = $this->search_model->searchData('profile', array(), $newsletter['params']['conditions'], $newsletter['params']['values'], array('type_id' => isset($newsletter['params']['values']['type_id']) ? $newsletter['params']['values']['type_id'] : 0));

			// Do we have any search terms?
			if ( $searchID != 'no_terms' && $searchID != 'no_results' && ( $search = $this->search_model->getSearch($searchID) ) )
			{
				// Did total user count change?
				if ( $search['results'] != $newsletter['total_users'] )
				{
					$newsletter = array('total_users' => $search['results']);
					$this->newsletters_model->saveNewsletter($newsletterID, $newsletter);
				}

				router::redirect('cp/users?search_id=' . $searchID);
			}
		}

		// Set filters
		$filters = array(
			array(
				'name' => __('user', 'system'),
				'type' => 'text',
				'keyword' => 'user',
			),
			array(
				'name' => __('user_group', 'users'),
				'type' => 'checkbox',
				'keyword' => 'groups',
				'items' => config::item('usergroups', 'core'),
			),
			array(
				'name' => __('user_type', 'users'),
				'type' => 'select',
				'keyword' => 'type_id',
				'items' => config::item('usertypes', 'core', 'names'),
			),
		);
		foreach ( config::item('usertypes', 'core', 'keywords') as $id => $type )
		{
			$filters['types'][$id] = $this->fields_model->getFields('users', $id, 'edit');
		}
		$filters[] = array(
			'name' => __('verified', 'users'),
			'type' => 'boolean',
			'keyword' => 'verified',
		);
		$filters[] = array(
			'name' => __('active', 'system'),
			'type' => 'boolean',
			'keyword' => 'active',
		);

		// Assign vars
		view::assign(array('filters' => $filters, 'values' => array()));

		// Assign vars
		view::assign(array('newsletterID' => $newsletterID, 'newsletter' => $newsletter));

		// Process form values
		if ( input::post('do_search') )
		{
			$this->_saveRecipients($newsletterID, $filters);
		}
		// Do we have recipient parameters?
		elseif ( isset($newsletter['params']['values']) )
		{
			// Assign vars
			view::assign(array('values' => $newsletter['params']['values']));
		}

		// Set title
		view::setTitle(__('newsletter_recipients', 'newsletters'));

		// Set trail
		view::setTrail('cp/content/newsletters/edit/' . $newsletterID, __('newsletter_edit', 'newsletters') . ' - ' . text_helper::entities($newsletter['subject']));
		view::setTrail('cp/content/newsletters/recipients/' . $newsletterID, __('newsletter_recipients', 'newsletters'));

		// Load view
		view::load('cp/content/newsletters/recipients');
	}

	protected function _saveRecipients($newsletterID, $filters)
	{
		// Check if demo mode is enabled
		if ( input::demo() ) return false;

		$values = $params['join_columns'] = array();

		// Check extra user field
		$user = utf8::trim(input::post_get('user'));
		if ( $user )
		{
			$params['join_columns'][] = $this->search_model->prepareValue($user, 'u', 'user');
			$values['user'] = $user;
		}

		// Check extra verified field
		$verified = input::post_get('verified');
		if ( $verified != '' )
		{
			$params['join_columns'][] = '`u`.`verified`=' . (int)$verified;
			$values['verified'] = $verified;
		}

		// Check extra status field
		$status = input::post_get('active');
		if ( $status != '' )
		{
			$params['join_columns'][] = '`u`.`active`=' . (int)$status;
			$values['active'] = $status;
		}

		// Check extra group field
		$groups = input::post_get('groups');
		if ( $groups )
		{
			foreach ( $groups as $index => $group )
			{
				if ( config::item('usergroups', 'core', $group) )
				{
					$groups[$index] = (int)$group;
				}
				else
				{
					unset($groups[$index]);
				}
			}
			if ( $groups )
			{
				$params['join_columns'][] = '`u`.`group_id` IN (' . implode(',', $groups) . ')';
				$values['groups'] = $groups;
			}
		}

		// Check extra type field
		$typeID = input::post_get('type_id');
		if ( $typeID != '' && config::item('usertypes', 'core', 'keywords', $typeID) )
		{
			$params['join_columns'][] = '`u`.`type_id`=' . $typeID;
			$values['type_id'] = $typeID;
		}

		// Search users
		$searchID = $values ? $this->search_model->searchData('profile', $filters, $params['join_columns'], $values, array('type_id' => $typeID)) : 'no_terms';

		// Do we have any search terms?
		if ( $searchID == 'no_terms' )
		{
			view::setError(__('search_no_terms', 'system'));
		}
		// Do we have any results?
		elseif ( $searchID == 'no_results' )
		{
			view::setError(__('search_no_results', 'system'));
		}
		// Redirect to review
		else
		{
			// Get search
			if ( !( $search = $this->search_model->getSearch($searchID) ) )
			{
				view::setError(__('save_error', 'system'));
			}

			$newsletter = array(
				'params' => array(
					'conditions' => $search['conditions'],
					'values' => $search['values'],
				),
				'total_users' => $search['results'],
				'total_sent' => 0,
			);

			// Save recipients
			if ( !$this->newsletters_model->saveNewsletter($newsletterID, $newsletter) )
			{
				view::setError(__('save_error', 'system'));
				return false;
			}

			router::redirect('cp/content/newsletters/review/' . $newsletterID);
		}
	}

	public function review()
	{
		// Get URI vars
		$newsletterID = (int)uri::segment(5);

		// Get newsletter
		if ( !$newsletterID || !( $newsletter = $this->newsletters_model->getNewsletter($newsletterID, false) ) )
		{
			view::setError(__('no_newsletter', 'newsletters'));
			router::redirect('cp/content/newsletters');
		}

		// Do we have recipients?
		if ( !$newsletter['total_users'] )
		{
			router::redirect('cp/content/newsletters/recipients/' . $newsletterID);
		}

		// Assign vars
		view::assign(array('newsletterID' => $newsletterID, 'newsletter' => $newsletter));

		// Set title
		view::setTitle(__('newsletter_review', 'newsletters'));

		// Set trail
		view::setTrail('cp/content/newsletters/edit/' . $newsletterID . '/review', __('newsletter_edit', 'newsletters') . ' - ' . text_helper::entities($newsletter['subject']));
		view::setTrail('cp/content/newsletters/recipients/' . $newsletterID, __('newsletter_recipients', 'newsletters'));
		view::setTrail('cp/content/newsletters/review/' . $newsletterID, __('newsletter_review', 'newsletters'));

		// Load view
		view::load('cp/content/newsletters/review');
	}

	public function reset()
	{
		// Get URI vars
		$newsletterID = (int)uri::segment(5);

		// Get newsletter
		if ( !$newsletterID || !( $newsletter = $this->newsletters_model->getNewsletter($newsletterID) ) )
		{
			view::setError(__('no_newsletter', 'newsletters'));
			router::redirect('cp/content/newsletters');
		}

		// Reset total sent counter
		$this->newsletters_model->saveNewsletter($newsletterID, array('total_sent' => 0));

		router::redirect('cp/content/newsletters/review/' . $newsletterID);
	}

	public function send()
	{
		// Get URI vars
		$newsletterID = (int)uri::segment(5);
		$counter = (int)uri::segment(6, 0);
		$test = uri::segment(7) == 'test' ? 1 : 0;
		$step = config::item('emails_batch', 'newsletters');

		// Get newsletter
		if ( !$newsletterID || !( $newsletter = $this->newsletters_model->getNewsletter($newsletterID, false) ) )
		{
			view::setError(__('no_newsletter', 'newsletters'));
			router::redirect('cp/content/newsletters');
		}

		// Do we have recipients?
		if ( !$newsletter['total_users'] )
		{
			router::redirect('cp/content/newsletters/recipients/' . $newsletterID);
		}

		// Is this the first step?
		if ( !$test && !$counter )
		{
			// Update total user count
			$searchID = $this->search_model->searchData('profile', array(), $newsletter['params']['conditions'], $newsletter['params']['values'], array('type_id' => isset($newsletter['params']['values']['type_id']) ? $newsletter['params']['values']['type_id'] : 0));

			// Do we have any search terms?
			if ( $searchID == 'no_terms' || $searchID == 'no_results' || !( $search = $this->search_model->getSearch($searchID) ) )
			{
				router::redirect('cp/content/newsletters/recipients/' . $newsletterID);
			}

			// Did total user count change?
			if ( $search['results'] != $newsletter['total_users'] )
			{
				$newsletter = array('total_users' => $search['results']);
				$this->newsletters_model->saveNewsletter($newsletterID, $newsletter);
			}
		}
		elseif ( $test && !config::item('email_test', 'newsletters') )
		{
			view::setError(__('newsletters_test_none', 'newsletters', array(), array('%' => html_helper::anchor('cp/system/config/newsletters', '\1'))));
			router::redirect('cp/content/newsletters/review/' . $newsletterID);
		}

		// Load email library
		loader::library('email');

		// Is this a test?
		if ( $test )
		{
			$this->email->sendEmail(config::item('email_test', 'newsletters'), $newsletter['subject'], $newsletter['message_text'], $newsletter['message_html']);
			view::setInfo(__('newsletter_sent', 'newsletters'));
			router::redirect('cp/content/newsletters/review/' . $newsletterID);
		}

		// Get users
		$users = $this->users_model->getUsers('in_view', ( isset($newsletter['params']['values']['type_id']) ? $newsletter['params']['values']['type_id'] : 0 ), $newsletter['params']['conditions']['columns'], $newsletter['params']['conditions']['items'], false, $counter . ',' . $step);

		foreach ( $users as $user )
		{
			if ( !input::demo(0) )
			{
				$this->email->sendEmail($user['email'], $newsletter['subject'], $newsletter['message_text'], $newsletter['message_html'], $user);
			}
			$counter++;
		}

		// Do we have any users?
		if ( !$users )
		{
			view::setInfo(__('newsletter_sent', 'newsletters'));
			$next = '';
		}
		else
		{
			$next = $counter;
		}

		// Update total sent
		$this->newsletters_model->saveNewsletter($newsletterID, array('total_sent' => $next ? $counter : 0));

		// Did total number of users change?
		if ( $counter > $newsletter['total_users'] || !$next && $counter < $newsletter['total_users'] )
		{
			$newsletter['total_users'] = $counter;
			$this->newsletters_model->saveNewsletter($newsletterID, array('total_users' => $counter));
		}

		$message = __('newsletter_sending_status', 'newsletters', array('%1' => $counter, '%2' => $newsletter['total_users']));
		$message .= '<br/>' . __('newsletter_sending_redirect', 'newsletters', array(), array('%' => html_helper::anchor('cp/content/newsletters/send/' . $newsletterID . '/' . $next, '\1')));;

		// Assign vars
		view::assign(array('newsletterID' => $newsletterID, 'newsletter' => $newsletter, 'counter' => $counter, 'redirect' => $next, 'output' => $message));

		if ( input::isAjaxRequest() )
		{
			view::ajaxResponse(array('output' => $message, 'redirect' => $next));
		}

		// Set title
		view::setTitle(__('newsletter_sending', 'newsletters'));

		// Set trail
		view::setTrail('cp/content/newsletters/send/' . $newsletterID, __('newsletter_send', 'newsletters') . ' - ' . text_helper::entities($newsletter['subject']));

		// Load view
		view::load('cp/content/newsletters/send');
	}

	public function delete()
	{
		// Check if demo mode is enabled
		if ( input::demo(1, 'cp/content/newsletters') ) return false;

		// Get URI vars
		$newsletterID = (int)uri::segment(5);

		// Get newsletter
		if ( !$newsletterID || !( $newsletter = $this->newsletters_model->getNewsletter($newsletterID) ) )
		{
			view::setError(__('no_newsletter', 'newsletters'));
			router::redirect('cp/content/newsletters');
		}

		// Delete newsletter
		$this->newsletters_model->deleteNewsletter($newsletterID, $newsletter);

		// Success
		view::setInfo(__('newsletter_deleted', 'newsletters'));

		router::redirect('cp/content/newsletters');
	}
}
