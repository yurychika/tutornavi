<?php

class CP_Plugins_Pictures_Albums_Controller extends Controller
{
	public $albumsPerPage = 30;

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
		view::setTrail('cp/plugins/pictures/albums', __('pictures_albums', 'system_navigation'));

		loader::model('pictures/albums', array(), 'pictures_albums_model');
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
			'delete' => __('delete', 'system'),
		);

		// Check form action
		if ( input::post('do_action') )
		{
			// Delete selected albums
			if ( input::post('action') == 'delete' )
			{
				if ( input::post('album_id') && is_array(input::post('album_id')) )
				{
					foreach ( input::post('album_id') as $albumID )
					{
						$albumID = (int)$albumID;
						if ( $albumID && $albumID > 0 )
						{
							$this->delete($albumID);
						}
					}
				}
			}

			// Success
			view::setInfo(__('action_applied', 'system'));
			router::redirect('cp/plugins/pictures/albums?' . $qstring['url'] . 'page=' . $qstring['page']);
		}

		// Get albums
		$albums = array();
		if ( $params['total'] )
		{
			$albums = $this->pictures_albums_model->getAlbums('in_list', $params['join_columns'], $params['join_items'], $qstring['order'], $qstring['limit']);
		}

		// Create table grid
		$grid = array(
			'uri' => 'cp/plugins/pictures/albums',
			'keyword' => 'pictures_albums',
			'header' => array(
				'check' => array(
					'html' => 'album_id',
					'class' => 'check',
				),
				'data_title' => array(
					'html' => __('name', 'system'),
					'class' => 'name',
					'sortable' => true,
				),
				'pictures' => array(
					'html' => __('pictures', 'pictures'),
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
				'actions' => array(
					'html' => __('actions', 'system'),
					'class' => 'actions',
				),
			),
			'content' => array()
		);

		// Create grid content
		foreach ( $albums as $album )
		{
			$grid['content'][] = array(
				'check' => array(
					'html' => $album['album_id'],
				),
				'data_title' => array(
					'html' => html_helper::anchor('cp/plugins/pictures/albums/edit/' . $album['album_id'], text_helper::truncate($album['data_title'], 64)),
				),
				'pictures' => array(
					'html' => ( $album['total_pictures'] + $album['total_pictures_i'] ),
				),
				'user' => array(
					'html' => users_helper::anchor($album['user']),
				),
				'post_date' => array(
					'html' => date_helper::formatDate($album['post_date']),
				),
				'actions' => array(
					'html' => array(
						'edit' => html_helper::anchor('cp/plugins/pictures/albums/edit/' . $album['album_id'], __('edit', 'system'), array('class' => 'edit')),
						'delete' => html_helper::anchor('cp/plugins/pictures/albums/delete/' . $album['album_id'] . '?' . $qstring['url'] . 'page=' . $qstring['page'], __('delete', 'system'), array('data-html' => __('album_delete?', 'pictures'), 'data-role' => 'confirm', 'class' => 'delete')),
					),
				),
			);
		}

		// Set pagination
		$config = array(
			'base_url' => config::siteURL('cp/plugins/pictures/albums?' . $qstring['url']),
			'total_items' => $params['total'],
			'items_per_page' => $this->albumsPerPage,
			'current_page' => $qstring['page'],
			'uri_segment' => 'page',
		);
		$pagination = loader::library('pagination', $config, null);

		// Filter hooks
		hook::filter('cp/plugins/pictures/albums/browse/grid', $grid);
		hook::filter('cp/plugins/pictures/albums/browse/actions', $actions);

		// Assign vars
		view::assign(array('grid' => $grid, 'actions' => $actions, 'pagination' => $pagination));

		// Set title
		view::setTitle(__('pictures_albums_manage', 'system_navigation'));

		// Set trail
		if ( $qstring['search_id'] )
		{
			view::setTrail('cp/plugins/pictures/albums?' . $qstring['url'] . 'page=' . $qstring['page'], __('search_results', 'system'));
		}

		// Assign actions
		view::setAction('#', __('search', 'system'), array('class' => 'icon-text icon-system-search', 'onclick' => '$(\'#albums-search\').toggle();return false;'));

		// Load view
		view::load('cp/plugins/pictures/albums/browse');
	}

	public function edit()
	{
		// Get URI vars
		$albumID = (int)uri::segment(6);

		// Get fields
		$fields = $this->fields_model->getFields('pictures', 1, 'edit');

		// Get album
		if ( !$albumID || !( $album = $this->pictures_albums_model->getAlbum($albumID, $fields, array('escape' => false, 'parse' => false)) ) )
		{
			view::setError(__('no_album', 'pictures'));
			router::redirect('cp/plugins/pictures/albums');
		}

		// Get user
		if ( !( $user = $this->users_model->getUser($album['user_id']) ) )
		{
			view::setError(__('no_user', 'users'));
			router::redirect('cp/plugins/pictures/albums');
		}

		// Privacy and general options
		$privacy = $options = array();

		// Do we need to add privacy field?
		if ( config::item('album_privacy_view', 'pictures') )
		{
			$items = $this->users_model->getPrivacyOptions( isset($user['config']['privacy_profile']) ? $user['config']['privacy_profile'] : 1 );

			$privacy[] = array(
				'name' => __('privacy_album_view', 'pictures_privacy', array(), array(), false),
				'keyword' => 'privacy',
				'type' => 'select',
				'items' => $items,
			);
		}

		// Do we need to add enable comments field?
		if ( config::item('picture_comments', 'pictures') && config::item('picture_privacy_comments', 'pictures') )
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

		// Do we need to add search field?
		if ( config::item('album_privacy_public', 'pictures') )
		{
			$privacy[] = array(
				'name' => __('privacy_search', 'system', array(), array(), false),
				'keyword' => 'public',
				'type' => 'boolean',
			);
		}

		// Assign vars
		view::assign(array('albumID' => $albumID, 'album' => $album, 'user' => $user, 'fields' => $fields, 'privacy' => $privacy, 'options' => $options));

		// Process form values
		if ( input::post('do_save_album') )
		{
			$this->_saveAlbum($albumID, $album, $fields);
		}

		// Set title
		view::setTitle(__('album_edit', 'pictures'));

		// Set trail
		view::setTrail('cp/plugins/pictures/albums/edit/' . $albumID, __('album_edit', 'pictures') . ' - ' . text_helper::entities($album['data_title']));

		// Assign actions
		view::setAction('cp/plugins/pictures/browse/' . $albumID, __('pictures', 'system_navigation'), array('class' => 'icon-text icon-pictures'));

		// Load view
		view::load('cp/plugins/pictures/albums/edit');
	}

	protected function _saveAlbum($albumID, $album, $fields)
	{
		// Check if demo mode is enabled
		if ( input::demo() ) return false;

		// Extra rules
		$rules = array(
			'comments' => array('rules' => 'intval'),
			'privacy' => array('rules' => 'intval'),
			'public' => array('rules' => 'intval'),
		);

		// Validate form values
		if ( !$this->fields_model->validateValues($fields, $rules) )
		{
			return false;
		}

		// Extras
		$extra = array();
		$extra['comments'] = config::item('picture_comments', 'pictures') && config::item('picture_privacy_comments', 'pictures') ? (int)input::post('comments') : 1;
		$extra['privacy'] = config::item('album_privacy_view', 'pictures') ? (int)input::post('privacy') : 1;
		$extra['public'] = config::item('album_privacy_public', 'pictures') ? (int)input::post('public') : 1;

		// Save album
		if ( !( $albumID = $this->pictures_albums_model->saveAlbumData($albumID, 0, $album, $fields, $extra) ) )
		{
			if ( !validate::getTotalErrors() )
			{
				view::setError(__('save_error', 'system'));
			}
			return false;
		}

		// Success
		view::setInfo(__('album_saved', 'pictures', array(), array('%1' => html_helper::anchor('cp/plugins/pictures/albums/edit/' . $albumID, '\1'), '%2' => html_helper::anchor('cp/plugins/pictures/browse/' . $albumID, '\1'))));
		router::redirect('cp/plugins/pictures/albums/edit/' . $albumID);
	}

	public function delete($actionID = false)
	{
		// Check if demo mode is enabled
		if ( input::demo(1, 'cp/plugins/pictures/albums') ) return false;

		// Get URI vars
		$albumID = $actionID ? $actionID : (int)uri::segment(6);

		// Get album
		if ( !$albumID || !( $album = $this->pictures_albums_model->getAlbum($albumID) ) )
		{
			view::setError(__('no_album', 'pictures'));
			router::redirect('cp/plugins/pictures/albums');
		}

		// Delete album
		$this->pictures_albums_model->deleteAlbum($albumID, $album['user_id'], $album);

		// Is this an action call?
		if ( $actionID ) return;

		// Process query string
		$qstring = $this->parseQuerystring();

		// Success
		view::setInfo(__('album_deleted', 'pictures'));
		router::redirect('cp/plugins/pictures/albums?' . $qstring['url'] . 'page=' . $qstring['page']);
	}

	protected function parseCounters($params)
	{
		// Get fields
		$filters = $this->fields_model->getFields('pictures', 1, 'edit', 'in_search', true);

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
				$params['join_columns'][] = $this->search_model->prepareValue($keyword, 'a', array('data_title', 'data_description'));
				$values['q'] = $keyword;
			}

			// Check extra user field
			$user = utf8::trim(input::post_get('user'));
			if ( $user )
			{
				$params['join_columns'][] = $this->search_model->prepareValue($user, 'u', 'user');
				$values['user'] = $user;
			}

			// Search albums
			$searchID = $this->search_model->searchData('picture_album', $filters, $params['join_columns'], $values);

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
				router::redirect('cp/plugins/pictures/albums?search_id=' . $searchID);
			}
		}

		// Do we have a search ID?
		if ( !input::post_get('do_search') && input::get('search_id') )
		{
			// Get search
			if ( !( $search = $this->search_model->getSearch(input::get('search_id')) ) )
			{
				view::setError(__('search_expired', 'system'));
				router::redirect('cp/plugins/pictures/albums');
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
			// Count albums
			if ( !( $params['total'] = $this->counters_model->countData('picture_album', 0, 0, $params['join_columns'], $params['join_items'], $params) ) )
			{
				view::setInfo(__('no_albums', 'pictures'));
			}
		}

		return $params;
	}

	protected function parseQuerystring($max = 0)
	{
		$qstring = array();

		// Set max page
		$maxpage = $max ? ceil($max / $this->albumsPerPage) : 0;

		// Get current page
		$qstring['page'] = (int)input::get('page', 1);
		$qstring['page'] = $qstring['page'] > 0 ? ( !$maxpage || $qstring['page'] <= $maxpage ? $qstring['page'] : $maxpage ) : 1;

		// Get search id
		$qstring['search_id'] = input::get('search_id');

		// Get order field and direction
		$qstring['orderby'] = input::post_get('o') && in_array(input::post_get('o'), array('data_title', 'post_date', 'total_views', 'total_rating', 'total_votes', 'total_likes', 'total_pictures')) ? input::post_get('o') : 'post_date';
		$qstring['orderdir'] = input::post_get('d') && in_array(input::post_get('d'), array('asc', 'desc')) ? input::post_get('d') : 'desc';
		$qstring['order'] = $qstring['orderby'] ? array($qstring['orderby'] => $qstring['orderdir']) : array();

		// Create url string
		$qstring['url'] = ( $qstring['search_id'] ? 'search_id=' . $qstring['search_id'] . '&' : '' ) .
			( $qstring['orderby'] ? 'o=' . $qstring['orderby'] . '&' : '' ) .
			( $qstring['orderby'] && $qstring['orderdir'] ? 'd=' . $qstring['orderdir'] . '&' : '' );

		// Set limit
		$from = ( $qstring['page'] - 1 ) * $this->albumsPerPage;
		$qstring['limit'] = ( !$max || $from <= $max ? $from : $max ) . ', ' . $this->albumsPerPage;

		// Assign vars
		view::assign(array('qstring' => $qstring));

		return $qstring;
	}
}
