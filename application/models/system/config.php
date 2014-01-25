<?php

class System_Config_Model extends Model
{
	public function saveSetting($plugin, $keyword, $value, $orderID = false)
	{
		$data = array('val' => $value);

		if ( $orderID !== false )
		{
			$data['order_id'] = $orderID;
		}

		$retval = $this->db->update('core_config', $data, array('plugin' => $plugin, 'keyword' => $keyword), 1);

		if ( $retval )
		{
			// Action hook
			hook::action('system/settings/update', $plugin, $keyword, $value, $orderID);
		}

		session::delete('', 'config');

		$this->cache->cleanup();

		return $retval;
	}

	public function getSettingsGroups($plugin = '')
	{
		$data = array();

		$groups = $this->db->query("SELECT * FROM `:prefix:core_config_groups` " . ( $plugin ? "WHERE `plugin`=?" : "" ) . " ORDER BY `order_id`", array($plugin))->result();
		foreach ( $groups as $group )
		{
			if ( __('group_' . $group['keyword'], $group['plugin'] . '_config') !== false )
			{
				$value = __('group_' . $group['keyword'], $group['plugin'] . '_config');
			}
			elseif ( __('group_' . $group['keyword'], 'system_config') !== false )
			{
				$value = __('group_' . $group['keyword'], 'system_config');
			}
			else
			{
				$value = $group['keyword'];
			}

			$data[$group['plugin']][$group['keyword']] = $value;
		}

		if ( $plugin != '' && isset($data[$plugin]) )
		{
			$data = $data[$plugin];
		}

		return $data;
	}

	public function getSettings($plugin = '', $compile = false)
	{
		$settings = array();

		$qsettings = $this->db->query("SELECT `c`.*, `c`.`val` AS `value`
			FROM `:prefix:core_config` AS `c` LEFT JOIN `:prefix:core_config_groups` AS `g` ON `c`.`plugin`=`g`.`plugin` AND `c`.`group`=`g`.`keyword`
			WHERE 1=1 " . ( !$compile ? " AND `c`.`group`!='' " : "" ) . ( $plugin ? " AND `c`.`plugin`=? " : "" ) . "
			ORDER BY `g`.`order_id` ASC, `c`.`order_id`, `c`.`keyword` ASC", array($plugin))->result();

		foreach ( $qsettings as $setting )
		{
			unset($setting['val']);

			if ( !$compile )
			{
				if ( __($setting['keyword'], $setting['plugin'] . '_config') !== false )
				{
					$setting['name'] = __($setting['keyword'], $setting['plugin'] . '_config');
				}
				elseif ( __($setting['keyword'], $setting['plugin']) !== false )
				{
					$setting['name'] = __($setting['keyword'], $setting['plugin']);
				}
				elseif ( __($setting['keyword'], 'system_config') !== false )
				{
					$setting['name'] = __($setting['keyword'], 'system_config');
				}
				elseif ( __($setting['keyword'], 'system') !== false )
				{
					$setting['name'] = __($setting['keyword'], 'system');
				}
				else
				{
					$setting['name'] = $setting['keyword'];
				}
			}

			if ( !$compile && $setting['items'] )
			{
				$setting['items'] = @json_decode($setting['items'], true);
				if ( !is_array($setting['items']) )
				{
					$setting['items'] = array();
				}

				foreach ( $setting['items'] as $index => $key )
				{
					if ( __($key, $setting['plugin'] . '_config') !== false )
					{
						$setting['items'][$index] = __($key, $setting['plugin'] . '_config');
					}
					elseif ( __($key, $setting['plugin']) !== false )
					{
						$setting['items'][$index] = __($key, $setting['plugin']);
					}
					elseif ( __($key, 'system_config') !== false )
					{
						$setting['items'][$index] = __($key, 'system_config');
					}
					elseif ( __($key, 'system') !== false )
					{
						$setting['items'][$index] = __($key, 'system');
					}
					else
					{
						$setting['items'][$index] = $key;
					}
				}
			}

			if ( $setting['type'] == 'checkbox' || $setting['type'] == 'json' )
			{
				$setting['value'] = @json_decode($setting['value'], true);
			}

			if ( $setting['plugin'] == 'system' && $setting['keyword'] == 'license' )
			{
				$setting['value'] = array(
					'key' => isset($setting['value']['license']) ? $setting['value']['license'] : '',
					'addons' => isset($setting['value']['addons']) ? $setting['value']['addons'] : array(),
				);
			}

			if ( $plugin )
			{
				$settings[$setting['group']][] = $setting;
			}
			else
			{
				$settings[$setting['plugin']][$setting['group']][] = $setting;
			}
		}

		return $settings;
	}
}
