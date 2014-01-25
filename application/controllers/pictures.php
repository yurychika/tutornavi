<?php

class Pictures_Controller extends Controller
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

		loader::model('pictures/albums', array(), 'pictures_albums_model');
		loader::model('pictures/pictures');
	}

	public function index()
	{
		// Get URI vars
		$albumID = (int)uri::segment(3);

		if ( !$albumID )
		{
			loader::controller('pictures/albums', array(), 'pictures_albums');
			$this->pictures_albums->index();
			return;
		}

		// Get album
		if ( !( $album = $this->pictures_albums_model->getAlbum($albumID, 'in_view') ) )
		{
			view::setError(__('no_album', 'pictures'));
			router::redirect('pictures');
		}

		// Is this our own album?
		if ( $album['user_id'] == session::item('user_id') )
		{
			// Assign user from session to variable
			$user = session::section('session');
		}
		else
		{
			// Get user
			if ( !( $user = $this->users_model->getUser($album['user_id']) ) || !$user['active'] || !$user['verified'] )
			{
				error::show404();
			}

			// Does user have permission to view this user group/type and browse pictures?
			if ( !in_array($user['group_id'], session::permission('users_groups_browse', 'users')) || !in_array($user['type_id'], session::permission('users_types_browse', 'users')) || !session::permission('albums_view', 'pictures') )
			{
				view::noAccess();
			}
			// Validate profile and album privacy
			elseif ( !$this->users_model->getPrivacyAccess($user['user_id'], ( isset($user['config']['privacy_profile']) ? $user['config']['privacy_profile'] : 1 )) || !$this->users_model->getPrivacyAccess($user['user_id'], $album['privacy']) )
			{
				view::noAccess($user['slug']);
			}
		}

		// Parameters
		$params = array(
			'join_columns' => array(
				'`p`.`album_id`=' . $albumID,
			),
			'join_items' => array(),
			'total' => $user['user_id'] != session::item('user_id') ? $album['total_pictures'] : ( $album['total_pictures'] + $album['total_pictures_i'] ),
			'select_users' => false,
		);

		if ( $user['user_id'] != session::item('user_id') )
		{
			$params['join_columns'][] = '`p`.`active`=1';
		}

		// Process filters
		$params = $this->parseCounters($params, 'manage');

		// Process query string
		$qstring = $this->parseQuerystring(config::item('user_pictures_per_page', 'pictures'), $params['max']);

		// Get pictures
		$pictures = array();
		if ( $params['total'] )
		{
			$pictures = $this->pictures_model->getPictures('in_list', $params['join_columns'], $params['join_items'], $qstring['order'], $qstring['limit'], $params);
		}

		// Set pagination
		$config = array(
			'base_url' => config::siteURL('pictures/index/' . $albumID . '/' . text_helper::slug($album['data_title'], 100) . '/?' . $qstring['url']),
			'total_items' => $params['total'],
			'max_items' => $params['max'],
			'items_per_page' => config::item('user_pictures_per_page', 'pictures'),
			'current_page' => $qstring['page'],
			'uri_segment' => 'page',
		);
		$pagination = loader::library('pagination', $config, null);

		// Load ratings
		if ( config::item('album_rating', 'pictures') == 'stars' )
		{
			// Load votes model
			loader::model('comments/votes');

			// Get votes
			$album['user_vote'] = $this->votes_model->getVote('picture_album', $albumID);
		}
		elseif ( config::item('album_rating', 'pictures') == 'likes' )
		{
			// Load likes model
			loader::model('comments/likes');

			// Get likes
			$album['user_vote'] = $this->likes_model->getLike('picture_album', $albumID);
		}

		// Assign vars
		view::assign(array('albumID' => $albumID, 'album' => $album, 'user' => $user, 'pictures' => $pictures, 'pagination' => $pagination));

		// Do we have views enabled?
		if ( config::item('album_views', 'pictures') )
		{
			// Update views counter
			$this->pictures_albums_model->updateViews($albumID);
		}

		// Set meta tags
		$this->metatags_model->set('pictures', 'albums_view', array('user' => $user, 'album' => $album));

		// Set title
		view::setTitle($album['data_title'], false);

		// Set trail
		if ( $user['user_id'] == session::item('user_id') )
		{
			view::setTrail(session::item('slug'), __('my_profile', 'system_navigation'));
			view::setTrail('pictures/manage', __('pictures_albums', 'system_navigation'));
		}
		else
		{
			view::setTrail($user['slug'], $user['name']);
			view::setTrail('pictures/user/' . $user['slug_id'], __('pictures_albums', 'system_navigation'));
		}

		// Assign actions
		if ( $user['user_id'] == session::item('user_id') )
		{
			view::setAction('pictures/upload/' . $albumID, __('pictures_new', 'pictures'), array('class' => 'icon-text icon-pictures-new', 'data-role' => 'modal', 'data-title' => __('pictures_new', 'pictures')));
			if ( $album['total_pictures'] + $album['total_pictures_i'] > 0 )
			{
				view::setAction('pictures/manage/' . $albumID, __('pictures_organize', 'pictures'), array('class' => 'icon-text icon-pictures-edit'));
			}
		}

		// Load view
		view::load('pictures/index');
	}

	public function user()
	{
		loader::controller('pictures_albums', array(), 'pictures_albums');
		$this->pictures_albums->user();
		return;
	}

	public function manage()
	{
		// Is user loggedin ?
		if ( !users_helper::isLoggedin() )
		{
			router::redirect('users/login');
		}
		// Does user have permission to post pictures?
		elseif ( !session::permission('pictures_post', 'pictures') )
		{
			view::noAccess(session::item('slug'));
		}

		// Get URI vars
		$albumID = (int)uri::segment(3);

		if ( !$albumID )
		{
			loader::controller('pictures/albums', array(), 'pictures_albums');
			$this->pictures_albums->manage();
			return;
		}

		// Assign user from session to variable
		$user = session::section('session');

		// Get album
		if ( !( $album = $this->pictures_albums_model->getAlbum($albumID, 'in_view') ) || session::item('user_id') != $album['user_id'] )
		{
			view::setError(__('no_album', 'pictures'));
			router::redirect('pictures');
		}

		// Does album have any pictures?
		if ( $album['total_pictures'] + $album['total_pictures_i'] == 0 )
		{
			view::setInfo(__('no_pictures_album', 'pictures'));
			router::redirect('pictures/index/' . $albumID . '/' . text_helper::slug($album['data_title'], 100));
		}

		// Get fields
		$fields = $this->fields_model->getFields('pictures', 0, 'edit', 'in_account');
		foreach ( $fields as $index => $field )
		{
			if ( $field['keyword'] != 'description' )
			{
				unset($fields[$index]);
			}
		}

		// Parameters
		$params = array(
			'join_columns' => array(
				'`p`.`album_id`=' . $albumID,
			),
			'join_items' => array(),
			'total' => ( $album['total_pictures'] + $album['total_pictures_i'] ),
		);

		// Process query string
		$qstring = $this->parseQuerystring();

		// Get pictures
		$pictures = $this->pictures_model->getPictures('in_account', $params['join_columns'], $params['join_items'], $qstring['order'], ( $album['total_pictures'] + $album['total_pictures_i'] ), array('escape' => false, 'parse' => false, 'select_users' => false));

		// Assign vars
		view::assign(array('albumID' => $albumID, 'user' => $user, 'album' => $album, 'fields' => $fields, 'pictures' => $pictures));

		// Process form values
		if ( input::post('do_save_pictures') )
		{
			$this->_savePictures($albumID, $pictures, $album, $fields);
		}

		// Set title
		view::setTitle(__('pictures_organize', 'pictures'));

		// Set trail
		view::setTrail(session::item('slug'), __('my_profile', 'system_navigation'));
		view::setTrail('pictures/manage', __('pictures_albums', 'system_navigation'));
		view::setTrail('pictures/index/'.$album['album_id'].'/'.text_helper::slug($album['data_title'], 100), __('album_view', 'pictures'), array('side' => true));

		// Load view
		view::load('pictures/manage');
	}

	protected function _savePictures($albumID, $pictures, $album, $fields)
	{
		// Loop through pictures
		foreach ( $pictures as $picture )
		{
			foreach ( $fields as $index => $field )
			{
				if ( !isset($fields[$index]['keyword_original']) )
				{
					$fields[$index]['keyword_original'] = $field['keyword_original'] = $field['keyword'];
				}
				$fields[$index]['keyword'] = $field['keyword_original'] . '_' . $picture['picture_id'];
			}

			// Validate form values
			if ( !$this->fields_model->validateValues($fields) )
			{
				return false;
			}
		}

		// Number of deleted pictures
		$deleted = 0;

		// Loop through pictures
		foreach ( $pictures as $picture )
		{
			foreach ( $fields as $index => $field )
			{
				$fields[$index]['keyword'] = $field['keyword_original'];
				$_POST['data_' . $field['keyword_original']] = input::post('data_' . $field['keyword_original'] . '_' . $picture['picture_id']);
			}

			// Do we need to delete this picture?
			if ( input::post('delete_' . $picture['picture_id']) )
			{
				// Update picture's order ID
				$picture['order_id'] = $picture['order_id'] - $deleted;

				// Delete picture
				$this->pictures_model->deletePicture($picture['picture_id'], $albumID, session::item('user_id'), $picture, $album);

				// Update current album counters
				$album[$picture['active'] ? 'total_pictures' : 'total_pictures_i']--;
				$deleted++;
			}
			else
			{
				// Extras
				$extra = array();

				// Save picture
				if ( !( $pictureID = $this->pictures_model->savePictureData($picture['picture_id'], $albumID, $picture, $album, $fields, $extra) ) )
				{
					if ( !validate::getTotalErrors() )
					{
						view::setError(__('save_error', 'system'));
					}
					return false;
				}
			}
		}

		// Success
		view::setInfo(__('pictures_saved', 'pictures'));
		router::redirect('pictures/manage/' . $albumID);
	}

	public function upload()
	{
		// Is user logged in?
		if ( !users_helper::isLoggedin() )
		{
			router::redirect('users/login');
		}
		// Does user have permission to post pictures?
		elseif ( !session::permission('pictures_post', 'pictures') )
		{
			view::noAccess(session::item('slug'));
		}

		// Get URI vars
		$albumID = (int)uri::segment(3);

		// Get album
		if ( !$albumID  || !( $album = $this->pictures_albums_model->getAlbum($albumID, 'in_view') ) || $album['user_id'] != session::item('user_id') )
		{
			view::setError(__('no_album', 'pictures'));
			router::redirect('pictures');
		}

		// Did user reach the max pictures limit?
		if ( session::permission('pictures_limit', 'pictures') && session::permission('pictures_limit', 'pictures') <= ( $album['total_pictures'] + $album['total_pictures_i'] ) )
		{
			view::setError(__('picture_limit_reached', 'pictures', array('%limit%' => session::permission('pictures_limit', 'pictures'))));
			router::redirect('pictures/index/' . $albumID . '/' . text_helper::slug($album['data_title'], 100));
		}

		// Set limit
		$limit = !session::permission('pictures_limit', 'pictures') || session::permission('pictures_limit', 'pictures') > config::item('picture_upload_limit', 'pictures') ? config::item('picture_upload_limit', 'pictures') : session::permission('pictures_limit', 'pictures');
		$limit = session::permission('pictures_limit', 'pictures') && ( $album['total_pictures'] + $album['total_pictures_i'] + $limit ) > session::permission('pictures_limit', 'pictures') ? ( session::permission('pictures_limit', 'pictures') - $album['total_pictures'] - $album['total_pictures_i'] ) : $limit;

		// Process form values
		if ( input::files('file') )
		{
			$this->_uploadPicture($albumID, $album);
		}

		// Assign vars
		view::assign(array('albumID' => $albumID, 'album' => $album, 'limit' => $limit));

		// Add storage includes
		//$this->storage_model->includeExternals();

		// Set title
		view::setTitle(__('pictures_new', 'pictures'));

		// Set trail
		view::setTrail(session::item('slug'), __('my_profile', 'system_navigation'));
		view::setTrail('pictures/manage', __('pictures_albums', 'system_navigation'));
		view::setTrail('pictures/index/'.$album['album_id'].'/'.text_helper::slug($album['data_title'], 100), __('album_view', 'pictures'), array('side' => true));

		// Load view
		view::load('pictures/upload');
	}

	protected function _uploadPicture($albumID, $album)
	{
		// Create rules
		$rules = array(
			'file' => array(
				'label' => __('file_select', 'system_files'),
				'rules' => array('required_file' => 'file'),
			)
		);

		// Assign rules
		validate::setRules($rules);

		// Validate form values
		if ( !validate::run() )
		{
			return false;
		}

		// Thumbnails config
		$thumbs = array(
			array(
				'suffix' => 'x', // original
			),
			array(
				'dimensions' => config::item('picture_dimensions', 'pictures'),
				'method' => 'preserve',
				'suffix' => '', // large
			),
			array(
				'dimensions' => config::item('picture_dimensions_t', 'pictures'),
				'method' => 'crop',
				'suffix' => 't', // thumbnail
			),
		);

		// Upload picture
		if ( !( $fileID = $this->storage_model->upload('picture', session::item('user_id'), 'file', 'jpg|jpeg|gif|png', config::item('picture_max_size', 'pictures'), config::item('picture_dimensions_max', 'pictures'), $thumbs) ) )
		{
			if ( input::isAjaxRequest() )
			{
				view::ajaxError(config::item('devmode', 'system') ? $this->storage_model->getError() : __('file_upload_error', 'system_files'));
			}
			else
			{
				validate::setFieldError('file', config::item('devmode', 'system') ? $this->storage_model->getError() : __('file_upload_error', 'system_files'));
				return false;
			}
		}

		// Extras
		$extra = array();

		// Save picture file
		if ( !( $pictureID = $this->pictures_model->savePictureFile($fileID, $albumID, $album, $extra) ) )
		{
			if ( input::isAjaxRequest() )
			{
				view::ajaxError(__('save_error', 'system'));
			}
			else
			{
				validate::setFieldError('file', __('save_error', 'system'));
				return false;
			}
		}

		// Update current album counters
		$album[session::permission('pictures_approve', 'pictures') ? 'total_pictures' : 'total_pictures_i']++;

		// Update album's modification date
		$this->pictures_albums_model->updateModifyDate($albumID);

		// Was this an ajax request?
		if ( input::isAjaxRequest() )
		{
			//view::ajaxResponse(__('picture_uploaded', 'pictures'));
			view::ajaxResponse(array('redirect' => html_helper::siteURL('pictures/index/' . $albumID)));
		}

		// Success
		view::setInfo(__('picture_uploaded', 'pictures'));

		router::redirect('pictures/index/' . $albumID);
	}

	public function thumbnail()
	{
		// Is user logged in?
		if ( !users_helper::isLoggedin() )
		{
			router::redirect('users/login');
		}

		// Get URI vars
		$albumID = (int)uri::segment(3);
		$pictureID = (int)uri::segment(4);

		// Get album
		if ( !$albumID  || !( $album = $this->pictures_albums_model->getAlbum($albumID, 'in_view') ) || $album['user_id'] != session::item('user_id') )
		{
			view::setError(__('no_album', 'pictures'));
			router::redirect('pictures');
		}

		// Get picture
		if ( !$pictureID || !( $picture = $this->pictures_model->getPicture($pictureID, 'in_view') ) || $picture['user_id'] != session::item('user_id') )
		{
			view::setError(__('no_picture', 'pictures'));
			router::redirect('pictures/index/' . $albumID . '/' . text_helper::slug($album['data_title'], 100));
		}

		// Assign vars
		view::assign(array('albumID' => $albumID, 'pictureID' => $pictureID, 'picture' => $picture, 'album' => $album));

		// Resize picture?
		if ( input::post('do_save_thumbnail') )
		{
			$this->_saveThumbnail($pictureID, $picture, $album);
		}

		// Set title
		view::setTitle(__('picture_thumbnail_edit', 'system_files'));

		// Set trail
		view::setTrail(session::item('slug'), __('my_profile', 'system_navigation'));
		view::setTrail('pictures/manage', __('pictures_albums', 'system_navigation'));
		view::setTrail('pictures/index/'.$album['album_id'].'/'.text_helper::slug($album['data_title'], 100), __('album_view', 'pictures'), array('side' => true));

		// Include jcron files
		view::includeJavascript('externals/jcrop/jcrop.min.js');
		view::includeStylesheet('externals/jcrop/style.css');

		// Load view
		view::load('pictures/thumbnail');
	}

	protected function _saveThumbnail($pictureID, $picture, $album)
	{
		// Get coordinates
		$x = (int)input::post('picture_thumb_x');
		$y = (int)input::post('picture_thumb_y');
		$w = (int)input::post('picture_thumb_w');
		$h = (int)input::post('picture_thumb_h');

		// Validate coordinates
		if (  ( $w + 10 ) < config::item('picture_dimensions_t_width', 'pictures') || ( $h + 10 ) < config::item('picture_dimensions_p_height', 'pictures') ||
			( $w - 10 ) > $picture['file_width'] || ( $h - 10 ) > $picture['file_height'] ||
			$x < 0 || $y < 0 || ( $x + $w - 10 ) > $picture['file_width'] || ( $y + $h - 10 ) > $picture['file_height'] )
		{
			view::setError(__('save_error', 'system'));
			return false;
		}

		// Create thumbnails
		if ( !$this->pictures_model->saveThumbnail($picture['file_id'], $x, $y, $w, $h) )
		{
			view::setError(__('save_error', 'system'));
			return false;
		}

		// Success
		router::redirect('pictures/view/' . $pictureID . '/' . text_helper::slug($picture['data_description'], 100));
	}

	public function edit()
	{
		// Is user logged in?
		if ( !users_helper::isLoggedin() )
		{
			router::redirect('users/login');
		}
		// Does user have permission to post pictures?
		elseif ( !session::permission('pictures_post', 'pictures') )
		{
			view::noAccess(session::item('slug'));
		}

		// Get URI vars
		$albumID = (int)uri::segment(3);
		$pictureID = (int)uri::segment(4);

		// Get album
		if ( !$albumID  || !( $album = $this->pictures_albums_model->getAlbum($albumID, 'in_view') ) || $album['user_id'] != session::item('user_id') )
		{
			view::setError(__('no_album', 'pictures'));
			router::redirect('pictures');
		}

		// Get fields
		$fields = $this->fields_model->getFields('pictures', 0, 'edit', 'in_account');

		// Get picture
		if ( !$pictureID || !( $picture = $this->pictures_model->getPicture($pictureID, $fields, array('escape' => false, 'parse' => false)) ) || $picture['user_id'] != session::item('user_id') )
		{
			view::setError(__('no_picture', 'pictures'));
			router::redirect('pictures/index/' . $albumID . '/' . text_helper::slug($album['data_title'], 100));
		}

		// Assign vars
		view::assign(array('albumID' => $albumID, 'pictureID' => $pictureID, 'picture' => $picture, 'fields' => $fields));

		// Process form values
		if ( input::post('do_save_picture') )
		{
			$this->_savePicture($pictureID, $albumID, $picture, $album, $fields);
		}

		// Set title
		view::setTitle(__('picture_edit', 'pictures'));

		// Set trail
		view::setTrail(session::item('slug'), __('my_profile', 'system_navigation'));
		view::setTrail('pictures/manage', __('pictures_albums', 'system_navigation'));
		view::setTrail('pictures/index/'.$album['album_id'].'/'.text_helper::slug($album['data_title'], 100), __('album_view', 'pictures'), array('side' => true));

		// Load view
		view::load('pictures/edit');
	}

	protected function _savePicture($pictureID, $albumID, $picture, $album, $fields)
	{
		// Create rules
		$rules = array();

		// Validate form values
		if ( !$this->fields_model->validateValues($fields, $rules) )
		{
			return false;
		}

		// Extras
		$extra = array();

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
		router::redirect('pictures/edit/' . $albumID . '/' . $pictureID);
	}

	public function rotate()
	{
		// Is user logged in?
		if ( !users_helper::isLoggedin() )
		{
			router::redirect('users/login');
		}

		// Get URI vars
		$albumID = (int)uri::segment(3);
		$pictureID = (int)uri::segment(4);
		$angle = uri::segment(5) == 'left' ? 90 : -90;

		// Get album
		if ( !$albumID  || !( $album = $this->pictures_albums_model->getAlbum($albumID, 'in_view') ) || $album['user_id'] != session::item('user_id') )
		{
			view::setError(__('no_album', 'pictures'));
			router::redirect('pictures');
		}

		// Get picture
		if ( !$pictureID || !( $picture = $this->pictures_model->getPicture($pictureID, 'in_view') ) || $picture['user_id'] != session::item('user_id') )
		{
			view::setError(__('no_picture', 'pictures'));
			router::redirect('pictures/index/' . $albumID . '/' . text_helper::slug($album['data_title'], 100));
		}

		// Assign vars
		view::assign(array('albumID' => $albumID, 'pictureID' => $pictureID, 'picture' => $picture, 'album' => $album));

		// Rotate picture
		$this->pictures_model->rotatePicture($picture['file_id'], $angle);

		// Success
		router::redirect('pictures/view/' . $pictureID . '/' . text_helper::slug($picture['data_description'], 100));
	}

	public function cover()
	{
		// Is user loggedin ?
		if ( !users_helper::isLoggedin() )
		{
			router::redirect('users/login');
		}
		// Does user have permission to post pictures?
		elseif ( !session::permission('pictures_post', 'pictures') )
		{
			view::noAccess(session::item('slug'));
		}

		// Get URI vars
		$albumID = (int)uri::segment(3);
		$pictureID = (int)uri::segment(4);

		// Get album
		if ( !$albumID  || !( $album = $this->pictures_albums_model->getAlbum($albumID, 'in_view') ) || $album['user_id'] != session::item('user_id') )
		{
			view::setError(__('no_album', 'pictures'));
			router::redirect('pictures');
		}

		// Get picture
		if ( !$pictureID || !( $picture = $this->pictures_model->getPicture($pictureID, 'in_view') ) || $picture['album_id'] != $albumID  )
		{
			view::setError(__('no_picture', 'pictures'));
			router::redirect('pictures/index/' . $albumID . '/' . text_helper::slug($album['data_title'], 100));
		}

		// Update album cover
		$this->pictures_albums_model->updateCover($albumID, $pictureID);

		// Process query string
		$qstring = $this->parseQuerystring();

		// Success
		view::setInfo(__('album_cover_saved', 'pictures'));
		router::redirect('pictures/view/' . $pictureID . '/' . text_helper::slug($picture['data_description'], 100));
	}

	public function view()
	{
		// Get URI vars
		$pictureID = (int)uri::segment(3);

		// Get picture
		if ( !$pictureID || !( $picture = $this->pictures_model->getPicture($pictureID, 'in_view') ) || ( !$picture['active'] && $picture['user_id'] != session::item('user_id') ) )
		{
			error::show404();
		}
		$pictureID = $picture['picture_id'];

		// Get album
		if ( !( $album = $this->pictures_albums_model->getAlbum($picture['album_id'], 'in_view') ) )
		{
			error::show404();
		}

		// Is this our own picture?
		if ( $picture['user_id'] == session::item('user_id') )
		{
			// Assign user from session to variable
			$user = session::section('session');
		}
		else
		{
			// Get user
			if ( !( $user = $this->users_model->getUser($picture['user_id']) ) || !$user['active'] || !$user['verified'] )
			{
				error::show404();
			}

			// Does user have permission to view this user group/type and view pictures?
			if ( !in_array($user['group_id'], session::permission('users_groups_browse', 'users')) || !in_array($user['type_id'], session::permission('users_types_browse', 'users')) || !session::permission('pictures_view', 'pictures') )
			{
				view::noAccess();
			}
			// Validate profile and album privacy
			elseif ( !$this->users_model->getPrivacyAccess($user['user_id'], ( isset($user['config']['privacy_profile']) ? $user['config']['privacy_profile'] : 1 )) || !$this->users_model->getPrivacyAccess($user['user_id'], $album['privacy']) )
			{
				view::noAccess($user['slug']);
			}
		}

		// Do we have views enabled?
		if ( config::item('picture_views', 'pictures') )
		{
			// Update views counter
			$this->pictures_model->updateViews($pictureID);
		}

		$previousPicture = $nextPicture = array();
		$previousURL = $nextURL = '';
		// Does album have more than 1 active picture?
		if ( $album['total_pictures'] > 1 )
		{
			// Get previous/next pictures
			list($previousPicture, $nextPicture) = $this->pictures_model->getPictureSiblings(session::item('user_id'), $picture['album_id'], $picture['order_id'], ( $user['user_id'] != session::item('user_id') ? $album['total_pictures'] : ( $album['total_pictures'] + $album['total_pictures_i'] ) ));

			if ( $previousPicture )
			{
				$previousURL = 'pictures/view/' . $previousPicture['picture_id'] . '/' . text_helper::slug($previousPicture['data_description'] ? $previousPicture['data_description'] : $album['data_title'], 100);
			}

			if ( $nextPicture )
			{
				$nextURL = 'pictures/view/' . $nextPicture['picture_id'] . '/' . text_helper::slug($nextPicture['data_description'] ? $nextPicture['data_description'] : $album['data_title'], 100);
			}
		}

		// Load ratings
		if ( config::item('picture_rating', 'pictures') == 'stars' )
		{
			// Load votes model
			loader::model('comments/votes');

			// Get votes
			$picture['user_vote'] = $this->votes_model->getVote('picture', $pictureID);
		}
		elseif ( config::item('picture_rating', 'pictures') == 'likes' )
		{
			// Load likes model
			loader::model('comments/likes');

			// Get likes
			$picture['user_vote'] = $this->likes_model->getLike('picture', $pictureID);
		}

		// Assign vars
		view::assign(array('pictureID' => $pictureID, 'picture' => $picture, 'album' => $album, 'user' => $user, 'previousURL' => $previousURL, 'nextURL' => $nextURL));

		// Set meta tags
		$this->metatags_model->set('pictures', 'pictures_view', array('user' => $user, 'album' => $album, 'picture' => $picture));

		// Set title
		view::setTitle($album['data_title'] . ( $picture['data_description'] ? ' - ' . $picture['data_description'] : '' ), false);

		// Set trail
		if ( $user['user_id'] == session::item('user_id') )
		{
			view::setTrail(session::item('slug'), __('my_profile', 'system_navigation'));
			view::setTrail('pictures/manage', __('pictures_albums', 'system_navigation'));
		}
		else
		{
			view::setTrail($user['slug'], $user['name']);
			view::setTrail('pictures/user/' . $user['slug_id'], __('pictures_albums', 'system_navigation'));
		}
		view::setTrail('pictures/index/'.$album['album_id'].'/'.text_helper::slug($album['data_title'], 100), __('album_view', 'pictures'), array('side' => true));

		// Assign actions
		view::setAction(false, __('pictures_view_counter', 'pictures', array('%current' => $picture['order_id'], '%total' => ( $user['user_id'] != session::item('user_id') ? $album['total_pictures'] : ( $album['total_pictures'] + $album['total_pictures_i'] ) ))));

		// Load view
		view::load('pictures/view');
	}

	public function delete()
	{
		// Is user loggedin ?
		if ( !users_helper::isLoggedin() )
		{
			router::redirect('users/login');
		}
		// Does user have permission to delete pictures?
		elseif ( !session::permission('pictures_post', 'pictures') )
		{
			view::noAccess(session::item('slug'));
		}

		// Get URI vars
		$albumID = (int)uri::segment(3);
		$pictureID = (int)uri::segment(4);

		// Get album
		if ( !$albumID  || !( $album = $this->pictures_albums_model->getAlbum($albumID, 'in_view') ) || $album['user_id'] != session::item('user_id') )
		{
			view::setError(__('no_album', 'pictures'));
			router::redirect('pictures');
		}

		// Get picture
		if ( !$pictureID || !( $picture = $this->pictures_model->getPicture($pictureID) ) || $picture['album_id'] != $albumID  )
		{
			view::setError(__('no_picture', 'pictures'));
			router::redirect('pictures/index/' . $albumID . '/' . text_helper::slug($album['data_title'], 100));
		}

		// Delete picture
		$this->pictures_model->deletePicture($pictureID, $albumID, session::item('user_id'), $picture, $album);

		// Process query string
		$qstring = $this->parseQuerystring(config::item('user_pictures_per_page', 'pictures'));

		// Success
		view::setInfo(__('picture_deleted', 'pictures'));
		router::redirect('pictures/index/' . $albumID . '/' . text_helper::slug($album['data_title'], 100) . '?' . $qstring['url'] . 'page=' . $qstring['page']);
	}

	protected function parseCounters($params = array())
	{
		// Do we have any pictures?
		if ( !$params['total'] )
		{
			view::setInfo(__('no_pictures_album', 'pictures'));
		}
		$params['max'] = $params['total'];

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
		$qstring['orderby'] = input::post_get('o') && in_array(input::post_get('o'), array('post_date', 'total_views', 'total_rating', 'total_votes', 'total_likes', 'total_comments', 'order_id')) ? input::post_get('o') : 'order_id';
		$qstring['orderdir'] = input::post_get('d') && in_array(input::post_get('d'), array('asc', 'desc')) ? input::post_get('d') : 'asc';
		$qstring['order'] = $qstring['orderby'] ? array($qstring['orderby'] => $qstring['orderdir']) : array('order_id' => 'asc');

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
