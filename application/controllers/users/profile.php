<?php

class Users_Profile_Controller extends Controller
{
	public function __construct()
	{
		parent::__construct();
	}

	public function index()
	{
		$ths->view();
	}

	public function view()
	{
		// Get URI vars
		$slugID = urldecode(utf8::trim(uri::segment(2)));

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
		if ( !( $user = $this->users_model->getUser($slugID) ) )
		{
			error::show404();
		}
		elseif ( $user['user_id'] != session::item('user_id') && ( !$user['active'] || !$user['verified'] || $user['group_id'] == config::item('group_cancelled_id', 'users') ) )
		{
			view::setError(__('user_not_active', 'users_signup'));
			router::redirect(users_helper::isLoggedin() ? users_helper::slug() : '');
		}

		// Does user have permission to view this user group/type?
		if ( !in_array($user['group_id'], session::permission('users_groups_view', 'users')) || !in_array($user['type_id'], session::permission('users_types_view', 'users')) )
		{
			view::noAccess();
		}

		// Do we have cached user counters?
		if ( !( $counters = $this->counters_model->getCounters('user', session::item('user_id'), $user['user_id']) ) )
		{
			// Filter hook
			$counters = hook::filter('users/profile/view/counters', array(), $user);

			// Save counters for 24 hours
			$this->counters_model->saveCounters(false, 'user', session::item('user_id'), $user['user_id'], $counters, 60*24);
		}

		// Merge user and counters
		$user = array_merge($user, $counters);

		// Get fields
		$fields = $this->fields_model->getFields('users', $user['type_id'], 'view', 'in_view');

		// Delete empty sections
		$this->fields_model->deleteEmptySections($fields, $user);

		// Assign vars
		view::assign(array('slugID' => $slugID, 'user' => $user, 'fields' => $fields));

		// Set meta tags
		$this->metatags_model->set('users', 'users_view', array('user' => $user), '');

		// Set trail
		view::setTrail($user['slug'], $user['name']);

		// Did we block this user or did they block us?
		if ( users_helper::isLoggedin() && config::item('blacklist_active', 'users') && ( $blocked = $this->users_blocked_model->getUser($user['user_id']) ) )
		{
			if ( $blocked['user_id'] == session::item('user_id') )
			{
				view::setError(__('user_blocked', 'users'));
			}
			else
			{
				view::setError(__('user_blocked_self', 'users'));
			}

			// Load view
			view::load('users/profile/preview');
			return;
		}

		// Set online/last visit status
		if ( config::item('user_last_visit', 'users') )
		{
			if ( !$user['invisible'] )
			{
				if ( $user['online'] )
				{
					view::setTrail(false, '<span class="users online">' . __('status_online', 'users') . '</span>', array('side' => true));
				}
				else
				{
					view::setTrail(false, '<span class="users date">' . __('status_visit_date', 'users', array('%span' => utf8::strtolower(date_helper::humanSpan($user['visit_date'])))) . '</span>', array('side' => true));
				}
			}
		}

		// Validate profile privacy
		if ( !$this->users_model->getPrivacyAccess($user['user_id'], ( isset($user['config']['privacy_profile']) ? $user['config']['privacy_profile'] : 1 ), false) )
		{
			view::setError(__('user_profile_limited', 'users'));

			// Load view
			view::load('users/profile/preview');
			return;
		}

		// Do we have views enabled?
		if ( config::item('user_views', 'users') && $user['user_id'] != session::item('user_id') )
		{
			// Update views counter
			$this->users_model->updateViews($user['user_id']);
		}

		// Do we have visitors enabled?
		if ( users_helper::isLoggedin() && config::item('visitors_active', 'users') && $user['user_id'] != session::item('user_id') && !session::permission('users_visitors_anon', 'users') )
		{
			// Load visitors model
			loader::model('users/visitors', array(), 'users_visitors_model');

			// Update views counter
			$this->users_visitors_model->addVisitor($user['user_id']);
		}

		// Load view
		view::load('users/profile/view');
	}

