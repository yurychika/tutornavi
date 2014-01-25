<?php

class CP_Plugins_Messages_Templates_Controller extends Controller
{
	public function __construct()
	{
		parent::__construct();

		// Does user have permission to access this plugin?
		if ( !session::permission('templates_manage', 'messages') )
		{
			view::noAccess();
		}

		view::setCustomParam('section', 'plugins');
		view::setCustomParam('options', config::item('cp_top_nav', 'lists', 'plugins', 'items', 'plugins/messages', 'items'));

		view::setTrail('cp/system/plugins', __('plugins', 'system_navigation'));
		view::setTrail('cp/plugins/messages', __('messages', 'system_navigation'));
		view::setTrail('cp/plugins/messages/templates', __('messages_templates', 'system_navigation'));

		loader::model('messages/templates', array(), 'messages_templates_model');
	}

	public function index()
	{
		$this->browse();
	}

	public function browse()
	{
		// Get templates
		if ( !( $templates = $this->messages_templates_model->getTemplates() ) )
		{
			view::setInfo(__('no_templates', 'messages_templates'));
		}

		// Create table grid
		$grid = array(
			'uri' => 'cp/plugins/messages/templates/browse',
			'keyword' => 'messages_templates',
			'header' => array(
				'name' => array(
					'html' => __('name', 'system'),
					'class' => 'name',
				),
				'status' => array(
					'html' => __('active', 'system'),
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
			$grid['content'][] = array(
				'name' => array(
					'html' => html_helper::anchor('cp/plugins/messages/templates/edit/' . $template['template_id'], text_helper::truncate($template['name'], 64)),
				),
				'status' => array(
					'html' => $template['active'] ? '<span class="label success small">' . __('yes', 'system') . '</span>' : '<span class="label important small">' . __('no', 'system') . '</span>',
				),
				'actions' => array(
					'html' => array(
						'edit' => html_helper::anchor('cp/plugins/messages/templates/edit/' . $template['template_id'], __('edit', 'system'), array('class' => 'edit')),
						'delete' => html_helper::anchor('cp/plugins/messages/templates/delete/' . $template['template_id'], __('delete', 'system'), array('data-html' => __('template_delete?', 'messages_templates'), 'data-role' => 'confirm', 'class' => 'delete')),
					),
				),
			);
		}

		// Filter hooks
		hook::filter('cp/plugins/messages/templates/browse/grid', $grid);

		// Assign vars
		view::assign(array('grid' => $grid));

		// Set title
		view::setTitle(__('messages_templates_manage', 'system_navigation'));

		// Set action
		view::setAction('cp/plugins/messages/templates/edit', __('template_new', 'messages_templates'), array('class' => 'icon-text icon-messages-templates-new'));

		// Load view
		view::load('cp/plugins/messages/templates/browse');
	}

	public function edit()
	{
		// Get URI vars
		$templateID = (int)uri::segment(6);

		// Get template
		$template = array();
		if ( $templateID && !( $template = $this->messages_templates_model->getTemplate($templateID, false) ) )
		{
			view::setError(__('no_template', 'messages_templates'));
			router::redirect('cp/plugins/messages/templates');
		}

		// Assign vars
		view::assign(array('templateID' => $templateID, 'template' => $template));

		// Process form values
		if ( input::post('do_save_template') )
		{
			$this->_saveTemplate($templateID);
		}

		// Set title
		view::setTitle($templateID ? __('template_edit', 'messages_templates') : __('template_new', 'messages_templates'));

		// Set trail
		view::setTrail('cp/plugins/messages/templates/edit/' . ( $templateID ? $templateID : '' ), ( $templateID ? __('template_edit', 'messages_templates') . ' - ' . text_helper::entities($template['name']) : __('template_new', 'messages_templates') ));

		// Set actions
		if ( count(config::item('languages', 'core', 'keywords')) > 1 )
		{
			view::setAction('translate', '');
		}

		// Load view
		view::load('cp/plugins/messages/templates/edit');
	}

	protected function _saveTemplate($templateID)
	{
		// Check if demo mode is enabled
		if ( input::demo() ) return false;

		// Create rules
		$rules = array(
			'category_id' => array(
				'label' => __('gift_category', 'gifts'),
				'rules' => array('intval')
			),
			'active' => array(
				'label' => __('active', 'system'),
				'rules' => array('intval')
			)
		);

		// Get template data
		$templateData = $input = array();
		foreach ( config::item('languages', 'core', 'keywords') as $languageID => $language )
		{
			$rules['name_' . $language] = array(
				'label' => __('name', 'system') . ( count(config::item('languages', 'core', 'keywords')) > 1 ? ' [' . config::item('languages', 'core', 'names', $languageID) . ']' : '' ),
				'rules' => array('trim', 'required', 'max_length' => 255)
			);
			$rules['subject_' . $language] = array(
				'label' => __('template_subject', 'messages_templates') . ( count(config::item('languages', 'core', 'keywords')) > 1 ? ' [' . config::item('languages', 'core', 'names', $languageID) . ']' : '' ),
				'rules' => array('trim', 'required', 'max_length' => 255)
			);
			$rules['message_' . $language] = array(
				'label' => __('template_message', 'messages_templates') . ( count(config::item('languages', 'core', 'keywords')) > 1 ? ' [' . config::item('languages', 'core', 'names', $languageID) . ']' : '' ),
				'rules' => array('trim', 'required')
			);
			$input[] = 'name_' . $language;
			$input[] = 'subject_' . $language;
			$input[] = 'message_' . $language;
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
		$template['active'] = input::post('active') ? 1 : 0;

		// Save template
		if ( !( $templateID = $this->messages_templates_model->saveTemplate($templateID, $template) ) )
		{
			view::setError(__('save_error', 'system'));
			return false;
		}

		// Success
		view::setInfo(__('template_saved', 'messages_templates'));

		router::redirect('cp/plugins/messages/templates/edit/'.$templateID);
	}

	public function delete()
	{
		// Check if demo mode is enabled
		if ( input::demo(1, 'cp/plugins/messages/templates') ) return false;

		// Get URI vars
		$templateID = (int)uri::segment(6);

		// Get template
		if ( !$templateID || !( $template = $this->messages_templates_model->getTemplate($templateID) ) )
		{
			view::setError(__('no_template', 'messages_templates'));
			router::redirect('cp/plugins/messages/templates');
		}

		// Delete template
		$this->messages_templates_model->deleteTemplate($templateID, $template);

		// Success
		view::setInfo(__('template_deleted', 'messages_templates'));

		router::redirect('cp/plugins/messages/templates');
	}
}
