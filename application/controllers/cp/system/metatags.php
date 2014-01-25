<?php

class CP_System_MetaTags_Controller extends Controller
{
	public function __construct()
	{
		parent::__construct();

		// Does user have permission to access this plugin?
		if ( !session::permission('meta_tags_manage', 'system') )
		{
			view::noAccess();
		}

		view::setCustomParam('section', 'system');
		view::setCustomParam('options', config::item('cp_top_nav', 'lists', 'system', 'items', 'system/settings', 'items'));

		view::setTrail('cp/system/config/system', __('system', 'system_navigation'));
		view::setTrail('cp/system/metatags', __('system_meta_tags', 'system_navigation'));
	}

	public function index()
	{
		$this->plugins();
	}

	public function plugins()
	{
		// Get plugins
		if ( !( $plugins = $this->metatags_model->getPlugins() ) )
		{
			view::setInfo(__('no_plugins', 'system_plugins'));
			router::redirect('cp/system/config/system');
		}

		// Create table grid
		$grid = array(
			'uri' => 'cp/system/metatags',
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
			$actions['html']['edit'] = html_helper::anchor('cp/system/metatags/edit/' . $plugin, __('edit', 'system'), array('class' => 'edit'));

			$grid['content'][] = array(
				'name' => array(
					'html' => html_helper::anchor('cp/system/metatags/edit/' . $plugin, $name),
				),
				'actions' => $actions,
			);
		}

		// Filter hooks
		hook::filter('cp/system/metatags/plugins/grid', $grid);

		// Assign vars
		view::assign(array('grid' => $grid));

		// Set title
		view::setTitle(__('plugin_select', 'system'));

		// Load view
		view::load('cp/system/metatags/plugins');
	}

	public function edit()
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

		// Get meta tags
		if ( !( $tags = $this->metatags_model->getMetaTags($plugin) ) )
		{
			view::setError(__('no_meta_tags', 'system_metatags'));
			router::redirect('cp/system/config/' . $plugin);
		}

		// Process form values
		if ( input::post('do_save_meta_tags') )
		{
			$this->_saveMetaTags($plugin, $tags);
		}

		// Assign vars
		view::assign(array('tags' => $tags));

		// Set title
		view::setTitle(__('system_meta_tags_manage', 'system_navigation'));

		// Set trail
		view::setTrail('cp/system/metatags/edit/' . $plugin, text_helper::entities(config::item('plugins', 'core', $plugin, 'name')));

		// Set actions
		if ( count(config::item('languages', 'core', 'keywords')) > 1 )
		{
			view::setAction('translate', '');
		}

		// Set tabs
		foreach ( $tags as $keyword => $group )
		{
			view::setTab('#' . $keyword, __($keyword, $plugin . '_metatags'), array('class' => 'group_' . $keyword));
		}

		// Load view
		view::load('cp/system/metatags/edit');
	}

	protected function _saveMetaTags($plugin, $tags)
	{
		// Check if demo mode is enabled
		if ( input::demo() ) return false;

		$rules = array();
		foreach ( $tags as $keyword => $group )
		{
			foreach ( config::item('languages', 'core', 'keywords') as $language )
			{
				$rules[$keyword . '_title_' . $language] = $rules[$keyword . '_description_' . $language] = $rules[$keyword . '_keywords_' . $language] = array(
					'label' => '',
					'rules' => array('is_string', 'trim', 'max_length' => 255),
				);
			}
		}

		validate::setRules($rules);

		if ( !validate::run() )
		{
			return false;
		}

		foreach ( $tags as $keyword => $group )
		{
			$data = array();
			foreach ( config::item('languages', 'core', 'keywords') as $language )
			{
				$data['meta_title_' . $language] = input::post($keyword . '_title_' . $language);
				$data['meta_description_' . $language] = input::post($keyword . '_description_' . $language);
				$data['meta_keywords_' . $language] = input::post($keyword . '_keywords_' . $language);
			}

			$this->metatags_model->saveMetaTags($plugin, $keyword, $data);
		}

		view::setInfo(__('meta_tags_saved', 'system_metatags'));
		router::redirect('cp/system/metatags/edit/' . $plugin);
	}
}
