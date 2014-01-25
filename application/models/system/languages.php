<?php

class System_Languages_Model extends Model
{
	public function saveLanguage($languageID, $name, $keyword)
	{
		$path = DOCPATH . 'languages/' . $keyword;
		$filename = 'manifest' . EXT;

		// Does language path exist?
		if ( !@is_dir($path) )
		{
			if ( !@mkdir($path, octdec(config::item('folder_chmod')), true) )
			{
				$this->setError(__('path_not_created', 'uploader'));
				return false;
			}
		}

		$content = "<?php\n" .
			"\$params = array(\n" .
			"\t'name' => '" . utf8::str_replace("'", "\'", $name) . "'\n" .
			");";

		if ( !@file_put_contents($path . '/' . $filename, $content) )
		{
			error::show('Could not save the file: ' . $filename . ' Make sure the "' . $path . '" folder exists and is writable.');
		}

		@chmod($path . '/' . $filename, octdec(config::item('file_chmod')));

		// Is this language pack installed?
		if ( $languageID )
		{
			$this->db->update('core_languages', array('name' => $name, 'keyword' => $keyword), array('keyword' => $languageID), 1);
		}

		$this->cache->cleanup();

		return true;
	}

	public function getLanguages()
	{
		$languages = array();

		foreach ( $this->db->query("SELECT * FROM `:prefix:core_languages` ORDER BY `name` ASC")->result() as $language )
		{
			$languages[$language['language_id']] = $language;
		}

		return $languages;
	}

	public function getLanguage($keyword)
	{
		$language = $this->db->query("SELECT * FROM `:prefix:core_languages` WHERE `keyword`=? LIMIT 1", array($keyword))->row();

		return $language;
	}

	public function scanLanguages($merge = true)
	{
		// Load file helper and read languages directory
		loader::helper('file');
		$dirs = file_helper::scanDirectoryNames(DOCPATH . 'languages');

		$languages = array();

		// Loop through found directories
		foreach ( $dirs as $language )
		{
			if ( $manifest = $this->getManifest($language) )
			{
				$languages[$language] = $manifest;
				$languages[$language]['default'] = 0;
			}
		}

		// Do we need to merge results with installed languages?
		if ( $merge )
		{
			// Loop through installed languages
			foreach ( $this->getlanguages() as $language )
			{
				if ( isset($languages[$language['keyword']]) )
				{
					$languages[$language['keyword']]['language_id'] = $language['language_id'];
					$languages[$language['keyword']]['default'] = $language['default'];
				}
				elseif ( !isset($languages[$language['keyword']]) )
				{
					$languages[$language['keyword']] = $language;
				}
			}
		}

		// Order languages
		ksort($languages);

		return $languages;
	}

	public function getManifest($keyword)
	{
		$manifest = array();

		// Verify manifest file
		if ( @is_file(DOCPATH . 'languages/' . $keyword . '/manifest.php') )
		{
			// Include manifest file
			if ( @include(DOCPATH . 'languages/' . $keyword . '/manifest.php') )
			{
				// Does params variable exist?
				if ( isset($params) && isset($params['name']) )
				{
					$manifest = array(
						'keyword' => $keyword,
						'name' => $params['name'],
					);
				}
			}
		}

		return $manifest;
	}

