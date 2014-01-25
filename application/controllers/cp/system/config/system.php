<?php

class CP_System_Config_System_Controller extends Controller
{
	public function __construct()
	{
		parent::__construct();

		if ( uri::segment(4) == 'system' )
		{
			// Does user have permission to access this plugin?
			if ( !session::permission('settings_manage', 'system') )
			{
				view::noAccess();
			}

			view::setCustomParam('section', 'system');
			view::setCustomParam('options', config::item('cp_top_nav', 'lists', 'system', 'items', 'system/settings', 'items'));

			view::setTrail('cp/system/config/system', __('system', 'system_navigation'));
		}

		loader::model('system/config');
	}

	public function index()
	{
		$this->browse();
	}

	public function browse($param = '')
	{
		// Get URI vars
		$plugin = $param ? $param : uri::segment(4, 'system');

		// Assign vars
		view::assign(array('plugin' => $plugin));

		// Does plugin exist?
		if ( !config::item('plugins', 'core', $plugin) )
		{
			view::setError(__('no_config_plugin', 'system_config'));
			router::redirect($param ? 'cp/system/plugins' : 'cp/system/config/system');
		}

		// Get configuration groups
		if ( !( $groups = $this->config_model->getSettingsGroups($plugin) ) )
		{
			view::setError(__('no_config_groups', 'system_config'));
			router::redirect($param ? 'cp/system/plugins' : 'cp/system/config/system');
		}

		// Assign vars
		view::assign(array('groups' => $groups));

		// Get configuration settings
		if ( !( $settings = $this->config_model->getSettings($plugin) ) )
		{
			view::setError(__('no_config_settings', 'system_config'));
			router::redirect($param ? 'cp/system/plugins' : 'cp/system/config/system');
		}

		// Loop through settings
		foreach ( $settings as $group => $configs )
		{
			foreach ( $configs as $index => $setting )
			{
				if ( $setting['callback'] && method_exists($this, '_'.$setting['callback']) )
				{
					$settings[$group][$index] = $this->{'_'.$setting['callback']}($setting);
				}

				// Check if copyright removal addon exists
				if ( $group == 'looknfeel' && $setting['keyword'] == 'branding_text' && config::item('license', 'system', 'addons', 2, 'status') != 'Active' )
				{
					unset($settings[$group][$index]);
				}
			}
		}

		// Process form values
		if ( input::post('do_save_settings') )
		{
			$this->_saveSettings($plugin, $settings, $param);
		}

		// Update plugin settings if necessary
		if ( method_exists($this, '_updatePluginSettings') )
		{
			$settings = $this->_updatePluginSettings($settings);
		}

		if ( $plugin == 'system' )
		{
			if ( isset($settings['cron']) && !input::demo(0) )
			{
				$array = array();
				foreach ( $settings['cron'] as $index => $setting )
				{
					$array[] = $setting;
					if ( $setting['keyword'] == 'cron_shash' )
					{
						$setting['name'] = __('cron_command', 'system_config');
						$setting['keyword'] = 'cron_command';
						$setting['required'] = false;
						$setting['value'] = 'curl "' . config::baseURL('cron/run/'.$setting['value']) .'"';
						$array[] = $setting;
					}
				}
				 $settings['cron'] = $array;
			}

			// Is demo mode enabled?
			if ( input::demo(0) )
			{
				foreach ( $settings['emails'] as $index => $setting )
				{
					if ( in_array($setting['keyword'], array('email_smtp_address', 'email_smtp_username', 'email_smtp_password')) )
					{
						$settings['emails'][$index]['value'] = 'hidden in this demo';
					}
				}
				foreach ( $settings['cron'] as $index => $setting )
				{
					if ( in_array($setting['keyword'], array('cron_shash')) )
					{
						$settings['cron'][$index]['value'] = 'hidden in this demo';
					}
				}
			}
		}

		// Assign vars
		view::assign(array('settings' => $settings));

		// Set title
		view::setTitle(__('system_settings_manage', 'system_navigation'));

		// Set trail
		view::setTrail(( $param ? 'cp/system/plugins/settings/' : 'cp/system/config/' ) . $plugin, __('settings', 'system'));

		// Set tabs
		foreach ( $groups as $keyword => $name )
		{
			view::setTab('#' . $keyword, $name, array('class' => 'settings_' . $keyword));
		}

		// Load view
		view::load('cp/system/config/browse');
	}

	protected function _saveSettings($plugin, $settings, $param)
	{
		// Check if demo mode is enabled
		if ( input::demo() ) return false;

		$rules = array();
		foreach ( $settings as $group => $configs )
		{
			foreach ( $configs as $setting )
			{
				$options = array();

				if ( $setting['required'] )
				{
					$options[] = 'required';
				}

				if ( $setting['type'] == 'email' )
				{
					$options[] = 'valid_email';
				}
				elseif ( $setting['type'] == 'number' )
				{
					if ( $setting['required'] )
					{
						$options[] = 'is_numeric_no_zero';
						$options['min_value'] = '1';
					}
					else
					{
						$options[] = 'is_numeric';
					}
				}

				$rules[$setting['keyword']] = array(
					'label' => $setting['name'],
					'rules' => $options,
				);
			}
		}

		validate::setRules($rules);

		if ( !validate::run() )
		{
			return false;
		}

		foreach ( $settings as $group => $configs )
		{
			foreach ( $configs as $setting )
			{
				if ( $setting['type'] != 'static' )
				{
					$value = input::post($setting['keyword']);

					if ( method_exists($this, '_savePluginSettings') )
					{
						$this->_savePluginSettings($setting['keyword'], $value);
					}

					if ( $setting['type'] == 'checkbox' )
					{
						if ( !is_array($value) )
						{
							$value = array();
						}
						$value = json_encode(array_flip($value));
					}
					elseif ( $setting['type'] == 'number' )
					{
						$value = $value == '' ? 0 : $value;
					}

					$orderID = false;
					if ( config::item('devmode', 'system') == 2 )
					{
						$orderID = (int)input::post($setting['keyword'].'___order');
					}

					$this->config_model->saveSetting($plugin, $setting['keyword'], $value, $orderID);
				}
			}
		}

		view::setInfo(__('config_saved', 'system_config'));
		router::redirect(( $param ? 'cp/system/plugins/settings/' : 'cp/system/config/' ) . $plugin);
	}

	protected function _get_templates($setting)
	{
		$setting['items'] = config::item('templates', 'core', 'names');

		return $setting;
	}

	protected function _get_languages($setting)
	{
		$setting['items'] = config::item('languages', 'core', 'names');

		return $setting;
	}

	protected function _get_timezones($setting)
	{
		$setting['items'] = date_helper::timezones();

		return $setting;
	}

	protected function _get_currencies($setting)
	{
		loader::helper('money');

		$setting['items'] = money_helper::currencies();

		return $setting;
	}

	protected function _set_url($setting)
	{
		$setting['value'] = config::siteURL($setting['value']);

		return $setting;
	}
}
