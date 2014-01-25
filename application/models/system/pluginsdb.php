<?php defined('SYSPATH') || die('No direct script access allowed.');

class Plugins extends Model
{
	protected $manifest = array();
	protected $dbEngine = false;

	public function __construct($manifest = array())
	{
		parent::__construct();

		$this->manifest = $manifest;

		loader::library('dbforge');

		$engines = $this->dbforge->getEngines();
		$this->dbEngine = in_array('InnoDB', $engines) ? 'InnoDB' : 'MyISAM';
		//$this->dbEngine = 'MyISAM';
	}

	public function installLanguageData($update = false)
	{
		// Set plugin keyword
		$plugin = $this->manifest['keyword'];

		// Get installed languages
		$languages = $this->getInstalledLanguages();

		// Get language files
		$files = $this->getLanguageFiles($plugin, $languages);

		// Set english language data
		if ( !( $system = isset($files['english']) ? array('system' => $files['english']) : $this->getLanguageFiles($plugin, array('system')) ) )
		{
			return array();
		}

		// Merge system language
		$files['system'] = $system['system'];

		// Update missing languages
		foreach ( $languages as $language )
		{
			if ( !isset($files[$language]) )
			{
				$files[$language] = $files['system'];
			}
		}

		if ( isset($files['system']) && $files['system'] )
		{
			foreach ( $files['system'] as $file => $data )
			{
				if ( $file == 'language' )
				{
					foreach ( $data as $section => $groups )
					{
						foreach ( $groups as $group => $types )
						{
							foreach ( $types as $type => $keywords )
							{
								foreach ( $keywords as $keyword => $value )
								{
									$item = array(
										'section' => $section,
										'cp' => $type == 'cp' ? 1 : 0,
										'value_system' => $value,
									);

									foreach ( $languages as $language )
									{
										if ( isset($files[$language]['language'][$section][$group][$type][$keyword]) )
										{
											$item['value_' . $language] = $files[$language]['language'][$section][$group][$type][$keyword];
										}
									}

									// Save language string
									$this->saveLanguageItem($plugin, $group, $keyword, $item, $languages, $update);
								}
							}
						}
					}
				}
				elseif ( $file == 'fields' && !$update )
				{
					foreach ( $data as $categoryID => $fields )
					{
						foreach ( $fields as $keyword => $field )
						{
							if ( isset($field['items']) && $field['items'] )
							{
								foreach ( $field['items'] as $itemID => $item )
								{
									$item['order_id'] = $itemID+1;

									foreach ( $languages as $language )
									{
										if ( isset($files[$language]['fields'][$categoryID][$keyword]['items'][$itemID]) )
										{
											$item = $item + $files[$language]['fields'][$categoryID][$keyword]['items'][$itemID];
										}
									}

									$this->saveLanguageFieldItem($plugin, $categoryID, $keyword, $item);
								}

								unset($field['items']);
							}

							foreach ( $languages as $language )
							{
								if ( isset($files[$language]['fields'][$categoryID][$keyword]) )
								{
									if ( isset($files[$language]['fields'][$categoryID][$keyword]['items']) )
									{
										unset($files[$language]['fields'][$categoryID][$keyword]['items']);
									}
									$field = $field + $files[$language]['fields'][$categoryID][$keyword];
								}
							}

							$this->saveLanguageField($plugin, $categoryID, $keyword, $field);
						}
					}
				}
				elseif ( $file == 'email_templates' )
				{
					foreach ( $data as $keyword => $template )
					{
						foreach ( $languages as $language )
						{
							if ( isset($files[$language]['email_templates'][$keyword]) )
							{
								$template = $template + $files[$language]['email_templates'][$keyword];
							}
						}

						$this->saveLanguageEmailTemplate($plugin, $keyword, $template);
					}
				}
				elseif ( $file == 'meta_tags' )
				{
					foreach ( $data as $keyword => $metatags )
					{
						foreach ( $languages as $language )
						{
							if ( isset($files[$language]['meta_tags'][$keyword]) )
							{
								$metatags = $metatags + $files[$language]['meta_tags'][$keyword];
							}
						}

						$this->saveLanguageMetaTag($plugin, $keyword, $metatags);
					}
				}
			}
		}

		return $files['system'];
	}

	public function getInstalledLanguages()
	{
		$languages = array();

		// Get installed languages
		foreach ( $this->db->query("SELECT * FROM `:prefix:core_languages` ORDER BY `name` ASC")->result() as $row )
		{
			$languages[] = $row['keyword'];
		}

		return $languages;
	}

