<?php

class CP_Plugins_Classifieds_Controller extends Controller
{
	public $adsPerPage = 30;

	public function __construct()
	{
		parent::__construct();

		// Does user have permission to access this plugin?
		if ( !session::permission('ads_manage', 'classifieds') )
		{
			view::noAccess();
		}

		view::setCustomParam('section', 'plugins');
		view::setCustomParam('options', config::item('cp_top_nav', 'lists', 'plugins', 'items', 'plugins/classifieds', 'items'));

		view::setTrail('cp/system/plugins', __('plugins', 'system_navigation'));
		view::setTrail('cp/plugins/classifieds', __('classifieds', 'system_navigation'));

		loader::model('classifieds/classifieds');
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
			'join_items' => array(),
		);

		// Process filters
		$params = $this->parseCounters($params);

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
			// Delete selected ads
			if ( input::post('action') && isset($actions[input::post('action')]) && input::post('ad_id') && is_array(input::post('ad_id')) )
			{
				foreach ( input::post('ad_id') as $adID )
				{
					$adID = (int)$adID;
					if ( $adID && $adID > 0 )
					{
						$this->action(input::post('action'), $adID);
					}
				}
			}

			// Success
			view::setInfo(__('action_applied', 'system'));
			router::redirect('cp/plugins/classifieds?' . $qstring['url'] . 'page=' . $qstring['page']);
		}

		// Get ads
		$ads = array();
		if ( $params['total'] )
		{
			$ads = $this->classifieds_model->getAds('in_list', $params['join_columns'], $params['join_items'], $qstring['order'], $qstring['limit']);
		}

		// Create table grid
		$grid = array(
			'uri' => 'cp/plugins/classifieds',
			'keyword' => 'classifieds',
			'header' => array(
				'check' => array(
					'html' => 'ad_id',
					'class' => 'check',
				),
				'data_title' => array(
					'html' => __('name', 'system'),
					'class' => 'name',
					'sortable' => true,
				),
				'pictures' => array(
					'html' => __('pictures', 'classifieds'),
					'class' => 'pictures',
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
		foreach ( $ads as $ad )
		{
			if ( $ad['active'] == 1 )
			{
				$status = html_helper::anchor('cp/plugins/classifieds/decline/' . $ad['ad_id'] . '?' . $qstring['url'] . 'page=' . $qstring['page'], __('active', 'system'), array('class' => 'label small success'));
			}
			else
			{
				$status = html_helper::anchor('cp/plugins/classifieds/approve/' . $ad['ad_id'] . '?' . $qstring['url'] . 'page=' . $qstring['page'], $ad['active'] ? __('pending', 'system') : __('inactive', 'system'), array('class' => 'label small ' . ( $ad['active'] ? 'info' : 'important' )));
			}

			$grid['content'][] = array(
				'check' => array(
					'html' => $ad['ad_id'],
				),
				'data_title' => array(
					'html' => html_helper::anchor('cp/plugins/classifieds/edit/' . $ad['ad_id'], text_helper::truncate($ad['data_title'], 64)),
				),
				'pictures' => array(
					'html' => ( $ad['total_pictures'] + $ad['total_pictures_i'] ),
				),
				'user' => array(
					'html' => users_helper::anchor($ad['user']),
				),
				'post_date' => array(
					'html' => date_helper::formatDate($ad['post_date']),
				),
				'status' => array(
					'html' => $status,
				),
				'actions' => array(
					'html' => array(
						'edit' => html_helper::anchor('cp/plugins/classifieds/edit/'.$ad['ad_id'], __('edit', 'system'), array('class' => 'edit')),
						'delete' => html_helper::anchor('cp/plugins/classifieds/delete/'.$ad['ad_id'].'?'.$qstring['url'].'page='.$qstring['page'], __('delete', 'system'), array('data-html' => __('ad_delete?', 'classifieds'), 'data-role' => 'confirm', 'class' => 'delete')),
					),
				),
			);
		}

		// Set pagination
		$config = array(
			'base_url' => config::siteURL('cp/plugins/classifieds?' . $qstring['url']),
			'total_items' => $params['total'],
			'items_per_page' => $this->adsPerPage,
			'current_page' => $qstring['page'],
			'uri_segment' => 'page',
		);
		$pagination = loader::library('pagination', $config, null);

		// Filter hooks
		hook::filter('cp/plugins/classifieds/browse/grid', $grid);
		hook::filter('cp/plugins/classifieds/browse/actions', $actions);

		// Assign vars
		view::assign(array('grid' => $grid, 'actions' => $actions, 'pagination' => $pagination));

		// Set title
		view::setTitle(__('classifieds_manage', 'system_navigation'));

		// Set trail
		if ( $qstring['search_id'] )
		{
			view::setTrail('cp/plugins/classifieds?' . $qstring['url'] . 'page=' . $qstring['page'], __('search_results', 'system'));
		}

		// Assign actions
		view::setAction('#', __('search', 'system'), array('class' => 'icon-text icon-system-search', 'onclick' => '$(\'#classifieds-search\').toggle();return false;'));

		// Load view
		view::load('cp/plugins/classifieds/browse');
	}

	public function edit()
	{
		// Get URI vars
		$adID = (int)uri::segment(5);

		// Get fields
		$fields = $this->fields_model->getFields('classifieds', 0, 'edit');

		// Get ad
		if ( !$adID || !( $ad = $this->classifieds_model->getAd($adID, $fields, array('escape' => false, 'parse' => false)) ) )
		{
			view::setError(__('no_ad', 'classifieds'));
			router::redirect('cp/plugins/classifieds');
		}

		// Get user
		if ( !( $user = $this->users_model->getUser($ad['user_id']) ) )
		{
			view::setError(__('no_user', 'users'));
			router::redirect('cp/plugins/classifieds');
		}

		// Privacy and general options
		$privacy = $options = array();

		// Do we need to add enable comments field?
		if ( config::item('ad_comments', 'classifieds') && config::item('ad_privacy_comments', 'classifieds') )
		{
			$items = $this->users_model->getPrivacyOptions(( isset($user['config']['privacy_profile']) ? $user['config']['privacy_profile'] : 1 ), false);
			$items[0] = __('privacy_comments_disable', 'comments_privacy');

			$privacy[] = array(
				'name' => __('privacy_comments_post', 'comments_privacy', array(), array(), false),
				'keyword' => 'comments',
				'type' => 'select',
				'items' => $items,
			);
		}

		// Active field
		$options[] = array(
			'name' => __('status', 'system', array(), array(), false),
			'keyword' => 'active',
			'type' => 'select',
			'items' => array(
				1 => __('active', 'system'),
				9 => __('pending', 'system'),
				0 => __('inactive', 'system'),
			),
			'value' => 1,
		);

		// Assign vars
		view::assign(array('adID' => $adID, 'ad' => $ad, 'user' => $user, 'fields' => $fields, 'privacy' => $privacy, 'options' => $options));

		// Process form values
		if ( input::post('do_save_ad') )
		{
			$this->_saveAd($adID, $ad, $fields);
		}

		// Set title
		view::setTitle(__('ad_edit', 'classifieds'));

		// Set trail
		view::setTrail('cp/plugins/classifieds/edit/' . $adID, __('ad_edit', 'classifieds') . ' - ' . text_helper::entities($ad['data_title']));

		// Assign actions
		view::setAction('cp/plugins/classifieds/pictures/browse/' . $adID, __('classifieds_pictures', 'system_navigation'), array('class' => 'icon-text icon-classifieds-pictures'));

		// Load view
		view::load('cp/plugins/classifieds/edit');
	}

	protected function _saveAd($adID, $ad, $fields)
	{
		// Check if demo mode is enabled
		if ( input::demo() ) return false;

		// Extra rules
		$rules = array(
			'comments' => array('rules' => 'intval'),
			'active' => array('rules' => 'intval'),
		);

		// Validate form values
		if ( !$this->fields_model->validateValues($fields, $rules) )
		{
			return false;
		}

		// Extras
		$extra = array();
		$extra['comments'] = config::item('ad_comments', 'classifieds') && config::item('ad_privacy_comments', 'classifieds') ? (int)input::post('comments') : 1;
		$extra['active'] = (int)input::post('active');

		// Save ad
		if ( !( $adID = $this->classifieds_model->saveAdData($adID, 0, $ad, $fields, $extra) ) )
		{
			if ( !validate::getTotalErrors() )
			{
				view::setError(__('save_error', 'system'));
			}
			return false;
		}

		// Success
		view::setInfo(__('ad_saved', 'classifieds'));
		router::redirect('cp/plugins/classifieds/edit/' . $adID);
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
		if ( input::demo(1, 'cp/plugins/classifieds') ) return false;

		// Get URI vars
		$adID = $actionID ? $actionID : (int)uri::segment(5);

		// Get ad
		if ( !$adID || !( $ad = $this->classifieds_model->getAd($adID) ) )
		{
			view::setError(__('no_ad', 'classifieds'));
			router::redirect('cp/plugins/classifieds');
		}

		switch ( $action )
		{
			case 'approve':

				$this->classifieds_model->toggleAdStatus($adID, $ad['user_id'], $ad, 1);
				$str = __('ad_approved', 'classifieds');

				break;

			case 'decline':

				$this->classifieds_model->toggleAdStatus($adID, $ad['user_id'], $ad, 0);
				$str = __('ad_declined', 'classifieds');

				break;

			case 'delete':

				$this->classifieds_model->deleteAd($adID, $ad['user_id'], $ad);
				$str = __('ad_deleted', 'classifieds');

				break;
		}

		// Is this an action call?
		if ( $actionID ) return;

		// Process query string
		$qstring = $this->parseQuerystring();

		// Success
		view::setInfo($str);
		router::redirect('cp/plugins/classifieds?' . $qstring['url'] . 'page=' . $qstring['page']);
	}

	protected function parseCounters($params)
	{
		// Get fields
		$filters = $this->fields_model->getFields('classifieds', 0, 'edit', 'in_search', true);

		// Set extra fields
		$filters[] = array(
			'name' => __('search_keyword', 'system'),
			'type' => 'text',
			'keyword' => 'q',
		);
		$filters[] = array(
			'name' => __('user', 'system'),
			'type' => 'text',
			'keyword' => 'user',
		);
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

			// Check extra keyword
			$keyword = utf8::trim(input::post_get('q'));
			if ( $keyword )
			{
				$params['join_columns'][] = $this->search_model->prepareValue($keyword, 'a', array('data_title', 'data_body'));
				$values['q'] = $keyword;
			}

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
				$params['join_columns'][] = '`a`.`active`=' . (int)$status;
				$values['active'] = $status;
			}

			// Search ads
			$searchID = $this->search_model->searchData('classified_ad', $filters, $params['join_columns'], $values);

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
				router::redirect('cp/plugins/classifieds?search_id=' . $searchID);
			}
		}

		// Do we have a search ID?
		if ( !input::post_get('do_search') && input::get('search_id') )
		{
			// Get search
			if ( !( $search = $this->search_model->getSearch(input::get('search_id')) ) )
			{
				view::setError(__('search_expired', 'system'));
				router::redirect('cp/plugins/classifieds');
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
			// Count ads
			if ( !( $params['total'] = $this->counters_model->countData('classified_ad', 0, 0, $params['join_columns'], $params['join_items'], $params) ) )
			{
				view::setInfo(__('no_ads', 'classifieds'));
			}
		}

		return $params;
	}

	protected function parseQuerystring($max = 0)
	{
		$qstring = array();

		// Set max page
		$maxpage = $max ? ceil($max / $this->adsPerPage) : 0;

		// Get current page
		$qstring['page'] = (int)input::get('page', 1);
		$qstring['page'] = $qstring['page'] > 0 ? ( !$maxpage || $qstring['page'] <= $maxpage ? $qstring['page'] : $maxpage ) : 1;

		// Get search id
		$qstring['search_id'] = input::get('search_id');

		// Get order field and direction
		$qstring['orderby'] = input::post_get('o') && in_array(input::post_get('o'), array('data_title', 'post_date', 'total_views', 'total_rating', 'total_votes', 'total_likes', 'total_comments')) ? input::post_get('o') : 'post_date';
		$qstring['orderdir'] = input::post_get('d') && in_array(input::post_get('d'), array('asc', 'desc')) ? input::post_get('d') : 'desc';
		$qstring['order'] = $qstring['orderby'] ? array($qstring['orderby'] => $qstring['orderdir']) : array();

		// Create url string
		$qstring['url'] = ( $qstring['search_id'] ? 'search_id=' . $qstring['search_id'] . '&' : '' ) .
			( $qstring['orderby'] ? 'o=' . $qstring['orderby'] . '&' : '' ) .
			( $qstring['orderby'] && $qstring['orderdir'] ? 'd=' . $qstring['orderdir'] . '&' : '' );

		// Set limit
		$from = ( $qstring['page'] - 1 ) * $this->adsPerPage;
		$qstring['limit'] = ( !$max || $from <= $max ? $from : $max ) . ', ' . $this->adsPerPage;

		// Assign vars
		view::assign(array('qstring' => $qstring));

		return $qstring;
	}
}
