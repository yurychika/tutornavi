<?php

class CP_Users_Pictures_Controller extends Controller
{
	public $picturesPerPage = 24;

	public function __construct()
	{
		parent::__construct();

		// Does user have permission to access this plugin?
		if ( !session::permission('users_pictures', 'users') )
		{
			view::noAccess();
		}

		view::setCustomParam('section', 'users');
		view::setCustomParam('options', config::item('cp_top_nav', 'lists', 'users', 'items'));

		view::setTrail('cp/users', __('users', 'system_navigation'));
		view::setTrail('cp/users/pictures', __('users_pictures', 'system_navigation'));
	}

	public function index()
	{
		$this->browse();
	}

	public function browse()
	{
		// Parameters
		$params = array(
			'join_columns' => array("`u`.`picture_id`!=0"),
			'join_items' => array(),
		);

		// Process filters
		$params = $this->parseCounters($params, 0);

		// Process query string
		$qstring = $this->parseQuerystring($params['total']);

		// Actions
		$actions = array(
			0 => __('select', 'system'),
			'approve' => __('approve', 'system'),
			'decline' => __('decline', 'system'),
			'delete' => __('delete', 'system'),
		);

		// Check form action
		if ( input::post('do_action') )
		{
			// Delete selected albums
			if ( input::post('action') && isset($actions[input::post('action')]) && input::post('user_id') && is_array(input::post('user_id')) )
			{
				foreach ( input::post('user_id') as $userID )
				{
					$userID = (int)$userID;
					if ( $userID && $userID > 0 )
					{
						$this->action(input::post('action'), $userID);
					}
				}
			}

			// Success
			view::setInfo(__('action_applied', 'system'));
			router::redirect('cp/users/pictures/browse?' . $qstring['url'] . 'page=' . $qstring['page']);
		}

		// Get pictures
		$users = array();
		if ( $params['total'] )
		{
			$users = $this->users_model->getUsers('in_list', ( isset($params['values']['type']) ? $params['values']['type'] : 0 ), $params['join_columns'], $params['join_items'], $qstring['order'], $qstring['limit']);
		}

		// Set pagination
		$config = array(
			'base_url' => config::siteURL('cp/users/pictures/browse?' . $qstring['url']),
			'total_items' => $params['total'],
			'items_per_page' => $this->picturesPerPage,
			'current_page' => $qstring['page'],
			'uri_segment' => 'page',
		);
		$pagination = loader::library('pagination', $config, null);

		// Assign vars
		view::assign(array('users' => $users, 'pagination' => $pagination, 'actions' => $actions));

		// Set title
		view::setTitle(__('users_pictures_manage', 'system_navigation'));

		// Set trail
		if ( $qstring['search_id'] )
		{
			view::setTrail('cp/users/pictures/browse?' . $qstring['url'] . 'page=' . $qstring['page'], __('search_results', 'system'));
		}

		// Assign actions
		view::setAction('#', __('search', 'system'), array('class' => 'icon-text icon-system-search', 'onclick' => '$(\'#pictures-search\').toggle();return false;'));

		// Load view
		view::load('cp/users/pictures/browse');
	}

	public function approve()
	{
		$this->action('approve');
	}

	public function decline()
	{
		$this->action('decline');
	}

	public function delete()
	{
		$this->action('delete');
	}

	protected function action($action, $actionID = false)
	{
		// Check if demo mode is enabled
		if ( input::demo(1, 'cp/users/pictures') ) return false;

		// Get URI vars
		$userID = $actionID ? $actionID : (int)uri::segment(5);

		// Get user
		if ( !$userID || !( $user = $this->users_model->getUser($userID) ) )
		{
			view::setError(__('no_user', 'users'));
			router::redirect('cp/users/pictures/browse');
		}

		switch ( $action )
		{
			case 'approve':

				$this->users_model->togglePictureStatus($userID, $user, 1);
				$str = __('picture_approved', 'users_picture');

				break;

			case 'decline':

				$this->users_model->togglePictureStatus($userID, $user, 0);
				$str = __('picture_declined', 'users_picture');

				break;

			case 'delete':

				$this->users_model->deletePicture($userID, $user['picture_id']);
				$str = __('picture_deleted', 'users_picture');

				break;
		}

		// Is this an action call?
		if ( $actionID ) return;

		// Process query string
		$qstring = $this->parseQuerystring();

		// Success
		view::setInfo($str);
		router::redirect('cp/users/pictures/browse?' . $qstring['url'] . 'page=' . $qstring['page']);
	}

