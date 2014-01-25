<?php

class Users_Signup_Controller extends Controller {

    // Sign up process steps
    protected $steps = array();

    public function __construct() {
        parent::__construct();

        loader::library('email');
        loader::model('system/requests');
    }

    public function index() {
        $this->account();
    }
	
	public function test(){
		echo '<pre>';
		var_dump($_SESSION);
		// echo session::item('profile', 'signup', 'company')
		// $this->_resetSteps('gee');
		// echo config::item('charset');
		// echo config::item('cp', 'routes');
		// var_dump(config::item('signup_email_verify', 'users'));
		// $fields = $this->fields_model->getFields('users', session::item('account', 'signup', 'type_id'), 'edit', 'in_signup');
		// $fieldsMap = array();
		// $error = '';
// 		
		// foreach($fields as $item){
			// $fieldsMap[$item['keyword']] = $item;
		// }
		// $profiles = array();
		// $keys = array_keys(session::item('profile', 'signup'));
		// foreach($keys as $key){
			// $k = substr($key, 5);
// 
			// $profiles[$key] = array(
					// 'label' => $fieldsMap[$k]['name'], 
					// 'value' => session::item('profile', 'signup', $key)
			// );
		// }
		// var_dump($profiles);
		echo '</pre>';
		exit;
	}
	/**
	 * Email Registration
	 */
	public function step1() {
		$error = '';
		if(input::post('reg_step1')){
			
			$email = input::post('email');
			$data = array('email' => $email);
			session::set(array('account' => $data), '', 'signup');
			
			loader::library('email');

 			if($this->_is_unique_email($email)){
 				$md5 = md5($email);
				$url = config::siteURL('users/signup/step3?vid=' . $md5 . '&email=' . $email);
				
				$message = view::load('users/signup/mail_template', array('url' => $url), true);
				
				$headers  = 'MIME-Version: 1.0' . "\r\n";
				$headers .= 'Content-type: text/html; charset=iso-8859-1' . "\r\n";
				
				if(mail($email, '[TutorNavi]The introducation of TutorNavi ID Registration', $message, $headers)){
					// Set title
					
					$create_time = date('Y-m-d H:i:s');
					$data = array(
						'md5' => $md5,
						'create_time' => $create_time
					);
				
					$this->db->insert('users_email_validation', $data);

					view::setTitle(__('Email Registration', 'system_navigation'));
					view::load('users/signup/step2');
					return;
					
				}
 			}else{
 				// echo 'duplicate ';
 				$error = 'Email duplicate, please try again!';
				view::setError(__('email_duplicate', 'users_signup'));
				// router::redirect('users/signup');		
 			}
		}
		// Set title
		view::setTitle(__('Email Registration', 'system_navigation'));

		// Load view
		view::load('users/signup/step1', array('error'=>$error));
	}

    /**
     * Email Registration
     */
    public function step2() {

        // Set title
        view::setTitle(__('Email Registration', 'system_navigation'));

        // Load view
        view::load('users/signup/step1');
    }

    public function step3() {
    	// echo 123;
    	// var_dump($_SESSION);
		// session::set('gee', 'gee');
		// echo 'gee is' . '<br>';
		// echo session::item('gee');
		// echo session::item('reg_account_type');
		// echo session::item('slug');
		// exit;

		if(input::post('account_type')){
			if($this->_checkVID()){
				$typeId = input::post('account_type');

				// $_SESSION['reg_account_type'] = $type;
				$data = session::item('account', 'signup');
				$data['type_id'] = $typeId;			
				session::set(array('account' => $data), '', 'signup');
				
				// session::set('reg_account_type', $type);
				router::redirect('users/signup/step4');
			}else{
				echo 'not valid';
				exit;
			}
		}
		
		$email = input::get('email');
		$data = array('email' => $email);
		session::set(array('account' => $data), '', 'signup');
				
		$vid = input::get('vid');
		// echo $vid;
		// echo "SELECT * from ss_users_email_validation WHERE md5={$vid}";
		$row = $this->db->query("SELECT * from ss_users_email_validation WHERE md5='{$vid}' ORDER BY create_time DESC")->result();
		// var_dump($row);
		
		if(count($row)){
			$row = $row[0];
			// $_SESSION['vid'] = $vid;
			$data = session::item('account', 'signup');
			$data['vid'] = $vid;			
			session::set(array('account' => $data), '', 'signup');
			
			if(time() - strtotime($row['create_time']) < 24 * 3600){
				//$this->db->query("DELETE from ss_users_email_validation WHERE md5='{$vid}'");
				view::setTitle(__('Set Account Type', 'system_navigation'));
				view::load('users/signup/step_account_type');
				return;
			}
			
		}else{
			echo 'no valid link';
			exit;
			return;
		}
		
        // Set title
        view::setTitle(__('Email Registration', 'system_navigation'));

        // Load view
        view::load('users/signup/step1');
    }