	public function manage()
	{
		// Is user loggedin ?
		if ( !users_helper::isLoggedin() )
		{
			router::redirect('users/login');
		}

		// Assign user from session to variable
		$user = session::section('session');
		$user['config'] = session::section('config');

		// Get user counters
		$counters = hook::filter('users/profile/view/counters', array(), $user);

		// Merge user and counters
		if ( $counters )
		{
			$user = array_merge($user, $counters);
		}

		// Get fields
		$fields = $this->fields_model->getFields('users', session::item('type_id'), 'view', 'in_view');

		// Delete empty sections
		$this->fields_model->deleteEmptySections($fields, $user);

		// Assign vars
		view::assign(array('user' => $user, 'fields' => $fields));

		// Set title
		view::setMetaTitle(__('my_profile', 'system_navigation'));

		// Set trail
		view::setTrail($user['slug'], __('my_profile', 'system_navigation'));

		if ( config::item('user_last_visit', 'users') )
		{
			if ( !$user['invisible'] )
			{
				if ( $user['online'] )
				{
					view::setTrail(false, '<span class="users online">' . __('status_online', 'users') . '</span>', array('side' => true));
				}
				else
				{
					view::setTrail(false, '<span class="users date">' . __('status_visit_date', 'users', array('%span' => utf8::strtolower(date_helper::humanSpan($user['visit_date'])))) . '</span>', array('side' => true));
				}
			}
		}

		// Load view
		view::load('users/profile/view');
	}

	public function edit()
	{
		// Is user loggedin ?
		if ( !users_helper::isLoggedin() )
		{
			router::redirect('users/login');
		}

		// Get fields
		$fields = $this->fields_model->getFields('users', session::item('type_id'), 'edit', 'in_account');

		// Get profile
		$profile = $this->users_model->getProfile(session::item('user_id'), session::item('type_id'), $fields, array('escape' => false, 'parse' => false));

		// Assign vars
		view::assign(array('profile' => $profile, 'fields' => $fields));

		// Process form values
		if ( input::post('do_save_profile') )
		{
			$this->_saveProfile($fields);
		}

		// Set title
		view::setTitle(__('profile_edit', 'users_profile'));

		// Set trail
		view::setTrail(session::item('slug'), __('my_profile', 'system_navigation'));

		// Load view
		view::load('users/profile/edit');
	}

	protected function _saveProfile($fields)
	{
		// Validate form fields
		if ( !$this->fields_model->validateValues($fields) )
		{
			return false;
		}

		// Extras
		$extra = array();

		// Save profile
		if ( !$this->users_model->saveProfile(session::item('user_id'), session::item('type_id'), session::section('session'), $fields, $extra) )
		{
			view::setError(__('save_error', 'system'));
			return false;
		}

		// Success
		view::setInfo(__('profile_saved', 'users_profile'));

		router::redirect('users/profile/edit');
	}

	public function picture()
	{
		// Is user loggedin ?
		if ( !users_helper::isLoggedin() )
		{
			router::redirect('users/login');
		}

		// Process form values
		if ( input::files('file') )
		{
			$this->_uploadPicture();
		}
		// Rotate profile picture?
		elseif ( uri::segment(4) == 'rotate' && session::item('picture_id') )
		{
			$this->_rotatePicture();
		}
		// Delete profile picture?
		elseif ( uri::segment(4) == 'delete' && session::item('picture_id') )
		{
			$this->_deletePicture();
		}

		// Set title
		view::setTitle(__('picture_change', 'users_picture'));

		// Set trail
		view::setTrail(session::item('slug'), __('my_profile', 'system_navigation'));

		// Load view
		view::load('users/profile/picture');
	}

