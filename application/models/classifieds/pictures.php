<?php

class Classifieds_Pictures_Model extends Model
{
	public function savePictureFile($fileID, $adID, $ad, $extra = array())
	{
		// Basic picture data
		$picture = array(
			'file_id' => $fileID,
			'ad_id' => $adID,
			'user_id' => session::item('user_id'),
			'post_date' => date_helper::now(),
			'active' => session::permission('pictures_approve', 'classifieds') ? 1 : 9,
			'order_id' => ( $ad['total_pictures'] + $ad['total_pictures_i'] ) + 1,
		);

		// Do we have extras?
		if ( $extra )
		{
			// Merge extras
			$picture = array_merge($picture, $extra);
		}

		// Save picture
		$pictureID = $this->db->insert('classifieds_pictures_data', $picture);

		// Do we have picture ID?
		if ( $pictureID )
		{
			// Update album's counter
			$column = $picture['active'] == 1 ? 'total_pictures' : 'total_pictures_i';
			$this->db->query("UPDATE `:prefix:classifieds_data` SET `$column`=`$column`+1 WHERE `user_id`=? AND `ad_id`=? LIMIT 1", array(session::item('user_id'), $adID));

			// Does album have a cover?
			if ( !$ad['picture_id'] )
			{
				// Update ad cover
				$this->classifieds_model->updatePicture($adID, $pictureID);
			}

			// Action hook
			hook::action('classifieds/pictures/insert', $pictureID, $picture);
		}

		return $pictureID;
	}

	public function savePictureData($pictureID, $adID, $pictureOld, $adOld, $fields, $extra = array())
	{
		// Save picture
		if ( !( $pictureID = $this->fields_model->saveValues('classified_picture', $pictureID, $pictureOld, $fields, $extra) ) )
		{
			return 0;
		}

		// Did picture status change?
		if ( $pictureID && isset($extra['active']) && $extra['active'] != $pictureOld['active'] )
		{
			// Did we approve this picture?
			if ( $extra['active'] == 1 )
			{
				$this->db->query("UPDATE `:prefix:classifieds_data` SET `total_pictures`=`total_pictures`+1, `total_pictures_i`=`total_pictures_i`-1 WHERE `ad_id`=? LIMIT 1", array($pictureOld['ad_id']));
			}
			// Did we deactivate this picture?
			elseif ( $pictureOld['active'] == 1 )
			{
				$this->db->query("UPDATE `:prefix:classifieds_data` SET `total_pictures`=`total_pictures`-1, `total_pictures_i`=`total_pictures_i`+1 WHERE `ad_id`=? LIMIT 1", array($pictureOld['ad_id']));
			}
		}

		// Action hook
		hook::action('classifieds/pictures/update', $pictureID, $extra);

		return $pictureID;
	}

	public function rotatePicture($pictureID, $angle = 90)
	{
		$files = $this->storage_model->getFiles($pictureID, 3, array('', 'x', 't'));

		if ( $retval = $this->storage_model->rotate($files['x'], $angle) )
		{
			$this->storage_model->resize($files['x'], config::item('picture_dimensions', 'classifieds'), '', 'preserve', $files['']['file_id']);
			$this->storage_model->resize($files[''], config::item('picture_dimensions_t', 'classifieds'), 't', 'crop', $files['t']['file_id']);
		}

		// Action hook
		hook::action('classifieds/pictures/rotate', $pictureID);

		return $retval;
	}

	public function saveThumbnail($pictureID, $x, $y, $w, $h)
	{
		$files = $this->storage_model->getFiles($pictureID, 2, array('', 't'));

		$retval = $this->storage_model->thumbnail($files[''], $x, $y, $w, $h, config::item('picture_dimensions_t', 'classifieds'), $files['t']['suffix'], $files['t']['file_id']);

		// Action hook
		hook::action('classifieds/pictures/thumbnail', $pictureID);

		return $retval;
	}