	public function step4() {
		$fields = $this->fields_model->getFields('users', session::item('account', 'signup', 'type_id'), 'edit', 'in_signup');
		$fieldsMap = array();
		$error = '';
		
		foreach($fields as $item){
			$fieldsMap[$item['keyword']] = $item;
		}
		
		if($this->ispost()){
			$type_id = session::item('account', 'signup', 'type_id');
			if($type_id == 4){
		
			}
			
			//validate
			if (!$this->_saveProfile($fields)){
				$error = 'Fields can not be empty';
			}else{
				//set username & password to session
				$data = session::item('account', 'signup');
				$data['username'] = input::post('username');
				$data['password'] = input::post('password');
				
				session::set(array('account' => $data), '', 'signup');
								
				router::redirect('users/signup/step5');
				return;
			}
		}
		
		view::assign(array('fields' => $fieldsMap));
		view::assign(array('email' => session::item('account', 'signup', 'email')));
		view::assign(array('error' => $error));
		
		if($this->_checkVID()){
			// $account_type = $_SESSION['reg_account_type'];
			$account_type = session::item('account', 'signup', 'type_id');
			switch($account_type){
				case 2:			//client`
					view::setTitle(__('Client Registration Page', 'system_navigation'));
					view::load('users/signup/client_registration_form');				
					break;

				case 3:			//student
					view::setTitle(__('Student Registration Page', 'system_navigation'));
					view::load('users/signup/student_registration_form');
					break;

				case 4:			//tutor
					view::setTitle(__('Tutor Registration Page', 'system_navigation'));
					view::load('users/signup/tutor_registration_form_1');					
					break;
			}
		}
	}
	
	//profile confirmation page
	public function step5() {
		$fields = $this->fields_model->getFields('users', session::item('account', 'signup', 'type_id'), 'edit', 'in_signup');
		$fieldsMap = array();
		$error = '';
		
		foreach($fields as $item){
			$fieldsMap[$item['keyword']] = $item;
		}
		$profiles = array();
		$keys = array_keys(session::item('profile', 'signup'));
		foreach($keys as $key){
			$k = substr($key, 5);
			$profiles[] = array(
					'label' => $fieldsMap[$k]['name'], 
					'value' => session::item('profile', 'signup', $key)
			);
		}
				
		if($this->ispost()){
			$this->_createUser();
		}else{
			$email = session::item('account', 'signup', 'email');
			$username = session::item('account', 'signup', 'username');
		
			view::assign(array('email' => $email, 'username' => $username, 'fields' => $fieldsMap));
	// 
			view::setTitle(__('Client Registration Page', 'system_navigation'));
			
			$type_id = session::item('account', 'signup', 'type_id');
			if($type_id == 4){
				view::load('users/signup/tutor_profile_confirmation');
			}else{
				view::load('users/signup/profile_confirmation');		
			}
		}
	}
		
	public function account() {	
        // Is user logged in?
        if (users_helper::isLoggedin()) {
            router::redirect(session::item('slug'));
        }
        // Are signups enabled?
        elseif (!config::item('signup_enable', 'users')) {
            // Success
            view::setError(__('signup_disabled', 'users_signup'));
            router::redirect('users/login');
        }

        // Reset steps
        $this->_resetSteps('account');

        // Process form values
        if (input::post('do_save_account')) {
            $this->_saveAccount();
        }

        // Set title
        view::setTitle(__('signup', 'system_navigation'));

        // Load view
        view::load('users/signup/step1');
    }

