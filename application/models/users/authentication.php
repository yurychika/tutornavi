<?php

class Users_Authentication_Model extends Model
{
	public function getServices($active = false)
	{
		$services = array();

		foreach ( $this->db->query("SELECT `service_id`, `name`, `keyword`, `active` FROM `:prefix:users_auth` " . ( $active ? "WHERE `active`=1" : "" ) . " ORDER BY `name` ASC")->result() as $service )
		{
			$services[$service['service_id']] = $service;
		}

		return $services;
	}

	public function getService($keyword)
	{
		$service = $this->db->query("SELECT `service_id`, `name`, `keyword`, `settings`, `active` FROM `:prefix:users_auth` WHERE `keyword`=? LIMIT 1", array($keyword))->row();

		if ( $service && !($service['settings'] = @json_decode($service['settings'], true)) )
		{
			$service['settings'] = array();
		}

		return $service;
	}

	public function scanServices($merge = true)
	{
		// Load file helper and read services directory
		loader::helper('file');
		$dirs = file_helper::scanFileNames(DOCPATH . 'libraries/authentication');

		$services = array();

		// Loop through found directories
		foreach ( $dirs as $service )
		{
			// Remove file extension
			$service = substr($service, 0, -4);

			if ( $manifest = $this->getManifest($service) )
			{
				$services[$service] = $manifest;
			}
		}

		// Do we need to merge results with installed services?
		if ( $merge )
		{
			// Loop through installed services
			foreach ( $this->getServices() as $service )
			{
				if ( isset($services[$service['keyword']]) )
				{
					$services[$service['keyword']]['service_id'] = $service['service_id'];
					$services[$service['keyword']]['active'] = $service['active'];
				}
			}
		}

		// Order services
		ksort($services);

		return $services;
	}

	public function getManifest($keyword)
	{
		$manifest = array();

		// Load service
		$service = loader::library('authentication/' . $keyword, array(), null);

		// Does service exist?
		if ( $service )
		{
			$params = $service->getManifest();

			$manifest = array(
				'keyword' => $keyword,
				'name' => $params['name'],
				'description' => $params['description'],
				'settings' => isset($params['settings']) && is_array($params['settings']) ? $params['settings'] : array(),
				'values' => array(),
				'active' => 0,
			);
		}

		return $manifest;
	}

	public function saveSettings($serviceID, $settings, $active, $service)
	{
		$retval = $this->db->update('users_auth', array('settings' => json_encode($settings), 'active' => $active), array('service_id' => $serviceID), 1);

		if ( $retval )
		{
			$services = array();
			foreach ( $this->getServices(1) as $service )
			{
				$services[$service['keyword']] = $service['keyword'];
			}
			$this->db->update('core_config', array('val' => json_encode($services)), array('plugin' => 'users', 'keyword' => 'auth_methods'), 1);

			// Action hook
			hook::action('users/authentication/settings/update', $serviceID, $settings);

			$this->cache->cleanup();
		}

		return $retval;
	}

	public function isInUse($keyword)
	{
		// Load service
		$service = loader::library('authentication/' . $keyword, array(), null);

		$retval = method_exists($service, 'isInUse') && $service->isInUse() ? 1 : 0;

		return $retval;
	}

	public function install($keyword, $service)
	{
		// Create data array
		$data = array(
			'name' => $service['name'],
			'keyword' => $service['keyword'],
			'settings' => array(),
		);

		// Parse settings
		foreach ( $service['settings'] as $setting )
		{
			if ( isset($setting['value']) )
			{
				$data['settings'][$setting['keyword']] = $setting['value'];
			}
		}

		// Load dbforge library
		loader::library('dbforge');
		$this->dbforge->setEngine('InnoDB');

		// Load service
		$service = loader::library('authentication/' . $keyword, array(), null);

		// Run install method
		if ( method_exists($service, 'install') )
		{
			$service->install();
		}

		// Encode settings
		$data['settings'] = json_encode($data['settings']);

		// Insert service
		$serviceID = $this->db->insert('users_auth', $data);

		if ( $serviceID )
		{
			// Action hook
			hook::action('users/authentication/install', $serviceID, $data);
		}

		return $serviceID;
	}

	public function uninstall($serviceID, $service)
	{
		// Load dbforge library
		loader::library('dbforge');
		$this->dbforge->setEngine('InnoDB');

		// Load service
		$service = loader::library('authentication/' . $service['keyword'], array(), null);

		// Run install method
		if ( method_exists($service, 'uninstall') )
		{
			$service->uninstall();
		}

		// Delete service
		$retval = $this->db->delete('users_auth', array('service_id' => $serviceID), 1);

		if ( $retval )
		{
			// Action hook
			hook::action('users/authentication/uninstall', $serviceID, $service);
		}

		return $retval;
	}
}
