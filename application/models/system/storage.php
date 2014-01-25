<?php

class System_Storage_Model extends Model
{
	protected $errorMessage = false;

	public function getServices()
	{
		$services = array();

		foreach ( $this->db->query("SELECT * FROM `:prefix:storage_services` ORDER BY `name` ASC")->result() as $service )
		{
			$services[$service['service_id']] = $service;
		}

		return $services;
	}

	public function getService($keyword)
	{
		$service = $this->db->query("SELECT * FROM `:prefix:storage_services` WHERE `" . ( $keyword === 1 ? "default" : "keyword" ) . "`=? LIMIT 1", array($keyword))->row();

		if ( $service && !( $service['settings'] = @json_decode($service['settings'], true) ) )
		{
			$service['settings'] = array();
		}

		return $service;
	}

	public function scanServices($merge = true)
	{
		// Load file helper and read storage services directory
		loader::helper('file');
		$dirs = file_helper::scanFileNames(DOCPATH . 'libraries/storages');

		$services = array();

		// Loop through found directories
		foreach ( $dirs as $service )
		{
			// Remove file extension
			$service = substr($service, 0, -4);

			if ( $manifest = $this->getManifest($service) )
			{
				$services[$service] = $manifest;
				$services[$service]['default'] = 0;
			}
		}

		// Do we need to merge results with installed storage services?
		if ( $merge )
		{
			// Loop through installed storage services
			foreach ( $this->getServices() as $service )
			{
				if ( isset($services[$service['keyword']]) )
				{
					$services[$service['keyword']]['service_id'] = $service['service_id'];
					$services[$service['keyword']]['default'] = $service['default'];
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
		$service = loader::library('storages/'.$keyword, array(), null);

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
				'default' => 0,
			);
		}

		return $manifest;
	}

	public function setDefault($serviceID, $service)
	{
		// Reset current default storage
		$this->db->update('storage_services', array('default' => 0), array('default' => 1), 1);

		// Set new default storage
		$retval = $this->db->update('storage_services', array('default' => 1), array('service_id' => $serviceID), 1);

		if ( $retval )
		{
			// Update default system storage ID and settings
			$this->db->update('core_config', array('val' => $service['keyword']), array('plugin' => 'system', 'keyword' => 'default_storage'), 1);
			$this->db->update('core_config', array('val' => json_encode($service['settings'])), array('plugin' => 'system', 'keyword' => 'default_storage_settings'), 1);

			// Action hook
			hook::action('system/storage/default', $serviceID, $service);

			$this->cache->cleanup();
		}

		return $retval;
	}

	public function saveSettings($serviceID, $settings, $service)
	{
		$retval = $this->db->update('storage_services', array('settings' => json_encode($settings)), array('service_id' => $serviceID), 1);

		if ( $retval )
		{
			if ( $service['default'] )
			{
				$this->db->update('core_config', array('val' => json_encode($settings)), array('plugin' => 'system', 'keyword' => 'default_storage_settings'), 1);
			}

			// Action hook
			hook::action('system/storage/settings/update', $serviceID, $settings, $service);

			$this->cache->cleanup();
		}

		return $retval;
	}

	public function install($keyword)
	{
		// Get service
		$manifest = $this->getManifest($keyword);

		// Create data array
		$data = array(
			'name' => $manifest['name'],
			'keyword' => $manifest['keyword'],
			'settings' => array(),
		);

		// Parse settings
		foreach ( $manifest['settings'] as $setting )
		{
			if ( isset($setting['value']) )
			{
				$data['settings'][$setting['keyword']] = $setting['value'];
			}
		}

		// Encode settings
		$data['settings'] = json_encode($data['settings']);

		// Insert service
		$serviceID = $this->db->insert('storage_services', $data);

		if ( $serviceID )
		{
			// Action hook
			hook::action('system/storage/install', $serviceID, $data);

			$this->cache->cleanup();
		}

		return $serviceID;
	}

	public function uninstall($serviceID, $service)
	{
		// Delete service
		$retval = $this->db->delete('storage_services', array('service_id' => $serviceID), 1);

		if ( $retval )
		{
			// Action hook
			hook::action('system/storage/uninstall', $serviceID, $service);

			$this->cache->cleanup();
		}

		return $retval;
	}

	public function isInUse($serviceID)
	{
		$file = $this->db->query("SELECT `file_id` FROM `:prefix:storage_files` WHERE `service_id`=? LIMIT 1", array($serviceID))->row();

		return $file ? true : false;
	}

	public function copy($resource, $userID, $source, $thumbs = array())
	{
		// Get resource ID
		if ( !( $resourceID = config::item('resources', 'core', $resource, 'resource_id') ) )
		{
			return false;
		}

		// Get default storage
		if ( !( $service = config::item('default_storage', 'system') ) )
		{
			return false;
		}

		// Get service ID and settings
		if ( !( $serviceID = config::item('storages', 'core', $service) ) )
		{
			return false;
		}
		$settings = config::item('storages', 'core', 'settings', $serviceID);

		// Load storage library
		loader::library('storages/' . $service, $settings, 'storage_' . $service);

		// Copy file
		if ( !( $file = $this->{'storage_' . $service}->copy($source) ) )
		{
			$this->setError($this->{'storage_' . $service}->getError());
			return false;
		}

		// File array
		$file = array(
			'path' => $file['path_suffix'],
			'name' => $file['name_raw'],
			'extension' => $file['extension'],
			'suffix' => '',
			'size' => $file['size'],
			'width' => $file['width'],
			'height' => $file['height'],
		);

		// Save file in the database
		$fileID = $this->saveFile(0, 0, $serviceID, $resourceID, $userID, $file);

		if ( $fileID )
		{
			// Action hook
			hook::action('system/storage/files/copy', $fileID, $serviceID, $resourceID, $userID, $source, $file);
		}

		// Do we need to resize uploaded file?
		if ( $thumbs )
		{
			foreach ( $thumbs as $thumb )
			{
				$dimensions = isset($thumb['dimensions']) ? $thumb['dimensions'] : '';
				$method = isset($thumb['method']) ? $thumb['method'] : 'preserve';
				$suffix = isset($thumb['suffix']) ? $thumb['suffix'] : '';

				$file['file_id'] = $suffix == '' ? 0 : $fileID;
				$file['parent_id'] = 0;
				$file['service_id'] = $serviceID;
				$file['resource_id'] = $resourceID;
				$file['user_id'] = $userID;
				$file['suffix'] = '';

				if ( !( $thumbID = $this->resize($file, $dimensions, $suffix, $method, $suffix == '' ? $fileID : 0) ) )
				{
					$this->deleteFiles($fileID, 2);
					$this->setError($this->{'storage_' . $service}->getError());
					return false;
				}

			}
		}

		return $fileID;
	}

	public function download($resource, $userID, $url, $maxsize, $thumbs = array())
	{
		// Get resource ID
		if ( !( $resourceID = config::item('resources', 'core', $resource, 'resource_id') ) )
		{
			return false;
		}

		// Get default storage
		if ( !( $service = config::item('default_storage', 'system') ) )
		{
			return false;
		}

		// Get service ID and settings
		if ( !( $serviceID = config::item('storages', 'core', $service) ) )
		{
			return false;
		}
		$settings = config::item('storages', 'core', 'settings', $serviceID);

		// Load storage library
		loader::library('storages/' . $service, $settings, 'storage_' . $service);

		// Download file
		if ( !( $file = $this->{'storage_' . $service}->download($url, $maxsize) ) )
		{
			$this->setError($this->{'storage_' . $service}->getError());
			return false;
		}

		// File array
		$file = array(
			'path' => $file['path_suffix'],
			'name' => $file['name_raw'],
			'extension' => $file['extension'],
			'suffix' => '',
			'size' => $file['size'],
			'width' => $file['width'],
			'height' => $file['height'],
		);

		// Save file in the database
		$fileID = $this->saveFile(0, 0, $serviceID, $resourceID, $userID, $file);

		if ( $fileID )
		{
			// Action hook
			hook::action('system/storage/files/download', $fileID, $serviceID, $resourceID, $userID, $url, $file);
		}

		// Do we need to resize uploaded file?
		if ( $thumbs )
		{
			foreach ( $thumbs as $thumb )
			{
				$dimensions = isset($thumb['dimensions']) ? $thumb['dimensions'] : '';
				$method = isset($thumb['method']) ? $thumb['method'] : 'preserve';
				$suffix = isset($thumb['suffix']) ? $thumb['suffix'] : '';

				$file['file_id'] = $suffix == '' ? 0 : $fileID;
				$file['parent_id'] = 0;
				$file['service_id'] = $serviceID;
				$file['resource_id'] = $resourceID;
				$file['user_id'] = $userID;
				$file['suffix'] = '';

				if ( !( $thumbID = $this->resize($file, $dimensions, $suffix, $method, $suffix == '' ? $fileID : 0) ) )
				{
					$this->deleteFiles($fileID, 2);
					$this->setError($this->{'storage_' . $service}->getError());
					return false;
				}

			}
		}

		return $fileID;
	}

	public function upload($resource, $userID, $field, $extensions, $maxsize, $maxdimensions = '', $thumbs = array())
	{
		// Get resource ID
		if ( !( $resourceID = config::item('resources', 'core', $resource, 'resource_id') ) )
		{
			return false;
		}

		// Get default storage
		if ( !( $service = config::item('default_storage', 'system') ) )
		{
			return false;
		}

		// Get service ID and settings
		if ( !( $serviceID = config::item('storages', 'core', $service) ) )
		{
			return false;
		}
		$config = config::item('storages', 'core', 'settings', $serviceID);

		// Load storage library
		loader::library('storages/' . $service, $config, 'storage_' . $service);

		// Upload file
		if ( !( $file = $this->{'storage_' . $service}->upload($field, $extensions, $maxsize, $maxdimensions) ) )
		{
			$this->setError($this->{'storage_' . $service}->getError());
			return false;
		}

		// File array
		$file = array(
			'path' => $file['path_suffix'],
			'name' => $file['name_raw'],
			'suffix' => '',
			'extension' => $file['extension'],
			'size' => $file['size'],
			'width' => $file['width'],
			'height' => $file['height'],
		);

		// Save file in the database
		$fileID = $this->saveFile(0, 0, $serviceID, $resourceID, $userID, $file);

		if ( $fileID )
		{
			// Action hook
			hook::action('system/storage/files/upload', $fileID, $serviceID, $resourceID, $userID, $file);
		}

		// Do we need to create thumbnails?
		if ( $thumbs )
		{
			foreach ( $thumbs as $thumb )
			{
				$dimensions = isset($thumb['dimensions']) ? $thumb['dimensions'] : '';
				$method = isset($thumb['method']) ? $thumb['method'] : 'preserve';
				$suffix = isset($thumb['suffix']) ? $thumb['suffix'] : '';

				$file['file_id'] = $suffix == '' ? 0 : $fileID;
				$file['parent_id'] = 0;
				$file['service_id'] = $serviceID;
				$file['resource_id'] = $resourceID;
				$file['user_id'] = $userID;

				if ( !( $thumbID = $this->resize($file, $dimensions, $suffix, $method, $suffix == '' ? $fileID : 0) ) )
				{
					$this->deleteFiles($fileID, 5);
					$this->setError($this->{'storage_' . $service}->getError());
					return false;
				}

			}
		}

		return $fileID;
	}

	public function saveFile($fileID, $parentID, $serviceID, $resourceID, $userID, $data)
	{
		// Is this a new file
		if ( !$fileID )
		{
			$data['parent_id'] = $parentID;
			$data['resource_id'] = $resourceID;
			$data['service_id'] = $serviceID;
			$data['user_id'] = $userID;
			$data['post_date'] = date_helper::now();

			// Save file
			$fileID = $this->db->insert('storage_files', $data);
		}
		else
		{
			$data['modify_date'] = date_helper::now();

			// Update file
			$this->db->update('storage_files', $data, array('file_id' => $fileID), 1);

			if ( $parentID )
			{
				$this->db->update('storage_files', array('modify_date' => date_helper::now()), array('file_id' => $parentID), 1);
			}
		}

		return $fileID;
	}

	public function resize($source, $dimensions, $suffix = '', $method = 'preserve', $updateID = 0)
	{
		// Get service ID and settings
		if ( !( $service = config::item('storages', 'core', $source['service_id']) ) )
		{
			return false;
		}
		$config = config::item('storages', 'core', 'settings', $source['service_id']);

		// Load storage library
		loader::library('storages/' . $service, $config, 'storage_' . $service);

		// Resize file
		if ( !( $thumb = $this->{'storage_' . $service}->resize($source, $dimensions, $suffix, $method) ) )
		{
			$this->setError($this->{'storage_' . $service}->getError());
			return false;
		}

		$thumb = array(
			'path' => $source['path'],
			'name' => $source['name'],
			'extension' => $source['extension'],
			'suffix' => $suffix,
			'size' => $thumb['size'],
			'width' => $thumb['width'],
			'height' => $thumb['height'],
		);

		// Save file in the database
		$fileID = $this->saveFile($updateID, $source['file_id'], $source['service_id'], $source['resource_id'], $source['user_id'], $thumb);

		if ( $fileID )
		{
			// Action hook
			hook::action('system/storage/files/resize', $fileID, $source[$updateID ? 'parent_id' : 'file_id'], $source['service_id'], $source['resource_id'], $source['user_id'], $thumb);
		}

		return true;
	}

	public function thumbnail($source, $x1, $y1, $x2, $y2, $dimensions, $suffix = '', $updateID = false)
	{
		// Get service ID and settings
		if ( !( $service = config::item('storages', 'core', $source['service_id']) ) )
		{
			return false;
		}
		$config = config::item('storages', 'core', 'settings', $source['service_id']);

		// Load storage library
		loader::library('storages/' . $service, $config, 'storage_' . $service);

		// Create thumbnail
		if ( !( $thumb = $this->{'storage_' . $service}->thumbnail($source, $x1, $y1, $x2, $y2, $dimensions, $suffix) ) )
		{
			$this->setError($this->{'storage_' . $service}->getError());
			return false;
		}

		$thumb = array(
			'path' => $source['path'],
			'name' => $source['name'],
			'extension' => $source['extension'],
			'suffix' => $suffix,
			'size' => $thumb['size'],
			'width' => $thumb['width'],
			'height' => $thumb['height'],
		);

		// Save file in the database
		$fileID = $this->saveFile($updateID, $source['file_id'], $source['service_id'], $source['resource_id'], $source['user_id'], $thumb);

		return true;
	}

	public function rotate($source, $angle = 90)
	{
		// Get service ID and settings
		if ( !( $service = config::item('storages', 'core', $source['service_id']) ) )
		{
			return false;
		}
		$config = config::item('storages', 'core', 'settings', $source['service_id']);

		// Load storage library
		loader::library('storages/' . $service, $config, 'storage_' . $service);

		// Rotate image
		if ( !( $thumb = $this->{'storage_' . $service}->rotate($source, $angle) ) )
		{
			$this->setError($this->{'storage_' . $service}->getError());
			return false;
		}

		$thumb = array(
			'size' => $thumb['size'],
			'width' => $thumb['width'],
			'height' => $thumb['height'],
		);

		// Save file in the database
		$fileID = $this->saveFile($source['file_id'], $source['parent_id'], $source['service_id'], $source['resource_id'], $source['user_id'], $thumb);

		return $fileID;
	}

	public function getFile($fileID, $suffix = '')
	{
		if ( $suffix )
		{
			$file = $this->db->query("SELECT * FROM `:prefix:storage_files` WHERE (`file_id`=? OR `parent_id`=?) AND `suffix`=? LIMIT 1", array($fileID, $fileID, $suffix))->row();
		}
		else
		{
			$file = $this->db->query("SELECT * FROM `:prefix:storage_files` WHERE `file_id`=? LIMIT 1", array($fileID))->row();
		}

		return $file;
	}

	public function getFiles($fileID, $limit = 1, $suffixes = array())
	{
		if ( is_array($fileID) )
		{
			$files = $this->db->query("SELECT *
				FROM `:prefix:storage_files`
				WHERE (`file_id` IN (?) AND `parent_id`=0 OR `parent_id` IN (?)) " . ( $suffixes ? "AND `suffix` IN ('" . implode("','", $suffixes) . "')" : "") . "
				LIMIT ?", array($fileID, $fileID, ( count($fileID) * $limit )))->result();
		}
		else
		{
			$files = $this->db->query("SELECT *
				FROM `:prefix:storage_files`
				WHERE (`file_id`=? AND `parent_id`=0 OR `parent_id`=?) " . ( $suffixes ? "AND `suffix` IN ('" . implode("','", $suffixes) . "')" : "") . "
				LIMIT ?", array($fileID, $fileID, $limit))->result();
		}

		if ( $suffixes && $files )
		{
			foreach ( $files as $index => $file )
			{
				$files[$file['suffix']] = $file;
				unset($files[$index]);
			}
		}

		return $files;
	}

	public function deleteFiles($fileID, $limit = 1)
	{
		$files = $this->getFiles($fileID, $limit);

		if ( !$files )
		{
			return true;
		}

		$services = array();

		foreach ( $files as $file )
		{
			// Did we already load storage library?
			if ( !isset($services[$file['service_id']]) )
			{
				// Get storage service and settings
				$service = config::item('storages', 'core', $file['service_id']);
				$settings = config::item('storages', 'core', 'settings', $file['service_id']);

				// Load library
				loader::library('storages/' . $service, $settings, 'storage_' . $service);

				$services[$file['service_id']] = true;
			}

			$this->{'storage_' . $service}->delete($file['path'], $file['name'], $file['extension'], $file['suffix']);
		}

		if ( is_array($fileID) )
		{
			$retval = $this->db->query("DELETE FROM `:prefix:storage_files` WHERE `file_id` IN (?) OR `parent_id` IN (?) LIMIT ?", array($fileID, $fileID, count($files)));
		}
		else
		{
			$retval = $this->db->query("DELETE FROM `:prefix:storage_files` WHERE `file_id`=? OR `parent_id`=? LIMIT ?", array($fileID, $fileID, count($files)));
		}

		if ( $retval )
		{
			// Action hook
			hook::action('system/storage/files/delete', $fileID, $files);
		}

		return $retval;
	}

	/**
	* Set error message.
	*
	* @param  string  error message
	*/
	protected function setError($error)
	{
		$this->errorMessage = $error;
	}

	/**
	* Get error message.
	*
	* @return  string
	*/
	public function getError()
	{
		if ( $this->errorMessage == '' )
		{
			return '';
		}

		return $this->errorMessage;
	}

	public function includeExternals()
	{
	}

	public function updateUserID($fileID, $userID, $limit = 1)
	{
		$retval = $this->db->query("UPDATE `:prefix:storage_files` SET `user_id`=? WHERE `file_id`=? OR `parent_id`=? LIMIT ?", array($userID, $fileID, $fileID, $limit));

		return $retval;
	}

	/*
	public function uploadFlash($resource, $userID, $field, $config = array(), $stream = false)
	{
		// Load local storage library
		loader::library('storages/local', array(), 'storage_local');

		// Was file uploaded?
		if ( !($file = $this->storage_local->upload($field, $config, $stream)) )
		{
			if ( $stream )
			{
				view::printAjaxError(15, $this->storage_local->getError());
			}

			// Set field error
			validate::setFieldError($field, $this->storage_local->getError());
			return false;
		}

		// Save file in the database
		$fileID = $this->saveFilez($resource, 0, $userID, $file, isset($config['resize']) ? $config['resize'] : array());

		return $fileID;
	}


	public function complete()
	{
		view::printAjaxData(array('jsonrpc' => 'jsonrpc', 'result' => null, 'id' => 'id'));
	}

	public function includeExternals()
	{
		view::includeJavascript('externals/plupload/browserplus.js');
		view::includeJavascript('externals/plupload/plupload.browserplus.js');
		view::includeJavascript('externals/plupload/plupload.js');
		view::includeJavascript('externals/plupload/plupload.gears.js');
		view::includeJavascript('externals/plupload/plupload.silverlight.js');
		view::includeJavascript('externals/plupload/plupload.flash.js');
		view::includeJavascript('externals/plupload/plupload.browserplus.js');
		view::includeJavascript('externals/plupload/plupload.html4.js');
		view::includeJavascript('externals/plupload/plupload.html5.js');
	}
	*/
}