    protected function _saveAccount() {
        // Create rules
        $rules = array(
          'email' => array(
            'label' => __('email', 'users'),
            'rules' => array('trim', 'required', 'max_length' => 255, 'valid_email', 'callback__is_unique_email')
          ),
        );

        // Did we login using a third party site?
        if (!session::item('connection', 'remote_connect')) {
            $rules['password'] = array(
              'label' => __('password', 'users'),
              'rules' => array('trim', 'required', 'min_length' => 4, 'max_length' => 128)
            );
            $rules['password2'] = array(
              'label' => __('password_confirm', 'users'),
              'rules' => array('trim', 'matches' => 'password')
            );
        }

        // Do we have usernames?
        if (config::item('user_username', 'users')) {
            $rules['username'] = array(
              'label' => __('username', 'users'),
              'rules' => array('trim', 'required', 'min_length' => 3, 'max_length' => 128, 'callback__is_valid_username')
            );
        }

        // Do we have more than 1 user type?
        if (count(config::item('usertypes', 'core', 'names')) > 1) {
            $rules['type_id'] = array(
              'label' => __('user_type', 'users'),
              'rules' => array('required', 'intval', 'callback__is_user_type'),
            );
        }

        // Is captcha enabled?
        if (!session::item('connection', 'remote_connect') && config::item('signup_captcha', 'users')) {
            $rules['captcha'] = array('rules' => array('is_captcha'));
        }

        // Do we require terms of service?
        if (config::item('signup_require_terms', 'users')) {
            $rules['terms'] = array(
              'label' => '',
              'rules' => array('callback__is_terms'),
            );
        }

        // Assign rules
        validate::setRules($rules);

        // Validate fields
        if (!validate::run()) {
            return false;
        }

        // Is this a recent request?
        if (config::item('signup_delay', 'users') != -1 && ( $this->requests_model->isRecentRequest('signup', input::ipaddress(), 0, config::item('signup_delay', 'users')) || $this->users_model->isRecentSignup() )) {
            // Success
            view::setError(__('request_recent_signup', 'users_signup'));
            return false;
        }

        // Get post data
        $data = input::post(array('email', 'password'));

        // Do we have usernames?
        if (config::item('user_username', 'users')) {
            $data['username'] = input::post('username');
        }

        // Do we have more than 1 user type?
        if (count(config::item('usertypes', 'core', 'names')) > 1) {
            $data['type_id'] = input::post('type_id');
        } else {
            $data['type_id'] = config::item('type_default_id', 'users');
        }

        // Set user session data
        session::set(array('account' => $data), '', 'signup');

        // Redirect to the next step
        $this->_nextStep();
    }

    public function profile() {
        // Is user logged in?
        if (users_helper::isLoggedin()) {
            router::redirect(session::item('slug'));
        }
        // Are signups enabled?
        elseif (!config::item('signup_enable', 'users')) {
            // Success
            view::setError(__('signup_disabled', 'users_signup'));
            router::redirect('users/login');
        }
        // Did user complete previous steps?
        elseif (!session::item('account', 'signup')) {
            router::redirect('users/signup');
        }

        // Reset steps
        $this->_resetSteps('profile');

        // Get fields
        echo session::item('account', 'signup', 'type_id');
        $fields = $this->fields_model->getFields('users', session::item('account', 'signup', 'type_id'), 'edit', 'in_signup');
		
		echo '<pre>';
		var_dump($fields);
		echo '</pre>';
        // Assign vars
        view::assign(array('fields' => $fields));

        // Process form values
        if (input::post('do_save_profile')) {
            $this->_saveProfile($fields);
        }

        // Set title
        view::setTitle(__('profile_edit', 'users_signup'));

        // Load view
        view::load('users/signup/profile');
    }

    protected function _saveProfile($fields) {
        // Validate form fields
        if (!$this->fields_model->validateValues($fields)) {
            return false;
        }

        $data = $this->fields_model->parseValues($fields, $_POST);

        // Set user session data
        session::set(array('profile' => $data), '', 'signup');

        // Redirect to the next step
        // $this->_nextStep();
        return true;
    }

    public function picture() {
        // Is user logged in?
        if (users_helper::isLoggedin()) {
            router::redirect(session::item('slug'));
        }
        // Are signups enabled?
        elseif (!config::item('signup_enable', 'users')) {
            // Success
            view::setError(__('signup_disabled', 'users_signup'));
            router::redirect('users/login');
        }
        // Did user complete previous steps?
        elseif (!session::item('account', 'signup') ||
            config::item('signup_steps', 'users', 'profile') !== false && !session::item('profile', 'signup')) {
            router::redirect('users/signup');
        }

        // Reset steps
        $this->_resetSteps('picture');

        // Process form values
        if (input::post('do_save_picture') || input::files('file')) {
            $this->_uploadPicture();
        } elseif (uri::segment(4) == 'delete' && session::item('picture', 'signup', 'file_id')) {
            // Delete picture
            $this->users_model->deletePicture(0, session::item('picture', 'signup', 'file_id'));

            // Reset user session data
            session::set(array('picture' => array()), '', 'signup');

            router::redirect('users/signup/picture');
        }

        // Add storage includes
        $this->storage_model->includeExternals();

        // Set title
        view::setTitle(__('picture_upload', 'users_signup'));

        // Load view
        view::load('users/signup/picture');
    }

