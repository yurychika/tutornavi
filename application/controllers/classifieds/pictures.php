<?php

class Classifieds_Pictures_Controller extends Controller
{
	public function __construct()
	{
		parent::__construct();

		if ( !config::item('classifieds_active', 'classifieds') )
		{
			error::show404();
		}
		elseif ( !session::permission('ads_access', 'classifieds') )
		{
			view::noAccess();
		}

		loader::model('classifieds/classifieds');
		loader::model('classifieds/pictures', array(), 'classifieds_pictures_model');
	}

	public function index()
	{
		// Get URI vars
		$adID = (int)uri::segment(4);

		// Get albadum
		if ( !$adID || !( $ad = $this->classifieds_model->getAd($adID, 'in_view') ) )
		{
			view::setError(__('no_ad', 'classifieds'));
			router::redirect('classifieds');
		}

		// Is this our own album?
		if ( $ad['user_id'] == session::item('user_id') )
		{
			// Assign user from session to variable
			$user = session::section('session');
		}
		else
		{
			// Get user
			if ( !( $user = $this->users_model->getUser($ad['user_id']) ) || !$user['active'] || !$user['verified'] )
			{
				error::show404();
			}

			// Does user have permission to view this user group/type and browse pictures?
			if ( !in_array($user['group_id'], session::permission('users_groups_browse', 'users')) || !in_array($user['type_id'], session::permission('users_types_browse', 'users')) || !session::permission('ads_view', 'classifieds') )
			{
				view::noAccess();
			}
		}

		// Parameters
		$params = array(
			'select_users' => false,
			'join_columns' => array(
				'`a`.`ad_id`=' . $adID,
			),
			'join_items' => array(),
			'total' => $user['user_id'] != session::item('user_id') ? $ad['total_pictures'] : ( $ad['total_pictures'] + $ad['total_pictures_i'] ),
		);

		if ( $user['user_id'] != session::item('user_id') )
		{
			$params['join_columns'][] = '`a`.`active`=1';
			$params['join_columns'][] = '`p`.`active`=1';
		}

		// Process filters
		$params = $this->parseCounters($params, 'manage');

		// Process query string
		$qstring = $this->parseQuerystring(config::item('pictures_per_page', 'classifieds'), $params['max']);

		// Get pictures
		$pictures = array();
		if ( $params['total'] )
		{
			$pictures = $this->classifieds_pictures_model->getPictures('in_list', $params['join_columns'], $params['join_items'], $qstring['order'], $qstring['limit'], $params);
		}

		// Set pagination
		$config = array(
			'base_url' => config::siteURL('classifieds/pictures/index/' . $adID . '/' . text_helper::slug($ad['data_title'], 100) . '/?' . $qstring['url']),
			'total_items' => $params['total'],
			'max_items' => $params['max'],
			'items_per_page' => config::item('pictures_per_page', 'classifieds'),
			'current_page' => $qstring['page'],
			'uri_segment' => 'page',
		);
		$pagination = loader::library('pagination', $config, null);

		// Load ratings
		if ( config::item('ad_rating', 'classifieds') == 'stars' )
		{
			// Load votes model
			loader::model('comments/votes');

			// Get votes
			$ad['user_vote'] = $this->votes_model->getVote('classified_ad', $adID);
		}
		elseif ( config::item('ad_rating', 'classifieds') == 'likes' )
		{
			// Load likes model
			loader::model('comments/likes');

			// Get likes
			$ad['user_vote'] = $this->likes_model->getLike('classified_ad', $adID);
		}

		// Assign vars
		view::assign(array('adID' => $adID, 'ad' => $ad, 'user' => $user, 'pictures' => $pictures, 'pagination' => $pagination));

		// Set meta tags
		$this->metatags_model->set('classifieds', 'classifieds_view', array('user' => $user, 'ad' => $ad));

		// Set title
		view::setTitle($ad['data_title'], false);

		// Set trail
		if ( $user['user_id'] == session::item('user_id') )
		{
			view::setTrail(session::item('slug'), __('my_profile', 'system_navigation'));
			view::setTrail('classifieds/manage',  __('classifieds', 'system_navigation'));
		}
		else
		{
			view::setTrail($user['slug'], $user['name']);
			view::setTrail('classifieds/user/' . $user['slug_id'], __('classifieds', 'system_navigation'));
		}
		view::setTrail('classifieds/view/'.$ad['ad_id'].'/'.text_helper::slug($ad['data_title'], 100), __('ad_view', 'classifieds'), array('side' => true));

		// Assign actions
		if ( $user['user_id'] == session::item('user_id') )
		{
			view::setAction('classifieds/pictures/upload/' . $adID, __('pictures_new', 'classifieds'), array('class' => 'icon-text icon-classifieds-pictures-new', 'data-role' => 'modal', 'data-title' => __('pictures_new', 'classifieds')));
			if ( $ad['total_pictures'] + $ad['total_pictures_i'] > 0 )
			{
				view::setAction('classifieds/pictures/manage/' . $adID, __('pictures_organize', 'classifieds'), array('class' => 'icon-text icon-classifieds-pictures-edit'));
			}
		}

		// Load view
		view::load('classifieds/pictures/index');
	}

