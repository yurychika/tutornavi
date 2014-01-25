<?php

class CP_System_Emailtemplates_Controller extends Controller
{
	public function __construct()
	{
		parent::__construct();

		// Does user have permission to access this plugin?
		if ( !session::permission('email_templates_manage', 'system') )
		{
			view::noAccess();
		}

		view::setCustomParam('section', 'system');
		view::setCustomParam('options', config::item('cp_top_nav', 'lists', 'system', 'items', 'system/settings', 'items'));

		loader::model('system/emailtemplates');

		view::setTrail('cp/system/config/system', __('system', 'system_navigation'));
		view::setTrail('cp/system/emailtemplates', __('system_email_templates', 'system_navigation'));
	}

	public function index()
	{
		$this->plugins();
	}

	public function plugins()
	{
		// Get plugins
		if ( !( $plugins = $this->emailtemplates_model->getPlugins() ) )
		{
			view::setInfo(__('no_plugins', 'system_plugins'));
			router::redirect('cp/system/config/system');
		}

		// Create table grid
		$grid = array(
			'uri' => 'cp/system/emailtemplates',
			'keyword' => 'metatags',
			'header' => array(
				'name' => array(
					'html' => __('name', 'system'),
					'class' => 'name',
				),
				'actions' => array(
					'html' => __('actions', 'system'),
					'class' => 'actions',
				),
			),
			'content' => array()
		);

		// Create grid content
		foreach ( $plugins as $plugin => $name )
		{
			$grid['content'][] = array(
				'name' => array(
					'html' => html_helper::anchor('cp/system/emailtemplates/browse/' . $plugin, $name),
				),
				'actions' => array(
					'html' => array(
						'edit' => html_helper::anchor('cp/system/emailtemplates/browse/' . $plugin, __('edit', 'system'), array('class' => 'edit')),
					),
				),
			);
		}

		// Filter hooks
		hook::filter('cp/system/emailtemplates/plugins/grid', $grid);

		// Assign vars
		view::assign(array('grid' => $grid));

		// Set title
		view::setTitle(__('plugin_select', 'system'));

		// Load view
		view::load('cp/system/emailtemplates/plugins');
	}