	protected function getLanguageFiles($plugin, $languages)
	{
		$data = array();

		foreach ( $languages as $lang )
		{
			// Get language file
			if ( @is_file(DOCPATH . 'plugins/' . $plugin . '/install/languages/' . ( $lang == 'system' ? 'english' : $lang ) . '.php') &&
				@include(DOCPATH . 'plugins/' . $plugin . '/install/languages/' . ( $lang == 'system' ? 'english' : $lang ) . '.php') )
			{
				foreach ( array('language', 'fields', 'email_templates', 'meta_tags') as $key )
				{
					if ( isset($$key) && is_array($$key) && $$key )
					{
						$data[$lang][$key] = $$key;
					}
				}
			}
		}

		return $data;
	}

	protected function saveLanguageItem($plugin, $group, $keyword, $data = array(), $languages = array(), $update = false)
	{
		$data['plugin'] = $plugin;
		$data['group'] = $group;
		$data['keyword'] = $keyword;

		$row = $this->db->query("SELECT * FROM `:prefix:core_languages_data` WHERE `plugin`=? AND `group`=? AND `keyword`=? LIMIT 1", array($plugin, $group, $keyword))->row();

		if ( $row )
		{
			if ( $update )
			{
				foreach ( $languages as $language )
				{
					if ( $row['value_' . $language] && isset($data['value_' . $language]) )
					{
						unset($data['value_' . $language]);
					}
				}

				// Check if the original English value changed
				if ( in_array('english', $languages) && isset($row['value_system']) && $data['value_system'] && strcasecmp($row['value_system'], $data['value_system']) )
				{
					$data['value_english'] = $data['value_system'];
				}
			}

			$retval = $this->db->update('core_languages_data', $data, array('plugin' => $plugin, 'group' => $group, 'keyword' => $keyword), 1);
		}
		else
		{
			$retval = $this->db->insert('core_languages_data', $data);
		}

		return $retval;
	}

	protected function saveLanguageField($plugin, $categoryID, $keyword, $data)
	{
		$retval = $this->db->update('core_fields', $data, array('plugin' => $plugin, 'category_id' => $categoryID, 'keyword' => $keyword), 1);

		return $retval;
	}

	protected function saveLanguageFieldItem($plugin, $categoryID, $keyword, $data)
	{
		$row = $this->db->query("SELECT * FROM `:prefix:core_fields` WHERE `plugin`=? AND `category_id`=? AND `keyword`=? LIMIT 1", array($plugin, $categoryID, $keyword))->row();

		if ( $row )
		{
			$data['field_id'] = $row['field_id'];

			$this->db->insert('core_fields_items', $data);
		}

		return true;
	}

	protected function saveLanguageEmailTemplate($plugin, $keyword, $data)
	{
		$retval = true;

		if ( $data )
		{
			$column = key($data);

			$row = $this->db->query("SELECT `$column` AS `column` FROM `:prefix:core_email_templates` WHERE `plugin`=? AND `keyword`=? LIMIT 1", array($plugin, $keyword))->row();

			if ( $row && !$row['column'] )
			{
				$retval = $this->db->update('core_email_templates', $data, array('plugin' => $plugin, 'keyword' => $keyword), 1);
			}
		}

		return $retval;
	}

	protected function saveLanguageMetaTag($plugin, $keyword, $data)
	{
		$retval = true;

		if ( $data )
		{
			$column = key($data);

			$row = $this->db->query("SELECT `$column` AS `column` FROM `:prefix:core_meta_tags` WHERE `plugin`=? AND `keyword`=? LIMIT 1", array($plugin, $keyword))->row();

			if ( $row && !$row['column'] )
			{
				$retval = $this->db->update('core_meta_tags', $data, array('plugin' => $plugin, 'keyword' => $keyword), 1);
			}
		}

		return $retval;
	}

	public function updateLanguageData()
	{
		// Set plugin keyword
		$plugin = $this->manifest['keyword'];

		// Install language data
		$language = $this->installLanguageData(true);

		// Get current language data
		$result = $this->db->query("SELECT `section`, `group`, `keyword`, `cp` FROM `:prefix:core_languages_data` WHERE `plugin`=?", array($plugin))->result();

		foreach ( $result as $row )
		{
			if ( !isset($language['language'][$row['section']][$row['group']][$row['cp'] ? 'cp' : 'ca'][$row['keyword']]) )
			{
				$this->db->delete('core_languages_data', array('plugin' => $plugin, 'group' => $row['group'], 'keyword' => $row['keyword']), 1);
			}
		}

		return true;
	}

	public function uninstallLanguageData()
	{
		// Set plugin keyword
		$plugin = $this->manifest['keyword'];

		$this->db->delete('core_languages_data', array('plugin' => $plugin));

		return true;
	}