	protected function _uploadPicture()
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
				'dimensions' => config::item('picture_dimensions', 'users'),
				'method' => 'preserve',
				'suffix' => '', // large
			),
			array(
				'dimensions' => config::item('picture_dimensions_p', 'users'),
				'method' => 'crop',
				'suffix' => 'p', // profile
			),
			array(
				'dimensions' => config::item('picture_dimensions_l', 'users'),
				'method' => 'crop',
				'suffix' => 'l', // lists
			),
			array(
				'dimensions' => config::item('picture_dimensions_t', 'users'),
				'method' => 'crop',
				'suffix' => 't', // comments
			),
		);

		// Upload picture
		if ( !( $fileID = $this->storage_model->upload('user', session::item('user_id'), 'file', 'jpg|jpeg|gif|png', config::item('picture_max_size', 'users'), config::item('picture_dimensions_max', 'users'), $thumbs) ) )
		{
			if ( input::isAjaxRequest() )
			{
				view::ajaxError(config::item('devmode', 'system') ? $this->storage_model->getError() : __('file_upload_error', 'system_files'));
			}
			else
			{
				validate::setFieldError('file', config::item('devmode', 'system') ? $this->storage_model->getError() : __('file_upload_error', 'system_files'));
			}
			return false;
		}

		// Delete old picture if it exists
		if ( session::item('picture_id') )
		{
			// Delete picture
			$this->users_model->deletePicture(session::item('user_id'), session::item('picture_id'), false);
		}

		// Save new picture ID
		if ( !$this->users_model->savePicture(session::item('user_id'), $fileID) )
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

		// Was this an ajax request?
		if ( input::isAjaxRequest() )
		{
			view::ajaxResponse(array('redirect' => html_helper::siteURL(session::item('slug'))));
		}

		// Success
		view::setInfo(__('picture_uploaded', 'users_picture'));

		router::redirect(session::item('slug'));
	}

	protected function _rotatePicture()
	{
		$angle = uri::segment(5) == 'left' ? 90 : -90;

		// Rotate image
		if ( !$this->users_model->rotatePicture(session::item('user_id'), session::item('picture_id'), $angle) )
		{
			view::setError(__('save_error', 'system'));
			return false;
		}

		router::redirect(session::item('slug'));
	}

	public function thumbnail()
	{
		// Is user loggedin ?
		if ( !users_helper::isLoggedin() )
		{
			router::redirect('users/login');
		}

		// Resize picture?
		if ( input::post('do_save_thumbnail') )
		{
			$this->_saveThumbnail();
		}

		// Set title
		view::setTitle(__('picture_thumbnail_edit', 'system_files'));

		// Set trail
		view::setTrail(session::item('slug'), __('my_profile', 'system_navigation'));

		// Include jcron files
		view::includeJavascript('externals/jcrop/jcrop.min.js');
		view::includeStylesheet('externals/jcrop/style.css');

		// Load view
		view::load('users/profile/thumbnail');
	}

	protected function _saveThumbnail()
	{
		// Get coordinates
		$x = (int)input::post('picture_thumb_x');
		$y = (int)input::post('picture_thumb_y');
		$w = (int)input::post('picture_thumb_w');
		$h = (int)input::post('picture_thumb_h');

		// Validate coordinates
		if ( ( $w + 10 ) < config::item('picture_dimensions_p_width', 'users') || ( $h + 10 ) < config::item('picture_dimensions_p_height', 'users') ||
			( $w - 10 ) > session::item('picture_file_width') || ( $h - 10 ) > session::item('picture_file_height') ||
			$x < 0 || $y < 0 || ( $x + $w - 10 ) > session::item('picture_file_width') || ( $y + $h - 10 ) > session::item('picture_file_height') )
		{
			view::setError(__('save_error', 'system'));
			return false;
		}

		// Create thumbnails
		if ( !$this->users_model->saveThumbnail(session::item('user_id'), session::item('picture_id'), $x, $y, $w, $h) )
		{
			view::setError(__('save_error', 'system'));
			return false;
		}

		router::redirect(session::item('slug'));
	}

	protected function _deletePicture()
	{
		// Do we have profile picture?
		if ( session::item('picture_id') )
		{
			// Delete picture
			$this->users_model->deletePicture(session::item('user_id'), session::item('picture_id'));

			// Success
			view::setInfo(__('picture_deleted', 'users_picture'));
		}

		router::redirect(session::item('slug'));
	}
}