	public function getLanguageData($language, $plugin = '', $sort = false)
	{
		$data = array();

		$languages = $this->db->query("SELECT `plugin`, `section`, `keyword`, `group`, `cp`, `value_" . $language . "` AS `lang_value`
			FROM `:prefix:core_languages_data` WHERE 1=1 " . ( $plugin ? " AND `plugin`=? " : "" ) . "
			ORDER BY `cp` DESC, `keyword` ASC", array($plugin))->result();

		foreach ( $languages as $row )
		{
			if ( !isset($data[$row['plugin']][$row['section']][$row['group']]) )
			{
				$data[$row['plugin']][$row['section']][$row['group']] = array(
					'cp' => array(),
					'ca' => array(),
				);
			}
			$data[$row['plugin']][$row['section']][$row['group']][$row['cp'] ? 'cp' : 'ca'][$row['keyword']] = $row['lang_value'];
		}

		if ( $sort )
		{
			foreach ( $data as $plug => $sections )
			{
				ksort($data[$plug]);

				foreach ( $sections as $section => $groups )
				{
					ksort($data[$plug][$section]);

					foreach ( $groups as $group => $types )
					{
						foreach ( $types as $type => $values )
						{
							ksort($data[$plug][$section][$group][$type]);
						}
					}
				}
			}
		}

		if ( $plugin != '' && isset($data[$plugin]) )
		{
			$data = $data[$plugin];
		}

		return $data;
	}

	public function saveLanguageData($plugin, $section, $group, $keyword, $data)
	{
		$retval = $this->db->update('core_languages_data', $data, array('plugin' => $plugin, 'group' => $group, 'keyword' => $keyword), 1);

		return $retval;
	}

	public function deleteLanguageString($plugin, $group, $keyword)
	{
		$retval = $this->db->delete('core_languages_data', array('plugin' => $plugin, 'group' => $group, 'keyword' => $keyword));

		return $retval;
	}

	public function deleteLanguageGroup($plugin, $group)
	{
		$retval = $this->db->delete('core_languages_data', array('plugin' => $plugin, 'group' => $group));

		return $retval;
	}

	public function setDefault($languageID, $language)
	{
		// Reset current default language
		$this->db->update('core_languages', array('default' => 0), array('default' => 1), 1);

		// Set new default language
		$retval = $this->db->update('core_languages', array('default' => 1), array('language_id' => $languageID), 1);

		if ( $retval )
		{
			// Update default system language ID
			$this->db->update('core_config', array('val' => $languageID), array('plugin' => 'system', 'keyword' => 'language_id'), 1);

			if ( $languageID == session::item('language_id') )
			{
				session::delete('', 'config');
			}

			// Action hook
			hook::action('system/languages/default', $languageID, $language);

			$this->cache->cleanup();
		}

		return $retval;
	}

	public function install($keyword)
	{
		// Get language
		$manifest = $this->getManifest($keyword);

		// Create data array
		$data = array(
			'name' => $manifest['name'],
			'keyword' => $manifest['keyword'],
		);

		// Insert language
		$languageID = $this->db->insert('core_languages', $data);

		if ( $languageID )
		{
			// Load dbforge library
			loader::library('dbforge');

			$default = config::item('languages', 'core', 'keywords', config::item('language_id', 'system'));

			// Languages
			$this->dbforge->addColumn(':prefix:core_languages_data', array(
				'name' => 'value_' . $keyword,
				'type' => 'text',
			));

			$this->db->query("UPDATE `:prefix:core_languages_data` SET `value_" . $keyword . "`=`value_system`");

			// Email templates
			$this->dbforge->addColumn(':prefix:core_email_templates', array(
				'name' => 'subject_' . $keyword,
				'constraint' => 255,
				'type' => 'varchar',
			));

			$this->dbforge->addColumn(':prefix:core_email_templates', array(
				'name' => 'message_html_' . $keyword,
				'type' => 'text',
			));

			$this->dbforge->addColumn(':prefix:core_email_templates', array(
				'name' => 'message_text_' . $keyword,
				'type' => 'text',
			));

			$this->db->query("UPDATE `:prefix:core_email_templates` SET `subject_" . $keyword . "`=`subject_" . $default . "`");
			$this->db->query("UPDATE `:prefix:core_email_templates` SET `message_html_" . $keyword . "`=`message_html_" . $default . "`");
			$this->db->query("UPDATE `:prefix:core_email_templates` SET `message_text_" . $keyword . "`=`message_text_" . $default . "`");

			// Meta tags
			$this->dbforge->addColumn(':prefix:core_meta_tags', array(
				'name' => 'meta_title_' . $keyword,
				'constraint' => 255,
				'type' => 'varchar',
			));

			$this->dbforge->addColumn(':prefix:core_meta_tags', array(
				'name' => 'meta_description_' . $keyword,
				'constraint' => 255,
				'type' => 'varchar',
			));

			$this->dbforge->addColumn(':prefix:core_meta_tags', array(
				'name' => 'meta_keywords_' . $keyword,
				'constraint' => 255,
				'type' => 'varchar',
			));

			$this->db->query("UPDATE `:prefix:core_meta_tags` SET `meta_title_" . $keyword . "`=`meta_title_" . $default . "`");
			$this->db->query("UPDATE `:prefix:core_meta_tags` SET `meta_description_" . $keyword . "`=`meta_description_" . $default . "`");
			$this->db->query("UPDATE `:prefix:core_meta_tags` SET `meta_keywords_" . $keyword . "`=`meta_keywords_" . $default . "`");

			// Custom fields
			$this->dbforge->addColumn(':prefix:core_fields', array(
				'name' => 'name_' . $keyword,
				'constraint' => 255,
				'type' => 'varchar',
				'null' => true,
			));

			$this->dbforge->addColumn(':prefix:core_fields', array(
				'name' => 'sname_' . $keyword,
				'constraint' => 255,
				'type' => 'varchar',
				'null' => true,
			));

			$this->dbforge->addColumn(':prefix:core_fields', array(
				'name' => 'vname_' . $keyword,
				'constraint' => 255,
				'type' => 'varchar',
				'null' => true,
			));

			$this->dbforge->addColumn(':prefix:core_fields', array(
				'name' => 'validate_error_' . $keyword,
				'constraint' => 255,
				'type' => 'varchar',
				'null' => true,
			));

			$this->dbforge->addColumn(':prefix:core_fields_items', array(
				'name' => 'name_' . $keyword,
				'constraint' => 255,
				'type' => 'varchar',
				'null' => true,
			));

			$this->dbforge->addColumn(':prefix:core_fields_items', array(
				'name' => 'sname_' . $keyword,
				'constraint' => 255,
				'type' => 'varchar',
				'null' => true,
			));

			$this->db->query("UPDATE `:prefix:core_fields` SET `name_" . $keyword . "`=`name_" . $default . "`");
			$this->db->query("UPDATE `:prefix:core_fields` SET `sname_" . $keyword . "`=`sname_" . $default . "`");
			$this->db->query("UPDATE `:prefix:core_fields` SET `vname_" . $keyword . "`=`vname_" . $default . "`");
			$this->db->query("UPDATE `:prefix:core_fields` SET `validate_error_" . $keyword . "`=`validate_error_" . $default . "`");

			$this->db->query("UPDATE `:prefix:core_fields_items` SET `name_" . $keyword . "`=`name_" . $default . "`");
			$this->db->query("UPDATE `:prefix:core_fields_items` SET `sname_" . $keyword . "`=`sname_" . $default . "`");

			// Geo data
			foreach ( array('countries', 'states', 'cities') as $table )
			{
				$this->dbforge->addColumn(':prefix:geo_' . $table, array(
					'name' => 'name_' . $keyword,
					'constraint' => 255,
					'type' => 'varchar',
					'null' => true,
				));
			}

			// Report subjects
			$this->dbforge->addColumn(':prefix:reports_subjects', array(
				'name' => 'name_' . $keyword,
				'constraint' => 255,
				'type' => 'varchar',
				'null' => true,
			));

			$this->db->query("UPDATE `:prefix:reports_subjects` SET `name_" . $keyword . "`=`name_" . $default . "`");

			// Import language files
			$this->import($keyword);

			// Action hook
			hook::action('system/languages/install', $languageID, $keyword);

			$this->cache->cleanup();
		}

		return $languageID;
	}

	public function uninstall($languageID, $language)
	{
		// Delete language
		$retval = $this->db->delete('core_languages', array('language_id' => $languageID), 1);

		if ( $retval )
		{
			// Update users with the new system language ID
			$this->db->update('users', array('language_id' => config::item('language_id', 'system')), array('language_id' => $languageID));

			// Load dbforge library
			loader::library('dbforge');

			// Languages
			$this->dbforge->dropColumns(':prefix:core_languages_data', array('value_' . $language['keyword']));

			// Email templates
			$this->dbforge->dropColumns(':prefix:core_email_templates', array('subject_' . $language['keyword']));
			$this->dbforge->dropColumns(':prefix:core_email_templates', array('message_html_' . $language['keyword']));
			$this->dbforge->dropColumns(':prefix:core_email_templates', array('message_text_' . $language['keyword']));

			// Meta tags
			$this->dbforge->dropColumns(':prefix:core_meta_tags', array('meta_title_' . $language['keyword']));
			$this->dbforge->dropColumns(':prefix:core_meta_tags', array('meta_description_' . $language['keyword']));
			$this->dbforge->dropColumns(':prefix:core_meta_tags', array('meta_keywords_' . $language['keyword']));

			// Custom fields
			$this->dbforge->dropColumns(':prefix:core_fields', array('name_' . $language['keyword']));
			$this->dbforge->dropColumns(':prefix:core_fields', array('sname_' . $language['keyword']));
			$this->dbforge->dropColumns(':prefix:core_fields', array('vname_' . $language['keyword']));
			$this->dbforge->dropColumns(':prefix:core_fields', array('validate_error_' . $language['keyword']));
			$this->dbforge->dropColumns(':prefix:core_fields_items', array('name_' . $language['keyword']));
			$this->dbforge->dropColumns(':prefix:core_fields_items', array('sname_' . $language['keyword']));

			// Geo data
			foreach ( array('countries', 'states', 'cities') as $table )
			{
				$this->dbforge->dropColumns(':prefix:geo_' . $table, array('name_' . $language['keyword']));
			}

			// Report subjects
			$this->dbforge->dropColumns(':prefix:reports_subjects', array('name_' . $language['keyword']));

			if ( $languageID == session::item('language_id') )
			{
				session::set('language', config::item('language_id', 'system'));
				session::delete('', 'config');
			}

			// Action hook
			hook::action('system/languages/uninstall', $languageID, $language['keyword']);

			$this->cache->cleanup();
		}

		return $retval;
	}

	public function compile($lang)
	{
		$path = DOCPATH . 'cache/language_' . $lang . '_';

		// Prepend this line in generated files
		$prefix = '// Do not edit this file';

		// Get language data
		$system = $this->getLanguageData('system');
		$language = $this->getLanguageData($lang);

		// Parse language array into sections
		$system = $this->combineSections($system);
		$language = $this->combineSections($language);

		// Loop through language sections
		foreach ( $language as $section => $groups )
		{
			// Content array
			$content = array();

			// Loop through plugins content
			foreach ( $groups as $group => $types )
			{
				$content[$group] = array();

				// Loop through control panel and client types
				foreach ( $types as $type => $lang )
				{
					// Does section have any values?
					if ( $lang )
					{
						$content[$group][$type] = array();

						// Loop through language strings
						foreach ( $lang as $keyword => $value )
						{
							if ( $value == '' && isset($system[$section][$group][$keyword]) )
							{
								$value = $system[$section][$group][$keyword];
							}
							$content[$group][$type][] = "'" . $keyword . "' => \"" . str_replace(array("\r", "\n", '"'), array('', '\n', '\"'), $value) . "\"";
						}

						$output = "<?php\n" . $prefix . "\n\$language = array(\n\t" . implode(",\n\t", $content[$group][$type]) . "\n);";

						$filename = $path . $section . ( $section != $group ? '_' . $group : '' ) . ( $type == 'cp' ? '_cp' : '' ) . '.php';

						if ( !@file_put_contents($filename, $output) )
						{
							error::show('Could not save the file: ' . $filename . ' Make sure the "' . DOCPATH . 'cache" folder is writable.');
						}

						@chmod($filename, octdec(config::item('file_chmod')));
					}
					// Create empty cache file
					else
					{
						$output = "<?php\n" . $prefix . "\n\$language = array();";

						$filename = $path . $section . ( $section != $group ? '_' . $group : '' ) . ( $type == 'cp' ? '_cp' : '' ) . '.php';

						if ( !@file_put_contents($filename, $output) )
						{
							error::show('Could not save the file: ' . $filename . ' Make sure the "' . DOCPATH . 'cache" folder is writable.');
						}

						@chmod($filename, octdec(config::item('file_chmod')));
					}
				}
			}
		}

		return true;
	}

	protected function combineSections($current)
	{
		$language = array();

		foreach ( $current as $plugin => $sections )
		{
			foreach ( $sections as $section => $groups )
			{
				if ( !isset($language[$section]) )
				{
					$language[$section] = $groups;
				}
				else
				{
					foreach ( $groups as $group => $types )
					{
						foreach ( $types as $type => $data )
						{
							if ( $data )
							{
								if ( !isset($language[$section][$group][$type]) )
								{
									$language[$section][$group][$type] = array();
								}
								$language[$section][$group][$type] = array_merge($language[$section][$group][$type], $data);
							}
						}
					}
				}
			}
		}

		return $language;
	}

	public function export($lang, $cache = false)
	{
		$path = DOCPATH . 'languages/' . $lang . '/';

		// Prepend this line in generated files
		$prefix = '// Do not edit this file unless you know what you are doing';

		// Get language data
		$language = $this->getLanguageData($lang, '', true);

		// Loop through language plugins
		foreach ( $language as $plugin => $sections )
		{
			// Content array
			$content = array();

			// Loop through language sections
			foreach ( $sections as $section => $groups )
			{
				$content[$section] = array();

				// Loop through plugins content
				foreach ( $groups as $group => $types )
				{
					$content[$section][$group] = array();

					// Loop through control panel and client types
					foreach ( $types as $type => $lang )
					{
						// Does section have any values?
						if ( $lang )
						{
							$content[$section][$group][$type] = array();

							// Loop through language strings
							foreach ( $lang as $keyword => $name )
							{
								$content[$section][$group][$type][] = "'" . $keyword . "' => \"" . str_replace(array("\r", "\n", '"'), array('', '\n', '\"'), $name) . "\"";
							}

							$content[$section][$group][$type] = "\t\t'" . $type . "' => array(\n\t\t\t\t" . implode(",\n\t\t\t\t", $content[$section][$group][$type]) . "\n\t\t\t),\n";
						}
					}

					$content[$section][$group] = "\t'" . $group . "' => array(\n\t" . implode("\t", $content[$section][$group]) . "\t\t),\n";
				}

				$content[$section] = "\t'" . $section . "' => array(\n\t" . implode("\t", $content[$section]) . "\t)";
			}

			$content = "<?php\n" . $prefix . "\n\$language = array(\n" . implode(",\n", $content) . "\n);";

			$filename = $path . $plugin . '.php';

			if ( !@file_put_contents($filename, $content) )
			{
				error::show('Could not save the file: ' . $filename . ' Make sure the "' . $path . '" folder exists and is writable.');
			}

			@chmod($filename, octdec(config::item('file_chmod')));
		}

		return true;
	}

	public function import($lang)
	{
		$default = $this->getLanguageData('system');

		$path = DOCPATH . 'languages/' . $lang . '/';

		// Loop through language plugins
		foreach ( $default as $plugin => $sections )
		{
			// Set file name
			$filename = $plugin . '.php';

			// Does file exist and can be included?
			if ( @is_file($path . $filename) && @include($path . $filename) )
			{
				// Does language array exist?
				if ( isset($language) && is_array($language) && $language )
				{
					// Loop through language strings
					foreach ( $language as $section => $groups )
					{
						foreach ( $groups as $group => $types )
						{
							foreach ( $types as $type => $data )
							{
								foreach ( $data as $keyword => $value )
								{
									$values = array(
										'value_' . $lang => $value,
									);

									// Save language string
									$this->saveLanguageData($plugin, $section, $group, $keyword, $values);
								}
							}
						}
					}
				}
			}
		}

		$this->cache->cleanup();

		return true;
	}

	public function delete($language)
	{
		loader::helper('file');
		$path = DOCPATH . 'languages/' . $language;

		// Delete files
		if ( @is_dir($path) )
		{
			foreach ( file_helper::scanFileNames($path) as $file )
			{
				@unlink($path . '/' . $file);
			}

			@rmdir($path);
		}

		return true;
	}
}