	protected function parseCounters($params, $typeID)
	{
		// Set filters
		$filters = array(
			array(
				'name' => __('user', 'system'),
				'type' => 'text',
				'keyword' => 'user',
			),
			array(
				'name' => __('user_group', 'users'),
				'type' => 'select',
				'keyword' => 'group',
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
			'name' => __('status', 'system'),
			'keyword' => 'active',
			'type' => 'select',
			'items' => array(
				1 => __('active', 'system'),
				9 => __('pending', 'system'),
				0 => __('inactive', 'system'),
			),
		);

		// Assign vars
		view::assign(array('filters' => $filters, 'values' => array()));

		// Did user submit the filter form?
		if ( input::post_get('do_search') )
		{
			$values = array();

			// Check extra user field
			$user = utf8::trim(input::post_get('user'));
			if ( $user )
			{
				$params['join_columns'][] = $this->search_model->prepareValue($user, 'u', 'user');
				$values['user'] = $user;
			}

			// Check extra status field
			$status = input::post_get('active');
			if ( $status != '' )
			{
				$params['join_columns'][] = '`u`.`picture_active`=' . (int)$status;
				$values['active'] = $status;
			}

			// Check extra group field
			$group = input::post_get('group');
			if ( $group != '' && config::item('usergroups', 'core', $group))
			{
				$params['join_columns'][] = '`u`.`group_id`=' . $group;
				$values['group'] = $group;
			}

			// Check extra type field
			$typeID = input::post_get('type_id');
			if ( $typeID != '' && config::item('usertypes', 'core', 'keywords', $typeID) )
			{
				$params['join_columns'][] = '`u`.`type_id`=' . $typeID;
				$values['type_id'] = $typeID;
			}

			// Search users
			$searchID = $this->search_model->searchData('profile', $filters, $params['join_columns'], $values, array('type_id' => $typeID));

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
				router::redirect('cp/users/pictures?search_id=' . $searchID);
			}
		}

		// Do we have a search ID?
		if ( !input::post_get('do_search') && input::get('search_id') )
		{
			// Get search
			if ( !( $search = $this->search_model->getSearch(input::get('search_id')) ) )
			{
				view::setError(__('search_expired', 'system'));
				router::redirect('cp/users/pictures');
			}

			// Combine results
			$params['join_columns'] = $search['conditions']['columns'];
			$params['join_items'] = $search['conditions']['items'];
			$params['values'] = $search['values'];
			$params['total'] = $search['results'];

			// Assign vars
			view::assign(array('values' => $search['values']));
		}
		else
		{
			// Count users
			if ( !( $params['total'] = $this->counters_model->countData('user', 0, 1, $params['join_columns'], $params['join_items'], $params) ) )
			{
				view::setInfo(__('no_users', 'users'));
			}
		}

		return $params;
	}

	protected function parseQuerystring($max = 0)
	{
		$qstring = array();

		// Set max page
		$maxpage = $max ? ceil($max / $this->picturesPerPage) : 0;

		// Get current page
		$qstring['page'] = (int)input::get('page', 1);
		$qstring['page'] = $qstring['page'] > 0 ? ( !$maxpage || $qstring['page'] <= $maxpage ? $qstring['page'] : $maxpage ) : 1;

		// Get search id
		$qstring['search_id'] = input::get('search_id');

		// Get order field and direction
		$qstring['orderby'] = input::post_get('o') && in_array(input::post_get('o'), array('name1', 'picture_date', 'join_date', 'total_views', 'total_rating', 'total_likes', 'total_comments')) ? input::post_get('o') : 'picture_date';
		$qstring['orderdir'] = input::post_get('d') && in_array(input::post_get('d'), array('asc', 'desc')) ? input::post_get('d') : 'desc';
		$qstring['order'] = $qstring['orderby'] ? array($qstring['orderby'] => $qstring['orderdir']) : array();

		// Create url string
		$qstring['url'] = ( $qstring['search_id'] ? 'search_id=' . $qstring['search_id'] . '&' : '' ) .
			( $qstring['orderby'] ? 'o=' . $qstring['orderby'] . '&' : '' ) .
			( $qstring['orderby'] && $qstring['orderdir'] ? 'd=' . $qstring['orderdir'] . '&' : '' );

		// Set limit
		$from = ( $qstring['page'] - 1 ) * $this->picturesPerPage;
		$qstring['limit'] = ( !$max || $from <= $max ? $from : $max ) . ', ' . $this->picturesPerPage;

		// Assign vars
		view::assign(array('qstring' => $qstring));

		return $qstring;
	}
}