	public function installSettings($update = false)
	{
		// Set plugin keyword
		$plugin = $this->manifest['keyword'];

		// Load config file
		if ( @is_file(DOCPATH . 'plugins/' . $plugin . '/install/config.php') && @include(DOCPATH . 'plugins/' . $plugin . '/install/config.php') )
		{
			// Does config array exist?
			if ( isset($config) && is_array($config) && $config )
			{
				$groupOrderID = 1;

				// Loop through groups
				foreach ( $config as $group => $settings )
				{
					$settingOrderID = 1;

					// Loop through settings
					foreach ( $settings as $keyword => $setting )
					{
						if ( ($setting['type'] == 'json' || $setting['type'] == 'checkbox') && isset($setting['value']) && is_array($setting['value']) )
						{
							$setting['value'] = json_encode($setting['value']);
						}

						$data = array(
							'type' => $setting['type'],
							'val' => isset($setting['value']) ? $setting['value'] : null,
							'items' => isset($setting['items']) ? json_encode($setting['items']) : null,
							'required' => isset($setting['required']) && $setting['required'] ? 1 : 0,
							'order_id' => $settingOrderID,
							'class' => isset($setting['class']) ? $setting['class'] : null,
							'style' => isset($setting['style']) ? $setting['style'] : null,
							'callback' => isset($setting['callback']) ? $setting['callback'] : null,
						);

						// Save setting
						$this->saveSetting($plugin, $group, $keyword, $data, $update);

						$settingOrderID++;
					}

					// Save group
					if ( $group != '' )
					{
						$data = array(
							'order_id' => $groupOrderID,
						);

						$this->saveSettingGroup($plugin, $group, $data);

						$groupOrderID++;
					}
				}

				return $config;
			}
		}

		return array();
	}

	public function updateSettings()
	{
		// Set plugin keyword
		$plugin = $this->manifest['keyword'];

		// Install settings
		$settings = $this->installSettings(true);

		// Get current setting groups
		$result = $this->db->query("SELECT `keyword` FROM `:prefix:core_config_groups` WHERE `plugin`=?", array($plugin))->result();

		foreach ( $result as $row )
		{
			if ( !isset($settings[$row['keyword']]) )
			{
				$this->db->delete('core_config_groups', array('plugin' => $plugin, 'keyword' => $row['keyword']), 1);
			}
		}

		// Get current settings
		$result = $this->db->query("SELECT `group`, `keyword` FROM `:prefix:core_config` WHERE `plugin`=?", array($plugin))->result();

		foreach ( $result as $row )
		{
			if ( !isset($settings[$row['group']][$row['keyword']]) )
			{
				$this->db->delete('core_config', array('plugin' => $plugin, 'keyword' => $row['keyword']), 1);
			}
		}

		return true;
	}

	public function uninstallSettings()
	{
		// Set plugin keyword
		$plugin = $this->manifest['keyword'];

		$this->db->delete('core_config', array('plugin' => $plugin));
		$this->db->delete('core_config_groups', array('plugin' => $plugin));

		return false;
	}

	protected function saveSetting($plugin, $group, $keyword, $data = array(), $update = false)
	{
		$data['plugin'] = $plugin;
		$data['group'] = $group;
		$data['keyword'] = $keyword;

		$row = $this->db->query("SELECT * FROM `:prefix:core_config` WHERE `plugin`=? AND `keyword`=? LIMIT 1", array($plugin, $keyword))->row();

		if ( $row )
		{
			if ( $update )
			{
				unset($data['val']);
			}

			$retval = $this->db->update('core_config', $data, array('plugin' => $plugin, 'keyword' => $keyword), 1);
		}
		else
		{
			$retval = $this->db->insert('core_config', $data);
		}

		return $retval;
	}

	protected function saveSettingGroup($plugin, $keyword, $data)
	{
		$data['plugin'] = $plugin;
		$data['keyword'] = $keyword;

		$row = $this->db->query("SELECT * FROM `:prefix:core_config_groups` WHERE `plugin`=? AND `keyword`=? LIMIT 1", array($plugin, $keyword))->row();

		if ( $row )
		{
			$retval = $this->db->update('core_config_groups', $data, array('plugin' => $plugin, 'keyword' => $keyword), 1);
		}
		else
		{
			$retval = $this->db->insert('core_config_groups', $data);
		}

		return $retval;
	}

