<?php

class CP_System_Templates_Controller extends Controller
{
	public function __construct()
	{
		parent::__construct();

		// Does user have permission to access this plugin?
		if ( !session::permission('templates_manage', 'system') )
		{
			view::noAccess();
		}

		view::setCustomParam('section', 'system');
		view::setCustomParam('options', config::item('cp_top_nav', 'lists', 'system', 'items', 'system/templates', 'items'));

		view::setTrail('cp/system/config/system', __('system', 'system_navigation'));
		view::setTrail('cp/system/templates', __('system_templates', 'system_navigation'));

		loader::model('system/templates');
	}

	public function index()
	{
		$this->browse();
	}

	public function browse()
	{
		// Get templates
		if ( !( $templates = $this->templates_model->scanTemplates() ) )
		{
			view::setInfo(__('no_templates', 'system_templates'));
			router::redirect('cp/system/config/system');
		}

		// Create table grid
		$grid = array(
			'uri' => 'cp/system/templates',
			'keyword' => 'templates',
			'header' => array(
				'name' => array(
					'html' => __('name', 'system'),
					'class' => 'name',
				),
				'status' => array(
					'html' => __('default', 'system'),
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
		foreach ( $templates as $template )
		{
			$actions = $status = array();
			if ( isset($template['template_id']) && $template['template_id'] )
			{
				$status['html'] = $template['default'] ? '<span class="label success small">' . __('yes', 'system') . '</span>' : html_helper::anchor('cp/system/templates/setdefault/' . $template['keyword'], __('no', 'system'), array('class' => 'label important small'));
				if ( $template['settings'] )
				{
					$actions['html']['settings'] = html_helper::anchor('cp/system/templates/settings/' . $template['keyword'], __('settings', 'system'), array('class' => 'settings'));
				}

				$actions['html']['uninstall'] = html_helper::anchor('cp/system/templates/uninstall/' . $template['keyword'], __('uninstall', 'system'), array('data-html' => __('template_uninstall?', 'system_templates'), 'data-role' => 'confirm', 'class' => 'uninstall'));
			}
			else
			{
				$status['html'] = '<span class="label important small">' . __('no', 'system') . '</span>';
				$actions['html']['install'] = html_helper::anchor('cp/system/templates/install/' . $template['keyword'], __('install', 'system'), array('class' => 'install'));
			}

			$grid['content'][] = array(
				'name' => array(
					'html' => text_helper::entities($template['name']),
				),
				'status' => $status,
				'actions' => $actions,
			);
		}

		// Filter hooks
		hook::filter('cp/system/templates/browse/grid', $grid);

		// Assign vars
		view::assign(array('grid' => $grid));

		// Set title
		view::setTitle(__('system_templates_manage', 'system_navigation'));

		// Load view
		view::load('cp/system/templates/browse');
	}

	public function settings()
	{
		// Get URI vars
		$keyword = uri::segment(5);

		// Get template
		if ( !$keyword || !( $template = $this->templates_model->getTemplate($keyword) ) )
		{
			view::setError(__('no_template', 'system_templates'));
			router::redirect('cp/system/templates');
		}

		// Get manifest
		$manifest = $this->templates_model->getManifest($keyword);

		// Do we have any settings for this template?
		if ( !$manifest['settings'] )
		{
			view::setError(__('no_template_settings', 'system_templates'));
			router::redirect('cp/system/templates');
		}

		// Legacy support for non-groupped settings
		if ( !array_key_exists('settings', current($manifest['settings'])) )
		{
			$manifest['settings'] = array(
				array(
					'name' => 'General',
					'settings' => $manifest['settings']
				)
			);
		}

		// Set tabs
		foreach ( $manifest['settings'] as $groupID => $group )
		{
			view::setTab('#' . $groupID, isset($group['name']) ? $group['name'] : 'General', array('class' => 'settings_' . $groupID));
		}

		// Assign vars
		view::assign(array('manifest' => $manifest, 'template' => $template));

		// Process form values
		if ( input::post('do_save_settings') )
		{
			$this->_saveSettings($keyword, $manifest, $template);
		}

		// Set title
		view::setTitle(__('settings', 'system'));

		// Set trail
		view::setTrail('cp/system/templates/settings/' . $keyword, __('settings', 'system') . ' - ' . text_helper::entities($template['name']));

		// Load view
		view::load('cp/system/templates/settings');
	}

	public function _saveSettings($keyword, $manifest, $template)
	{
		// Check if demo mode is enabled
		if ( input::demo() ) return false;

		// Create rules
		$rules = array();

		// Loop through available settings
		foreach ( $manifest['settings'] as $groupID => $group )
		{
			foreach ( $group['settings'] as $settingID => $setting )
			{
				// Rule options
				$options = array();

				if ( isset($setting['required']) && $setting['required'] )
				{
					$options[] = 'required';
				}

				$rules[$setting['keyword']] = array(
					'label' => $setting['name'],
					'rules' => $options,
				);
			}
		}

		// Assign rules
		validate::setRules($rules);

		// Run rules
		if ( !validate::run() )
		{
			return false;
		}

		$settings = array();
		foreach ( $manifest['settings'] as $groupID => $group )
		{
			foreach ( $group['settings'] as $settingID => $setting )
			{
				$value = input::post($setting['keyword']);

				if ( $setting['type'] == 'checkbox' )
				{
					$value = array_flip($value);
				}

				$settings[$setting['keyword']] = $value;
			}
		}

		$this->templates_model->saveSettings($template['template_id'], $settings, $template);

		view::setInfo(__('settings_saved', 'system'));

		router::redirect('cp/system/templates/settings/' . $keyword);
	}

	public function setDefault()
	{
		// Check if demo mode is enabled
		if ( input::demo(1, 'cp/system/templates') ) return false;

		// Get URI vars
		$keyword = uri::segment(5);

		// Get template
		if ( !$keyword || !( $template = $this->templates_model->getTemplate($keyword) ) )
		{
			view::setError(__('no_template', 'system_templates'));
			router::redirect('cp/system/templates');
		}

		// Is this a default template?
		if ( !$template['default'] )
		{
			// Set default template
			$this->templates_model->setDefault($template['template_id'], $template);
		}

		router::redirect('cp/system/templates');
	}

	public function install()
	{
		// Check if demo mode is enabled
		if ( input::demo(1, 'cp/system/templates') ) return false;

		// Get URI vars
		$keyword = uri::segment(5);

		// Get templates
		if ( !$keyword || !( $templates = $this->templates_model->scanTemplates() ) )
		{
			view::setError(__('no_templates', 'system_templates'));
			router::redirect('cp/system/config/system');
		}

		// Does template exist and is it installed?
		if ( !isset($templates[$keyword]) || isset($templates[$keyword]['template_id']) )
		{
			router::redirect('cp/system/templates');
		}

		// Install template
		$this->templates_model->install($keyword);

		view::setInfo(__('template_installed', 'system_templates'));
		router::redirect('cp/system/templates');
	}

	public function uninstall()
	{
		// Check if demo mode is enabled
		if ( input::demo(1, 'cp/system/templates') ) return false;

		// Get URI vars
		$keyword = uri::segment(5);

		// Get template
		if ( !$keyword || !( $template = $this->templates_model->getTemplate($keyword) ) )
		{
			view::setError(__('no_template', 'system_templates'));
			router::redirect('cp/system/templates');
		}

		// Is this a default template?
		if ( $template['default'] )
		{
			view::setError(__('template_default', 'system_templates'));
			router::redirect('cp/system/templates');
		}

		// Uninstall template
		$this->templates_model->uninstall($template['template_id'], $template);

		view::setInfo(__('template_uninstalled', 'system_templates'));
		router::redirect('cp/system/templates');
	}
}
