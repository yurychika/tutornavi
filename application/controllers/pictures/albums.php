<?php

class Pictures_Albums_Controller extends Controller
{
	public function __construct()
	{
		parent::__construct();

		if ( !config::item('pictures_active', 'pictures') )
		{
			error::show404();
		}
		elseif ( !session::permission('pictures_access', 'pictures') )
		{
			view::noAccess();
		}

		loader::model('pictures/pictures');
		loader::model('pictures/albums', array(), 'pictures_albums_model');
	}

	public function index()
	{
		// Is album gallery enabled?
		if ( !config::item('albums_gallery', 'pictures') )
		{
			if ( users_helper::isLoggedin() )
			{
				$this->manage();
				return;
			}
			else
			{
				error::show404();
			}
		}
		// Does user have permission to view any of the user groups/types and browse albums?
		elseif ( !session::permission('users_groups_browse', 'users') || !session::permission('users_types_browse', 'users') || !session::permission('albums_browse', 'pictures') )
		{
			view::noAccess();
		}

		// Parameters
		$params = array(
			'join_columns' => array(
				'`a`.`total_pictures`>0',
				'`a`.`public`=1',
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
		$qstring = $this->parseQuerystring(config::item('public_albums_per_page', 'pictures'), $params['max']);

		// Get albums
		$albums = array();
		if ( $params['total'] )
		{
			$albums = $this->pictures_albums_model->getAlbums('in_list', $params['join_columns'], $params['join_items'], $qstring['order'], $qstring['limit']);
		}

		// Set pagination
		$config = array(
			'base_url' => config::siteURL('pictures?' . $qstring['url']),
			'total_items' => $params['total'],
			'max_items' => $params['max'],
			'items_per_page' => config::item('public_albums_per_page', 'pictures'),
			'current_page' => $qstring['page'],
			'uri_segment' => 'page',
		);
		$pagination = loader::library('pagination', $config, null);

		// Assign vars
		view::assign(array('albums' => $albums, 'pagination' => $pagination));

		// Set meta tags
		$this->metatags_model->set('pictures', 'albums_index');

		// Set title
		view::setTitle(__('pictures_albums', 'system_navigation'), false);

		// Assign actions
		if ( users_helper::isLoggedin() )
		{
			view::setAction('pictures/albums/edit', __('album_new', 'pictures'), array('class' => 'icon-text icon-pictures-albums-new'));
		}
		if ( session::permission('albums_search', 'pictures') && ( $params['total'] || input::post_get('do_search') ) )
		{
			view::setAction('#', __('search', 'system'), array('class' => 'icon-text icon-system-search', 'onclick' => '$(\'#albums-search\').toggle();return false;'));
		}

		// Load view
		view::load('pictures/albums/index');
	}

	public function user()
	{
		// Get URI vars
		$slugID = urldecode(utf8::trim(uri::segment(3)));

		// Do we have a slug ID?
		if ( $slugID == '' )
		{
			error::show404();
		}

		// Is this our own account?
		if ( strcasecmp($slugID, session::item('slug_id')) == 0 )
		{
			$this->manage();
			return;
		}

		// Get user
		if ( !( $user = $this->users_model->getUser($slugID) ) || !$user['active'] || !$user['verified'] )
		{
			error::show404();
		}

		// Does user have permission to view this user group/type and browse albums?
		if ( !in_array($user['group_id'], session::permission('users_groups_browse', 'users')) || !in_array($user['type_id'], session::permission('users_types_browse', 'users')) || !session::permission('albums_browse', 'pictures') )
		{
			view::noAccess();
		}
		// Validate profile privacy
		elseif ( !$this->users_model->getPrivacyAccess($user['user_id'], ( isset($user['config']['privacy_profile']) ? $user['config']['privacy_profile'] : 1 )) )
		{
			view::noAccess($user['slug']);
		}

		// Parameters
		$params = array(
			'join_columns' => array(
				'`a`.`user_id`=' . $user['user_id'],
			),
			'join_items' => array(),
			'privacy' => $user['user_id'],
			'select_users' => false,
		);

		// Process filters
		$params = $this->parseCounters($params, 'user');

		// Process query string
		$qstring = $this->parseQuerystring(config::item('user_albums_per_page', 'pictures'), $params['max']);

		// Get albums
		$albums = array();
		if ( $params['total'] )
		{
			$albums = $this->pictures_albums_model->getAlbums('in_list', $params['join_columns'], $params['join_items'], $qstring['order'], $qstring['limit'], $params);
		}

		// Set pagination
		$config = array(
			'base_url' => config::siteURL('pictures/user/' . $user['slug_id'] . '?' . $qstring['url']),
			'total_items' => $params['total'],
			'max_items' => $params['max'],
			'items_per_page' => config::item('user_albums_per_page', 'pictures'),
			'current_page' => $qstring['page'],
			'uri_segment' => 'page',
		);
		$pagination = loader::library('pagination', $config, null);

		// Assign vars
		view::assign(array('slugID' => $slugID, 'user' => $user, 'albums' => $albums, 'pagination' => $pagination));

		// Set meta tags
		$this->metatags_model->set('pictures', 'albums_user', array('user' => $user));

		// Set title
		view::setTitle(__('pictures_albums', 'system_navigation'), false);

		// Set trail
		view::setTrail($user['slug'], $user['name']);
		view::setTrail('pictures/user/' . $user['slug_id'], __('pictures_albums', 'system_navigation'));

		// Assign actions
		if ( session::permission('albums_search', 'pictures') && ( $params['total'] || input::post_get('do_search') ) )
		{
			view::setAction('#', __('search', 'system'), array('class' => 'icon-text icon-system-search', 'onclick' => '$(\'#albums-search\').toggle();return false;'));
		}

		// Load view
		view::load('pictures/albums/user');
	}

	public function manage()
	{
		// Is user loggedin ?
		if ( !users_helper::isLoggedin() )
		{
			router::redirect('users/login');
		}
		// Does user have permission to create albums?
		elseif ( !session::permission('albums_post', 'pictures') )
		{
			view::noAccess(session::item('slug'));
		}

		// Assign user from session to variable
		$user = session::section('session');

		// Parameters
		$params = array(
			'select_users' => false,
			'join_columns' => array(
				'`a`.`user_id`=' . session::item('user_id'),
			),
			'join_items' => array(),
			'total' => $user['total_albums'],
		);

		// Process filters
		$params = $this->parseCounters($params, 'manage');

		// Process query string
		$qstring = $this->parseQuerystring(config::item('user_albums_per_page', 'pictures'), $params['max']);

		// Get albums
		$albums = array();
		if ( $params['total'] )
		{
			$albums = $this->pictures_albums_model->getAlbums('in_list', $params['join_columns'], $params['join_items'], $qstring['order'], $qstring['limit'], $params);
		}

		// Set pagination
		$config = array(
			'base_url' => config::siteURL('pictures/albums/manage?' . $qstring['url']),
			'total_items' => $params['total'],
			'max_items' => $params['max'],
			'items_per_page' => config::item('user_albums_per_page', 'pictures'),
			'current_page' => $qstring['page'],
			'uri_segment' => 'page',
		);
		$pagination = loader::library('pagination', $config, null);

		// Assign vars
		view::assign(array('user' => $user, 'albums' => $albums, 'pagination' => $pagination));

		// Set title
		view::setTitle(__('my_albums', 'system_navigation'));

		// Set trail
		view::setTrail(session::item('slug'), __('my_profile', 'system_navigation'));
		view::setTrail('pictures/manage',  __('pictures_albums', 'system_navigation'));

		// Assign actions
		view::setAction('pictures/albums/edit', __('album_new', 'pictures'), array('class' => 'icon-text icon-pictures-albums-new'));
		if ( session::permission('albums_search', 'pictures') && ( $params['total'] || input::post_get('do_search') ) )
		{
			view::setAction('#', __('search', 'system'), array('class' => 'icon-text icon-system-search', 'onclick' => '$(\'#albums-search\').toggle();return false;'));
		}

		// Load view
		view::load('pictures/albums/manage');
	}

	public function edit()
	{
		// Is user logged in?
		if ( !users_helper::isLoggedin() )
		{
			router::redirect('users/login');
		}
		// Does user have permission to create albums?
		elseif ( !session::permission('albums_post', 'pictures') )
		{
			view::noAccess(session::item('slug'));
		}

		// Get URI vars
		$albumID = (int)uri::segment(4);

		// Did user reach the max albums limit?
		if ( !$albumID && session::permission('albums_limit', 'pictures') && session::permission('albums_limit', 'pictures') <= session::item('total_albums') )
		{
			view::setError(__('album_limit_reached', 'pictures', array('%limit%' => session::permission('albums_limit', 'pictures'))));
			router::redirect('pictures/albums/manage');
		}

		// Get fields
		$fields = $this->fields_model->getFields('pictures', 1, 'edit', 'in_account');

		// Get album
		$album = array();
		if ( $albumID && ( !( $album = $this->pictures_albums_model->getAlbum($albumID, $fields, array('escape' => false, 'parse' => false)) ) || $album['user_id'] != session::item('user_id') ) )
		{
			view::setError(__('no_album', 'pictures'));
			router::redirect('pictures/albums/manage');
		}

		// Privacy options
		$privacy = array();

		// Do we need to add privacy field?
		if ( config::item('album_privacy_view', 'pictures') )
		{
			$items = $this->users_model->getPrivacyOptions(session::item('privacy_profile', 'config'));

			$privacy[] = array(
				'name' => __('privacy_album_view', 'pictures_privacy', array(), array(), false),
				'keyword' => 'privacy',
				'type' => 'select',
				'items' => $items,
				'privacy' => config::item('privacy_default', 'users'),
			);
		}

		// Do we need to add enable comments field?
		if ( config::item('picture_comments', 'pictures') && config::item('picture_privacy_comments', 'pictures') )
		{
			$items = $this->users_model->getPrivacyOptions(session::item('privacy_profile', 'config'), false);
			$items[0] = __('privacy_comments_disable', 'comments_privacy');

			$privacy[] = array(
				'name' => __('privacy_comments_post', 'comments_privacy', array(), array(), false),
				'keyword' => 'comments',
				'type' => 'select',
				'items' => $items,
				'comments' => config::item('privacy_default', 'users'),
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
		view::assign(array('albumID' => $albumID, 'album' => $album, 'fields' => $fields, 'privacy' => $privacy));

		// Process form values
		if ( input::post('do_save_album') )
		{
			$this->_saveAlbum($albumID, $album, $fields);
		}

		// Set title
		view::setTitle(__($albumID ? 'album_edit' : 'album_new', 'pictures'));

		// Set trail
		view::setTrail(session::item('slug'), __('my_profile', 'system_navigation'));
		view::setTrail('pictures/manage',  __('pictures_albums', 'system_navigation'));
		if ( $albumID && $album['total_pictures'] + $album['total_pictures_i'] > 0 )
		{
			view::setTrail('pictures/index/'.$album['album_id'].'/'.text_helper::slug($album['data_title'], 100), __('album_view', 'pictures'), array('side' => true));
		}

		// Assign actions
		if ( $albumID )
		{
			view::setAction('pictures/upload/' . $albumID, __('pictures_new', 'pictures'), array('class' => 'icon-text icon-pictures-new', 'data-role' => 'modal', 'data-title' => __('pictures_new', 'pictures')));
		}

		// Load view
		view::load('pictures/albums/edit');
	}

	protected function _saveAlbum($albumID, $album, $fields)
	{
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

		// Extra fields
		$extra = array();
		$extra['comments'] = config::item('picture_comments', 'pictures') && config::item('picture_privacy_comments', 'pictures') ? (int)input::post('comments') : 1;
		$extra['privacy'] = config::item('album_privacy_view', 'pictures') ? (int)input::post('privacy') : 1;
		$extra['public'] = config::item('album_privacy_public', 'pictures') ? (int)input::post('public') : 1;

		// Save album
		if ( !( $albumID = $this->pictures_albums_model->saveAlbumData($albumID, session::item('user_id'), $album, $fields, $extra) ) )
		{
			if ( !validate::getTotalErrors() )
			{
				view::setError(__('save_error', 'system'));
			}
			return false;
		}

		// Success
		view::setInfo(__('album_saved', 'pictures'));
		router::redirect('pictures/albums/edit/' . $albumID);
	}

	public function delete()
	{
		// Is user logged in?
		if ( !users_helper::isLoggedin() )
		{
			router::redirect('users/login');
		}
		// Does user have permission to delete albums?
		elseif ( !session::permission('albums_post', 'pictures') )
		{
			view::noAccess(session::item('slug'));
		}

		// Get URI vars
		$albumID = (int)uri::segment(4);

		// Get album
		if ( !$albumID || !( $album = $this->pictures_albums_model->getAlbum($albumID) ) || $album['user_id'] != session::item('user_id') )
		{
			view::setError(__('no_album', 'pictures'));
			router::redirect('pictures/albums/manage');
		}

		// Delete album
		$this->pictures_albums_model->deleteAlbum($albumID, session::item('user_id'), $album);

		// Process query string
		$qstring = $this->parseQuerystring(config::item('user_albums_per_page', 'pictures'));

		// Success
		view::setInfo(__('album_deleted', 'pictures'));
		router::redirect('pictures/albums/manage?' . $qstring['url'] . 'page=' . $qstring['page']);
	}

	protected function parseCounters($params = array(), $type = 'index')
	{
		// Assign vars
		view::assign(array('filters' => array(), 'values' => array()));

		// Do we have permission to search?
		if ( session::permission('albums_search', 'pictures') )
		{
			// Get fields
			$filters = $this->fields_model->getFields('pictures', 1, 'edit', 'in_search', true);

			// Set extra fields
			$filters[] = array(
				'name' => __('search_keyword', 'system'),
				'type' => 'text',
				'keyword' => 'q',
			);

			// Assign vars
			view::assign(array('filters' => $filters));

			// Did user submit the filter form?
			if ( input::post_get('do_search') && session::permission('albums_search', 'pictures') )
			{
				$values = array();
				$params['total'] = $params['max'] = 0;

				// Check extra keyword
				$keyword = utf8::trim(input::post_get('q'));
				if ( $keyword )
				{
					$params['join_columns'][] = $this->search_model->prepareValue($keyword, 'a', array('data_title', 'data_description'));
					$values['q'] = $keyword;
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
					return $params;
				}
				// Redirect to search results
				else
				{
					switch ( $type )
					{
						case 'user':
							router::redirect('pictures/user/' . uri::segment(4) . '?search_id=' . $searchID);
							break;

						case 'manage':
							router::redirect('pictures/manage?search_id=' . $searchID);
							break;

						default:
							router::redirect('pictures?search_id=' . $searchID);
							break;
					}
				}
			}

			// Do we have a search ID?
			if ( !input::post_get('do_search') && input::get('search_id') )
			{
				// Get search
				if ( !( $search = $this->search_model->getSearch(input::get('search_id')) ) )
				{
					view::setError(__('search_expired', 'system'));
					switch ( $type )
					{
						case 'user':
							router::redirect('pictures/user/' . uri::segment(4));
							break;

						case 'manage':
							router::redirect('pictures/manage');
							break;

						default:
							router::redirect('pictures');
							break;
					}
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
		}

		if ( !input::get('search_id') )
		{
			// Count albums
			if ( $type == 'manage' && !$params['total'] || $type != 'manage' && !( $params['total'] = $this->counters_model->countData('picture_album', 0, 0, $params['join_columns'], $params['join_items'], $params) ) )
			{
				if ( $type == 'manage' )
				{
					view::setInfo(__('no_albums_self', 'pictures'));
				}
				else
				{
					view::setInfo(__('no_albums', 'pictures'));
				}
			}
			$params['max'] = $params['total'];
		}

		return $params;
	}

	protected function parseQuerystring($pagination = 15, $max = 0)
	{
		$qstring = array();

		// Set max page
		$maxpage = $max ? ceil($max / $pagination) : 0;

		// Get current page
		$qstring['page'] = (int)input::get('page', 1);
		$qstring['page'] = $qstring['page'] > 0 ? ( !$maxpage || $qstring['page'] <= $maxpage ? $qstring['page'] : $maxpage ) : 1;

		// Get search id
		$qstring['search_id'] = input::get('search_id');

		// Get order field and direction
		$qstring['orderby'] = input::post_get('o') && in_array(input::post_get('o'), array('post_date', 'total_views', 'total_rating', 'total_votes', 'total_likes', 'total_pictures')) ? input::post_get('o') : 'post_date';
		$qstring['orderdir'] = input::post_get('d') && in_array(input::post_get('d'), array('asc', 'desc')) ? input::post_get('d') : 'desc';
		$qstring['order'] = $qstring['orderby'] ? array($qstring['orderby'] => $qstring['orderdir']) : array();

		// Create url string
		$qstring['url'] = ( $qstring['search_id'] ? 'search_id=' . $qstring['search_id'] . '&' : '' ) .
			( $qstring['orderby'] ? 'o=' . $qstring['orderby'] . '&' : '' ) .
			( $qstring['orderby'] && $qstring['orderdir'] ? 'd=' . $qstring['orderdir'] . '&' : '' );

		// Set limit
		$from = ( $qstring['page'] - 1 ) * $pagination;
		$qstring['limit'] = ( !$max || $from <= $max ? $from : $max ) . ', ' . ( !$max || $max >= $pagination ? $pagination : $max );

		// Assign vars
		view::assign(array('qstring' => $qstring));

		return $qstring;
	}
}
