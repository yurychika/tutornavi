<?php

class Billing_Credits_Controller extends Billing_Payments_Controller
{
	public function __construct()
	{
		parent::__construct();

		// Is user loggedin ?
		if ( !users_helper::isLoggedin() )
		{
			router::redirect('users/login');
		}
		elseif ( !config::item('credits_active', 'billing') )
		{
			router::redirect('users/settings');
		}

		loader::model('billing/credits');
	}

	public function index()
	{
		$this->credits();
	}

	public function credits()
	{
		// Get packages
		if ( !( $packages = $this->credits_model->getPackages() ) )
		{
			view::setInfo(__('no_packages_user', 'billing_credits'));
		}

		// Assign vars
		view::assign(array('packages' => $packages));

		// Set title
		view::setTitle(__('credits', 'billing_credits'));

		// Load view
		view::load('billing/credits');
	}

	public function payment()
	{
		// Get URI vars
		$packageID = (int)uri::segment(4);

		// Get package
		if ( !$packageID || !( $package = $this->credits_model->getPackage($packageID) ) || !$package['active'] )
		{
			view::setError(__('no_package', 'billing_credits'));
			router::redirect('billing/credits');
		}

		// Get gateways
		if ( !( $gateways = $this->gateways_model->getGateways() ) )
		{
			view::setError(__('no_gateways_user', 'billing_gateways'));
			router::redirect('billing/credits');
		}

		// Product
		$package['product_id'] = $packageID;
		$package['name'] = __('credits_info', 'billing_credits', array('%s' => $package['credits']));
		$package['type'] = 'credits';

		// Assign vars
		view::assign(array('product' => $package, 'gateways' => $gateways, 'location' => 'billing/credits/payment/' . $packageID));

		// Set title
		view::setTitle(__('payment', 'billing_transactions'));

		// Set trail
		view::setTrail('billing/credits', __('credits', 'billing_credits'));

		// Load view
		view::load('billing/payment');
	}

	public function checkout()
	{
		// Get URI vars
		$packageID = (int)uri::segment(4);
		$gatewayID = uri::segment(5);

		// Get package
		if ( !$packageID || !( $package = $this->credits_model->getPackage($packageID) ) || !$package['active'] )
		{
			view::setError(__('no_package', 'billing_credits'));
			router::redirect('billing/credits');
		}

		// Set package name
		$name = __('credits_info', 'billing_credits', array('%s' => $package['credits']));

		$retval = $this->process($gatewayID, session::item('user_id'), 'credits', $packageID, $name, $package['price'], '', 'billing/credits');
		if ( !$retval )
		{
			router::redirect('billing/credits/payment/' . $packageID);
		}
	}
}
