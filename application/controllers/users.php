<?php

class Users_Controller extends Controller
{
	public function __construct()
	{
		parent::__construct();

		// Does user have permission to search users?
		if ( !session::permission('users_search_access', 'users') && !session::permission('users_search_access_advanced', 'users') )
		{
			view::noAccess();
		}
		// Does user have permission to view any of the user groups/types?
		elseif ( !session::permission('users_groups_browse', 'users') || !session::permission('users_types_browse', 'users') )
		{
			view::noAccess();
		}
	}

	public function index()
	{
		// Do we have permission to access advanced search?
		if ( input::get('a') && !session::permission('users_search_access_advanced', 'users') )
		{
			router::redirect('users');
		}

		// Parameters
		$params = array(
			'join_columns' => array(
				'`u`.`verified`=1',
				'`u`.`active`=1',
				'`u`.`group_id` IN (' . implode(',', session::permission('users_groups_browse', 'users')) . ')',
				'`u`.`type_id` IN (' . implode(',', session::permission('users_types_browse', 'users')) . ')',
			),
			'join_items' => array(),
		);

		// Process filters
		$params = $this->parseCounters($params);

		// Set meta tags
		$this->metatags_model->set('users', 'users_search');

		// Set title
		view::setTitle(__('search', 'system'), false);

		// Assign tabs
		view::setTab('users', __('search', 'system'), array('class' => ( input::get('a') ? '' : 'active' ) . ' icon-text icon-users-search'));
		if ( session::permission('users_search_access_advanced', 'users') )
		{
			view::setTab('users?a=1', __('search_advanced', 'system'), array('class' => ( input::get('a') ? 'active' : '' ) . ' icon-text icon-users-search-advanced'));
		}
		if ( users_helper::isLoggedin() )
		{
			//view::setTab('users/saved', __('saved_searches', 'users'));
		}

		// Load view
		view::load('users/search');
	}

	public function results()
	{
		if ( !input::get('search_id') )
		{
			$this->index();
			return;
		}

		// Parameters
		$params = array(
			'join_columns' => array(
				'`u`.`verified`=1',
				'`u`.`active`=1',
				'`u`.`group_id` IN (' . implode(',', session::permission('users_groups_browse', 'users')) . ')',
				'`u`.`type_id` IN (' . implode(',', session::permission('users_types_browse', 'users')) . ')',
			),
			'join_items' => array(),
		);

		// Process filters
		$params = $this->parseCounters($params);

		// Process query string
		$qstring = $this->parseQuerystring($params['max']);

		// Get users
		$users = array();
		if ( $params['total'] )
		{
			$users = $this->users_model->getUsers('in_list', ( isset($params['values']['type_id']) ? $params['values']['type_id'] : 0 ), $params['join_columns'], $params['join_items'], $qstring['order'], $qstring['limit']);
		}

		// Get fields
		$fields = $this->fields_model->getFields('users', ( isset($params['values']['type_id']) ? $params['values']['type_id'] : 0 ), 'view', 'in_list');

		// Set pagination
		$config = array(
			'base_url' => config::siteURL('users/results?'.$qstring['url']),
			'total_items' => $params['total'],
			'max_items' => config::item('max_search_results', 'system'),
			'items_per_page' => config::item('users_per_page', 'users'),
			'current_page' => $qstring['page'],
			'uri_segment' => 'page',
		);
		$pagination = loader::library('pagination', $config, null);

		// Assign vars
		view::assign(array('users' => $users, 'fields' => $fields, 'pagination' => $pagination));

		// Set meta tags
		$this->metatags_model->set('users', 'users_search_results');

		// Set title
		view::setTitle(__('search_results', 'system'), false);

		// Assign actions
		view::setAction('users?' . substr($qstring['url'], 0, -1), __('search_modify', 'system'), array('class' => 'icon-text icon-users-search-edit'));

		// Load view
		view::load('users/index');
	}

