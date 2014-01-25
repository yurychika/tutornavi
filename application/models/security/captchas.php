<?php

class Security_Captchas_Model extends Model
{
	public function getCaptchas()
	{
		$captchas = array();

		foreach ( $this->db->query("SELECT `captcha_id`, `name`, `keyword`, `default` FROM `:prefix:security_captcha` ORDER BY `name` ASC")->result() as $captcha )
		{
			$captchas[$captcha['captcha_id']] = $captcha;
		}

		return $captchas;
	}

	public function getCaptcha($keyword)
	{
		if ( $captcha = $this->db->query("SELECT `captcha_id`, `name`, `keyword`, `settings`, `default`
			FROM `:prefix:security_captcha`
			WHERE `" . ( $keyword === 1 ? "default" : "keyword" ) . "`=?
			LIMIT 1", array($keyword))->row() )
		{
			if ( !($captcha['settings'] = @json_decode($captcha['settings'], true)) )
			{
				$captcha['settings'] = array();
			}
		}

		return $captcha;
	}

	public function scanCaptchas($merge = true)
	{
		// Load file helper and read captcha directory
		loader::helper('file');
		$dirs = file_helper::scanFileNames(DOCPATH . 'libraries/captchas');

		$captchas = array();

		// Loop through found directories
		foreach ( $dirs as $captcha )
		{
			// Remove file extension
			$captcha = substr($captcha, 0, -4);

			if ( $manifest = $this->getManifest($captcha) )
			{
				$captchas[$captcha] = $manifest;
				$captchas[$captcha]['default'] = 0;
			}
		}

		// Do we need to merge results with installed captchas?
		if ( $merge )
		{
			// Loop through installed captchas
			foreach ( $this->getCaptchas() as $captcha )
			{
				if ( isset($captchas[$captcha['keyword']]) )
				{
					$captchas[$captcha['keyword']]['captcha_id'] = $captcha['captcha_id'];
					$captchas[$captcha['keyword']]['default'] = $captcha['default'];
				}
			}
		}

		// Order captchas
		ksort($captchas);

		return $captchas;
	}

	public function getManifest($keyword)
	{
		$manifest = array();

		// Load captcha
		$captcha = loader::library('captchas/' . $keyword, array(), null);

		// Does captcha exist?
		if ( $captcha )
		{
			$params = $captcha->getManifest();

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

	public function setDefault($captchaID, $captcha)
	{
		// Reset current default captcha
		$this->db->update('security_captcha', array('default' => 0), array('default' => 1), 1);

		// Set new default captcha
		$retval = $this->db->update('security_captcha', array('default' => 1), array('captcha_id' => $captchaID), 1);

		if ( $retval )
		{
			// Update default captcha ID and settings
			$this->db->update('core_config', array('val' => $captcha['keyword']), array('plugin' => 'security', 'keyword' => 'default_captcha'), 1);
			$this->db->update('core_config', array('val' => json_encode($captcha['settings'])), array('plugin' => 'security', 'keyword' => 'default_captcha_settings'), 1);

			// Action hook
			hook::action('security/forms/default', $captchaID, $captcha);

			$this->cache->cleanup();
		}

		return $retval;
	}

	public function saveSettings($captchaID, $settings, $captcha)
	{
		$retval = $this->db->update('security_captcha', array('settings' => json_encode($settings)), array('captcha_id' => $captchaID), 1);

		if ( $retval )
		{
			if ( $captcha['default'] )
			{
				$this->db->update('core_config', array('val' => json_encode($settings)), array('plugin' => 'security', 'keyword' => 'default_captcha_settings'), 1);
			}

			// Action hook
			hook::action('security/forms/settings/update', $captchaID, $settings);

			$this->cache->cleanup();
		}

		return $retval;
	}

	public function install($keyword)
	{
		// Get captcha
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

		// Insert captcha
		$captchaID = $this->db->insert('security_captcha', $data);

		if ( $captchaID )
		{
			// Action hook
			hook::action('security/forms/install', $captchaID, $data);
		}

		return $captchaID;
	}

	public function uninstall($captchaID, $captcha)
	{
		// Delete captcha
		$retval = $this->db->delete('security_captcha', array('captcha_id' => $captchaID), 1);

		if ( $retval )
		{
			// Action hook
			hook::action('security/forms/uninstall', $captchaID, $captcha);
		}

		return $retval;
	}
}
