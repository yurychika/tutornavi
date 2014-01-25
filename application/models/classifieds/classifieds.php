<?php

class Classifieds_Classifieds_Model extends Model
{
	public function __construct()
	{
		parent::__construct();

		loader::helper('money');
	}

	public function saveAdData($adID, $userID, $adOld, $fields, $extra = array())
	{
		// Is this a new ad?
		if ( !$adID )
		{
			$extra['post_date'] = date_helper::now();
		}

		// Do we have user ID?
		if ( $userID )
		{
			$extra['active'] = session::permission('ads_approve', 'classifieds') ? 1 : 9;
			$extra['user_id'] = $userID;
		}

		// Save ad
		if ( !( $newAdID = $this->fields_model->saveValues('classified_ad', $adID, $adOld, $fields, $extra) ) )
		{
			return 0;
		}

		// Is this a new ad?
		if ( !$adID && $userID )
		{
			$column = $extra['active'] == 1 ? 'total_classifieds' : 'total_classifieds_i';
			$this->db->query("UPDATE `:prefix:users` SET `$column`=`$column`+1 WHERE `user_id`=? LIMIT 1", array($userID));
		}

		// Did ad status change?
		if ( $adID && $extra['active'] != $adOld['active'] )
		{
			// Did we approve this ad?
			if ( $extra['active'] == 1 )
			{
				$this->db->query("UPDATE `:prefix:users` SET `total_classifieds`=`total_classifieds`+1, `total_classifieds_i`=`total_classifieds_i`-1 WHERE `user_id`=? LIMIT 1", array($adOld['user_id']));
			}
			// Did we deactivate this ad?
			elseif ( $adID && $adOld['active'] == 1 )
			{
				$this->db->query("UPDATE `:prefix:users` SET `total_classifieds`=`total_classifieds`-1, `total_classifieds_i`=`total_classifieds_i`+1 WHERE `user_id`=? LIMIT 1", array($adOld['user_id']));
			}
		}

		if ( !$adID )
		{
			// Deduct credits?
			if ( config::item('credits_active', 'billing') && session::permission('ads_credits', 'classifieds') )
			{
				loader::model('billing/credits');
				$this->credits_model->removeCredits(session::item('user_id'), session::permission('ads_credits', 'classifieds'));
			}
		}

		if ( $adID )
		{
			// Update timeline action
			timeline_helper::update(true, 'classified_ad_post', $adOld['user_id'], $newAdID, $extra['active']);

			// Action hook
			hook::action('classifieds/update', $newAdID, $extra);
		}
		else
		{
			// Save timeline action
			if ( session::item('timeline_classified_post', 'config') === false || session::item('timeline_classified_post', 'config') )
			{
				timeline_helper::save('classified_ad_post', $userID, $newAdID, $extra['active']);
			}

			// Action hook
			hook::action('classifieds/insert', $newAdID, $extra);
		}

		return $newAdID;
	}

	public function updatePicture($adID, $pictureID)
	{
		$retval = $this->db->query("UPDATE `:prefix:classifieds_data` SET `picture_id`=? WHERE `ad_id`=? LIMIT 1", array($pictureID, $adID));

		// Action hook
		hook::action('classifieds/picture/update', $adID, $pictureID);

		return $retval;
	}

	public function updateViews($adID)
	{
		$retval = $this->db->query("UPDATE `:prefix:classifieds_data` SET `total_views`=`total_views`+1 WHERE `ad_id`=? LIMIT 1", array($adID));

		return $retval;
	}

	public function updateModifyDate($adID)
	{
		$retval = $this->db->update('classifieds_data', array('modify_date' => date_helper::now()), array('ad_id' => $adID), 1);

		// Action hook
		hook::action('classifieds/date/update', $adID);

		return $retval;
	}

	public function getAd($adID, $fields = false, $params = array())
	{
		$params['select_columns'] = "`p`.`active` AS `picture_active`, `p`.`file_id`, `f`.`service_id` AS `file_service_id`, `f`.`path` AS `file_path`, `f`.`name` AS `file_name`, `f`.`extension` AS `file_ext`,
			`f`.`size` AS `file_size`, `f`.`post_date` AS `file_post_date`, `f`.`modify_date` AS `file_modify_date`";
		$params['join_tables'] = "LEFT JOIN `:prefix:classifieds_pictures_data` AS `p` ON `a`.`picture_id`=`p`.`picture_id` LEFT JOIN `:prefix:storage_files` AS `f` ON `p`.`file_id`=`f`.`file_id`";
		$params['type_id'] = 0;

		$ad = $this->fields_model->getRow('classified_ad', $adID, $fields, $params);

		return $ad;
	}