	public function installLists()
	{
		// Set plugin keyword
		$plugin = $this->manifest['keyword'];

		// Load config file
		if ( @is_file(DOCPATH . 'plugins/' . $plugin . '/install/lists.php') && @include(DOCPATH . 'plugins/' . $plugin . '/install/lists.php') )
		{
			// Does lists array exist?
			if ( isset($lists) && is_array($lists) && $lists )
			{
				// Loop through list types
				foreach ( $lists as $type => $items )
				{
					// Loop through list items
					foreach ( $items as $keyword => $item )
					{
						$data = array(
							'type' => $type,
							'keyword' => isset($item['keyword']) ? $item['keyword'] : $keyword,
							'parent' => $item['parent'],
							'uri' => $item['uri'],
							'name' => $item['name'],
							'order_id' => isset($item['order_id']) ? $item['order_id'] : 0,
							'attr' => isset($item['attr']) ? $item['attr'] : '',
						);

						// Save list item
						$this->saveListItem($plugin, $type, $data['keyword'], $data['parent'], $data['uri'], $data);
					}
				}

				return $lists;
			}
		}

		return array();
	}

	public function updateLists()
	{
		// Set plugin keyword
		$plugin = $this->manifest['keyword'];

		// Install lists
		$lists = $this->installLists();

		// Get current lists
		$result = $this->db->query("SELECT `type`, `keyword` FROM `:prefix:core_lists` WHERE `plugin`=?", array($plugin))->result();

		foreach ( $result as $row )
		{
			if ( !isset($lists[$row['type']][$row['keyword']]) )
			{
				$this->db->delete('core_lists', array('plugin' => $plugin, 'type' => $row['type'], 'keyword' => $row['keyword']), 1);
			}
		}

		return true;
	}

	public function uninstallLists()
	{
		// Set plugin keyword
		$plugin = $this->manifest['keyword'];

		$this->db->delete('core_lists', array('plugin' => $plugin));

		return false;
	}

	protected function saveListItem($plugin, $type, $keyword, $parent, $uri, $data = array())
	{
		$data['plugin'] = $plugin;
		$data['type'] = $type;
		$data['keyword'] = $keyword;
		$data['parent'] = $parent;
		$data['uri'] = $uri;

		$row = $this->db->query("SELECT * FROM `:prefix:core_lists` WHERE `plugin`=? AND `type`=? AND `keyword`=? AND `parent`=? AND `uri`=? LIMIT 1", array($plugin, $type, $keyword, $parent, $uri))->row();

		if ( $row )
		{
			$retval = $this->db->update('core_lists', $data, array('item_id' => $row['item_id']), 1);
		}
		else
		{
			$retval = $this->db->insert('core_lists', $data);
		}

		return $retval;
	}

	public function installTimeline()
	{
		// Set plugin keyword
		$plugin = $this->manifest['keyword'];

		// Load config file
		if ( @is_file(DOCPATH . 'plugins/' . $plugin . '/install/timeline.php') && @include(DOCPATH . 'plugins/' . $plugin . '/install/timeline.php') )
		{
			// Does types array exist?
			if ( isset($types) && is_array($types) && $types )
			{
				// Loop through action types
				foreach ( $types as $type => $resource )
				{
					// Get resource
					$resource = $this->db->query("SELECT * FROM `:prefix:core_resources` WHERE `plugin`=? AND `keyword`=? LIMIT 1", array($plugin, $resource))->row();
					if ( $resource )
					{
						$data = array(
							'resource_id' => $resource['resource_id'],
							'keyword' => $type,
						);

						// Save timeline action type
						$this->saveTimelineType($plugin, $data['resource_id'], $data['keyword'], $data);
					}
				}

				return $types;
			}
		}

		return array();
	}

