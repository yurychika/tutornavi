<?php

class System_Templates_Model extends Model
{
	public function getTemplates()
	{
		$templates = array();

		foreach ( $this->db->query("SELECT * FROM `:prefix:core_templates` ORDER BY `name` ASC")->result() as $template )
		{
			$templates[$template['template_id']] = $template;
		}

		return $templates;
	}

	public function getTemplate($keyword)
	{
		if ( $template = $this->db->query("SELECT * FROM `:prefix:core_templates` WHERE `keyword`=? LIMIT 1", array($keyword))->row() )
		{
			if ( !($template['settings'] = @json_decode($template['settings'], true)) )
			{
				$template['settings'] = array();
			}
		}

		return $template;
	}

	public function scanTemplates($merge = true)
	{
		// Load file helper and read templates directory
		loader::helper('file');
		$dirs = file_helper::scanDirectoryNames(BASEPATH . 'templates');

		$templates = array();

		// Loop through found directories
		foreach ( $dirs as $template )
		{
			if ( $manifest = $this->getManifest($template) )
			{
				$templates[$template] = $manifest;
				$templates[$template]['default'] = 0;
			}
		}

		// Do we need to merge results with installed templates?
		if ( $merge )
		{
			// Loop through installed templates
			foreach ( $this->getTemplates() as $template )
			{
				if ( isset($templates[$template['keyword']]) )
				{
					$templates[$template['keyword']]['template_id'] = $template['template_id'];
					$templates[$template['keyword']]['default'] = $template['default'];
				}
			}
		}

		// Order templates
		ksort($templates);

		return $templates;
	}

	public function getManifest($keyword)
	{
		$manifest = array();

		// Verify manifest file
		if ( @is_file(BASEPATH . 'templates/' . $keyword . '/manifest.php') )
		{
			// Include manifest file
			if ( @include(BASEPATH . 'templates/' . $keyword . '/manifest.php') )
			{
				// Does params variable exist?
				if ( isset($params) && isset($params['name']) )
				{
					$manifest = array(
						'keyword' => $keyword,
						'name' => $params['name'],
						'settings' => isset($params['settings']) && is_array($params['settings']) ? $params['settings'] : array(),
						'values' => array(),
					);
				}
			}
		}

		return $manifest;
	}

	public function saveSettings($templateID, $settings, $template)
	{
		$retval = $this->db->update('core_templates', array('settings' => json_encode($settings)), array('template_id' => $templateID), 1);

		if ( $retval )
		{
			// Action hook
			hook::action('system/templates/settings/update', $templateID, $settings, $template);

			$this->cache->cleanup();
		}

		return $retval;
	}

	public function setDefault($templateID, $template)
	{
		// Reset current default template
		$this->db->update('core_templates', array('default' => 0), array('default' => 1), 1);

		// Set new default template
		$retval = $this->db->update('core_templates', array('default' => 1), array('template_id' => $templateID), 1);

		if ( $retval )
		{
			// Update default system template ID
			$this->db->update('core_config', array('val' => $templateID), array('plugin' => 'system', 'keyword' => 'template_id'), 1);

			if ( $templateID == session::item('template_id') )
			{
				session::delete('', 'config');
			}

			// Action hook
			hook::action('system/templates/default', $templateID, $template);

			$this->cache->cleanup();
		}

		return $retval;
	}

	public function install($keyword)
	{
		// Get template
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

		// Insert template
		$templateID = $this->db->insert('core_templates', $data);

		if ( $templateID )
		{
			// Action hook
			hook::action('system/templates/install', $templateID, $data['settings']);

			$this->cache->cleanup();
		}

		return $templateID;
	}

	public function uninstall($templateID, $template)
	{
		// Delete template
		$retval = $this->db->delete('core_templates', array('template_id' => $templateID), 1);

		if ( $retval )
		{
			// Update users with the new system template ID
			$this->db->update('users', array('template_id' => config::item('template_id', 'system')), array('template_id' => $templateID));

			if ( $templateID == session::item('template_id') )
			{
				session::delete('', 'config');
			}

			// Action hook
			hook::action('system/templates/uninstall', $templateID, $template);

			$this->cache->cleanup();
		}

		return $retval;
	}
}
