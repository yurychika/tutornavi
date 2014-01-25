<?php

class CP_Utilities_Counters_Controller extends Controller
{
	public function __construct()
	{
		parent::__construct();

		// Does user have permission to access this plugin?
		if ( !session::permission('counters_manage', 'utilities') )
		{
			view::noAccess();
		}

		view::setCustomParam('section', 'system');
		view::setCustomParam('options', config::item('cp_top_nav', 'lists', 'system', 'items', 'system/utilities', 'items'));

		loader::model('utilities/counters', array(), 'recalculate_model');

		view::setTrail('cp/system/config/system', __('system', 'system_navigation'));
		view::setTrail('cp/utilities/counters', __('utilities_counters', 'system_navigation'));
	}

	public function index()
	{
		$this->plugins();
	}

	public function plugins()
	{
		// Get plugins
		if ( !( $plugins = $this->recalculate_model->getPlugins() ) )
		{
			view::setInfo(__('no_plugins', 'system_plugins'));
			router::redirect('cp/system/config/system');
		}

		// Create table grid
		$grid = array(
			'uri' => 'cp/utilities/counters',
			'keyword' => 'counters',
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
			$actions['html']['update'] = html_helper::anchor('cp/utilities/counters/update/' . $plugin, __('update', 'system'), array('data-html' => __('counters_update?', 'utilities_counters'), 'data-role' => 'confirm', 'class' => 'update'));

			$grid['content'][] = array(
				'name' => array(
					'html' => $name,
				),
				'actions' => $actions,
			);
		}

		// Filter hooks
		hook::filter('cp/utilities/counters/plugins/grid', $grid);

		// Assign vars
		view::assign(array('grid' => $grid));

		// Set title
		view::setTitle(__('utilities_counters_manage', 'system_navigation'));

		// Load view
		view::load('cp/utilities/counters/plugins');
	}

	public function update()
	{
		// Get URI vars
		$plugin = uri::segment(5);

		// Get plugins
		if ( !( $plugins = $this->recalculate_model->getPlugins() ) )
		{
			view::setInfo(__('no_plugins', 'system_plugins'));
			router::redirect('cp/system/config/system');
		}

		// Get captcha
		if ( !$plugin || !isset($plugins[$plugin]) )
		{
			view::setError(__('no_plugin', 'utilities_counters'));
			router::redirect('cp/utilities/counters');
		}

		// Load plugin model
		$model = loader::model($plugin . '/' . $plugin, array(), null);

		// Update counters
		$result = $model->updateDbCounters();

		// Do we have redirect uri?
		if ( isset($result['output']) && isset($result['redirect']) )
		{
			$result['redirect'] = $result['redirect'] ? 'update/' . $plugin . '/' . $result['redirect'] : '';
			$result['output'] .= '<br/>' . __('progress_redirect', 'utilities_counters', array(), array('%' => html_helper::anchor('cp/utilities/counters/' . $result['redirect'], '\1')));

			if ( !$result['redirect'] )
			{
				view::setInfo(__('progress_done', 'utilities_counters', array('%1' => $plugins[$plugin])));
			}

			// Assign vars
			view::assign(array('output' => $result['output'], 'redirect' => $result['redirect']));

			if ( input::isAjaxRequest() )
			{
				view::ajaxResponse(array('output' => $result['output'], 'redirect' => $result['redirect']));
			}
		}

		// Set title
		view::setTitle(__('utilities_counters_manage', 'system_navigation') . ' - ' . $plugins[$plugin]);

		// Load view
		view::load('cp/utilities/counters/update');
	}
}