	public function getPictureSiblings($userID, $adID, $orderID, $totalPictures)
	{
		$previousPicture = $nextPicture = array();

		// Is this the first picture?
		if ( $orderID > 1 )
		{
			// Get previous picture
			$previousPicture = $this->db->query("SELECT `p`.`picture_id`, `p`.`data_description` FROM `:prefix:classifieds_pictures_data` AS `p`
				WHERE `p`.`ad_id`=? AND `p`.`order_id`<? " . ($userID != session::item('user_id') ? "AND `p`.`active`=1" : "") . "
				ORDER BY `order_id` DESC LIMIT 1", array($adID, $orderID))->row();
		}

		// Is this the last picture?
		if ( $orderID < $totalPictures )
		{
			// Get next picture
			$nextPicture = $this->db->query("SELECT `p`.`picture_id`, `p`.`data_description` FROM `:prefix:classifieds_pictures_data` AS `p`
				WHERE `p`.`ad_id`=? AND `p`.`order_id`>? " . ($userID != session::item('user_id') ? "AND `p`.`active`=1" : "") . "
				ORDER BY `order_id` ASC LIMIT 1", array($adID, $orderID))->row();
		}

		return array($previousPicture, $nextPicture);
	}

	public function updateViews($pictureID)
	{
		$retval = $this->db->query("UPDATE `:prefix:classifieds_pictures_data` SET `total_views`=`total_views`+1 WHERE `picture_id`=? LIMIT 1", array($pictureID));

		return $retval;
	}

	public function getPicture($pictureID, $fields = false, $params = array())
	{
		$params['select_columns'] = "`f`.`service_id` AS `file_service_id`, `f`.`path` AS `file_path`, `f`.`name` AS `file_name`, `f`.`extension` AS `file_ext`, `f`.`width` AS `file_width`, `f`.`height` AS `file_height`,
			`f`.`size` AS `file_size`, `f`.`post_date` AS `file_post_date`, `f`.`modify_date` AS `file_modify_date`";
		$params['join_tables'] = "LEFT JOIN `:prefix:storage_files` AS `f` ON `p`.`file_id`=`f`.`file_id`";
		$params['type_id'] = 1;

		$picture = $this->fields_model->getRow('classified_picture', $pictureID, $fields, $params);

		return $picture;
	}

	public function countPictures($columns = array(), $items = array(), $params = array())
	{
		$params['count'] = 1;

		$total = $this->getPictures(false, $columns, $items, false, 0, $params);

		return $total;
	}

	public function getPictures($fields = false, $columns = array(), $items = array(), $order = false, $limit = 15, $params = array())
	{
		// Do we need to count pictures?
		if ( isset($params['count']) && $params['count'] )
		{
			$total = $this->fields_model->countRows('classified_picture', ( !isset($params['select_users']) || $params['select_users'] ? true : false ), $columns, $items, $params);

			return $total;
		}

		$params['select_columns'] = "`f`.`service_id` AS `file_service_id`, `f`.`path` AS `file_path`, `f`.`name` AS `file_name`, `f`.`extension` AS `file_ext`, `f`.`width` AS `file_width`, `f`.`height` AS `file_height`,
			`f`.`size` AS `file_size`, `f`.`post_date` AS `file_post_date`, `f`.`modify_date` AS `file_modify_date`";
		$params['join_tables'] = "INNER JOIN `:prefix:classifieds_data` AS `a` ON `a`.`ad_id`=`p`.`ad_id` LEFT JOIN `:prefix:storage_files` AS `f` ON `p`.`file_id`=`f`.`file_id`";
		$params['type_id'] = 1;

		// Get pictures
		$pictures = $this->fields_model->getRows('classified_picture', ( !isset($params['select_users']) || $params['select_users'] ? true : false ), $fields, $columns, $items, $order, $limit, $params);

		return $pictures;
	}

	public function togglePictureStatus($pictureID, $adID, $userID, $picture, $ad, $status = 1)
	{
		// Are you trying to assign the same status?
		if ( $picture['active'] == $status )
		{
			return true;
		}

		// Update status
		$this->db->query("UPDATE `:prefix:classifieds_pictures_data` SET `active`=? WHERE `picture_id`=? LIMIT 1", array($status, $pictureID));

		// Update counters
		if ( $status == 1 )
		{
			$this->db->query("UPDATE `:prefix:classifieds_data` SET `total_pictures`=`total_pictures`+1, `total_pictures_i`=`total_pictures_i`-1 WHERE `ad_id`=? LIMIT 1", array($adID));
		}
		elseif ( $picture['active'] != 9 )
		{
			$this->db->query("UPDATE `:prefix:classifieds_data` SET `total_pictures`=`total_pictures`-1, `total_pictures_i`=`total_pictures_i`+1 WHERE `ad_id`=? LIMIT 1", array($adID));
		}

		// Action hook
		hook::action('classifieds/pictures/status/update', $pictureID, $adID, $status);

		return true;
	}

	public function deletePicture($pictureID, $adID, $userID, $picture, $ad)
	{
		// Delete picture
		$retval = $this->fields_model->deleteValues('classified_picture', $pictureID);
		if ( $retval )
		{
			// Delete files
			$this->storage_model->deleteFiles($picture['file_id'], 3);

			// Update counters
			$column = $picture['active'] == 1 ? 'total_pictures' : 'total_pictures_i';
			$this->db->query("UPDATE `:prefix:classifieds_data` SET `$column`=`$column`-1 WHERE `ad_id`=? LIMIT 1", array($adID));
			$this->db->query("UPDATE `:prefix:classifieds_pictures_data` SET `order_id`=`order_id`-1 WHERE `ad_id`=? AND `order_id`>? LIMIT ?",
				array($adID, $picture['order_id'], ( $ad['total_pictures'] + $ad['total_pictures_i'] - $picture['order_id'] )));

			// Is this a cover picture?
			if ( $ad['picture_id'] && $ad['picture_id'] == $pictureID )
			{
				$this->classifieds_model->updatePicture($adID, 0);
			}

			// Delete reports
			loader::model('reports/reports');
			$this->reports_model->deleteReports('classified_picture', $pictureID);

			// Action hook
			hook::action('classifieds/pictures/delete', $pictureID, $picture);
		}

		return $retval;
	}

	public function deletePictures($adID, $userID, $ad)
	{
		// Do we have any pictures?
		if ( !( $ad['total_pictures'] + $ad['total_pictures_i'] ) )
		{
			return true;
		}

		$pictureIDs = $fileIDs = array();

		// Get picture and file IDs
		$result = $this->db->query("SELECT `picture_id`, `file_id` FROM `:prefix:classifieds_pictures_data` WHERE `ad_id`=? LIMIT ?", array($adID, ( $ad['total_pictures'] + $ad['total_pictures_i'] )))->result();
		foreach ( $result as $picture )
		{
			$pictureIDs[] = $picture['picture_id'];
			$fileIDs[] = $picture['file_id'];
		}

		// Delete pictures
		if ( $pictureIDs )
		{
			$retval = $this->fields_model->deleteValues('classified_picture', $adID, ( $ad['total_pictures'] + $ad['total_pictures_i'] ), '', 'ad_id');

			// Delete pictures
			if ( $retval )
			{
				// Delete files
				$this->storage_model->deleteFiles($fileIDs, 3);

				// Delete reports
				loader::model('reports/reports');
				$this->reports_model->deleteReports('classified_picture', $pictureIDs);
			}
		}

		// Action hook
		hook::action('classifieds/pictures/delete_multiple', $pictureIDs, $ad);

		return true;
	}

	public function getReportedActions()
	{
		$actions = array(
			'deactivate' => __('report_item_deactivate', 'reports'),
			'delete' => __('report_item_delete', 'reports'),
		);

		return $actions;
	}

	public function runReportedAction($pictureID, $action)
	{
		loader::model('classifieds');

		$picture = $this->getPicture($pictureID);

		if ( $picture )
		{
			$ad = $this->classifieds_model->getAd($picture['ad_id']);

			if ( $ad )
			{
				if ( $action == 'deactivate' )
				{
					$this->togglePictureStatus($pictureID, $picture['ad_id'], $picture['user_id'], $picture, $ad, 0);
				}
				elseif ( $action == 'delete' )
				{
					$this->deletePicture($pictureID, $picture['ad_id'], $picture['user_id'], $picture, $ad);
				}
			}
		}

		return true;
	}

	public function getReportedURL($pictureID)
	{
		$url = '';

		$picture = $this->getPicture($pictureID);

		if ( $picture )
		{
			$url = 'cp/plugins/classifieds/pictures/edit/' . $picture['ad_id'] . '/' . $pictureID;
		}

		return $url;
	}
}