	public function manage()
	{
		// Is user loggedin ?
		if ( !users_helper::isLoggedin() )
		{
			router::redirect('users/login');
		}
		// Does user have permission to post pictures?
		elseif ( !session::permission('pictures_post', 'classifieds') )
		{
			view::noAccess(session::item('slug'));
		}

		// Get URI vars
		$adID = (int)uri::segment(4);

		// Get album
		if ( !$adID || !( $ad = $this->classifieds_model->getAd($adID, 'in_view') ) || session::item('user_id') != $ad['user_id'] )
		{
			view::setError(__('no_ad', 'classifieds'));
			router::redirect('classifieds');
		}

		// Does album have any pictures?
		if ( $ad['total_pictures'] + $ad['total_pictures_i'] == 0 )
		{
			view::setInfo(__('no_pictures_ad', 'classifieds'));
			router::redirect('classifieds/pictures/index/' . $adID . '/' . text_helper::slug($ad['data_title'], 100));
		}

		// Get fields
		$fields = $this->fields_model->getFields('classifieds', 1, 'edit', 'in_account');
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
				'`p`.`ad_id`=' . $adID,
			),
			'join_items' => array(),
			'total' => ( $ad['total_pictures'] + $ad['total_pictures_i'] ),
		);

		// Process query string
		$qstring = $this->parseQuerystring();

		// Get pictures
		$pictures = $this->classifieds_pictures_model->getPictures('in_account', $params['join_columns'], $params['join_items'], $qstring['order'], ( $ad['total_pictures'] + $ad['total_pictures_i'] ), array('escape' => false, 'parse' => false, 'select_users' => false));

		// Assign vars
		view::assign(array('adID' => $adID, 'ad' => $ad, 'fields' => $fields, 'pictures' => $pictures));

		// Process form values
		if ( input::post('do_save_pictures') )
		{
			$this->_savePictures($adID, $pictures, $ad, $fields);
		}

		// Set title
		view::setTitle(__('pictures_organize', 'classifieds'));

		// Set trail
		view::setTrail(session::item('slug'), __('my_profile', 'system_navigation'));
		view::setTrail('classifieds/manage',  __('classifieds', 'system_navigation'));
		view::setTrail('classifieds/view/'.$ad['ad_id'].'/'.text_helper::slug($ad['data_title'], 100), __('ad_view', 'classifieds'), array('side' => true));

		// Load view
		view::load('classifieds/pictures/manage');
	}

	protected function _savePictures($adID, $pictures, $ad, $fields)
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
				$this->classifieds_pictures_model->deletePicture($picture['picture_id'], $adID, session::item('user_id'), $picture, $ad);

				// Update current album counters
				$ad[$picture['active'] ? 'total_pictures' : 'total_pictures_i']--;
				$deleted++;
			}
			else
			{
				// Extras
				$extra = array();

				// Save picture
				if ( !( $pictureID = $this->classifieds_pictures_model->savePictureData($picture['picture_id'], $adID, $picture, $ad, $fields, $extra) ) )
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
		view::setInfo(__('pictures_saved', 'classifieds'));
		router::redirect('classifieds/pictures/manage/' . $adID);
	}

	public function upload()
	{
		// Is user logged in?
		if ( !users_helper::isLoggedin() )
		{
			router::redirect('users/login');
		}
		// Does user have permission to post pictures?
		elseif ( !session::permission('pictures_post', 'classifieds') )
		{
			view::noAccess(session::item('slug'));
		}

		// Get URI vars
		$adID = (int)uri::segment(4);

		// Get album
		if ( !$adID  || !( $ad = $this->classifieds_model->getAd($adID, 'in_view') ) || $ad['user_id'] != session::item('user_id') )
		{
			view::setError(__('no_ad', 'classifieds'));
			router::redirect('classifieds');
		}

		// Did user reach the max pictures limit?
		if ( session::permission('pictures_limit', 'classifieds') && session::permission('pictures_limit', 'classifieds') <= ( $ad['total_pictures'] + $ad['total_pictures_i'] ) )
		{
			view::setError(__('picture_limit_reached', 'classifieds', array('%limit%' => session::permission('pictures_limit', 'classifieds'))));
			router::redirect('classifieds/view/' . $adID . '/' . text_helper::slug($ad['data_title'], 100));
		}

		// Set limit
		$limit = !session::permission('pictures_limit', 'classifieds') || session::permission('pictures_limit', 'classifieds') > config::item('picture_upload_limit', 'classifieds') ? config::item('picture_upload_limit', 'classifieds') : session::permission('pictures_limit', 'classifieds');
		$limit = session::permission('pictures_limit', 'classifieds') && ( $ad['total_pictures'] + $ad['total_pictures_i'] + $limit ) > session::permission('pictures_limit', 'classifieds') ? ( session::permission('pictures_limit', 'classifieds') - $ad['total_pictures'] - $ad['total_pictures_i'] ) : $limit;

		// Process form values
		if ( input::files('file') )
		{
			$this->_uploadPicture($adID, $ad);
		}

		// Assign vars
		view::assign(array('adID' => $adID, 'ad' => $ad, 'limit' => $limit));

		// Add storage includes
		//$this->storage_model->includeExternals();

		// Set title
		view::setTitle(__('pictures_new', 'classifieds'));

		// Set trail
		view::setTrail(session::item('slug'), __('my_profile', 'system_navigation'));
		view::setTrail('classifieds/manage',  __('classifieds', 'system_navigation'));
		view::setTrail('classifieds/view/'.$ad['ad_id'].'/'.text_helper::slug($ad['data_title'], 100), __('ad_view', 'classifieds'), array('side' => true));

		// Load view
		view::load('classifieds/pictures/upload');
	}

	protected function _uploadPicture($adID, $ad)
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
				'dimensions' => config::item('picture_dimensions', 'classifieds'),
				'method' => 'preserve',
				'suffix' => '', // large
			),
			array(
				'dimensions' => config::item('picture_dimensions_t', 'classifieds'),
				'method' => 'crop',
				'suffix' => 't', // thumbnail
			),
		);

		// Upload picture
		if ( !( $fileID = $this->storage_model->upload('classified_picture', session::item('user_id'), 'file', 'jpg|jpeg|gif|png', config::item('picture_max_size', 'classifieds'), config::item('picture_dimensions_max', 'classifieds'), $thumbs) ) )
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
		if ( !( $pictureID = $this->classifieds_pictures_model->savePictureFile($fileID, $adID, $ad, $extra) ) )
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
		$ad[session::permission('pictures_approve', 'classifieds') ? 'total_pictures' : 'total_pictures_i']++;

		// Update album's modification date
		$this->classifieds_model->updateModifyDate($adID);

		// Was this an ajax request?
		if ( input::isAjaxRequest() )
		{
			//view::ajaxResponse(__('picture_uploaded', 'pictures'));
			view::ajaxResponse(array('redirect' => html_helper::siteURL('classifieds/pictures/index/' . $adID)));
		}

		// Success
		view::setInfo(__('picture_uploaded', 'classifieds'));

		router::redirect('classifieds/pictures/index/' . $adID);
	}

	public function thumbnail()
	{
		// Is user logged in?
		if ( !users_helper::isLoggedin() )
		{
			router::redirect('users/login');
		}

		// Get URI vars
		$adID = (int)uri::segment(4);
		$pictureID = (int)uri::segment(5);

		// Get album
		if ( !$adID  || !( $ad = $this->classifieds_model->getAd($adID, 'in_view') ) || $ad['user_id'] != session::item('user_id') )
		{
			view::setError(__('no_ad', 'classifieds'));
			router::redirect('classifieds');
		}

		// Get picture
		if ( !$pictureID || !( $picture = $this->classifieds_pictures_model->getPicture($pictureID, 'in_view') ) || $picture['user_id'] != session::item('user_id') )
		{
			view::setError(__('no_picture', 'classifieds'));
			router::redirect('classifieds/pictures/index/' . $adID . '/' . text_helper::slug($ad['data_title'], 100));
		}

		// Assign vars
		view::assign(array('adID' => $adID, 'pictureID' => $pictureID, 'picture' => $picture, 'ad' => $ad));

		// Resize picture?
		if ( input::post('do_save_thumbnail') )
		{
			$this->_saveThumbnail($pictureID, $picture, $ad);
		}

		// Set title
		view::setTitle(__('picture_thumbnail_edit', 'system_files'));

		// Set trail
		view::setTrail(session::item('slug'), __('my_profile', 'system_navigation'));
		view::setTrail('classifieds/manage',  __('classifieds', 'system_navigation'));
		view::setTrail('classifieds/view/'.$ad['ad_id'].'/'.text_helper::slug($ad['data_title'], 100), __('ad_view', 'classifieds'), array('side' => true));

		// Include jcron files
		view::includeJavascript('externals/jcrop/jcrop.min.js');
		view::includeStylesheet('externals/jcrop/style.css');

		// Load view
		view::load('classifieds/pictures/thumbnail');
	}

	protected function _saveThumbnail($pictureID, $picture, $ad)
	{
		// Get coordinates
		$x = (int)input::post('picture_thumb_x');
		$y = (int)input::post('picture_thumb_y');
		$w = (int)input::post('picture_thumb_w');
		$h = (int)input::post('picture_thumb_h');

		// Validate coordinates
		if (  ( $w + 10 ) < config::item('picture_dimensions_t_width', 'classifieds') || ( $h + 10 ) < config::item('picture_dimensions_p_height', 'classifieds') ||
			( $w - 10 ) > $picture['file_width'] || ( $h - 10 ) > $picture['file_height'] ||
			$x < 0 || $y < 0 || ( $x + $w - 10 ) > $picture['file_width'] || ( $y + $h - 10 ) > $picture['file_height'] )
		{
			view::setError(__('save_error', 'system'));
			return false;
		}

		// Create thumbnails
		if ( !$this->classifieds_pictures_model->saveThumbnail($picture['file_id'], $x, $y, $w, $h) )
		{
			view::setError(__('save_error', 'system'));
			return false;
		}

		// Success
		router::redirect('classifieds/pictures/view/' . $pictureID . '/' . text_helper::slug($picture['data_description'], 100));
	}

	public function edit()
	{
		// Is user logged in?
		if ( !users_helper::isLoggedin() )
		{
			router::redirect('users/login');
		}
		// Does user have permission to post pictures?
		elseif ( !session::permission('pictures_post', 'classifieds') )
		{
			view::noAccess(session::item('slug'));
		}

		// Get URI vars
		$adID = (int)uri::segment(4);
		$pictureID = (int)uri::segment(5);

		// Get album
		if ( !$adID  || !( $ad = $this->classifieds_model->getAd($adID, 'in_view') ) || $ad['user_id'] != session::item('user_id') )
		{
			view::setError(__('no_ad', 'classifieds'));
			router::redirect('classifieds');
		}

		// Get fields
		$fields = $this->fields_model->getFields('classifieds', 1, 'edit', 'in_account');

		// Get picture
		if ( !$pictureID || !( $picture = $this->classifieds_pictures_model->getPicture($pictureID, $fields, array('escape' => false, 'parse' => false)) ) || $picture['user_id'] != session::item('user_id') )
		{
			view::setError(__('no_picture', 'classifieds'));
			router::redirect('classifieds/pictures/index/' . $adID . '/' . text_helper::slug($ad['data_title'], 100));
		}

		// Assign vars
		view::assign(array('adID' => $adID, 'pictureID' => $pictureID, 'ad' => $ad, 'picture' => $picture, 'fields' => $fields));

		// Process form values
		if ( input::post('do_save_picture') )
		{
			$this->_savePicture($pictureID, $adID, $picture, $ad, $fields);
		}

		// Set title
		view::setTitle(__('picture_edit', 'classifieds'));

		// Set trail
		view::setTrail(session::item('slug'), __('my_profile', 'system_navigation'));
		view::setTrail('classifieds/manage',  __('classifieds', 'system_navigation'));
		view::setTrail('classifieds/view/'.$ad['ad_id'].'/'.text_helper::slug($ad['data_title'], 100), __('ad_view', 'classifieds'), array('side' => true));

		// Load view
		view::load('classifieds/pictures/edit');
	}

	protected function _savePicture($pictureID, $adID, $picture, $ad, $fields)
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
		router::redirect('classifieds/pictures/edit/' . $adID . '/' . $pictureID);
	}

	public function rotate()
	{
		// Is user logged in?
		if ( !users_helper::isLoggedin() )
		{
			router::redirect('users/login');
		}

		// Get URI vars
		$adID = (int)uri::segment(4);
		$pictureID = (int)uri::segment(5);
		$angle = uri::segment(6) == 'left' ? 90 : -90;

		// Get album
		if ( !$adID  || !( $ad = $this->classifieds_model->getAd($adID, 'in_view') ) || $ad['user_id'] != session::item('user_id') )
		{
			view::setError(__('no_ad', 'classifieds'));
			router::redirect('classifieds');
		}

		// Get picture
		if ( !$pictureID || !( $picture = $this->classifieds_pictures_model->getPicture($pictureID, 'in_view') ) || $picture['user_id'] != session::item('user_id') )
		{
			view::setError(__('no_picture', 'classifieds'));
			router::redirect('classifieds/pictures/index/' . $adID . '/' . text_helper::slug($ad['data_title'], 100));
		}

		// Assign vars
		view::assign(array('adID' => $adID, 'pictureID' => $pictureID, 'picture' => $picture, 'ad' => $ad));

		// Rotate picture
		$this->classifieds_pictures_model->rotatePicture($picture['file_id'], $angle);

		// Success
		router::redirect('classifieds/pictures/view/' . $pictureID . '/' . text_helper::slug($picture['data_description'], 100));
	}

	public function cover()
	{
		// Is user loggedin ?
		if ( !users_helper::isLoggedin() )
		{
			router::redirect('users/login');
		}
		// Does user have permission to post pictures?
		elseif ( !session::permission('pictures_post', 'classifieds') )
		{
			view::noAccess(session::item('slug'));
		}

		// Get URI vars
		$adID = (int)uri::segment(4);
		$pictureID = (int)uri::segment(5);

		// Get album
		if ( !$adID  || !( $ad = $this->classifieds_model->getAd($adID, 'in_view') ) || $ad['user_id'] != session::item('user_id') )
		{
			view::setError(__('no_ad', 'classifieds'));
			router::redirect('classifieds');
		}

		// Get picture
		if ( !$pictureID || !( $picture = $this->classifieds_pictures_model->getPicture($pictureID, 'in_view') ) || $picture['ad_id'] != $adID  )
		{
			view::setError(__('no_picture', 'classifieds'));
			router::redirect('classifieds/pictures/index/' . $adID . '/' . text_helper::slug($ad['data_title'], 100));
		}

		// Update album cover
		$this->classifieds_model->updatePicture($adID, $pictureID);

		// Process query string
		$qstring = $this->parseQuerystring();

		// Success
		view::setInfo(__('ad_cover_saved', 'classifieds'));
		router::redirect('classifieds/pictures/view/' . $pictureID . '/' . text_helper::slug($picture['data_description'], 100));
	}

	public function view()
	{
		// Get URI vars
		$pictureID = (int)uri::segment(4);

		// Get picture
		if ( !$pictureID || !( $picture = $this->classifieds_pictures_model->getPicture($pictureID, 'in_view') ) || ( !$picture['active'] && $picture['user_id'] != session::item('user_id') ) )
		{
			error::show404();
		}

		// Get ad
		if ( !( $ad = $this->classifieds_model->getAd($picture['ad_id'], 'in_view') ) )
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
		}

		$previousPicture = $nextPicture = array();
		$previousURL = $nextURL = '';
		// Does album have more than 1 active picture?
		if ( $ad['total_pictures'] > 1 )
		{
			// Get previous/next pictures
			list($previousPicture, $nextPicture) = $this->classifieds_pictures_model->getPictureSiblings(session::item('user_id'), $picture['ad_id'], $picture['order_id'], ( $user['user_id'] != session::item('user_id') ? $ad['total_pictures'] : ( $ad['total_pictures'] + $ad['total_pictures_i'] ) ));

			if ( $previousPicture )
			{
				$previousURL = 'classifieds/pictures/view/' . $previousPicture['picture_id'] . '/' . text_helper::slug($previousPicture['data_description'] ? $previousPicture['data_description'] : '', 100);
			}

			if ( $nextPicture )
			{
				$nextURL = 'classifieds/pictures/view/' . $nextPicture['picture_id'] . '/' . text_helper::slug($nextPicture['data_description'] ? $nextPicture['data_description'] : '', 100);
			}
		}

		// Assign vars
		view::assign(array('pictureID' => $pictureID, 'picture' => $picture, 'ad' => $ad, 'user' => $user, 'previousURL' => $previousURL, 'nextURL' => $nextURL));

		// Set meta tags
		$this->metatags_model->set('classifieds', 'classifieds_view', array('user' => $user, 'ad' => $ad, 'picture' => $picture));

		// Set title
		view::setTitle($ad['data_title'] . ( $picture['data_description'] ? ' - ' . $picture['data_description'] : '' ), false);

		// Set trail
		if ( $user['user_id'] == session::item('user_id') )
		{
			view::setTrail(session::item('slug'), __('my_profile', 'system_navigation'));
			view::setTrail('classifieds/manage',  __('classifieds', 'system_navigation'));
		}
		else
		{
			view::setTrail($user['slug'], $user['name']);
			view::setTrail('classifieds/user/' . $user['slug_id'], __('classifieds', 'system_navigation'));
		}
		view::setTrail('classifieds/view/'.$ad['ad_id'].'/'.text_helper::slug($ad['data_title'], 100), __('ad_view', 'classifieds'), array('side' => true));

		// Assign actions
		view::setAction(false, __('pictures_view_counter', 'classifieds', array('%current' => $picture['order_id'], '%total' => ( $user['user_id'] != session::item('user_id') ? $ad['total_pictures'] : ( $ad['total_pictures'] + $ad['total_pictures_i'] ) ))));

		// Load view
		view::load('classifieds/pictures/view');
	}

	public function delete()
	{
		// Is user loggedin ?
		if ( !users_helper::isLoggedin() )
		{
			router::redirect('users/login');
		}
		// Does user have permission to delete pictures?
		elseif ( !session::permission('pictures_post', 'classifieds') )
		{
			view::noAccess(session::item('slug'));
		}

		// Get URI vars
		$adID = (int)uri::segment(4);
		$pictureID = (int)uri::segment(5);

		// Get album
		if ( !$adID  || !( $ad = $this->classifieds_model->getAd($adID, 'in_view') ) || $ad['user_id'] != session::item('user_id') )
		{
			view::setError(__('no_ad', 'classifieds'));
			router::redirect('classifieds');
		}

		// Get picture
		if ( !$pictureID || !( $picture = $this->classifieds_pictures_model->getPicture($pictureID) ) || $picture['ad_id'] != $adID  )
		{
			view::setError(__('no_picture', 'classifieds'));
			router::redirect('classifieds/pictures/index/' . $adID . '/' . text_helper::slug($ad['data_title'], 100));
		}

		// Delete picture
		$this->classifieds_pictures_model->deletePicture($pictureID, $adID, session::item('user_id'), $picture, $ad);

		// Process query string
		$qstring = $this->parseQuerystring(config::item('pictures_per_page', 'classifieds'));

		// Success
		view::setInfo(__('picture_deleted', 'classifieds'));
		router::redirect('classifieds/pictures/index/' . $adID . '/' . text_helper::slug($ad['data_title'], 100) . '?' . $qstring['url'] . 'page=' . $qstring['page']);
	}

	protected function parseCounters($params)
	{
		// Do we have any pictures?
		if ( !$params['total'] )
		{
			view::setInfo(__('no_pictures_ad', 'classifieds'));
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
