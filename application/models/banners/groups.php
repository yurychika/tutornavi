<?php

class Banners_Groups_Model extends Model
{
	public function saveGroup($groupID, $group)
	{
		// Is this a new banner group?
		if ( !$groupID )
		{
			// Save banner group
			$groupID = $this->db->insert('banners_groups', $group);

			// Action hook
			hook::action('banners/groups/insert', $groupID, $group);
		}
		else
		{
			// Save banner group
			$this->db->update('banners_groups', $group, array('group_id' => $groupID), 1);

			// Action hook
			hook::action('banners/groups/update', $groupID, $group);
		}

		return $groupID;
	}

	public function getGroup($groupID)
	{
		// Get banner group
		$group = $this->db->query("SELECT * FROM `:prefix:banners_groups` WHERE `group_id`=? LIMIT 1", array($groupID))->row();

		return $group;
	}

	public function getGroups($conditions = array(), $order = array())
	{
		$groups = array();

		// Get banner groups
		$qgroups = $this->db->query("SELECT `group_id`, `name`, `keyword` FROM `:prefix:banners_groups` ORDER BY `name` ASC")->result();
		foreach ( $qgroups as $group )
		{
			$groups[$group['group_id']] = $group;
		}

		return $groups;
	}

	public function deleteGroup($groupID, $group)
	{
		// Delete banner group
		$retval = $this->db->delete('banners_groups', array('group_id' => $groupID), 1);
		if ( $retval )
		{
			// Delete banners
			$this->db->delete('banners_data', array('group_id' => $groupID));

			// Action hook
			hook::action('banners/groups/delete', $groupID, $group);
		}

		return $retval;
	}
}