	public function browse()
	{
		// Get URI vars
		$plugin = uri::segment(5, 'system');

		// Assign vars
		view::assign(array('plugin' => $plugin));

		// Does plugin exist?
		if ( !config::item('plugins', 'core', $plugin) )
		{
			view::setError(__('no_config_plugin', 'system_config'));
			router::redirect('cp/system/config/' . $plugin);
		}

		// Get templates
		if ( !( $templates = $this->emailtemplates_model->getTemplates($plugin) ) )
		{
			view::setInfo(__('no_templates', 'system_templates'));
			router::redirect('cp/system/config/system');
		}

		// Create table grid
		$grid = array(
			'uri' => 'cp/system/emailtemplates',
			'keyword' => 'emailtemplates',
			'header' => array(
				'name' => array(
					'html' => __('name', 'system'),
					'class' => 'name',
				),
				'status' => array(
					'html' => __('status', 'system'),
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
			if ( $template['keyword'] != 'header' && $template['keyword'] != 'footer' )
			{
				if ( $template['active'] == 1 )
				{
					$status = html_helper::anchor('cp/system/emailtemplates/toggle/' . $template['template_id'], __('active', 'system'), array('class' => 'label small success'));
				}
				else
				{
					$status = html_helper::anchor('cp/system/emailtemplates/toggle/' . $template['template_id'], __('inactive', 'system'), array('class' => 'label small important'));
				}
			}
			else
			{
				$status = '<span class="label small success">' . __('active', 'system') . '</span>';
			}

			$grid['content'][] = array(
				'name' => array(
					'html' => html_helper::anchor('cp/system/emailtemplates/edit/' . $template['template_id'], __($template['keyword'], 'system_email_templates')),
				),
				'status' => array(
					'html' => $status,
				),
				'actions' => array(
					'html' => array(
						'edit' => html_helper::anchor('cp/system/emailtemplates/edit/' . $template['template_id'], __('edit', 'system'), array('class' => 'edit')),
					),
				),
			);
		}

		// Filter hooks
		hook::filter('cp/system/emailtemplates/browse/grid', $grid);

		// Assign vars
		view::assign(array('grid' => $grid));

		// Set title
		view::setTitle(__('system_email_templates_manage', 'system_navigation'));

		// Set trail
		view::setTrail('cp/system/emailtemplates/browse/' . $plugin, text_helper::entities(config::item('plugins', 'core', $plugin, 'name')));

		// Load view
		view::load('cp/system/emailtemplates/browse');
	}

	public function edit()
	{
		// Get URI vars
		$templateID = (int)uri::segment(5);

		// Get template
		if ( !$templateID || !( $template = $this->emailtemplates_model->getTemplate($templateID) ) )
		{
			view::setError(__('no_template', 'system_email_templates'));
			router::redirect('cp/system/config/system');
		}

		// Assign vars
		view::assign(array('templateID' => $templateID, 'template' => $template));

		// Process form values
		if ( input::post('do_save_template') )
		{
			$this->_saveTemplate($templateID, $template['keyword']);
		}

		// Set title
		view::setTitle(__($template['keyword'], 'system_email_templates'));

		// Set trail
		view::setTrail('cp/system/emailtemplates/browse/' . $template['plugin'], text_helper::entities(config::item('plugins', 'core', $template['plugin'], 'name')));
		view::setTrail('cp/system/emailtemplates/edit/' . $templateID, __($template['keyword'], 'system_email_templates'));

		// Set actions
		if ( count(config::item('languages', 'core', 'keywords')) > 1 )
		{
			view::setAction('translate', '');
		}

		// Load ckeditor
		view::includeJavascript('externals/ckeditor/ckeditor.js');

		// Load view
		view::load('cp/system/emailtemplates/edit');
	}

	protected function _saveTemplate($templateID, $keyword)
	{
		// Check if demo mode is enabled
		if ( input::demo() ) return false;

		// Rules and input arrays
		$rules = $input = array();

		// Build rules
		foreach ( config::item('languages', 'core', 'keywords') as $languageID => $language )
		{
			// Make sure this is not a header/footer template
			if ( !in_array($keyword, array('header', 'footer')) )
			{
				$rules['subject_' . $language] = array(
					'label' => __('template_subject', 'system_email_templates') . ( count(config::item('languages', 'core', 'keywords')) > 1 ? ' [' . config::item('languages', 'core', 'names', $languageID) . ']' : '' ),
					'rules' => array('trim', 'required')
				);
				$input[] = 'subject_' . $language;
			}
			$rules['message_html_' . $language] = array(
				'label' => __('template_message_html', 'system_email_templates') . ( count(config::item('languages', 'core', 'keywords')) > 1 ? ' [' . config::item('languages', 'core', 'names', $languageID) . ']' : '' ),
				'rules' => array('trim', 'required')
			);
			$rules['message_text_' . $language] = array(
				'label' => __('template_message_text', 'system_email_templates') . ( count(config::item('languages', 'core', 'keywords')) > 1 ? ' [' . config::item('languages', 'core', 'names', $languageID) . ']' : '' ),
				'rules' => array('trim', 'required')
			);
			$rules['active'] = array(
				'label' => __('active', 'system'),
				'rules' => array('required', 'intval')
			);
			$input[] = 'message_html_' . $language;
			$input[] = 'message_text_' . $language;
			$input[] = 'active';
		}

		// Assign rules
		validate::setRules($rules);

		// Validate fields
		if ( !validate::run() )
		{
			return false;
		}

		// Get template data
		$template = input::post($input);

		// Save template data
		$this->emailtemplates_model->saveTemplate($templateID, $template);

		// Success
		view::setInfo(__('template_saved', 'system_email_templates'));

		router::redirect('cp/system/emailtemplates/edit/' . $templateID);
	}

	public function toggle()
	{
		// Get URI vars
		$templateID = (int)uri::segment(5);

		// Get template
		if ( !$templateID || !( $template = $this->emailtemplates_model->getTemplate($templateID) ) )
		{
			view::setError(__('no_template', 'system_email_templates'));
			router::redirect('cp/system/config/system');
		}

		$this->emailtemplates_model->toggleStatus($templateID, $template);

		router::redirect('cp/system/emailtemplates/browse/' . text_helper::entities(config::item('plugins', 'core', $template['plugin'], 'keyword')));
	}
}
