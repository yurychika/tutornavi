<?php

class System_Requests_Model extends Model
{
	public function saveRequest($keyword, $userID, $itemID = 0, $value = '')
	{
		$data = array(
			'hash' => text_helper::random(16),
			'ip_address' => input::ipaddress(),
			'post_date' => date_helper::now(),
			'keyword' => $keyword,
			'user_id' => $userID,
			'item_id' => $itemID,
			'val' => $value,
		);

		$this->db->insert('core_requests', $data);

		return $data['hash'];
	}

	public function getRequest($keyword, $hash, $userID, $itemID = 0)
	{
		$request = $this->db->query("SELECT val FROM `:prefix:core_requests` WHERE `keyword`=? AND `hash`=? AND `user_id`=? AND `item_id`=? LIMIT 1", array($keyword, $hash, $userID, $itemID))->row();

		return $request;
	}

	public function deleteRequest($keyword, $hash, $userID, $itemID = 0)
	{
		$column = is_numeric($userID) ? 'user_id' : 'ip_address';

		$retval = $this->db->delete('core_requests', array('keyword' => $keyword, 'hash' => $hash, $column => $userID, 'item_id' => $itemID), 1);

		return $retval;
	}

	public function validateRequest($hash)
	{
		if ( strlen($hash) != 16 || preg_match('/[^a-zA-Z0-9]/', $hash) )
		{
			return false;
		}

		return true;
	}

	public function isRecentRequest($keyword, $userID, $itemID = 0, $delay = 5)
	{
		$time = date_helper::now() - 60*$delay;

		$column = is_numeric($userID) ? 'user_id' : 'ip_address';

		$request = $this->db->query("SELECT COUNT(*) as totalrows FROM `:prefix:core_requests` WHERE `keyword`=? AND `" . $column . "`=? AND `item_id`=? AND `post_date`>=? LIMIT 1",
			array($keyword, $userID, $itemID, $time))->row();

		return $request['totalrows'] ? true : false;
	}

	public function cleanup()
	{
		$this->db->query("DELETE FROM `:prefix:core_requests` WHERE `post_date`<?", array(date_helper::now() - 60*60*24));
		$this->cron_model->addLog('[System] Deleted expired security tokens.');
	}
}
