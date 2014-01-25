<?php

class Billing_Plans_Controller extends Billing_Payments_Controller
{
	public function __construct()
	{
		parent::__construct();

		// Is user loggedin ?
		if ( !users_helper::isLoggedin() )
		{
			router::redirect('users/login');
		}
		elseif ( !config::item('subscriptions_active', 'billing') )
		{
			router::redirect('users/settings');
		}

		loader::model('billing/plans');
	}

	public function index()
	{
		$this->plans();
	}

	public function plans()
	{
		// Get plans
		if ( !( $plans = $this->plans_model->getPlans() ) )
		{
			view::setInfo(__('no_plans_user', 'billing_plans'));
		}

		// Assign vars
		view::assign(array('plans' => $plans));

		// Set title
		view::setTitle(__('plans', 'billing_plans'));

		// Load view
		view::load('billing/plans');
	}

	public function payment()
	{
		// Get URI vars
		$planID = (int)uri::segment(4);

		// Get plan
		if ( !$planID || !( $plan = $this->plans_model->getPlan($planID) ) || !$plan['active'] )
		{
			view::setError(__('no_plan', 'billing_plans'));
			router::redirect('billing/plans');
		}

		// Get gateways
		if ( !( $gateways = $this->gateways_model->getGateways() ) )
		{
			view::setError(__('no_gateways_user', 'billing_gateways'));
			router::redirect('billing/plans');
		}

		// Product
		$plan['product_id'] = $planID;
		$plan['type'] = 'plans';

		// Assign vars
		view::assign(array('product' => $plan, 'gateways' => $gateways, 'location' => 'billing/plans/payment/' . $planID));

		// Set title
		view::setTitle(__('payment', 'billing_transactions'));

		// Set trail
		view::setTrail('billing/plans', __('plans', 'billing_plans'));

		// Load view
		view::load('billing/payment');
	}

	public function checkout()
	{
		// Get URI vars
		$planID = (int)uri::segment(4);
		$gatewayID = uri::segment(5);

		// Get plan
		if ( !$planID || !( $plan = $this->plans_model->getPlan($planID, false) ) || !$plan['active'] )
		{
			view::setError(__('no_plan', 'billing_plans'));
			router::redirect('billing/plans');
		}

		$retval = $this->process($gatewayID, session::item('user_id'), 'plans', $planID, $plan['name'], $plan['price'], '', 'billing/plans');
		if ( !$retval )
		{
			router::redirect('billing/plans/payment/' . $planID);
		}
	}
}
