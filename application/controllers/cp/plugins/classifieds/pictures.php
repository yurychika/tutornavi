<?php

class CP_Plugins_Classifieds_Pictures_Controller extends Controller
{
	public $picturesPerPage = 24;

	public function __construct()
	{
		parent::__construct();

		// Does user have permission to access this plugin?
		if ( !session::permission('pictures_manage', 'classifieds') )
		{
			view::noAccess();
		}

		view::setCustomParam('section', 'plugins');
		view::setCustomParam('options', config::item('cp_top_nav', 'lists', 'plugins', 'items', 'plugins/classifieds', 'items'));

		view::setTrail('cp/system/plugins', __('plugins', 'system_navigation'));
		view::setTrail('cp/plugins/classifieds', __('classifieds', 'system_navigation'));

		loader::model('classifieds/classifieds');
		loader::model('classifieds/pictures', array(), 'classifieds_pictures_model');
	}

	public function index()
	{
		$this->browse();
	}

	public function browse()
	{
		// Get URI vars
		$adID = (int)uri::segment(6);

		// Get ad
		if ( $adID && !( $ad = $this->classifieds_model->getAd($adID, 'in_view') ) )
		{
			view::setError(__('no_ad', 'classifieds'));
			router::redirect('cp/plugins/classifieds');
		}

		// Get user
		if ( $adID && !( $user = $this->users_model->getUser($ad['user_id']) ) )
		{
			view::setError(__('no_user', 'users'));
			router::redirect('cp/plugins/classifieds');
		}

		// Parameters
		$params = array(
			'join_columns' => ( $adID ? array('`p`.`ad_id`=' . $adID) : array() ),
			'join_items' => array(),
		);

		// Process filters
		$params = $this->parseCounters($adID, $params);

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
			if ( input::post('action') && isset($actions[input::post('action')]) && input::post('picture_id') && is_array(input::post('picture_id')) )
			{
				foreach ( input::post('picture_id') as $pictureID )
				{
					$pictureID = (int)$pictureID;
					if ( $pictureID && $pictureID > 0 )
					{
						$this->action(input::post('action'), $pictureID);
					}
				}
			}

			// Success
			view::setInfo(__('action_applied', 'system'));
			router::redirect('cp/plugins/classifieds/pictures/browse/' . $adID . '?' . $qstring['url'] . 'page=' . $qstring['page']);
		}

		// Get pictures
		$pictures = array();
		if ( $params['total'] )
		{
			$pictures = $this->classifieds_pictures_model->getPictures('in_list', $params['join_columns'], $params['join_items'], $qstring['order'], $qstring['limit']);
		}

		// Set pagination
		$config = array(
			'base_url' => config::siteURL('cp/plugins/classifieds/pictures/browse/' . $adID . '?' . $qstring['url']),
			'total_items' => $params['total'],
			'items_per_page' => $this->picturesPerPage,
			'current_page' => $qstring['page'],
			'uri_segment' => 'page',
		);
		$pagination = loader::library('pagination', $config, null);

		// Assign vars
		view::assign(array('adID' => $adID, 'ad' => ( $adID ? $ad : array() ), 'user' => ( $adID ? $user : array() ), 'pictures' => $pictures, 'pagination' => $pagination, 'actions' => $actions));

		// Set title
		view::setTitle(__('pictures', 'classifieds'));

		// Set trail
		if ( $adID )
		{
			view::setTrail('cp/plugins/classifieds/edit/' . $adID, __('ad_edit', 'classifieds') . ' - ' . $ad['data_title']);
		}
		view::setTrail('cp/plugins/classifieds/pictures/browse/' . $adID, __('pictures', 'classifieds'));
		if ( $qstring['search_id'] )
		{
			view::setTrail('cp/plugins/classifieds/pictures/browse/' . $adID . '?' . $qstring['url'] . 'page=' . $qstring['page'], __('search_results', 'system'));
		}

		// Assign actions
		view::setAction('#', __('search', 'system'), array('class' => 'icon-text icon-system-search', 'onclick' => '$(\'#pictures-search\').toggle();return false;'));