    protected function _uploadPicture() {
        if (input::files('file')) {
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
            if (!validate::run()) {
                return false;
            }

            // Resize config
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
                'suffix' => 'l', // member lists
              ),
              array(
                'dimensions' => config::item('picture_dimensions_t', 'users'),
                'method' => 'crop',
                'suffix' => 't', // comments
              ),
            );

            // Upload picture
            if (!( $fileID = $this->storage_model->upload('user', 0, 'file', 'jpg|jpeg|gif|png', config::item('picture_max_size', 'users'), config::item('picture_dimensions_max', 'users'), $thumbs) )) {
                if (input::isAjaxRequest()) {
                    view::ajaxError(config::item('devmode', 'system') ? $this->storage_model->getError() : __('file_upload_error', 'system_files'));
                } else {
                    validate::setFieldError('file', config::item('devmode', 'system') ? $this->storage_model->getError() : __('file_upload_error', 'system_files'));
                    return false;
                }
            }

            // Delete old picture if it exists
            if (session::item('picture', 'signup', 'file_id')) {
                $this->storage_model->deleteFiles(session::item('picture', 'signup', 'file_id'), 5);
            }

            // Get file details
            $file = $this->storage_model->getFile($fileID);

            // Set user session data
            session::set(array('picture' => $file), '', 'signup');

            // Was this an ajax request?
            if (input::isAjaxRequest()) {
                view::ajaxResponse(array('redirect' => html_helper::siteURL('users/signup/picture')));
            }

