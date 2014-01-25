<?php

class CP_Content_Newsletters_Templates_Controller extends Controller
{
	public function __construct()
	{
		parent::__construct();

		// Does user have permission to access this plugin?
		if ( !session::permission('templates_manage', 'newsletters') )
		{
			view::noAccess();
		}

		view::setCustomParam('section', 'content');
		view::setCustomParam('options', config::item('cp_top_nav', 'lists', 'content', 'items', 'content/newsletters', 'items'));

		view::setTrail('cp/system/plugins', __('content', 'system_navigation'));
		view::setTrail('cp/content/newsletters', __('newsletters', 'system_navigation'));
		view::setTrail('cp/content/newsletters/templates', __('newsletters_templates', 'system_navigation'));

		loader::model('newsletters/templates', array(), 'newsletters_templates_model');
	}

	public function index()
	{
		$this->browse();
	}

	public function browse()
	{
		// Get templates
		if ( !( $templates = $this->newsletters_templates_model->getTemplates() ) )
		{
			view::setInfo(__('no_templates', 'newsletters_templates'));
		}

		// Create table grid
		$grid = array(
			'uri' => 'cp/content/newsletters/templates/browse',
			'keyword' => 'newsletters_templates',
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
		foreach ( $templates as $template )
		{
			$grid['content'][] = array(
				'name' => array(
					'html' => html_helper::anchor('cp/content/newsletters/templates/edit/' . $template['template_id'], text_helper::truncate($template['name'], 64)),
				),
				'actions' => array(
					'html' => array(
						'edit' => html_helper::anchor('cp/content/newsletters/templates/edit/' . $template['template_id'], __('edit', 'system'), array('class' => 'edit')),
						'delete' => html_helper::anchor('cp/content/newsletters/templates/delete/' . $template['template_id'], __('delete', 'system'), array('data-html' => __('template_delete?', 'newsletters_templates'), 'data-role' => 'confirm', 'class' => 'delete')),
					),
				),
			);
		}

		// Filter hooks
		hook::filter('cp/content/newsletters/templates/browse/grid', $grid);

		// Assign vars
		view::assign(array('grid' => $grid));

		// Set title
		view::setTitle(__('newsletters_templates_manage', 'system_navigation'));

		// Set action
		view::setAction('cp/content/newsletters/templates/edit', __('template_new', 'newsletters_templates'), array('class' => 'icon-text icon-newsletters-templates-new'));

		// Load view
		view::load('cp/content/newsletters/templates/browse');
	}

	public function edit()
	{
		// Get URI vars
		$templateID = (int)uri::segment(6);

		// Get template
		$template = array();
		if ( $templateID && !( $template = $this->newsletters_templates_model->getTemplate($templateID, false) ) )
		{
			view::setError(__('no_template', 'newsletters_templates'));
			router::redirect('cp/content/newsletters/templates');
		}

		// Assign vars
		view::assign(array('templateID' => $templateID, 'template' => $template));

		// Process form values
		if ( input::post('do_save_template') )
		{
			$this->_saveTemplate($templateID);
		}

		// Set title
		view::setTitle($templateID ? __('template_edit', 'newsletters_templates') : __('template_new', 'newsletters_templates'));

		// Set trail
		view::setTrail('cp/content/newsletters/templates/edit/' . ( $templateID ? $templateID : '' ), ( $templateID ? __('template_edit', 'newsletters_templates') . ' - ' . text_helper::entities($template['name']) : __('template_new', 'newsletters_templates') ));

		// Load ckeditor
		view::includeJavascript('externals/ckeditor/ckeditor.js');

		// Load view
		view::load('cp/content/newsletters/templates/edit');
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
		);

		// Get template data
		$rules['name'] = array(
			'label' => __('name', 'system'),
			'rules' => array('trim', 'required', 'max_length' => 255)
		);
		$rules['subject'] = array(
			'label' => __('newsletter_subject', 'newsletters'),
			'rules' => array('trim', 'required', 'max_length' => 255)
		);
		$rules['message_html'] = array(
			'label' => __('newsletter_message_html', 'newsletters'),
			'rules' => array('trim', 'required')
		);
		$rules['message_text'] = array(
			'label' => __('newsletter_message_text', 'newsletters'),
			'rules' => array('trim', 'required')
		);
		$input = array('name', 'subject', 'message_html', 'message_text');

		// Assign rules
		validate::setRules($rules);

		// Validate fields
		if ( !validate::run() )
		{
			return false;
		}

		// Get template data
		$template = input::post($input);

		// Save template
		if ( !( $templateID = $this->newsletters_templates_model->saveTemplate($templateID, $template) ) )
		{
			view::setError(__('save_error', 'system'));
			return false;
		}

		// Success
		view::setInfo(__('template_saved', 'newsletters_templates'));

		router::redirect('cp/content/newsletters/templates/edit/'.$templateID);
	}

	public function delete()
	{
		// Check if demo mode is enabled
		if ( input::demo(1, 'cp/content/newsletters/templates') ) return false;

		// Get URI vars
		$templateID = (int)uri::segment(6);

		// Get template
		if ( !$templateID || !( $template = $this->newsletters_templates_model->getTemplate($templateID) ) )
		{
			view::setError(__('no_template', 'newsletters_templates'));
			router::redirect('cp/content/newsletters/templates');
		}

		// Delete template
		$this->newsletters_templates_model->deleteTemplate($templateID, $template);

		// Success
		view::setInfo(__('template_deleted', 'newsletters_templates'));

		router::redirect('cp/content/newsletters/templates');
	}
}