	public function countAds($columns = array(), $items = array(), $params = array())
	{
		$params['count'] = true;

		$total = $this->getAds(false, $columns, $items, false, 0, $params);

		return $total;
	}

	public function getAds($fields = false, $columns = array(), $items = array(), $order = false, $limit = 15, $params = array())
	{
		// Set resource ID?
		$columns[] = '`a`.`resource_id`=' . ( isset($params['resource_id']) ? $params['resource_id'] : 1 );

		// Set custom ID?
		$columns[] = '`a`.`custom_id`=' . ( isset($params['custom_id']) ? $params['custom_id'] : 0 );

		// Do we need to count ads?
		if ( isset($params['count']) && $params['count'] )
		{
			$total = $this->fields_model->countRows('classified_ad', ( !isset($params['select_users']) || $params['select_users'] ? true : false ), $columns, $items, $params);

			return $total;
		}

		$params['select_columns'] = "`p`.`active` AS `picture_active`, `p`.`file_id`, `f`.`service_id` AS `file_service_id`, `f`.`path` AS `file_path`, `f`.`name` AS `file_name`, `f`.`extension` AS `file_ext`,
			`f`.`size` AS `file_size`, `f`.`post_date` AS `file_post_date`, `f`.`modify_date` AS `file_modify_date`";
		$params['join_tables'] = "LEFT JOIN `:prefix:classifieds_pictures_data` AS `p` ON `a`.`picture_id`=`p`.`picture_id` LEFT JOIN `:prefix:storage_files` AS `f` ON `p`.`file_id`=`f`.`file_id`";
		$params['type_id'] = 0;

		// Get ads
		$ads = $this->fields_model->getRows('classified_ad', ( !isset($params['select_users']) || $params['select_users'] ? true : false ), $fields, $columns, $items, $order, $limit, $params);

		return $ads;
	}

	public function toggleAdStatus($adID, $userID, $ad, $status = 1)
	{
		// Are you trying to assign the same status?
		if ( $ad['active'] == $status )
		{
			return true;
		}

		// Update status
		$this->db->query("UPDATE `:prefix:classifieds_data` SET `active`=? WHERE `ad_id`=? LIMIT 1", array($status, $adID));

		// Update counters
		if ( $status == 1 )
		{
			$this->db->query("UPDATE `:prefix:users` SET `total_classifieds`=`total_classifieds`+1, `total_classifieds_i`=`total_classifieds_i`-1 WHERE `user_id`=? LIMIT 1", array($userID));
		}
		elseif ( $ad['active'] != 9 )
		{
			$this->db->query("UPDATE `:prefix:users` SET `total_classifieds`=`total_classifieds`-1, `total_classifieds_i`=`total_classifieds_i`+1 WHERE `user_id`=? LIMIT 1", array($userID));
		}

		// Update timeline action
		timeline_helper::update(true, 'classified_ad_post', $ad['user_id'], $adID, $status);

		// Action hook
		hook::action('classifieds/status/update', $adID, $status);

		// Clean up counters
		$this->counters_model->deleteCounters('user', $userID);

		return true;
	}

	public function deleteAd($adID, $userID, $ad)
	{
		// Load pictures model
		loader::model('classifieds/pictures', array(), 'classifieds_pictures_model');

		// Delete pictures
		$this->classifieds_pictures_model->deletePictures($adID, $userID, $ad);

		// Delete ad
		$retval = $this->fields_model->deleteValues('classified_ad', $adID);
		if ( $retval )
		{
			// Update counters
			$column = $ad['active'] == 1 ? 'total_classifieds' : 'total_classifieds_i';
			$this->db->query("UPDATE `:prefix:users` SET `$column`=`$column`-1 WHERE `user_id`=? LIMIT 1", array($userID));

			// Delete reports
			loader::model('reports/reports');
			$this->reports_model->deleteReports('classified_ad', $adID);

			// Delete comments
			if ( $ad['total_comments'] )
			{
				loader::model('comments/comments');
				$this->comments_model->deleteComments('classified_ad', $adID, $ad['total_comments']);
			}

			// Delete likes
			if ( $ad['total_likes'] )
			{
				loader::model('comments/likes');
				$this->likes_model->deleteLikes('classified_ad', $adID, $ad['total_likes']);
			}

			// Delete votes
			if ( $ad['total_votes'] )
			{
				loader::model('comments/votes');
				$this->votes_model->deleteVotes('classified_ad', $adID, $ad['total_votes']);
			}

			// Clean up counters
			$this->counters_model->deleteCounters('user', $userID);

			// Delete timeline action
			timeline_helper::delete('classified_ad_post', $userID, $adID);

			// Action hook
			hook::action('classifieds/delete', $adID, $ad);
		}

		return $retval;
	}

