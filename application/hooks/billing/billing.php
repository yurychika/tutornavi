<?php

class Billing_Billing_Hook extends Hook
{
	public function usersDelete($userID, $user)
	{
		if ( !$user['total_transactions'] && !$user['total_spent'] )
		{
			return true;
		}

		loader::model('billing/transactions');

		$retval = $this->transactions_model->deleteUser($userID, $user);

		return $retval;
	}

	public function usersSettingsTabs()
	{
		if ( config::item('invoices_active', 'billing') )
		{
			view::setTab('billing/invoices', __('invoices', 'users_account'), array('class' => ( uri::segment(1) == 'billing' && uri::segment(2) == 'invoices' ? 'active' : '' ) . ' icon-billing-invoices'));
		}
	}

	public function usersSettingsAccountOptions($settings, $user = array())
	{
		if ( input::isCP() )
		{
			if ( uri::segment(3) == 'edit' )
			{
				loader::helper('array');

				$expiration = array(
					'name' => __('expire_date', 'users_account'),
					'keyword' => 'expire_date',
					'type' => 'date',
					'value' => $user ? $user['expire_date'] : 0,
					'rules' => array('valid_date'),
					'select' => true,
				);

				$credits = array(
					'name' => __('credits_current', 'users_account'),
					'keyword' => 'total_credits',
					'type' => 'number',
					'value' => $user ? $user['total_credits'] : 0,
					'rules' => array('required' => 1, 'min_value' => 0),
				);

				$settings = array_helper::spliceArray($settings, 'group_id', $credits, 'total_credits');
				$settings = array_helper::spliceArray($settings, 'group_id', $expiration, 'expire_date');
			}
		}
		else
		{
			if ( config::item('subscriptions_active', 'billing') )
			{
				$settings['subscription'] = array(
					'name' => __('plan_current', 'users_account'),
					'keyword' => 'subscription',
					'type' => 'static',
					'value' => config::item('usergroups', 'core', session::item('group_id')) .
						( session::item('expire_date') ? ' (' . __('expire_date', 'users_account') . ': ' . date_helper::formatDate(session::item('expire_date'), 'date') . ')' : '' ) .
						( session::permission('plans_purchase', 'billing') ? ' - ' . html_helper::anchor('billing/plans', __('plan_change', 'users_account')) : '' )
				);
			}

			if ( config::item('credits_active', 'billing') )
			{
				$settings['credits'] = array(
					'name' => __('credits_current', 'users_account'),
					'keyword' => 'subscription',
					'type' => 'static',
					'value' => session::item('total_credits') .
						( session::permission('credits_purchase', 'billing') ? ' - ' . html_helper::anchor('billing/credits', __('credits_purchase', 'users_account')) : '' )
				);
			}
		}

		return $settings;
	}

	public function cronRun()
	{
		loader::model('billing/plans');

		$this->plans_model->checkExpiration();

		return true;
	}

	public function usersSignup($userID, $user)
	{
		if ( config::item('credits_signup_bonus', 'billing') && ( !isset($user['total_credits']) || $user['total_credits'] == 0 ) )
		{
			loader::model('billing/credits');

			$this->credits_model->addCredits($userID, config::item('credits_signup_bonus', 'billing'));
		}

		return true;
	}
}