		// Load view
		view::load('cp/plugins/classifieds/pictures/browse');
	}

	public function edit()
	{
		// Get URI vars
		$adID = (int)uri::segment(6);
		$pictureID = (int)uri::segment(7);

		// Get fields
		$fields = $this->fields_model->getFields('classifieds', 1, 'edit');

		// Get ad
		if ( !$adID || !( $ad = $this->classifieds_model->getAd($adID, 'in_view') ) )
		{
			view::setError(__('no_ad', 'classifieds'));
			router::redirect('cp/plugins/classifieds');
		}

		// Get picture
		if ( !$pictureID || !( $picture = $this->classifieds_pictures_model->getPicture($pictureID, $fields, array('escape' => false, 'parse' => false)) ) || $picture['ad_id'] != $adID  )
		{
			view::setError(__('no_picture', 'classifieds'));
			router::redirect('cp/plugins/classifieds/pictures/browse/' . $adID);
		}

		// Get user
		if ( !( $user = $this->users_model->getUser($ad['user_id']) ) )
		{
			view::setError(__('no_user', 'users'));
			router::redirect('cp/plugins/classifieds');
		}

		// Options
		$options = array();

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
		view::assign(array('adID' => $adID, 'pictureID' => $pictureID, 'ad' => $ad, 'user' => $user, 'picture' => $picture, 'fields' => $fields, 'options' => $options));

		// Process form values
		if ( input::post('do_save_picture') )
		{
			$this->_savePicture($pictureID, $adID, $picture, $ad, $fields);
		}

		// Set title
		view::setTitle(__('picture_edit', 'classifieds'));

		// Set trail
		view::setTrail('cp/plugins/classifieds/edit/' . $adID, __('ad_edit', 'classifieds') . ' - ' . $ad['data_title']);
		view::setTrail('cp/plugins/classifieds/pictures/browse/' . $adID, __('pictures', 'classifieds'));
		view::setTrail('cp/plugins/classifieds/pictures/edit/' . $adID . '/' . $pictureID, __('picture_edit', 'classifieds') . ( $picture['data_description'] ? ' - ' . text_helper::entities($picture['data_description']) : '' ));

		// Load view
		view::load('cp/plugins/classifieds/pictures/edit');
	}

	protected function _savePicture($pictureID, $adID, $picture, $ad, $fields)
	{
		// Check if demo mode is enabled
		if ( input::demo() ) return false;

		// Validate form values
		if ( !$this->fields_model->validateValues($fields) )
		{
			return false;
		}

		// Extras
		$extra = array();
		$extra['active'] = (int)input::post('active');

		// Save picture
		if ( !( $pictureID = $this->classifieds_pictures_model->savePictureData($pictureID, $adID, $picture, $ad, $fields, $extra) ) )
		{
			if ( !validate::getTotalErrors() )
			{
				view::setError(__('save_error', 'system'));
			}
			return false;
		}

		// Success
		view::setInfo(__('picture_saved', 'classifieds'));
		router::redirect('cp/plugins/classifieds/pictures/edit/' . $adID . '/' . $pictureID);
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
		// Get URI vars
		$adID = (int)uri::segment(6);
		$pictureID = $actionID ? $actionID : (int)uri::segment(7);

		// Check if demo mode is enabled
		if ( input::demo(1, 'cp/plugins/classifieds/pictures' . ( $adID ? '/browse/' . $adID : '' )) ) return false;

		// Get picture
		if ( !$pictureID || !( $picture = $this->classifieds_pictures_model->getPicture($pictureID) ) || $adID && $picture['ad_id'] != $adID )
		{
			view::setError(__('no_picture', 'classifieds'));
			router::redirect('cp/plugins/classifieds/pictures/browse/' . $adID);
		}

		// Get ad
		if ( !( $ad = $this->classifieds_model->getAd($picture['ad_id']) ) )
		{
			view::setError(__('no_ad', 'classifieds'));
			router::redirect('cp/plugins/classifieds');
		}

		switch ( $action )
		{
			case 'approve':

				$this->classifieds_pictures_model->togglePictureStatus($pictureID, $picture['ad_id'], $picture['user_id'], $picture, $ad, 1);
				$str = __('picture_approved', 'classifieds');

				break;

			case 'decline':

				$this->classifieds_pictures_model->togglePictureStatus($pictureID, $picture['ad_id'], $picture['user_id'], $picture, $ad, 0);
				$str = __('picture_declined', 'classifieds');

				break;

			case 'delete':

				$this->classifieds_pictures_model->deletePicture($pictureID, $picture['ad_id'], $picture['user_id'], $picture, $ad);
				$str = __('picture_deleted', 'classifieds');

				break;
		}

		// Is this an action call?
		if ( $actionID ) return;

		// Process query string
		$qstring = $this->parseQuerystring();

		// Success
		view::setInfo($str);
		router::redirect('cp/plugins/classifieds/pictures/browse/' . $adID . '?' . $qstring['url'] . 'page=' . $qstring['page']);
	}

	protected function parseCounters($adID, $params)
	{
		// Get fields
		$filters = $this->fields_model->getFields('classifieds', 1, 'edit', 'in_search', true);

		// Set extra fields
		$filters[] = array(
			'name' => __('search_keyword', 'system'),
			'type' => 'text',
			'keyword' => 'q',
		);
		if ( !$adID )
		{
			$filters[] = array(
				'name' => __('user', 'system'),
				'type' => 'text',
				'keyword' => 'user',
			);
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

			// Check extra keyword
			$keyword = utf8::trim(input::post_get('q'));
			if ( $keyword )
			{
				$params['join_columns'][] = $this->search_model->prepareValue($keyword, 'p', 'data_description');
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
				$params['join_columns'][] = '`p`.`active`=' . (int)$status;
				$values['active'] = $status;
			}

			// Search pictures
			$searchID = $this->search_model->searchData('classified_picture', $filters, $params['join_columns'], $values);

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
				router::redirect('cp/plugins/classifieds/pictures/browse/' . $adID . '?search_id=' . $searchID);
			}
		}

		// Do we have a search ID?
		if ( !input::post_get('do_search') && input::get('search_id') )
		{
			// Get search
			if ( !( $search = $this->search_model->getSearch(input::get('search_id')) ) )
			{
				view::setError(__('search_expired', 'system'));
				router::redirect('cp/plugins/classifieds/pictures/browse/' . $adID);
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
			// Count pictures
			if ( !( $params['total'] = $this->counters_model->countData('classified_picture', 0, 0, $params['join_columns'], $params['join_items'], $params) ) )
			{
				view::setInfo(__('no_pictures', 'classifieds'));
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
		$qstring['orderby'] = input::post_get('o') && in_array(input::post_get('o'), array('data_description', 'post_date')) ? input::post_get('o') : 'post_date';
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
