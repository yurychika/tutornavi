<?php

class Banners_Banners_Model extends Model
{
	public function saveBanner($groupID, $bannerID, $banner)
	{
		// Is this a new banner?
		if ( !$bannerID )
		{
			// Save banner
			$bannerID = $this->db->insert('banners_data', $banner);

			// Action hook
			hook::action('banners/insert', $bannerID, $banner);
		}
		else
		{
			// Save banner
			$this->db->update('banners_data', $banner, array('banner_id' => $bannerID), 1);

			// Action hook
			hook::action('banners/update', $bannerID, $banner);
		}

		return $bannerID;
	}

	public function getBanner($bannerID, $groupID = '')
	{
		if ( is_numeric($bannerID) )
		{
			$bannerColumn = 'banner_id';
		}
		else
		{
			$bannerColumn = 'keyword';
		}

		if ( is_numeric($groupID) )
		{
			$groupColumn = 'group_id';
		}
		else
		{
			$groupColumn = 'keyword';
		}

		// Get banner
		if ( $groupID )
		{
			$banner = $this->db->query("SELECT `b`.*
				FROM `:prefix:banners_data` AS `b` INNER JOIN `:prefix:banners_groups` AS `g` ON `b`.`group_id`=`g`.`group_id`
				WHERE `g`.`" . $groupColumn. "`=? AND `b`.`active`=1 " . ( $bannerID ? " AND `b`.`" . $bannerColumn . "`=?" : "" ) . "
				ORDER BY RAND() LIMIT 1", array($groupID, $bannerID))->row();
		}
		else
		{
			$banner = $this->db->query("SELECT * FROM `:prefix:banners_data` WHERE `$bannerColumn`=? LIMIT 1", array($bannerID))->row();
		}

		return $banner;
	}

	public function updateViews($bannerID)
	{
		$retval = $this->db->query("UPDATE `:prefix:banners_data` SET `total_views`=`total_views`+1 WHERE `banner_id`=? AND `active`=1 LIMIT 1", array($bannerID));

		return $retval;
	}

	public function updateClicks($bannerID)
	{
		$retval = $this->db->query("UPDATE `:prefix:banners_data` SET `total_clicks`=`total_clicks`+1 WHERE `banner_id`=? AND `active`=1 LIMIT 1", array($bannerID));

		return $retval;
	}

	public function getBanners($groupID, $params = array())
	{
		// Sorting
		$order = 'name` ASC';
		if ( isset($params['order']) && $params['order'] )
		{
			$order = is_array($params['order']) ? '`' . key($params['order']) . '` ' . current($params['order']) : $params['order'];
		}

		// Get banners
		$banners = $this->db->query("SELECT * FROM `:prefix:banners_data` " . ( $groupID ? "WHERE `group_id`=? " : "" ) . ( $order ? "ORDER BY $order" : "" ), array($groupID))->result();

		return $banners;
	}

	public function isUniqueKeyword($groupID, $keyword, $bannerID = 0)
	{
		$banner = $this->db->query("SELECT COUNT(*) AS `totalrows` FROM `:prefix:banners_data` WHERE `group_id`=? AND `keyword`=? AND `banner_id`!=? LIMIT 1", array($groupID, $keyword, $bannerID))->row();

		return $banner['totalrows'] ? false : true;
	}

	public function deleteBanner($groupID, $bannerID, $banner)
	{
		$retval = $this->db->delete('banners_data', array('banner_id' => $bannerID), 1);

		if ( $retval )
		{
			// Action hook
			hook::action('banners/delete', $bannerID, $banner);
		}

		return $retval;
	}
}
