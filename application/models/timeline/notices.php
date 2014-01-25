<?php

class Timeline_Notices_Model extends Model
{
	public function saveNotice($keyword, $userID, $posterID, $itemID, $childID = 0)
	{
		$typeID = config::item('keywords', 'timeline', $keyword);

		if ( !$typeID )
		{
			return false;
		}

		$notice = array(
			'type_id' => $typeID,
			'user_id' => $userID,
			'poster_id' => $posterID,
			'item_id' => $itemID,
			'child_id' => $childID,
			'post_date' => date_helper::now(),
			'new' => 1,
		);

		// Save notice
		$noticeID = $this->db->insert('timeline_notices', $notice);

		if ( $noticeID )
		{
			// Update user counters
			$this->db->query("UPDATE `:prefix:users` SET `total_notices_new`=`total_notices_new`+1 WHERE `user_id`=? LIMIT 1", array($userID));

			// Action hook
			hook::action('timeline/notices/insert', $typeID, $userID, $posterID, $itemID, $childID);
		}

		return $noticeID;
	}

	public function deleteNotice($keyword, $userID, $posterID, $itemID, $childID = 0)
	{
		$typeID = config::item('keywords', 'timeline', $keyword);

		if ( !$typeID )
		{
			return false;
		}

		// Delete actions
		$retval = $this->db->delete('timeline_notices', array('type_id' => $typeID, 'user_id' => $userID, 'poster_id' => $posterID, 'item_id' => $itemID, 'child_id' => $childID), 1);

		if ( $retval )
		{
			// Update user counters
			//$this->db->query("UPDATE `:prefix:users` SET `total_notices_new`=`total_notices_new`-1 WHERE `user_id`=? LIMIT 1", array($userID));

			// Action hook
			hook::action('timeline/notices/delete', $keyword, $userID, $posterID, $itemID, $childID);
		}

		return true;
	}

	public function deleteUser($userID, $user)
	{
		// Delete actions
		$this->db->query("DELETE FROM `:prefix:timeline_notices` WHERE `user_id`=? OR `poster_id`=?", array($userID, $userID));

		// Action hook
		hook::action('timeline/notices/delete_user', $userID);

		return true;
	}

	public function getNotices($userID, $lastID = 0, $limit = 15, $params = array())
	{
		$columns = array();

		// Set last action ID
		if ( $lastID )
		{
			$columns[] = '`n`.`notice_id`<' . $lastID;
		}

		// Set user ID
		$columns[] = "`n`.`user_id`=" . $userID;

		// Get notices
		$notices = $this->fields_model->getRows('timeline_notice', true, false, $columns, false, '', $limit, $params);

		// Loop through notices
		foreach ( $notices as $noticeID => $notice )
		{
			$keyword = array_search($notice['type_id'], config::item('keywords', 'timeline'));
			$plugin = config::item('resources', 'core', config::item('resources', 'core', config::item('resources', 'timeline', $notice['type_id'])), 'plugin');

			$notices[$noticeID] = hook::filter('timeline/notice/' . $keyword, $notice);
		}

		return $notices;
	}

	public function resetCounter()
	{
		// Update counters
		$this->db->query("UPDATE `:prefix:timeline_notices` SET `new`=0 WHERE `user_id`=? AND `new`=1 LIMIT ?", array(session::item('user_id'), session::item('total_notices_new')));
		$this->db->query("UPDATE `:prefix:users` SET `total_notices_new`=0 WHERE `user_id`=? LIMIT 1", array(session::item('user_id')));
	}

	public function cleanup()
	{
		$timestamp = date_helper::now()-60*60*24*config::item('notices_cleanup_delay', 'timeline');

		// Get old unseen notices
		$notices = $this->db->query("SELECT * FROM `:prefix:timeline_notices` WHERE `new`=1 AND `post_date`<?", array($timestamp))->result();

		foreach ( $notices as $notice )
		{
			$this->db->query("UPDATE `:prefix:users` SET `total_notices_new`=`total_notices_new`-1 WHERE `user_id`=? LIMIT 1", array($notice['user_id']));
		}

		// Delete notices
		$retval = $this->db->query("DELETE FROM `:prefix:timeline_notices` WHERE `post_date`<?", array($timestamp));

		// Action hook
		hook::action('timeline/notices/cleanup');

		$this->cron_model->addLog('[Timeline] Cleaned up old timeline notifications.');

		return $retval;
	}
}
