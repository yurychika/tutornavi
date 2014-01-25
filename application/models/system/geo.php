<?php

class System_Geo_Model extends Model
{
	public function saveCountry($countryID, $data)
	{
		if ( $data['name_' . config::item('languages', 'core', 'keywords', config::item('language_id', 'system'))] )
		{
			$data['name'] = $data['name_' . config::item('languages', 'core', 'keywords', config::item('language_id', 'system'))];
		}

		if ( $countryID )
		{
			$retval = $this->db->update('geo_countries', $data, array('country_id' => $countryID), 1);

			// Action hook
			hook::action('system/geo/countries/update', $countryID, $data);
		}
		else
		{
			$retval = $this->db->insert('geo_countries', $data);

			// Action hook
			hook::action('system/geo/countries/insert', $retval, $data);
		}

		$this->cache->cleanup();

		return $retval;
	}

	public function getCountries($return = false, $multilang = false)
	{
		$output = array();

		$result = $this->db->query("SELECT * FROM `:prefix:geo_countries` ORDER BY `name` ASC")->result();
		foreach ( $result as $row )
		{
			if ( $multilang )
			{
				$output[$row['country_id']] = $row;
			}
			else
			{
				$output[$row['country_id']] = $row['name'];
			}
		}

		if ( $return )
		{
			return $output;
		}
		else
		{
			echo json_encode($output);
			exit;
		}
	}

	public function getCountry($countryID, $multilang = false)
	{
		$row = $this->db->query("SELECT * FROM `:prefix:geo_countries` WHERE `country_id`=? LIMIT 1", array($countryID))->row();

		if ( $multilang )
		{
			return $row ? $row : array();
		}
		else
		{
			return $row ? $row['name'] : '';
		}
	}

	public function saveState($countryID, $stateID, $data)
	{
		if ( $data['name_' . config::item('languages', 'core', 'keywords', config::item('language_id', 'system'))] )
		{
			$data['name'] = $data['name_' . config::item('languages', 'core', 'keywords', config::item('language_id', 'system'))];
		}

		if ( $stateID )
		{
			$retval = $this->db->update('geo_states', $data, array('state_id' => $stateID), 1);

			// Action hook
			hook::action('system/geo/states/update', $stateID, $data);
		}
		else
		{
			$data['country_id'] = $countryID;

			$retval = $this->db->insert('geo_states', $data);

			// Action hook
			hook::action('system/geo/states/insert', $retval, $data);
		}

		return $retval;
	}

	public function getStates($countryID = false, $return = false, $multilang = false)
	{
		$countryID = $countryID ? $countryID : uri::segment(3);

		$output = array();

		$result = $this->db->query("SELECT * FROM `:prefix:geo_states` WHERE `country_id`=? ORDER BY `name` ASC", array($countryID))->result();
		foreach ( $result as $row )
		{
			if ( $multilang )
			{
				$output[$row['state_id']] = $row;
			}
			else
			{
				$output[$row['state_id']] = $row['name'];
			}
		}

		if ( $return )
		{
			return $output;
		}
		else
		{
			echo json_encode($output);
			exit;
		}
	}

	public function getState($stateID, $multilang = false)
	{
		$row = $this->db->query("SELECT * FROM `:prefix:geo_states` WHERE `state_id`=? LIMIT 1", array($stateID))->row();

		if ( $multilang )
		{
			return $row ? $row : array();
		}
		else
		{
			return $row ? $row['name'] : '';
		}
	}

	public function saveCity($countryID, $stateID, $cityID, $data)
	{
		if ( $data['name_' . config::item('languages', 'core', 'keywords', config::item('language_id', 'system'))] )
		{
			$data['name'] = $data['name_' . config::item('languages', 'core', 'keywords', config::item('language_id', 'system'))];
		}

		if ( $cityID )
		{
			$retval = $this->db->update('geo_cities', $data, array('city_id' => $cityID), 1);

			// Action hook
			hook::action('system/geo/cities/update', $cityID, $data);
		}
		else
		{
			$data['country_id'] = $countryID;
			$data['state_id'] = $stateID;

			$retval = $this->db->insert('geo_cities', $data);

			// Action hook
			hook::action('system/geo/cities/insert', $retval, $data);
		}

		return $retval;
	}

	public function getCities($stateID = false, $return = false, $multilang = false)
	{
		$stateID = $stateID ? $stateID : uri::segment(3);

		$output = array();

		$result = $this->db->query("SELECT * FROM `:prefix:geo_cities` WHERE `state_id`=? ORDER BY `name` ASC", array($stateID))->result();
		foreach ( $result as $row )
		{
			if ( $multilang )
			{
				$output[$row['city_id']] = $row;
			}
			else
			{
				$output[$row['city_id']] = $row['name'];
			}
		}

		if ( $return )
		{
			return $output;
		}
		else
		{
			echo json_encode($output);
			exit;
		}
	}

	public function getCity($cityID = false, $multilang = false)
	{
		$row = $this->db->query("SELECT * FROM `:prefix:geo_cities` WHERE `city_id`=? LIMIT 1", array($cityID))->row();

		if ( $multilang )
		{
			return $row ? $row : array();
		}
		else
		{
			return $row ? $row['name'] : '';
		}
	}

	public function deleteCountry($countryID)
	{
		$this->db->delete('geo_countries', array('country_id' => $countryID));
		$this->db->delete('geo_states', array('country_id' => $countryID));
		$this->db->delete('geo_cities', array('country_id' => $countryID));
	}

	public function deleteState($stateID)
	{
		$this->db->delete('geo_states', array('state_id' => $stateID));
		$this->db->delete('geo_cities', array('state_id' => $stateID));
	}

	public function deleteCity($cityID)
	{
		$this->db->delete('geo_cities', array('city_id' => $cityID));
	}
}
