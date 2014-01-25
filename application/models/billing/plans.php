<?php

class Billing_Plans_Model extends Model
{
	public function __construct()
	{
		parent::__construct();

		loader::helper('money');
	}

	public function savePlan($planID, $plan)
	{
		// Is this a new plan?
		if ( !$planID )
		{
			// Get last plan
			$lastPlan = $this->db->query("SELECT `order_id` FROM `:prefix:billing_plans` ORDER BY `order_id` DESC LIMIT 1")->row();
			$plan['order_id'] = $lastPlan ? ( $lastPlan['order_id'] + 1 ) : 1;

			// Save plan
			$planID = $this->db->insert('billing_plans', $plan);

			// Action hook
			hook::action('billing/plans/insert', $planID, $plan);
		}
		else
		{
			// Save plan
			$this->db->update('billing_plans', $plan, array('plan_id' => $planID), 1);

			// Action hook
			hook::action('billing/plans/insert', $planID, $plan);
		}

		return $planID;
	}

	public function updatePlan($planID, $plan)
	{
		// Update field
		$retval = $this->db->update('billing_plans', $plan, array('plan_id' => $planID), 1);

		if ( $retval )
		{
			// Action hook
			hook::action('billing/plans/update', $planID, $plan);
		}

		return $retval;
	}

	public function getPlan($planID, $escape = true)
	{
		// Get plan
		$plan = $this->db->query("SELECT * FROM `:prefix:billing_plans` WHERE `plan_id`=? LIMIT 1", array($planID))->row();

		if ( $plan )
		{
			if ( $escape )
			{
				foreach ( config::item('languages', 'core', 'keywords') as $language )
				{
					$plan['name_' . $language] = text_helper::entities($plan['name_' . $language]);
					$plan['description' . $language] = text_helper::entities($plan['description_' . $language]);
				}
			}

			$plan['name'] = $plan['name_' . session::item('language')];
			$plan['description'] = $plan['description_' . session::item('language')];
		}

		return $plan;
	}

	public function getPlans($active = true, $escape = true)
	{
		// Get plans
		$plans = $this->db->query("SELECT * FROM `:prefix:billing_plans` " . ( $active ? "WHERE `active`=1" : "") . " ORDER BY `order_id` ASC")->result();

		foreach ( $plans as $index => $plan )
		{
			$plans[$index]['name'] = $escape ? text_helper::entities($plan['name_' . session::item('language')]) : $plan['name_' . session::item('language')];
			$plans[$index]['description'] = $escape ? text_helper::entities($plan['description_' . session::item('language')]) : $plan['description_' . session::item('language')];
		}

		return $plans;
	}

	public function deletePlan($planID, $plan)
	{
		// Delete plans plan
		$retval = $this->db->delete('billing_plans', array('plan_id' => $planID), 1);

		if ( $retval )
		{
			// Update order IDs
			$this->db->query("UPDATE `:prefix:billing_plans` SET `order_id`=`order_id`-1 WHERE `order_id`>?", array($plan['order_id']));

			// Action hook
			hook::action('billing/plans/delete', $planID, $plan);
		}

		return $retval;
	}

	public function process($userID, $productID, $params)
	{
		// Get subscription plan and user
		$plan = $this->getPlan($productID);
		$user = $this->users_model->getUser($userID);

		// Set cycle
		switch ( $plan['cycle'] )
		{
			case 2:
				$cycle = 'weeks';
				break;
			case 3:
				$cycle = 'months';
				break;
			case 4:
				$cycle = 'years';
				break;
			default:
				$cycle = 'days';
		}

		// Truncate cycle for single digit duration
		if ( $plan['duration'] == 1 )
		{
			$cycle = substr($cycle, 0, -1);
		}

		// Set expiration date
		$expire = strtotime('+' . $plan['duration'] . ' ' . $cycle, ( $user['expire_date'] ? $user['expire_date'] : date_helper::now() ));

		// Update group ID and expiration date
		$retval = $this->db->query("UPDATE `:prefix:users` SET `group_id`=?, `old_group_id`=?, `expire_date`=? WHERE `user_id`=? LIMIT 1", array($plan['group_id'], $user['group_id'], $expire, $userID));

		// Action hook
		hook::action('billing/plans/process', $productID, $userID, $plan['group_id'], $expire);

		return true;
	}

	public function checkExpiration()
	{
		// Get expired users
		$users = $this->db->query("SELECT * FROM `:prefix:users` WHERE `expire_date`>0 AND `expire_date`<?", array(date_helper::now()))->result();

		foreach ( $users as $user )
		{
			$this->db->query("UPDATE `:prefix:users` SET `group_id`=?, `old_group_id`=0, `expire_date`=0 WHERE `user_id`=? LIMIT 1", array(( $user['old_group_id'] ? $user['old_group_id'] : config::item('group_default_id', 'users') ), $user['user_id']));
		}

		// Action hook
		hook::action('billing/plans/check_expiration');

		$this->cron_model->addLog('[Billing] Processed ' . count($users) . ' expired subscriptions.');
	}
}
