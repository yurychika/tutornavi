<?php

class CP_Plugins_Pictures_Controller extends Controller
{
	public $picturesPerPage = 24;

	public function __construct()
	{
		parent::__construct();

		// Does user have permission to access this plugin?
		if ( !session::permission('pictures_manage', 'pictures') )
		{
			view::noAccess();
		}

		view::setCustomParam('section', 'plugins');
		view::setCustomParam('options', config::item('cp_top_nav', 'lists', 'plugins', 'items', 'plugins/pictures', 'items'));

		view::setTrail('cp/system/plugins', __('plugins', 'system_navigation'));
		view::setTrail('cp/plugins/pictures', __('pictures', 'system_navigation'));

		loader::model('pictures/pictures');
		loader::model('pictures/albums', array(), 'pictures_albums_model');
	}

	public function index()
	{
		$this->browse();
	}

	public function browse()
	{
		// Get URI vars
		$albumID = (int)uri::segment(5);

		// Get album
		if ( $albumID && !( $album = $this->pictures_albums_model->getAlbum($albumID, 'in_view') ) )
		{
			view::setError(__('no_album', 'pictures'));
			router::redirect('cp/plugins/pictures');
		}

		// Get user
		if ( $albumID && !( $user = $this->users_model->getUser($album['user_id']) ) )
		{
			view::setError(__('no_user', 'users'));
			router::redirect('cp/plugins/pictures');
		}

		// Parameters
		$params = array(
			'join_columns' => ( $albumID ? array('`p`.`album_id`=' . $albumID) : array() ),
			'join_items' => array(),
		);

		// Process filters
		$params = $this->parseCounters($albumID, $params);

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
			router::redirect('cp/plugins/pictures/browse/' . $albumID . '?' . $qstring['url'] . 'page=' . $qstring['page']);
		}

		// Get pictures
		$pictures = array();
		if ( $params['total'] )
		{
			$pictures = $this->pictures_model->getPictures('in_list', $params['join_columns'], $params['join_items'], $qstring['order'], $qstring['limit']);
		}

		// Set pagination
		$config = array(
			'base_url' => config::siteURL('cp/plugins/pictures/browse/' . $albumID . '?' . $qstring['url']),
			'total_items' => $params['total'],
			'items_per_page' => $this->picturesPerPage,
			'current_page' => $qstring['page'],
			'uri_segment' => 'page',
		);
		$pagination = loader::library('pagination', $config, null);

		// Assign vars
		view::assign(array('albumID' => $albumID, 'album' => ( $albumID ? $album : array() ), 'user' => ( $albumID ? $user : array() ), 'pictures' => $pictures, 'pagination' => $pagination, 'actions' => $actions));

		// Set title
		view::setTitle(__('pictures_manage', 'system_navigation'));

		// Set trail
		if ( $albumID )
		{
			view::setTrail('cp/plugins/pictures/albums', __('pictures_albums', 'system_navigation'));
			view::setTrail('cp/plugins/pictures/albums/edit/' . $albumID, __('album_edit', 'pictures') . ' - ' . $album['data_title']);
			view::setTrail('cp/plugins/pictures/browse/' . $albumID, __('pictures', 'pictures'));
		}
		if ( $qstring['search_id'] )
		{
			view::setTrail('cp/plugins/pictures/browse/' . $albumID . '?' . $qstring['url'] . 'page=' . $qstring['page'], __('search_results', 'system'));
		}

		// Assign actions
		view::setAction('#', __('search', 'system'), array('class' => 'icon-text icon-system-search', 'onclick' => '$(\'#pictures-search\').toggle();return false;'));

