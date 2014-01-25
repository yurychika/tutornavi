<?php

class CP_Billing_Plans_Controller extends Controller
{
	public function __construct()
	{
		parent::__construct();

		// Does user have permission to access this plugin?
		if ( !session::permission('plans_manage', 'billing') )
		{
			view::noAccess();
		}

		view::setCustomParam('section', 'billing');
		view::setCustomParam('options', config::item('cp_top_nav', 'lists', 'billing', 'items'));

		loader::model('billing/plans');

		view::setTrail('cp/billing/transactions', __('billing', 'system_navigation'));
		view::setTrail('cp/billing/plans', __('billing_plans', 'system_navigation'));
	}

	public function index()
	{
		$this->browse();
	}

	public function browse()
	{
		// Did we submit the form?
		if ( input::post('action') == 'reorder' && input::post('ids') )
		{
			$this->_reorderPlans();
		}

		// Get plans
		if ( !( $plans = $this->plans_model->getPlans(false) ) )
		{
			view::setInfo(__('no_plans', 'billing_plans'));
		}

		// Set cycles
		$cycles = array_map('strtolower', array(1 => __('day', 'date'), 2 => __('week', 'date'), 3 => __('month', 'date'), 4 => __('year', 'date')));
		$cyclesMulti = array_map('strtolower', array(1 => __('days', 'date'), 2 => __('weeks', 'date'), 3 => __('months', 'date'), 4 => __('years', 'date')));

		// Create table grid
		$grid = array(
			'uri' => 'cp/billing/plans/browse',
			'keyword' => 'billing_plans',
			'header' => array(
				'name' => array(
					'html' => __('name', 'system'),
					'class' => 'name',
				),
				'group' => array(
					'html' => __('plan_group', 'billing_plans'),
					'class' => 'group',
				),
				'cycle' => array(
					'html' => __('plan_cycle', 'billing_plans'),
					'class' => 'cycle',
				),
				'price' => array(
					'html' => __('price', 'billing'),
					'class' => 'price',
				),
				'signup' => array(
					'html' => __('plan_signup', 'billing_plans'),
					'class' => 'signup',
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
		foreach ( $plans as $plan )
		{
			$grid['content'][] = array(
				'name' => array(
					'html' => html_helper::anchor('cp/billing/plans/edit/' . $plan['plan_id'], $plan['name']),
				),
				'group' => array(
					'html' => config::item('usergroups', 'core', $plan['group_id']),
				),
				'cycle' => array(
					'html' => $plan['duration'] . ' ' . ( $plan['duration'] == 1 ? $cycles[$plan['cycle']] : $cyclesMulti[$plan['cycle']] ),
				),
				'price' => array(
					'html' => money_helper::symbol(config::item('currency', 'billing')) . $plan['price'],
				),
				'signup' => array(
					'html' => $plan['signup'] ? '<span class="label success small">' . __('yes', 'system') . '</span>' : '<span class="label important small">' . __('no', 'system') . '</span>',
				),
				'status' => array(
					'html' => $plan['active'] ? '<span class="label success small">' . __('yes', 'system') . '</span>' : '<span class="label important small">' . __('no', 'system') . '</span>',
				),
				'actions' => array(
					'html' => array(
						'edit' => html_helper::anchor('cp/billing/plans/edit/' . $plan['plan_id'], __('edit', 'system'), array('class' => 'edit')),
						'delete' => html_helper::anchor('cp/billing/plans/delete/' . $plan['plan_id'], __('delete', 'system'), array('data-html' => __('plan_delete?', 'billing_plans'), 'data-role' => 'confirm', 'class' => 'delete')),
					),
				),
			);
		}

		// Filter hooks
		hook::filter('cp/billing/plans/browse/grid', $grid);

		// Assign vars
		view::assign(array('grid' => $grid, 'plans' => $plans));

		// Set title
		view::setTitle(__('billing_plans_manage', 'system_navigation'));

		// Assign actions
		view::setAction('cp/billing/plans/edit', __('plan_new', 'billing_plans'), array('class' => 'icon-text icon-billing-plans-new'));
		view::setAction('#', __('done', 'system'), array('class' => 'icon-text icon-system-done', 'onclick' => 'saveSortable();return false;', 'id' => 'actions_link_save'));
		view::setAction('#', __('cancel', 'system'), array('class' => 'icon-text icon-system-cancel', 'onclick' => 'cancelSortable();return false;', 'id' => 'actions_link_cancel'));
		view::setAction('#', __('reorder', 'system'), array('class' => 'icon-text icon-system-sort', 'onclick' => 'switchSortable();return false;', 'id' => 'actions_link_reorder'));

		// Include sortable vendor files
		view::includeJavascript('externals/html5sortable/html5sortable.js');
		view::includeStylesheet('externals/html5sortable/style.css');

		// Load view
		if ( input::isAjaxRequest() )
		{
			view::load('cp/billing/plans/browse_' . ( input::post('view') == 'list' ? 'list' :'grid' ));
		}
		else
		{
			view::load('cp/billing/plans/browse');
		}
	}

	protected function _reorderPlans()
	{
		// Check if demo mode is enabled
		if ( input::demo(0) ) return false;

		// Get submitted plan IDs
		$plans = input::post('ids');

		// Do we have any plan IDs?
		if ( $plans && is_array($plans) )
		{
			// Loop through plan IDs
			$orderID = 1;
			foreach ( $plans as $planID )
			{
				// Update plan ID
				$this->plans_model->updatePlan($planID, array('order_id' => $orderID));
				$orderID++;
			}
		}
	}

	public function edit()
	{
		// Get URI vars
		$planID = (int)uri::segment(5);

		// Get plan
		$plan = array();
		if ( $planID && !( $plan = $this->plans_model->getPlan($planID, false) ) )
		{
			view::setError(__('no_plan', 'billing_plans'));
			router::redirect('cp/billing/plans');
		}

		// Set user groups
		$groups = config::item('usergroups', 'core');
		unset($groups[config::item('group_guests_id', 'users')]);
		unset($groups[config::item('group_cancelled_id', 'users')]);

		// Set cycles
		$cycles = array(1 => __('days', 'date'), 2 => __('weeks', 'date'), 3 => __('months', 'date'), 4 => __('years', 'date'));

		// Assign vars
		view::assign(array('planID' => $planID, 'plan' => $plan, 'groups' => $groups, 'cycles' => $cycles));

		// Process form values
		if ( input::post('do_save_plan') )
		{
			$this->_savePlan($planID);
		}

		// Set title
		view::setTitle($planID ? __('plan_edit', 'billing_plans') : __('plan_new', 'billing_plans'));

		// Set actions
		if ( count(config::item('languages', 'core', 'keywords')) > 1 )
		{
			view::setAction('translate', '');
		}

		// Set trail
		view::setTrail('cp/billing/plans/edit/' . ( $planID ? $planID : '' ), ( $planID ? __('plan_edit', 'billing_plans') . ' - ' . text_helper::entities($plan['name']) : __('plan_new', 'billing_plans') ));

		// Load view
		view::load('cp/billing/plans/edit');
	}

	protected function _savePlan($planID)
	{
		// Check if demo mode is enabled
		if ( input::demo() ) return false;

		// Rules array
		$rules = array();

		// Data array
		$input = array('duration', 'cycle', 'price', 'group_id', 'signup', 'active');

		// Name
		foreach ( config::item('languages', 'core', 'keywords') as $languageID => $languageKey )
		{
			$rules['name_' . $languageKey] = array(
				'label' => __('name', 'system') . ( count(config::item('languages', 'core', 'keywords')) > 1 ? ' [' . config::item('languages', 'core', 'names', $languageID) . ']' : '' ),
				'rules' => array('trim', 'required', 'max_length' => 255)
			);
			$input[] = 'name_' . $languageKey;
			$rules['description_' . $languageKey] = array(
				'label' => __('description', 'system') . ( count(config::item('languages', 'core', 'keywords')) > 1 ? ' [' . config::item('languages', 'core', 'names', $languageID) . ']' : '' ),
				'rules' => array('trim', 'required')
			);
			$input[] = 'description_' . $languageKey;
		}

		// Additional rules
		$rules['duration'] = array(
			'label' => __('plan_cycle', 'billing_plans'),
			'rules' => array('trim', 'required', 'is_natural_no_zero')
		);
		$rules['cycle'] = array(
			'label' => __('plan_cycle', 'billing_plans'),
			'rules' => array('trim', 'required', 'intval')
		);
		$rules['price'] = array(
			'label' => __('price', 'billing'),
			'rules' => array('trim', 'required', 'numeric')
		);
		$rules['group_id'] = array(
			'label' => __('user_group', 'users'),
			'rules' => array('trim', 'required', 'intval')
		);
		$rules['signup'] = array(
			'label' => __('plan_show_signup', 'billing_plans'),
			'rules' => array('trim', 'required', 'intval')
		);
		$rules['active'] = array(
			'label' => __('active', 'system'),
			'rules' => array('trim', 'required', 'intval')
		);

		// Assign rules
		validate::setRules($rules);

		// Validate fields
		if ( !validate::run() )
		{
			return false;
		}

		// Get post data
		$plan = input::post($input);

		// Save banner group
		if ( !( $planID = $this->plans_model->savePlan($planID, $plan) ) )
		{
			view::setError(__('save_error', 'system'));
			return false;
		}

		// Success
		view::setInfo(__('plan_saved', 'billing_plans'));

		router::redirect('cp/billing/plans/edit/' . $planID);
	}

	public function delete()
	{
		// Check if demo mode is enabled
		if ( input::demo(1, 'cp/billing/plans') ) return false;

		// Get URI vars
		$planID = (int)uri::segment(5);

		// Get plan
		if ( !$planID || !( $plan = $this->plans_model->getPlan($planID) ) )
		{
			view::setError(__('no_plan', 'billing_plans'));
			router::redirect('cp/billing/plans');
		}

		// Delete plan
		$this->plans_model->deletePlan($planID, $plan);

		// Success
		view::setInfo(__('plan_deleted', 'billing_plans'));

		router::redirect('cp/billing/plans');
	}
}