            router::redirect('users/signup/picture');
        }

        // Redirect to the next step
        $this->_nextStep();
    }

    public function thumbnail() {
        // Is user logged in?
        if (users_helper::isLoggedin()) {
            router::redirect(session::item('slug'));
        }
        // Are signups enabled?
        elseif (!config::item('signup_enable', 'users')) {
            // Success
            view::setError(__('signup_disabled', 'users_signup'));
            router::redirect('users/login');
        }
        // Did user complete previous steps?
        elseif (!session::item('account', 'signup') ||
            config::item('signup_steps', 'users', 'profile') !== false && !session::item('profile', 'signup')) {
            router::redirect('users/signup');
        }
        // Did user upload a picture?
        elseif (!session::item('picture', 'signup', 'file_id')) {
            router::redirect('users/signup/picture');
        }

        // Reset steps
        $this->_resetSteps('picture');

        // Process form values
        if (input::post('do_save_thumbnail')) {
            $this->_saveThumbnail();
        }

        // Add storage includes
        $this->storage_model->includeExternals();

        // Set title
        view::setTitle(__('picture_thumbnail_edit', 'system_files'));

        // Include jcron files
        view::includeJavascript('externals/jcrop/jcrop.min.js');
        view::includeStylesheet('externals/jcrop/style.css');

        // Load view
        view::load('users/signup/thumbnail');
    }

    protected function _saveThumbnail() {
        // Get coordinates
        $x = (int) input::post('picture_thumb_x');
        $y = (int) input::post('picture_thumb_y');
        $w = (int) input::post('picture_thumb_w');
        $h = (int) input::post('picture_thumb_h');

        // Validate coordinates
        if (( $w + 10 ) < config::item('picture_dimensions_p_width', 'users') || ( $h + 10 ) < config::item('picture_dimensions_p_height', 'users') ||
            ( $w - 10 ) > session::item('picture', 'signup', 'width') || ( $h - 10 ) > session::item('picture', 'signup', 'height') ||
            $x < 0 || $y < 0 || ( $x + $w - 10 ) > session::item('picture', 'signup', 'width') || ( $y + $h - 10 ) > session::item('picture', 'signup', 'height')) {
            view::setError(__('save_error', 'system'));
            return false;
        }

        // Create thumbnails
        if (!$this->users_model->saveThumbnail(0, session::item('picture', 'signup', 'file_id'), $x, $y, $w, $h)) {
            view::setError(__('save_error', 'system'));
            return false;
        }

        router::redirect('users/signup/picture');
    }

    protected function _createUser() {
        // Verify email and username one more time
        if (!$this->_is_unique_email(session::item('account', 'signup', 'email'))) {
            view::setError(__('email_duplicate', 'users_signup'));
            router::redirect('users/signup');
        } elseif (config::item('user_username', 'users') && !$this->_is_valid_username(session::item('account', 'signup', 'username'))) {
            view::setError(__('duplicate_username', 'users_signup'));
            router::redirect('users/signup');
        }

        // Get user data
        $user = array(
          'email' => session::item('account', 'signup', 'email'),
          'password' => session::item('account', 'signup', 'password') ? session::item('account', 'signup', 'password') : '',
          'username' => session::item('account', 'signup', 'username'),
          'type_id' => count(config::item('usertypes', 'core', 'names')) > 1 ? session::item('account', 'signup', 'type_id') : config::item('type_default_id', 'users'),
          'group_id' => config::item('group_default_id', 'users'),
          'verified' => config::item('signup_email_verify', 'users') && !session::item('connection', 'remote_connect') ? 0 : 1,
          'active' => config::item('signup_admin_verify', 'users') ? 0 : 1,
          'picture_id' => session::item('picture', 'signup', 'file_id') ? session::item('picture', 'signup', 'file_id') : 0,
          'picture_active' => session::item('picture', 'signup', 'file_id') ? ( config::item('signup_picture_verify', 'users') ? 9 : 1 ) : 0,
          'picture_date' => date_helper::now(),
        );

        // Set names
        $user['name1'] = session::item('profile', 'signup', 'data_' . config::item('usertypes', 'core', 'fields', $user['type_id'], 1));
        $user['name2'] = session::item('profile', 'signup', 'data_' . config::item('usertypes', 'core', 'fields', $user['type_id'], 2));
        $user['name1'] = $user['name1'] ? $user['name1'] : '';
        $user['name2'] = $user['name2'] ? $user['name2'] : '';

        // Get fields
        $fields = $this->fields_model->getFields('users', $user['type_id'], 'edit', 'in_signup');

        // Save user
        $userID = $this->users_model->saveUser(0, $user);

        // Save profile
        if (session::item('profile', 'signup')) {
            $this->users_model->saveProfile($userID, $user['type_id'], session::item('profile', 'signup'), $fields, array(), true);
        }

        // Did user upload a picture?
        if (session::item('picture', 'signup', 'file_id')) {
            // Update file's user ID
            $this->storage_model->updateUserID(session::item('picture', 'signup', 'file_id'), $userID, 5);
        }

        // Are we signing up using a third party site?
        $remoteconn = false;
        if (session::item('connection', 'remote_connect')) {
            $remoteconn = true;

            loader::library('authentication/' . session::item('connection', 'remote_connect', 'name'));
            $this->{session::item('connection', 'remote_connect', 'name')}->saveToken($userID);

            // Remove temporary session values
            session::delete('', 'remote_connect');
        }

        // Remove temporary session values
        session::delete('', 'signup');

        // Do we need to verify email address?
        if (config::item('signup_email_verify', 'users') && !$remoteconn) {
            // Get user data
            if (!( $user = $this->users_model->getUser($userID) )) {
                validate::setFieldError('email', __('email_invalid', 'users_signup'));
                return false;
            }

            // Save signup request
            $hash = $this->requests_model->saveRequest('signup', $userID);

            $user['security_hash'] = $hash;
            $user['activation_link'] = config::siteURL('users/signup/confirm/' . $userID . '/' . $hash);

            // Send activation email
            $this->email->sendTemplate('users_account_confirm', $user['email'], $user, $user['language_id']);

            // Success
            view::setInfo(__('confirm_email', 'users_signup'));
            router::redirect('users/login/index/verify');
        } elseif (config::item('signup_admin_verify', 'users')) {
            // Success
            view::setInfo(__('confirm_user', 'users_signup'));

            router::redirect('users/login/index/approve');
        } else {
            // Do we need to send welcome email?
            if (config::item('signup_email_welcome', 'users')) {
                // Get user data
                if (!( $user = $this->users_model->getUser($userID) )) {
                    validate::setFieldError('email', __('email_invalid', 'users_signup'));
                    return false;
                }

                // Send welcome email
                $this->email->sendTemplate('users_account_welcome', $user['email'], $user, $user['language_id']);
            }

            // Login user
            // $this->users_model->login($userID);

            // Success
            view::setInfo(__('user_registered', 'users_signup'));
			view::load('users/signup/registration_finish');
            // router::redirect(session::item('slug'));
        }
    }

    public function confirm() {
        // Is user logged in?
        if (users_helper::isLoggedin()) {
            router::redirect(session::item('slug'));
        }

        // Get URI vars
        $userID = (int) uri::segment(4);
        $hash = uri::segment(5);

        // Validate user ID
        if (!$userID) {
            view::setError(__('user_id_invalid', 'users_signup'));
            router::redirect('users/login');
        }

        // Validate hash
        if (!$hash || !$this->requests_model->validateRequest($hash)) {
            view::setError(__('request_hash_invalid', 'system'));
            router::redirect('users/login');
        }

        // Get user
        if (!( $user = $this->users_model->getUser($userID) )) {
            view::setError(__('request_hash_invalid', 'system'));
            router::redirect('users/login');
        }

        // Get request
        if (!( $request = $this->requests_model->getRequest('signup', $hash, $userID) )) {
            view::setError(__('request_hash_expired', 'system'));
            router::redirect('users/login');
        }

        // Is user's email already verified?
        if ($user['verified']) {
            view::setError(__('user_already_verified', 'users_signup'));
            router::redirect('users/login');
        }

        // Update user's verification status
        $this->users_model->toggleVerifiedStatus($userID, $user, 1);

        // Remove verification request
        $this->requests_model->deleteRequest('signup', $hash, $userID);

        // Does admin need to verify the account?
        if (config::item('signup_admin_verify', 'users')) {
            // Success
            view::setInfo(__('confirm_user', 'users_signup'));

            router::redirect('users/login/index/approve');
        } else {
            // Do we need to send welcome email?
            if (config::item('signup_email_welcome', 'users')) {
                // Send welcome email
                $this->email->sendTemplate('users_account_welcome', $user['email'], $user, $user['language_id']);
            }

            // Login user
            $this->users_model->login($user['user_id']);

            // Success
            view::setInfo(__('user_registered', 'users_signup'));

            router::redirect(session::item('slug'));
        }
    }

    public function _is_unique_email($email) {
        if (!$this->users_model->isUniqueEmail($email)) {
            validate::setError('_is_unique_email', __('email_duplicate', 'users_signup'));
            return false;
        }

        return true;
    }

    public function _is_user_type($typeID) {
        if (!config::item('usertypes', 'core', 'names', $typeID)) {
            validate::setError('_is_user_type', __('user_type_invalid', 'users_signup'));
            return false;
        }

        return true;
    }

    public function _is_valid_username($username) {
        if (( $return = $this->users_model->isValidUsername($username) ) !== true) {
            validate::setError('_is_valid_username', $return);

            return false;
        }

        return true;
    }

    public function _is_terms($value) {
        if (!$value) {
            validate::setError('_is_terms', __('no_terms', 'users_signup'));
            return false;
        }

        return true;
    }

    protected function _resetSteps($current) {
        // Is current step found
        $found = false;

        if (is_array(config::item('signup_steps', 'users'))) {
            $steps = array('account' => 'system') + config::item('signup_steps', 'users');
        }
		
        foreach ($steps as $step => $value) {
            // Current step found
            if (strcmp($step, $current) == 0) {
                $found = true;
            }

            // Is current step found
            if ($found) {
                $this->steps[] = $step;
            }
        }
    }

    protected function _nextStep() {
        // Remove current step
        array_shift($this->steps);

        // Do we have any steps left?
        if ($this->steps) {
            $step = current($this->steps);

            // Redirect to the next step
            switch ($step) {
                case 'account':
                case 'profile':
                case 'picture':
                    router::redirect('users/signup/' . $step);
                    break;

                default:
                    router::redirect('users/signup');
                    exit;
            }
        } else {
            // Create user
            $this->_createUser();
        }
    }
	
	private function _checkVID(){
		// $vid = $_SESSION['vid'];
		$vid = session::item('vid');
		if(isset($vid) && $vid !== ''){
			$row = $this->db->query("SELECT * from ss_users_email_validation WHERE md5='{$vid}'")->result();
			if(count($row)){
				return true;
			}
		}
		return false;
	}
	
	private function ispost(){
		return $_SERVER['REQUEST_METHOD'] == 'POST';
	}
	
	private function isget(){
		return $_SERVER['REQUEST_METHOD'] == 'GET';
	}	
}