	protected function parseCounters($params = array())
	{
		// Get fields
		$filters = array();
		if ( count(config::item('usertypes', 'core', 'keywords')) > 1 )
		{
			// Set extra fields
			$filters[] = array(
				'name' => __('user_type', 'users'),
				'type' => 'select',
				'keyword' => 'type_id',
				'items' => config::item('usertypes', 'core', 'names'),
				'select' => 1,
			);

			foreach ( config::item('usertypes', 'core', 'keywords') as $id => $type )
			{
				$filters['types'][$id] = $this->fields_model->getFields('users', $id, 'edit', input::get('a') ? 'in_search_advanced' : 'in_search', true);
			}
		}
		else
		{
			$filters = $this->fields_model->getFields('users', config::item('type_default_id', 'users'), 'edit', input::get('a') ? 'in_search_advanced' : 'in_search', true);
		}

		// Additional options
		$options = array();

		// Pictures
		if ( config::item('search_option_picture', 'users') )
		{
			$options['pictures'] = __('search_option_picture', 'users', array(), array(), false);
		}

		// Online
		if ( config::item('search_option_online', 'users') )
		{
			$options['online'] = __('search_option_online', 'users', array(), array(), false);
		}

		if ( $options )
		{
			$filters[] = array(
				'name' => __('search_options', 'system', array(), array(), false),
				'type' => 'checkbox',
				'keyword' => 'search_options',
				'items' => $options,
			);
		}

		// Assign vars
		view::assign(array('filters' => $filters, 'values' => array()));

		// Did user submit the filter form?
		if ( input::post_get('do_search') )
		{
			$values = array();
			$params['total'] = $params['max'] = 0;

			// Check extra pictures field
			$pictures = in_array('pictures', input::post_get('search_options', array()));
			if ( $pictures )
			{
				$params['join_columns'][] = "`u`.`picture_id`!='' AND `u`.`picture_active`=1";
				$values['search_options'][] = 'pictures';
			}

			// Check extra online field
			$online = in_array('online', input::post_get('search_options', array()));
			if ( $online )
			{
				$params['join_columns'][] = "`u`.`visit_date`>=" . ( date_helper::now()-60*5 );
				$values['search_options'][] = 'online';
			}

			// Check extra type field
			$typeID = count(config::item('usertypes', 'core', 'keywords')) > 1 ? input::post_get('type_id') : config::item('type_default_id', 'users');
			if ( config::item('usertypes', 'core', 'keywords', $typeID) )
			{
				$params['join_columns'][] = '`u`.`type_id`=' . $typeID;
				$values['type_id'] = $typeID;

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
					return $params;
				}
				// Redirect to search results
				else
				{
					router::redirect('users/results?'.(input::get('a') ? 'a=1&' : '').'search_id='.$searchID);
				}
			}
			else
			{
				view::setError(__('search_no_type', 'users'));
			}
		}

		// Do we have a search ID?
		if ( !input::post_get('do_search') && input::get('search_id') )
		{
			// Get search
			if ( !( $search = $this->search_model->getSearch(input::get('search_id')) ) )
			{
				view::setError(__('search_expired', 'system'));
				router::redirect('users');
			}

			// Set results
			$params['join_columns'] = $search['conditions']['columns'];
			$params['join_items'] = $search['conditions']['items'];
			$params['values'] = $search['values'];
			$params['total'] = $search['results'];
			$params['max'] = config::item('max_search_results', 'system') && config::item('max_search_results', 'system') < $params['total'] ? config::item('max_search_results', 'system') : $params['total'];

			// Assign vars
			view::assign(array('values' => $search['values']));
		}

		return $params;
	}

	protected function parseQuerystring($max = 0)
	{
		$qstring = array();

		// Set max page
		$maxpage = $max ? ceil($max / config::item('users_per_page', 'users')) : 0;

		// Get current page
		$qstring['page'] = (int)input::get('page', 1);
		$qstring['page'] = $qstring['page'] > 0 ? ( !$maxpage || $qstring['page'] <= $maxpage ? $qstring['page'] : $maxpage ) : 1;

		// Get search id
		$qstring['search_id'] = input::get('search_id');
		$qstring['advanced'] = input::get('a');

		// Get order field and direction
		$qstring['orderby'] = input::post_get('o') && in_array(input::post_get('o'), array('join_date', 'visit_date', 'total_views', 'total_rating', 'total_votes', 'total_likes')) ? input::post_get('o') : 'join_date';
		$qstring['orderdir'] = input::post_get('d') && in_array(input::post_get('d'), array('asc', 'desc')) ? input::post_get('d') : 'desc';
		$qstring['order'] = $qstring['orderby'] ? array($qstring['orderby'] => $qstring['orderdir']) : array();

		// Create url string
		$qstring['url'] = ( $qstring['advanced'] ? 'a=' . $qstring['advanced'] . '&' : '' ) .
			( $qstring['search_id'] ? 'search_id=' . $qstring['search_id'] . '&' : '' ) .
			( $qstring['orderby'] ? 'o=' . $qstring['orderby'] . '&' : '' ) .
			( $qstring['orderby'] && $qstring['orderdir'] ? 'd=' . $qstring['orderdir'] . '&' : '' );

		// Set limit
		$from = ( $qstring['page'] - 1 ) * config::item('users_per_page', 'users');
		$qstring['limit'] = ( !$max || $from <= $max ? $from : $max ) . ', ' . config::item('users_per_page', 'users');

		// Assign vars
		view::assign(array('qstring' => $qstring));

		return $qstring;
	}
}