		// Load view
		view::load('cp/plugins/pictures/browse');
	}

	public function edit()
	{
		// Get URI vars
		$albumID = (int)uri::segment(5);
		$pictureID = (int)uri::segment(6);

		// Get fields
		$fields = $this->fields_model->getFields('pictures', 0, 'edit');

		// Get album
		if ( !$albumID || !( $album = $this->pictures_albums_model->getAlbum($albumID, 'in_view') ) )
		{
			view::setError(__('no_album', 'pictures'));
			router::redirect('cp/plugins/pictures');
		}

		// Get picture
		if ( !$pictureID || !( $picture = $this->pictures_model->getPicture($pictureID, $fields, array('escape' => false, 'parse' => false)) ) || $picture['album_id'] != $albumID  )
		{
			view::setError(__('no_picture', 'pictures'));
			router::redirect('cp/plugins/pictures/browse/' . $albumID);
		}

		// Get user
		if ( !( $user = $this->users_model->getUser($album['user_id']) ) )
		{
			view::setError(__('no_user', 'users'));
			router::redirect('cp/plugins/pictures');
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
		view::assign(array('albumID' => $albumID, 'pictureID' => $pictureID, 'album' => $album, 'user' => $user, 'picture' => $picture, 'fields' => $fields, 'options' => $options));

		// Process form values
		if ( input::post('do_save_picture') )
		{
			$this->_savePicture($pictureID, $albumID, $picture, $album, $fields);
		}

		// Set title
		view::setTitle(__('picture_edit', 'pictures'));

		// Set trail
		view::setTrail('cp/plugins/pictures/albums', __('pictures_albums', 'system_navigation'));
		view::setTrail('cp/plugins/pictures/albums/edit/' . $albumID, __('album_edit', 'pictures') . ' - ' . $album['data_title']);
		view::setTrail('cp/plugins/pictures/browse/' . $albumID, __('pictures', 'pictures'));
		view::setTrail('cp/plugins/pictures/edit/' . $albumID . '/' . $pictureID, __('picture_edit', 'pictures') . ( $picture['data_description'] ? ' - ' . text_helper::entities($picture['data_description']) : '' ));

		// Load view
		view::load('cp/plugins/pictures/edit');
	}

	protected function _savePicture($pictureID, $albumID, $picture, $album, $fields)
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
		if ( !( $pictureID = $this->pictures_model->savePictureData($pictureID, $albumID, $picture, $album, $fields, $extra) ) )
		{
			if ( !validate::getTotalErrors() )
			{
				view::setError(__('save_error', 'system'));
			}
			return false;
		}

		// Success
		view::setInfo(__('picture_saved', 'pictures'));
		router::redirect('cp/plugins/pictures/edit/' . $albumID . '/' . $pictureID);
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
		$albumID = (int)uri::segment(5);
		$pictureID = $actionID ? $actionID : (int)uri::segment(6);

		// Check if demo mode is enabled
		if ( input::demo(1, 'cp/plugins/pictures' . ( $albumID ? '/browse/' . $albumID : '' )) ) return false;

		// Get picture
		if ( !$pictureID || !( $picture = $this->pictures_model->getPicture($pictureID) ) || $albumID && $picture['album_id'] != $albumID )
		{
			view::setError(__('no_picture', 'pictures'));
			router::redirect('cp/plugins/pictures/browse/' . $albumID);
		}

		// Get album
		if ( !( $album = $this->pictures_albums_model->getAlbum($picture['album_id']) ) )
		{
			view::setError(__('no_album', 'pictures'));
			router::redirect('cp/plugins/pictures');
		}

		switch ( $action )
		{
			case 'approve':

				$this->pictures_model->togglePictureStatus($pictureID, $picture['album_id'], $picture['user_id'], $picture, $album, 1);
				$str = __('picture_approved', 'pictures');

				break;

			case 'decline':

				$this->pictures_model->togglePictureStatus($pictureID, $picture['album_id'], $picture['user_id'], $picture, $album, 0);
				$str = __('picture_declined', 'pictures');

				break;

			case 'delete':

				$this->pictures_model->deletePicture($pictureID, $picture['album_id'], $picture['user_id'], $picture, $album);
				$str = __('picture_deleted', 'pictures');

				break;
		}

		// Is this an action call?
		if ( $actionID ) return;

		// Process query string
		$qstring = $this->parseQuerystring();

		// Success
		view::setInfo($str);
		router::redirect('cp/plugins/pictures/browse/' . $albumID . '?' . $qstring['url'] . 'page=' . $qstring['page']);
	}

	protected function parseCounters($albumID, $params)
	{
		// Get fields
		$filters = $this->fields_model->getFields('pictures', 0, 'edit', 'in_search', true);

		// Set extra fields
		$filters[] = array(
			'name' => __('search_keyword', 'system'),
			'type' => 'text',
			'keyword' => 'q',
		);
		if ( !$albumID )
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
			$searchID = $this->search_model->searchData('picture', $filters, $params['join_columns'], $values);

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
				router::redirect('cp/plugins/pictures/browse/' . $albumID . '?search_id=' . $searchID);
			}
		}

		// Do we have a search ID?
		if ( !input::post_get('do_search') && input::get('search_id') )
		{
			// Get search
			if ( !( $search = $this->search_model->getSearch(input::get('search_id')) ) )
			{
				view::setError(__('search_expired', 'system'));
				router::redirect('cp/plugins/pictures/browse/' . $albumID);
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
			if ( !( $params['total'] = $this->counters_model->countData('picture', 0, 0, $params['join_columns'], $params['join_items'], $params) ) )
			{
				view::setInfo(__('no_pictures', 'pictures'));
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
		$qstring['orderby'] = input::post_get('o') && in_array(input::post_get('o'), array('data_description', 'post_date', 'total_views', 'total_rating', 'total_votes', 'total_likes', 'total_comments')) ? input::post_get('o') : 'post_date';
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
