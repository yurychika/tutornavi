<?php defined('SYSPATH') || die('No direct script access allowed.');

class Storage_Helper
{
	static public function getFileURL($serviceID, $path, $name, $ext, $suffix = '', $stamp = '')
	{
		// Get storage service and settings
		$service = config::item('storages', 'core', $serviceID);
		$settings = config::item('storages', 'core', 'settings', $serviceID);

		if ( !$service )
		{
			return '';
		}

		// Load library
		loader::library('storages/' . $service, $settings, 'storage_' . $service);

		$str = codebreeder::instance()->{'storage_' . $service}->getFileURL($path, $name, $ext, $suffix, ( $stamp && $stamp > date_helper::now()-60*60*24*3 ? $stamp : '' ));

		return $str;
	}

	static public function getFileHost($serviceID)
	{
		// Get storage service and settings
		$service = config::item('storages', 'core', $serviceID);
		$settings = config::item('storages', 'core', 'settings', $serviceID);

		if ( !$service )
		{
			return '';
		}

		// Load library
		loader::library('storages/' . $service, $settings, 'storage_' . $service);

		$str = codebreeder::instance()->{'storage_' . $service}->getFileHost();

		return $str;
	}

	static public function getFilePath($serviceID, $path, $name, $ext, $suffix = '')
	{
		// Get storage service and settings
		$service = config::item('storages', 'core', $serviceID);
		$settings = config::item('storages', 'core', 'settings', $serviceID);

		if ( !$service )
		{
			return '';
		}

		// Load library
		loader::library('storages/' . $service, $settings, 'storage_' . $service);

		$str = codebreeder::instance()->{'storage_' . $service}->getFilePath($path, $name, $ext, $suffix);

		return $str;
	}

}