	public function updateTimeline()
	{
		// Set plugin keyword
		$plugin = $this->manifest['keyword'];

		// Install timeline
		$types = $this->installTimeline();

		// Get current types
		$result = $this->db->query("SELECT `t`.`keyword` AS `type`
			FROM `:prefix:timeline_types` AS `t` INNER JOIN `:prefix:core_resources` AS `r` ON `r`.`resource_id`=`t`.`resource_id`
			WHERE `r`.`plugin`=? ORDER BY `t`.`keyword` ASC", array($plugin))->result();

		foreach ( $result as $row )
		{
			if ( !isset($types[$row['type']]) )
			{
				$this->db->delete('timeline_types', array('keyword' => $row['type']), 1);
			}
		}

		return true;
	}

	public function uninstallTimeline()
	{
		// Set plugin keyword
		$plugin = $this->manifest['keyword'];

		// Get current types
		$result = $this->db->query("SELECT `t`.`type_id`
			FROM `:prefix:timeline_types` AS `t` INNER JOIN `:prefix:core_resources` AS `r` ON `r`.`resource_id`=`t`.`resource_id`
			WHERE `r`.`plugin`=? ORDER BY `t`.`keyword` ASC", array($plugin))->result();
		foreach ( $result as $row )
		{
			$this->db->query("DELETE FROM `:prefix:timeline_attachments` WHERE `action_id` IN (SELECT `action_id` FROM `:prefix:timeline_actions` WHERE `type_id`=? AND `attachments`>0)", array($row['type_id']));
			$this->db->delete('timeline_actions', array('type_id' => $row['type_id']));
			$this->db->delete('timeline_types', array('type_id' => $row['type_id']), 1);
		}

		return false;
	}

	protected function saveTimelineType($plugin, $resourceID, $keyword, $data = array())
	{
		$data['resource_id'] = $resourceID;
		$data['keyword'] = $keyword;

		$row = $this->db->query("SELECT * FROM `:prefix:timeline_types` WHERE `keyword`=? LIMIT 1", array($keyword))->row();

		if ( $row )
		{
			$retval = $this->db->update('timeline_types', $data, array('type_id' => $row['type_id']), 1);
		}
		else
		{
			$retval = $this->db->insert('timeline_types', $data);
		}

		return $retval;
	}

	public function installHooks()
	{
		// Set plugin keyword
		$plugin = $this->manifest['keyword'];

		// Load config file
		if ( @is_file(DOCPATH . 'plugins/' . $plugin . '/install/hooks.php') && @include(DOCPATH . 'plugins/' . $plugin . '/install/hooks.php') )
		{
			// Does hooks array exist?
			if ( isset($hooks) && is_array($hooks) && $hooks )
			{
				// Loop through hook types
				foreach ( $hooks as $type => $keywords )
				{
					// Loop through keywords
					foreach ( $keywords as $keyword => $items )
					{
						// Loop through hooks
						foreach ( $items as $item )
						{
							$data = array(
								'path' => $item['path'],
								'object' => $item['object'],
								'function' => $item['function'],
								'order_id' => isset($item['order_id']) ? $item['order_id'] : 0,
							);

							// Save hook
							$this->saveHook($plugin, $type, $keyword, $data);
						}
					}
				}

				return $hooks;
			}
		}

		return array();
	}

	public function updateHooks()
	{
		// Set plugin keyword
		$plugin = $this->manifest['keyword'];

		// Install hooks
		$hooks = $this->installHooks();

		// Get current hooks
		$result = $this->db->query("SELECT `type`, `keyword` FROM `:prefix:core_hooks` WHERE `plugin`=?", array($plugin))->result();

		foreach ( $result as $row )
		{
			if ( !isset($hooks[$row['type']][$row['keyword']]) )
			{
				$this->db->delete('core_hooks', array('plugin' => $plugin, 'type' => $row['type'], 'keyword' => $row['keyword']), 1);
			}
		}

		return true;
	}

	public function uninstallHooks()
	{
		// Set plugin keyword
		$plugin = $this->manifest['keyword'];

		$this->db->delete('core_hooks', array('plugin' => $plugin));

		return false;
	}

	protected function saveHook($plugin, $type, $keyword, $data = array())
	{
		$data['plugin'] = $plugin;
		$data['type'] = $type;
		$data['keyword'] = $keyword;

		$row = $this->db->query("SELECT * FROM `:prefix:core_hooks` WHERE `plugin`=? AND `type`=? AND `keyword`=? LIMIT 1", array($plugin, $type, $keyword))->row();

		if ( $row )
		{
			$retval = $this->db->update('core_hooks', $data, array('hook_id' => $row['hook_id']), 1);
		}
		else
		{
			$retval = $this->db->insert('core_hooks', $data);
		}

		return $retval;
	}

	public function installFields()
	{
		// Set plugin keyword
		$plugin = $this->manifest['keyword'];

		// Load config file
		if ( @is_file(DOCPATH . 'plugins/' . $plugin . '/install/fields.php') && @include(DOCPATH . 'plugins/' . $plugin . '/install/fields.php') )
		{
			// Does fields array exist?
			if ( isset($fields) && is_array($fields) && $fields )
			{
				// Loop through categories
				foreach ( $fields as $categoryID => $items )
				{
					$orderID = 1;

					// Loop through fields
					foreach ( $items as $keyword => $item )
					{
						$data = array(
							'type' => $item['type'],
							'required' => isset($item['required']) ? $item['required'] : 0,
							'system' => isset($item['system']) ? $item['system'] : 0,
							'multilang' => isset($item['multilang']) ? $item['multilang'] : 0,
							'validate' => isset($item['validate']) ? $item['validate'] : '',
							'style' => isset($item['style']) ? $item['style'] : '',
							'class' => isset($item['class']) ? $item['class'] : '',
							'config' => isset($item['config']) ? json_encode($item['config']) : '',
							'order_id' => $orderID,
						);

						// Save field
						$fieldID = $this->saveField($plugin, $categoryID, $keyword, $data);

						$orderID++;
					}
				}

				return $fields;
			}
		}

		return array();
	}

	public function updateFields()
	{
		return true;
	}

	public function uninstallFields()
	{
		// Set plugin keyword
		$plugin = $this->manifest['keyword'];

		$result = $this->db->query("SELECT * FROM `:prefix:core_fields` WHERE `plugin`=?", array($plugin))->result();

		foreach ( $result as $row )
		{
			$this->db->delete('core_fields_items', array('field_id' => $row['field_id']));
		}

		$this->db->delete('core_fields', array('plugin' => $plugin));

		return false;
	}

	protected function saveField($plugin, $categoryID, $keyword, $data = array())
	{
		$data['plugin'] = $plugin;
		$data['category_id'] = $categoryID;
		$data['keyword'] = $keyword;

		$row = $this->db->query("SELECT * FROM `:prefix:core_fields` WHERE `plugin`=? AND `category_id`=? AND `keyword`=? LIMIT 1", array($plugin, $categoryID, $keyword))->row();

		if ( $row )
		{
			$retval = $this->db->update('core_fields', $data, array('field_id' => $row['field_id']), 1);
		}
		else
		{
			$retval = $this->db->insert('core_fields', $data);
		}

		return $retval;
	}

	protected function saveFieldItem($fieldID, $orderID)
	{
		$data = array(
			'field_id' => $fieldID,
			'order_id' => $orderID,
		);

		$row = $this->db->query("SELECT * FROM `:prefix:core_fields_items` WHERE `field_id`=? AND `order_id`=? LIMIT 1", array($fieldID, $orderID))->row();

		if ( $row )
		{
			$retval = $this->db->update('core_fields_items', $data, array('field_id' => $row['field_id'], 'order_id' => $row['order_id']), 1);
		}
		else
		{
			$retval = $this->db->insert('core_fields_items', $data);
		}

		return $retval;
	}

	public function installResources()
	{
		// Set plugin keyword
		$plugin = $this->manifest['keyword'];

		// Load config file
		if ( @is_file(DOCPATH . 'plugins/' . $plugin . '/install/resources.php') && @include(DOCPATH . 'plugins/' . $plugin . '/install/resources.php') )
		{
			// Does resources array exist?
			if ( isset($resources) && is_array($resources) && $resources )
			{
				// Loop through resources
				foreach ( $resources as $keyword => $item )
				{
					$data = array(
						'model' => $item['model'],
						'items' => $item['items'],
						'prefix' => $item['prefix'],
						'table' => $item['table'],
						'column' => $item['column'],
						'user' => isset($item['user']) ? $item['user'] : '',
						'orderby' => isset($item['orderby']) ? $item['orderby'] : '',
						'orderdir' => isset($item['orderdir']) ? $item['orderdir'] : '',
						'report' => $item['report'],
					);

					// Save resource
					$this->saveResource($plugin, $keyword, $data);
				}

				return $resources;
			}
		}

		return array();
	}

	public function updateResources()
	{
		// Set plugin keyword
		$plugin = $this->manifest['keyword'];

		// Install resources
		$resources = $this->installResources();

		// Get current resources
		$result = $this->db->query("SELECT `keyword` FROM `:prefix:core_resources` WHERE `plugin`=?", array($plugin))->result();

		foreach ( $result as $row )
		{
			if ( !isset($resources[$row['keyword']]) )
			{
				$this->db->delete('core_resources', array('plugin' => $plugin, 'keyword' => $row['keyword']), 1);
			}
		}

		return true;
	}

	public function uninstallResources()
	{
		// Set plugin keyword
		$plugin = $this->manifest['keyword'];

		$resources = array();

		$result = $this->db->query("SELECT * FROM `:prefix:core_resources` WHERE `plugin`=?", array($plugin))->result();
		foreach ( $result as $row )
		{
			$resources = $row['resource_id'];
		}

		if ( $resources )
		{
			if ( $this->dbforge->tableExists(':prefix:core_comments') )
			{
				$this->db->delete('core_comments', array('resource_id' => $resources));
			}

			if ( $this->dbforge->tableExists(':prefix:core_likes') )
			{
				$this->db->delete('core_likes', array('resource_id' => $resources));
			}

			if ( $this->dbforge->tableExists(':prefix:core_votes') )
			{
				$this->db->delete('core_votes', array('resource_id' => $resources));
			}
		}

		$this->db->delete('core_resources', array('plugin' => $plugin));

		return false;
	}

	protected function saveResource($plugin, $keyword, $data = array())
	{
		$data['plugin'] = $plugin;
		$data['keyword'] = $keyword;

		$row = $this->db->query("SELECT * FROM `:prefix:core_resources` WHERE `plugin`=? AND `keyword`=? LIMIT 1", array($plugin, $keyword))->row();

		if ( $row )
		{
			$retval = $this->db->update('core_resources', $data, array('resource_id' => $row['resource_id']), 1);
		}
		else
		{
			$retval = $this->db->insert('core_resources', $data);
		}

		return $retval;
	}

	public function installEmailTemplates()
	{
		// Set plugin keyword
		$plugin = $this->manifest['keyword'];

		// Load config file
		if ( @is_file(DOCPATH . 'plugins/' . $plugin . '/install/email_templates.php') && @include(DOCPATH . 'plugins/' . $plugin . '/install/email_templates.php') )
		{
			// Does templates array exist?
			if ( isset($templates) && is_array($templates) && $templates )
			{
				// Is this legacy format? (version 1.0.7 or less)
				if ( is_array(current($templates)) && array_key_exists('active', current($templates)) )
				{
					$templates = array_keys($templates);
				}

				// Loop through email templates
				foreach ( $templates as $template )
				{
					$data = array(
						'active' => 1,
					);

					// Save email template
					$this->saveEmailTemplate($plugin, $template, $data);
				}

				return $templates;
			}
		}

		return array();
	}

	public function updateEmailTemplates()
	{
		// Set plugin keyword
		$plugin = $this->manifest['keyword'];

		// Install templates
		$templates = $this->installEmailTemplates();

		// Get current email templates
		$result = $this->db->query("SELECT `keyword` FROM `:prefix:core_email_templates` WHERE `plugin`=?", array($plugin))->result();

		foreach ( $result as $row )
		{
			if ( !in_array($row['keyword'], $templates) )
			{
				$this->db->delete('core_email_templates', array('plugin' => $plugin, 'keyword' => $row['keyword']), 1);
			}
		}

		return true;
	}

	public function uninstallEmailTemplates()
	{
		// Set plugin keyword
		$plugin = $this->manifest['keyword'];

		$this->db->delete('core_email_templates', array('plugin' => $plugin));

		return false;
	}

	protected function saveEmailTemplate($plugin, $keyword, $data)
	{
		$data['plugin'] = $plugin;
		$data['keyword'] = $keyword;

		$row = $this->db->query("SELECT * FROM `:prefix:core_email_templates` WHERE `plugin`=? AND `keyword`=? LIMIT 1", array($plugin, $keyword))->row();

		if ( $row )
		{
			if ( array_key_exists('active', $data) )
			{
				unset($data['active']);
			}

			$retval = $this->db->update('core_email_templates', $data, array('template_id' => $row['template_id']), 1);
		}
		else
		{
			$retval = $this->db->insert('core_email_templates', $data);
		}

		return $retval;
	}

	public function installMetaTags()
	{
		// Set plugin keyword
		$plugin = $this->manifest['keyword'];

		// Load config file
		if ( @is_file(DOCPATH . 'plugins/' . $plugin . '/install/meta_tags.php') && @include(DOCPATH . 'plugins/' . $plugin . '/install/meta_tags.php') )
		{
			// Does metatags array exist?
			if ( isset($metatags) && is_array($metatags) && $metatags )
			{
				// Loop through meta tags
				foreach ( $metatags as $keyword )
				{
					// Save meta tag
					$this->saveMetaTag($plugin, $keyword);
				}

				return $metatags;
			}
		}

		return array();
	}

	public function updateMetaTags()
	{
		// Set plugin keyword
		$plugin = $this->manifest['keyword'];

		// Install meta tags
		$metatags = $this->installMetaTags();

		// Get current meta tags
		$result = $this->db->query("SELECT `keyword` FROM `:prefix:core_meta_tags` WHERE `plugin`=?", array($plugin))->result();

		foreach ( $result as $row )
		{
			if ( !in_array($row['keyword'], $metatags) )
			{
				$this->db->delete('core_meta_tags', array('plugin' => $plugin, 'keyword' => $row['keyword']), 1);
			}
		}

		return true;
	}

	public function uninstallMetaTags()
	{
		// Set plugin keyword
		$plugin = $this->manifest['keyword'];

		$this->db->delete('core_meta_tags', array('plugin' => $plugin));

		return false;
	}

	protected function saveMetaTag($plugin, $keyword)
	{
		$data = array();
		$data['plugin'] = $plugin;
		$data['keyword'] = $keyword;

		$row = $this->db->query("SELECT * FROM `:prefix:core_meta_tags` WHERE `plugin`=? AND `keyword`=? LIMIT 1", array($plugin, $keyword))->row();

		if ( $row )
		{
			$retval = $this->db->update('core_meta_tags', $data, array('item_id' => $row['item_id']), 1);
		}
		else
		{
			$retval = $this->db->insert('core_meta_tags', $data);
		}

		return $retval;
	}

	public function installPermissions()
	{
		// Set plugin keyword
		$plugin = $this->manifest['keyword'];

		// Load config file
		if ( @is_file(DOCPATH . 'plugins/' . $plugin . '/install/permissions.php') && @include(DOCPATH . 'plugins/' . $plugin . '/install/permissions.php') )
		{
			// Does permissions array exist?
			if ( isset($permissions) && is_array($permissions) && $permissions )
			{
				// Loop through permissions
				foreach ( $permissions as $section => $items )
				{
					$orderID = 1;
					foreach ( $items as $keyword => $permission )
					{
						$data = array(
							'type' => $permission['type'],
							'guests' => isset($permission['guests']) && $permission['guests'] ? 1 : 0,
							'items' => isset($permission['items']) ? json_encode($permission['items']) : null,
							'callback' => isset($permission['callback']) ? $permission['callback'] : null,
							'cp' => $section == 'cp' ? 1 : 0,
							'order_id' => $orderID++,
						);

						foreach ( array('group_guests', 'group_default', 'group_cancelled') as $key )
						{
							if ( isset($permission[$key]) )
							{
								$data['group_' . config::item($key . '_id', 'users')] = $permission[$key];
							}
						}

						$adminIDs = array();
						$admin = $this->db->query("SELECT * FROM `:prefix:users_permissions` WHERE `plugin`='system' AND `keyword`='site_access_cp' LIMIT 1")->row();
						foreach ( config::item('usergroups', 'core') as $groupID => $name )
						{
							if ( isset($admin['group_' . $groupID]) && $admin['group_' . $groupID] )
							{
								$adminIDs[] = $groupID;
							}
						}

						if ( isset($permission['group_admin']) )
						{
							if ( $adminIDs )
							{
								foreach ( $adminIDs as $adminID )
								{
									$data['group_' . $adminID] = $permission['group_admin'];
								}
							}
							elseif ( config::item('usergroups', 'core', 5) !== false )
							{
								$data['group_5'] = $permission['group_admin'];
							}
						}

						if ( isset($permission['group_default']) )
						{
							foreach ( config::item('usergroups', 'core') as $groupID => $group )
							{
								if ( !in_array($groupID, array(config::item('group_guests_id', 'users'), config::item('group_default_id', 'users'), config::item('group_cancelled_id', 'users'))) )
								{
									if ( $groupID != session::item('group_id') || !isset($permission['group_admin']) )
									{
										$data['group_' . $groupID] = $permission['group_default'];
									}
								}
							}
						}

						// Save email template
						$this->savePermissions($plugin, $keyword, $data);
					}
				}

				return $permissions;
			}
		}

		return array();
	}

	public function updatePermissions()
	{
		// Set plugin keyword
		$plugin = $this->manifest['keyword'];

		// Install permissions
		$permissions = $this->installPermissions();

		// Get current permissions
		$result = $this->db->query("SELECT `cp`, `keyword` FROM `:prefix:users_permissions` WHERE `plugin`=?", array($plugin))->result();

		foreach ( $result as $row )
		{
			if ( !isset($permissions[$row['cp'] ? 'cp' : 'ca'][$row['keyword']]) )
			{
				$this->db->delete('users_permissions', array('plugin' => $plugin, 'keyword' => $row['keyword']), 1);
			}
		}

		return true;
	}

	public function uninstallPermissions()
	{
		// Set plugin keyword
		$plugin = $this->manifest['keyword'];

		$this->db->delete('users_permissions', array('plugin' => $plugin));

		return false;
	}

	protected function savePermissions($plugin, $keyword, $data)
	{
		$data['plugin'] = $plugin;
		$data['keyword'] = $keyword;

		if ( isset($data['group_' . config::item('group_default_id', 'users')]) )
		{
			$groups = $this->db->query("SELECT * FROM `:prefix:users_groups`")->result();
			foreach ( $groups as $group )
			{
				if ( !isset($data['group_' . $group['group_id']]) )
				{
					$data['group_' . $group['group_id']] = $data['group_' . config::item('group_default_id', 'users')];
				}
			}
		}

		$row = $this->db->query("SELECT * FROM `:prefix:users_permissions` WHERE `plugin`=? AND `keyword`=? LIMIT 1", array($plugin, $keyword))->row();

		if ( $row )
		{
			foreach ( $data as $key => $val )
			{
				if ( strpos($key, 'group_') === 0 )
				{
					unset($data[$key]);
				}
			}

			$retval = $this->db->update('users_permissions', $data, array('plugin' => $plugin, 'keyword' => $keyword), 1);
		}
		else
		{
			$retval = $this->db->insert('users_permissions', $data);
		}

		return $retval;
	}
}