	public function deleteUser($userID, $user, $update = false)
	{
		// Load pictures model
		loader::model('classifieds/pictures', array(), 'classifieds_pictures_model');

		// Get ad IDs
		$result = $this->db->query("SELECT * FROM `:prefix:classifieds_data` WHERE `user_id`=? LIMIT ?", array($userID, ( $user['total_classifieds'] + $user['total_classifieds_i'] )))->result();
		foreach ( $result as $ad )
		{
			// Delete pictures
			$this->classifieds_pictures_model->deletePictures($ad['ad_id'], $userID, $ad);
		}

		$retval = $this->fields_model->deleteValues('classified_ad', $userID, ( $user['total_classifieds'] + $user['total_classifieds_i'] ), '', 'user_id');

		if ( $update )
		{
			// Update user counters
			$this->db->update('users', array('total_classifieds' => 0, 'total_classifieds_i' => 0), array('user_id' => $userID), 1);
		}

		// Action hook
		hook::action('classifieds/delete_user', $userID, $user);

		return $retval;
	}

	public function getReportedActions()
	{
		$actions = array(
			'deactivate' => __('report_item_deactivate', 'reports'),
			'delete' => __('report_item_delete', 'reports'),
		);

		return $actions;
	}

	public function runReportedAction($adID, $action)
	{
		$ad = $this->classifieds_model->getAd($adID);

		if ( $ad )
		{
			if ( $action == 'deactivate' )
			{
				$this->toggleAdStatus($adID, $ad['user_id'], $ad, 0);
			}
			elseif ( $action == 'delete' )
			{
				$this->deleteAd($adID, $ad['user_id'], $ad);
			}
		}

		return true;
	}

	public function getReportedURL($adID)
	{
		$url = 'cp/plugins/classifieds/edit/' . $adID;

		return $url;
	}

	public function updateDbCounters()
	{
		$offset = uri::segment(6, 0);
		$section = uri::segment(7, 'classifieds');
		$step = 50;
		$next = $offset + $step;

		if ( $section == 'classifieds' )
		{
			// Count users
			$total = $this->db->query("SELECT COUNT(*) as `total_rows` FROM `:prefix:users`")->row();
			$total = $total['total_rows'];

			// Get users
			$users = $this->db->query("SELECT `user_id` FROM `:prefix:users` ORDER BY `user_id` LIMIT ?, ?", array($offset, $step))->result();

			foreach ( $users as $user )
			{
				// Classifieds
				$classifieds = array(
					'total_classifieds' => 0,
					'total_classifieds_i' => 0,
				);

				$items = $this->db->query("SELECT COUNT(*) as `total_rows`, `active` FROM `:prefix:classifieds_data` WHERE `user_id`=? AND `resource_id`=? GROUP BY `active`", array($user['user_id'], 1))->result();
				foreach ( $items as $item )
				{
					$classifieds['total_classifieds' . ( $item['active'] ? '' : '_i' )] = $item['total_rows'];
				}

				$this->db->update('users', $classifieds, array('user_id' => $user['user_id']), 1);
			}

			$result = array(
				'output' => __('progress_status', 'utilities_counters', array('%1' => ( $offset + $step < $total ? ( $offset + $step ) : $total ), '%2' => $total)),
				'redirect' => $next < $total ? $next : '0/pictures',
			);
		}
		else
		{
			// Count classifieds
			$total = $this->db->query("SELECT COUNT(*) as `total_rows` FROM `:prefix:classifieds_data`")->row();
			$total = $total['total_rows'];

			// Get classifieds
			$classifieds = $this->db->query("SELECT `ad_id` FROM `:prefix:classifieds_data` WHERE `resource_id`=? ORDER BY `ad_id` LIMIT ?, ?", array(1, $offset, $step))->result();

			foreach ( $classifieds as $classified )
			{
				// Pictures
				$pictures = array(
					'total_pictures' => 0,
					'total_pictures_i' => 0,
				);

				$items = $this->db->query("SELECT COUNT(*) as `total_rows`, `active` FROM `:prefix:classifieds_pictures_data` WHERE `ad_id`=? GROUP BY `active`", array($classified['ad_id']))->result();
				foreach ( $items as $item )
				{
					$pictures['total_pictures' . ( $item['active'] ? '' : '_i' )] = $item['total_rows'];
				}

				$this->db->update('classifieds_data', $pictures, array('ad_id' => $classified['ad_id']), 1);
			}

			$result = array(
				'output' => __('progress_status', 'utilities_counters', array('%1' => ( $offset + $step < $total ? ( $offset + $step ) : $total ), '%2' => $total)),
				'redirect' => $next < $total ? $next . '/pictures' : '',
			);
		}

		return $result;
	}
}
