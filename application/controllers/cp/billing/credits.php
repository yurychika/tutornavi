<?php

class CP_Billing_Credits_Controller extends Controller
{
	public function __construct()
	{
		parent::__construct();

		// Does user have permission to access this plugin?
		if ( !session::permission('credits_manage', 'billing') )
		{
			view::noAccess();
		}

		view::setCustomParam('section', 'billing');
		view::setCustomParam('options', config::item('cp_top_nav', 'lists', 'billing', 'items'));

		loader::model('billing/credits');

		view::setTrail('cp/billing/transactions', __('billing', 'system_navigation'));
		view::setTrail('cp/billing/credits', __('billing_credits', 'system_navigation'));
	}

	public function index()
	{
		$this->browse();
	}

	public function browse()
	{
		// Get packages
		if ( !( $packages = $this->credits_model->getPackages(false) ) )
		{
			view::setInfo(__('no_packages', 'billing_credits'));
		}

		// Create table grid
		$grid = array(
			'uri' => 'cp/billing/credits/browse',
			'keyword' => 'billing_credits',
			'header' => array(
				'credits' => array(
					'html' => __('credits', 'billing_credits'),
					'class' => 'credits',
				),
				'price' => array(
					'html' => __('price', 'billing'),
					'class' => 'price',
				),
				'active' => array(
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
		foreach ( $packages as $package )
		{
			$grid['content'][] = array(
				'credits' => array(
					'html' => html_helper::anchor('cp/billing/credits/edit/' . $package['package_id'], $package['credits']),
				),
				'price' => array(
					'html' => money_helper::symbol(config::item('currency', 'billing')) . $package['price'],
				),
				'active' => array(
					'html' => $package['active'] ? '<span class="label success small">' . __('yes', 'system') . '</span>' : '<span class="label important small">' . __('no', 'system') . '</span>',
				),
				'actions' => array(
					'html' => array(
						'edit' => html_helper::anchor('cp/billing/credits/edit/' . $package['package_id'], __('edit', 'system'), array('class' => 'edit')),
						'delete' => html_helper::anchor('cp/billing/credits/delete/' . $package['package_id'], __('delete', 'system'), array('data-html' => __('package_delete?', 'billing_credits'), 'data-role' => 'confirm', 'class' => 'delete')),
					),
				),
			);
		}

		// Filter hooks
		hook::filter('cp/billing/credits/browse/grid', $grid);

		// Assign vars
		view::assign(array('grid' => $grid));

		// Set title
		view::setTitle(__('billing_credits_manage', 'system_navigation'));

		// Assign actions
		view::setAction('cp/billing/credits/edit', __('package_new', 'billing_credits'), array('class' => 'icon-text icon-billing-packages-new'));

		// Load view
		view::load('cp/billing/credits/browse');
	}

	public function edit()
	{
		// Get URI vars
		$packageID = (int)uri::segment(5);

		// Get package
		$package = array();
		if ( $packageID && !( $package = $this->credits_model->getPackage($packageID) ) )
		{
			view::setError(__('no_package', 'billing_credits'));
			router::redirect('cp/billing/credits');
		}

		// Assign vars
		view::assign(array('packageID' => $packageID, 'package' => $package));

		// Process form values
		if ( input::post('do_save_package') )
		{
			$this->_savePackage($packageID);
		}

		// Set title
		view::setTitle($packageID ? __('package_edit', 'billing_credits') : __('package_new', 'billing_credits'));

		// Set trail
		view::setTrail('cp/billing/credits/edit/' . ( $packageID ? $packageID : '' ), ( $packageID ? __('package_edit', 'billing_credits') : __('package_new', 'billing_credits') ));

		// Load view
		view::load('cp/billing/credits/edit');
	}

	protected function _savePackage($packageID)
	{
		// Check if demo mode is enabled
		if ( input::demo() ) return false;

		// Create rules
		$rules = array(
			'credits' => array(
				'label' => __('credits', 'billing_credits'),
				'rules' => array('trim', 'required', 'is_natural_no_zero')
			),
			'price' => array(
				'label' => __('price', 'billing'),
				'rules' => array('trim', 'required', 'numeric')
			),
			'active' => array(
				'label' => __('active', 'system'),
				'rules' => array('trim', 'required', 'intval')
			)
		);

		// Assign rules
		validate::setRules($rules);

		// Validate fields
		if ( !validate::run() )
		{
			return false;
		}

		// Get post data
		$package = input::post(array('credits', 'price', 'active'));

		// Save banner group
		if ( !( $packageID = $this->credits_model->savePackage($packageID, $package) ) )
		{
			view::setError(__('save_error', 'system'));
			return false;
		}

		// Success
		view::setInfo(__('package_saved', 'billing_credits'));

		router::redirect('cp/billing/credits/edit/' . $packageID);
	}

	public function delete()
	{
		// Check if demo mode is enabled
		if ( input::demo(1, 'cp/billing/credits') ) return false;

		// Get URI vars
		$packageID = (int)uri::segment(5);

		// Get package
		if ( !$packageID || !( $package = $this->credits_model->getPackage($packageID) ) )
		{
			view::setError(__('no_package', 'billing_credits'));
			router::redirect('cp/billing/credits');
		}

		// Delete package
		$this->credits_model->deletePackage($packageID, $package);

		// Success
		view::setInfo(__('package_deleted', 'billing_credits'));

		router::redirect('cp/billing/credits');
	}
}
