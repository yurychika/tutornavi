<?php

class CP_System_Geo_Controller extends Controller
{
	public function __construct()
	{
		parent::__construct();

		// Does user have permission to access this plugin?
		if ( !session::permission('geo_manage', 'system') )
		{
			view::noAccess();
		}

		view::setCustomParam('section', 'system');
		view::setCustomParam('options', config::item('cp_top_nav', 'lists', 'system', 'items', 'system/settings', 'items'));

		view::setTrail('cp/system/config/system', __('system', 'system_navigation'));
		view::setTrail('cp/system/geo', __('system_geo', 'system_navigation'));
	}

	public function index()
	{
		$this->browse();
	}

	public function browse()
	{
		// Get URI vars
		$countryID = uri::segment(5);
		$stateID = uri::segment(6);

		// Get country
		if ( $countryID && !( $country = $this->geo_model->getCountry($countryID, true) ) )
		{
			view::setError(__('no_country', 'system_geo'));
			router::redirect('cp/system/geo');
		}

		// Get state
		if ( $stateID && !( $state = $this->geo_model->getState($stateID, true) ) )
		{
			view::setError(__('no_state', 'system_geo'));
			router::redirect('cp/system/geo');
		}

		if ( $stateID )
		{
			// Get cities
			if ( $stateID && !( $data = $this->geo_model->getCities($stateID, true) ) )
			{
				view::setError(__('no_cities', 'system_geo'));
				router::redirect('cp/system/geo/edit/' . $countryID . '/' . $stateID);
			}
		}
		elseif ( $countryID )
		{
			// Get states
			if ( !( $data = $this->geo_model->getStates($countryID, true) ) )
			{
				view::setError(__('no_states', 'system_geo'));
				router::redirect('cp/system/geo/edit/' . $countryID);
			}
		}
		else
		{
			// Get countries
			if ( !( $data = $this->geo_model->getCountries(true) ) )
			{
				view::setError(__('no_countries', 'system_geo'));
				router::redirect('cp/system/config/system');
			}
		}

		// Create table grid
		$grid = array(
			'uri' => 'cp/system/geo',
			'keyword' => 'countries',
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
		foreach ( $data as $itemID => $item )
		{
			$name['html'] = $stateID ? text_helper::entities($item) : html_helper::anchor('cp/system/geo/browse/' . ( $countryID ? $countryID . '/' : '' ) . $itemID, text_helper::entities($item));

			$actions['html']['edit'] = html_helper::anchor('cp/system/geo/edit/' . ( $countryID ? $countryID . '/' : '' ) . ( $stateID ? $stateID . '/' : '' ) . $itemID, __('edit', 'system'), array('class' => 'edit'));
			$actions['html']['delete'] = html_helper::anchor('cp/system/geo/delete/' . ( $countryID ? $countryID . '/' : '' ) . ( $stateID ? $stateID . '/' : '' ) . $itemID, __('delete', 'system'), array('data-html' => __('item_delete?', 'system_geo'), 'data-role' => 'confirm', 'class' => 'delete'));

			$grid['content'][] = array(
				'name' => $name,
				'actions' => $actions,
			);
		}

		// Filter hooks
		hook::filter('cp/system/geo/countries/grid', $grid);

		// Assign vars
		view::assign(array('grid' => $grid));

		if ( $stateID )
		{
			// Set title
			view::setTitle(__('cities_manage', 'system_geo'));

			// Set trail
			view::setTrail('cp/system/geo/browse/' . $countryID, text_helper::entities($country['name']));
			view::setTrail('cp/system/geo/browse/' . $countryID . '/' . $stateID, text_helper::entities($state['name']));

			// Assign actions
			view::setAction('cp/system/geo/create/' . $countryID . '/' . $stateID, __('city_new', 'system_geo'), array('class' => 'icon-text icon-system-geo-new'));
		}
		elseif ( $countryID )
		{
			// Set title
			view::setTitle(__('states_manage', 'system_geo'));

			// Set trail
			view::setTrail('cp/system/geo/browse/' . $countryID, text_helper::entities($country['name']));

			// Assign actions
			view::setAction('cp/system/geo/create/' . $countryID, __('state_new', 'system_geo'), array('class' => 'icon-text icon-system-geo-new'));
		}
		else
		{
			// Set title
			view::setTitle(__('countries_manage', 'system_geo'));

			// Assign actions
			view::setAction('cp/system/geo/create/', __('country_new', 'system_geo'), array('class' => 'icon-text icon-system-geo-new'));
		}

		// Load view
		view::load('cp/system/geo/browse');
	}

	public function create()
	{
		// Get URI vars
		$countryID = uri::segment(5);
		$stateID = uri::segment(6);

		// Assign vars
		view::assign(array('countryID' => $countryID, 'stateID' => $stateID));

		// Get country
		if ( $countryID && !( $country = $this->geo_model->getCountry($countryID, true) ) )
		{
			view::setError(__('no_country', 'system_geo'));
			router::redirect('cp/system/geo');
		}

		// Get state
		if ( $stateID && !( $state = $this->geo_model->getState($stateID, true) ) )
		{
			view::setError(__('no_state', 'system_geo'));
			router::redirect('cp/system/geo/browse/' . $countryID);
		}

		// Process form values
		if ( input::post('do_save_geo') )
		{
			$this->_saveGeoData($countryID, $stateID, 0, 1);
		}

		if ( $stateID )
		{
			// Set title
			view::setTitle(__('city_new', 'system_geo'));

			// Set trail
			view::setTrail('cp/system/geo/browse/' . $countryID, text_helper::entities($country['name']));
			view::setTrail('cp/system/geo/browse/' . $countryID . '/' . $stateID, text_helper::entities($state['name']));
			view::setTrail('cp/system/geo/create/' . $countryID . '/' . $stateID, __('city_new', 'system_geo'));
		}
		elseif ( $countryID )
		{
			// Set title
			view::setTitle(__('state_new', 'system_geo'));

			// Set trail
			view::setTrail('cp/system/geo/browse/' . $countryID, text_helper::entities($country['name']));
			view::setTrail('cp/system/geo/create/' . $countryID, __('state_new', 'system_geo'));
		}
		else
		{
			// Set title
			view::setTitle(__('country_new', 'system_geo'));

			// Set trail
			view::setTrail('cp/system/geo/create/' . $countryID . '/' . $stateID, __('country_new', 'system_geo'));
		}


		// Set actions
		if ( count(config::item('languages', 'core', 'keywords')) > 1 )
		{
			view::setAction('translate', '');
		}

		// Load view
		view::load('cp/system/geo/edit');
	}

	public function edit()
	{
		// Get URI vars
		$countryID = uri::segment(5);
		$stateID = uri::segment(6);
		$cityID = uri::segment(7);

		// Assign vars
		view::assign(array('countryID' => $countryID, 'stateID' => $stateID, 'cityID' => $cityID));

		// Get country
		if ( !$countryID || !( $country = $this->geo_model->getCountry($countryID, true) ) )
		{
			view::setError(__('no_country', 'system_geo'));
			router::redirect('cp/system/geo');
		}

		// Get state
		if ( $stateID && !( $state = $this->geo_model->getState($stateID, true) ) )
		{
			view::setError(__('no_state', 'system_geo'));
			router::redirect('cp/system/geo/browse/' . $countryID);
		}

		// Get city
		if ( $cityID && !( $city = $this->geo_model->getCity($cityID, true) ) )
		{
			view::setError(__('no_state', 'system_geo'));
			router::redirect('cp/system/geo/browse/' . $countryID . '/' . $stateID);
		}

		// Process form values
		if ( input::post('do_save_geo') )
		{
			$this->_saveGeoData($countryID, $stateID, $cityID);
		}

		if ( $cityID )
		{
			// Assign vars
			view::assign(array('data' => $city));

			// Set title
			view::setTitle(__('city_edit', 'system_geo'));

			// Set trail
			view::setTrail('cp/system/geo/browse/' . $countryID, text_helper::entities($country['name']));
			view::setTrail('cp/system/geo/browse/' . $countryID . '/' . $stateID, text_helper::entities($state['name']));
			view::setTrail('cp/system/geo/edit/' . $countryID . '/' . $stateID . '/' . $cityID, __('city_edit', 'system_geo') . ' - ' . text_helper::entities($city['name']));
		}
		elseif ( $stateID )
		{
			// Assign vars
			view::assign(array('data' => $state));

			// Set title
			view::setTitle(__('state_edit', 'system_geo'));

			// Set trail
			view::setTrail('cp/system/geo/browse/' . $countryID, text_helper::entities($country['name']));
			view::setTrail('cp/system/geo/edit/' . $countryID . '/' . $stateID, __('state_edit', 'system_geo') . ' - ' . text_helper::entities($state['name']));

			// Assign actions
			view::setAction('cp/system/geo/create/' . $countryID . '/' . $stateID, __('city_new', 'system_geo'), array('class' => 'icon-text icon-system-geo-new'));
		}
		else
		{
			// Assign vars
			view::assign(array('data' => $country));

			// Set title
			view::setTitle(__('country_edit', 'system_geo'));

			// Set trail
			view::setTrail('cp/system/geo/edit/' . $countryID, __('country_edit', 'system_geo') . ' - ' . text_helper::entities($country['name']));

			// Assign actions
			view::setAction('cp/system/geo/create/' . $countryID, __('state_new', 'system_geo'), array('class' => 'icon-text icon-system-geo-new'));
		}

		// Set actions
		if ( count(config::item('languages', 'core', 'keywords')) > 1 )
		{
			view::setAction('translate', '');
		}

		// Load view
		view::load('cp/system/geo/edit');
	}

	protected function _saveGeoData($countryID, $stateID, $cityID, $new = false)
	{
		// Check if demo mode is enabled
		if ( input::demo() ) return false;

		// Is this a new value?
		if ( $new )
		{
			// Create rules
			$rules = array('name_' . config::item('languages', 'core', 'keywords', config::item('language_id', 'system')) =>
				array(
					'label' => __('name', 'system') . ( count(config::item('languages', 'core', 'keywords')) > 1 ? ' [' . config::item('languages', 'core', 'names', config::item('language_id', 'system')) . ']' : '' ),
					'rules' => array('trim', 'required', 'max_length' => 255)
				)
			);

			// Assign rules
			validate::setRules($rules);

			// Validate fields
			if ( !validate::run() )
			{
				return false;
			}
		}

		$data = array();
		foreach ( config::item('languages', 'core', 'keywords') as $language )
		{
			$data['name_' . $language] = input::post('name_' . $language);
		}

		if ( $cityID || $new && $stateID )
		{
			$this->geo_model->saveCity($countryID, $stateID, $cityID, $data);

			view::setInfo(__('city_saved', 'system_geo'));
		}
		elseif ( $stateID || $new && $countryID )
		{
			$this->geo_model->saveState($countryID, $stateID, $data);

			view::setInfo(__('state_saved', 'system_geo'));
		}
		else
		{
			$this->geo_model->saveCountry($countryID, $data);

			view::setInfo(__('country_saved', 'system_geo'));
		}

		if ( $new )
		{
			router::redirect('cp/system/geo/browse/' . $countryID . ( $stateID ? '/' . $stateID : '' ));
		}
		else
		{
			router::redirect('cp/system/geo/edit/' . $countryID . ( $stateID ? '/' . $stateID : '' ) . ( $cityID ? '/' . $cityID : '' ));
		}
	}

	public function delete()
	{
		// Get URI vars
		$countryID = uri::segment(5);
		$stateID = uri::segment(6);
		$cityID = uri::segment(7);

		// Check if demo mode is enabled
		if ( input::demo(1, 'cp/system/geo/browse/' . ( $stateID ? $countryID : '' ) . ( $cityID ? '/' . $stateID : '' )) ) return false;

		// Get country
		if ( !$countryID || !( $country = $this->geo_model->getCountry($countryID, true) ) )
		{
			view::setError(__('no_country', 'system_geo'));
			router::redirect('cp/system/geo');
		}

		// Get state
		if ( $stateID && !( $state = $this->geo_model->getState($stateID, true) ) )
		{
			view::setError(__('no_state', 'system_geo'));
			router::redirect('cp/system/geo/browse/' . $countryID);
		}

		// Get city
		if ( $cityID && !( $city = $this->geo_model->getCity($cityID, true) ) )
		{
			view::setError(__('no_state', 'system_geo'));
			router::redirect('cp/system/geo/browse/' . $countryID . '/' . $stateID);
		}

		if ( $cityID )
		{
			$this->geo_model->deleteCity($cityID);

			view::setInfo(__('city_deleted', 'system_geo'));
		}
		elseif ( $stateID )
		{
			$this->geo_model->deleteState($stateID);

			view::setInfo(__('state_deleted', 'system_geo'));
		}
		else
		{
			$this->geo_model->deleteCountry($countryID);

			view::setInfo(__('country_deleted', 'system_geo'));
		}

		// Redirect
		router::redirect('cp/system/geo/browse/' . ( $stateID ? $countryID : '' ) . ( $cityID ? '/' . $stateID : '' ));
	}
}
