<?php

class Pictures_Pictures_Model extends Model
{
	public function savePictureFile($fileID, $albumID, $album, $extra = array())
	{
		// Basic picture data
		$picture = array(
			'file_id' => $fileID,
			'album_id' => $albumID,
			'user_id' => session::item('user_id'),
			'post_date' => date_helper::now(),
			'active' => session::permission('pictures_approve', 'pictures') ? 1 : 9,
			'order_id' => ( $album['total_pictures'] + $album['total_pictures_i'] ) + 1,
		);

		// Do we have extras?
		if ( $extra )
		{
			// Merge extras
			$picture = array_merge($picture, $extra);
		}

		// Save picture
		$pictureID = $this->db->insert('pictures_data', $picture);

		// Do we have picture ID?
		if ( $pictureID )
		{
			// Update album's counter
			$column = $picture['active'] == 1 ? 'total_pictures' : 'total_pictures_i';
			$this->db->query("UPDATE `:prefix:pictures_albums_data` SET `$column`=`$column`+1 WHERE `user_id`=? AND `album_id`=? LIMIT 1", array(session::item('user_id'), $albumID));
			$this->db->query("UPDATE `:prefix:users` SET `$column`=`$column`+1 WHERE `user_id`=? LIMIT 1", array(session::item('user_id')));

			// Does album have a cover?
			if ( !$album['picture_id'] )
			{
				// Update album cover
				$this->pictures_albums_model->updateCover($albumID, $pictureID);
			}

			// Did we have any activity in the past hour?
			if ( session::item('timeline_picture_post', 'config') === false || session::item('timeline_picture_post', 'config') )
			{
				if ( ( $action = timeline_helper::get('picture_post', session::item('user_id'), $albumID, 12) ) )
				{
					$counter = isset($action['params']['count']) ? ( $action['params']['count'] + 1 ) : 1;

					// Update activity
					timeline_helper::update($action['action_id'], 'picture_post', session::item('user_id'), $albumID, $picture['active'], false, array('count' => $counter), ( $action['attachments'] < 5 ? $fileID : false ));
				}
				else
				{
					// Save activity
					timeline_helper::save('picture_post', session::item('user_id'), $albumID, $picture['active'], $album['privacy'], array('count' => 1), $fileID);
				}
			}

			// Action hook
			hook::action('pictures/insert', $pictureID, $picture);
		}

		return $pictureID;
	}

	public function savePictureData($pictureID, $albumID, $pictureOld, $albumOld, $fields, $extra = array())
	{
		// Save picture
		if ( !( $pictureID = $this->fields_model->saveValues('picture', $pictureID, $pictureOld, $fields, $extra) ) )
		{
			return 0;
		}

		// Did picture status change?
		if ( $pictureID && isset($extra['active']) && $extra['active'] != $pictureOld['active'] )
		{
			// Did we approve this picture?
			if ( $extra['active'] == 1 )
			{
				$this->db->query("UPDATE `:prefix:pictures_albums_data` SET `total_pictures`=`total_pictures`+1, `total_pictures_i`=`total_pictures_i`-1 WHERE `album_id`=? LIMIT 1", array($pictureOld['album_id']));
				$this->db->query("UPDATE `:prefix:users` SET `total_pictures`=`total_pictures`+1, `total_pictures_i`=`total_pictures_i`-1 WHERE `user_id`=? LIMIT 1", array($pictureOld['user_id']));
			}
			// Did we deactivate this picture?
			elseif ( $pictureOld['active'] == 1 )
			{
				$this->db->query("UPDATE `:prefix:pictures_albums_data` SET `total_pictures`=`total_pictures`-1, `total_pictures_i`=`total_pictures_i`+1 WHERE `album_id`=? LIMIT 1", array($pictureOld['album_id']));
				$this->db->query("UPDATE `:prefix:users` SET `total_pictures`=`total_pictures`-1, `total_pictures_i`=`total_pictures_i`+1 WHERE `user_id`=? LIMIT 1", array($pictureOld['user_id']));
			}
		}

		// Update timeline action
		if ( isset($extra['active']) )
		{
			timeline_helper::update(true, 'picture_post', $albumOld['user_id'], $albumID, $extra['active']);
		}

		// Action hook
		hook::action('pictures/update', $pictureID, $extra);

		return $pictureID;
	}

	public function rotatePicture($pictureID, $angle = 90)
	{
		$files = $this->storage_model->getFiles($pictureID, 3, array('', 'x', 't'));

		if ( $retval = $this->storage_model->rotate($files['x'], $angle) )
		{
			$this->storage_model->resize($files['x'], config::item('picture_dimensions', 'pictures'), '', 'preserve', $files['']['file_id']);
			$this->storage_model->resize($files[''], config::item('picture_dimensions_t', 'pictures'), 't', 'crop', $files['t']['file_id']);
		}

		// Action hook
		hook::action('pictures/rotate', $pictureID);

		return $retval;
	}

	public function saveThumbnail($pictureID, $x, $y, $w, $h)
	{
		$files = $this->storage_model->getFiles($pictureID, 2, array('', 't'));

		$retval = $this->storage_model->thumbnail($files[''], $x, $y, $w, $h, config::item('picture_dimensions_t', 'pictures'), $files['t']['suffix'], $files['t']['file_id']);

		// Action hook
		hook::action('pictures/thumbnail', $pictureID);

		return $retval;
	}

	public function updateViews($pictureID)
	{
		$retval = $this->db->query("UPDATE `:prefix:pictures_data` SET `total_views`=`total_views`+1 WHERE `picture_id`=? LIMIT 1", array($pictureID));

		return $retval;
	}

	public function getPictureSiblings($userID, $albumID, $orderID, $totalPictures)
	{
		$previousPicture = $nextPicture = array();

		// Is this the first picture?
		if ( $orderID > 1 )
		{
			// Get previous picture
			$previousPicture = $this->db->query("SELECT `p`.`picture_id`, `p`.`data_description` FROM `:prefix:pictures_data` AS `p`
				WHERE `p`.`album_id`=? AND `p`.`order_id`<? " . ($userID != session::item('user_id') ? "AND `p`.`active`=1" : "") . "
				ORDER BY `order_id` DESC LIMIT 1", array($albumID, $orderID))->row();
		}

		// Is this the last picture?
		if ( $orderID < $totalPictures )
		{
			// Get next picture
			$nextPicture = $this->db->query("SELECT `p`.`picture_id`, `p`.`data_description` FROM `:prefix:pictures_data` AS `p`
				WHERE `p`.`album_id`=? AND `p`.`order_id`>? " . ($userID != session::item('user_id') ? "AND `p`.`active`=1" : "") . "
				ORDER BY `order_id` ASC LIMIT 1", array($albumID, $orderID))->row();
		}

		return array($previousPicture, $nextPicture);
	}

	public function getPicture($pictureID, $fields = false, $params = array())
	{
		$params['select_columns'] = "`f`.`service_id` AS `file_service_id`, `f`.`path` AS `file_path`, `f`.`name` AS `file_name`, `f`.`extension` AS `file_ext`, `f`.`width` AS `file_width`, `f`.`height` AS `file_height`,
			`f`.`size` AS `file_size`, `f`.`post_date` AS `file_post_date`, `f`.`modify_date` AS `file_modify_date`";
		$params['join_tables'] = "LEFT JOIN `:prefix:storage_files` AS `f` ON `p`.`file_id`=`f`.`file_id`";

		$picture = $this->fields_model->getRow('picture', $pictureID, $fields, $params);

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
		// Do we need to include albums?
		if ( isset($params['albums']) && $params['albums'] )
		{
			$params['join_tables'] = "INNER JOIN `:prefix:pictures_albums_data` AS `a` ON `a`.`album_id`=`p`.`album_id`";

			// Do we need to validate privacy settings?
			if ( isset($params['privacy']) && $params['privacy'] )
			{
				$friend = $this->users_friends_model->getFriend($params['privacy']);

				// Are users friends?
				if ( $friend )
				{
					$columns[] = '`a`.`privacy`<=3';
				}
				// Is user logged in?
				elseif ( users_helper::isLoggedin() )
				{
					$columns[] = '`a`.`privacy`<=2';
				}
				else
				{
					$columns[] = '`a`.`privacy`=1';
				}
			}

			// Set resource ID?
			$columns[] = '`a`.`resource_id`=' . ( isset($params['resource_id']) ? $params['resource_id'] : 1 );

			// Set custom ID?
			$columns[] = '`a`.`custom_id`=' . ( isset($params['custom_id']) ? $params['custom_id'] : 0 );
		}

		// Do we need to count pictures?
		if ( isset($params['count']) && $params['count'] )
		{
			$total = $this->fields_model->countRows('picture', ( !isset($params['select_users']) || $params['select_users'] ? true : false ), $columns, $items, $params);

			return $total;
		}

		$params['select_columns'] = "`f`.`service_id` AS `file_service_id`, `f`.`path` AS `file_path`, `f`.`name` AS `file_name`, `f`.`extension` AS `file_ext`, `f`.`width` AS `file_width`, `f`.`height` AS `file_height`,
			`f`.`size` AS `file_size`, `f`.`post_date` AS `file_post_date`, `f`.`modify_date` AS `file_modify_date`";
		$params['join_tables'] = ( isset($params['join_tables']) ? $params['join_tables'] . ' ' : '' ) . "LEFT JOIN `:prefix:storage_files` AS `f` ON `p`.`file_id`=`f`.`file_id`";

		// Get pictures
		$pictures = $this->fields_model->getRows('picture', ( !isset($params['select_users']) || $params['select_users'] ? true : false ), $fields, $columns, $items, $order, $limit, $params);

		return $pictures;
	}

	public function togglePictureStatus($pictureID, $albumID, $userID, $picture, $album, $status = 1)
	{
		// Are you trying to assign the same status?
		if ( $picture['active'] == $status )
		{
			return true;
		}

		// Update status
		$this->db->query("UPDATE `:prefix:pictures_data` SET `active`=? WHERE `picture_id`=? LIMIT 1", array($status, $pictureID));

		// Update counters
		if ( $status == 1 )
		{
			$this->db->query("UPDATE `:prefix:pictures_albums_data` SET `total_pictures`=`total_pictures`+1, `total_pictures_i`=`total_pictures_i`-1 WHERE `album_id`=? LIMIT 1", array($albumID));
			$this->db->query("UPDATE `:prefix:users` SET `total_pictures`=`total_pictures`+1, `total_pictures_i`=`total_pictures_i`-1 WHERE `user_id`=? LIMIT 1", array($userID));
		}
		elseif ( $picture['active'] != 9 )
		{
			$this->db->query("UPDATE `:prefix:pictures_albums_data` SET `total_pictures`=`total_pictures`-1, `total_pictures_i`=`total_pictures_i`+1 WHERE `album_id`=? LIMIT 1", array($albumID));
			$this->db->query("UPDATE `:prefix:users` SET `total_pictures`=`total_pictures`-1, `total_pictures_i`=`total_pictures_i`+1 WHERE `user_id`=? LIMIT 1", array($userID));
		}

		// Update timeline action
		timeline_helper::update(true, 'picture_post', $userID, $albumID, $status);

		// Action hook
		hook::action('pictures/status/update', $pictureID, $albumID, $status);

		return true;
	}

	public function deletePicture($pictureID, $albumID, $userID, $picture, $album)
	{
		// Delete picture
		$retval = $this->fields_model->deleteValues('picture', $pictureID);
		if ( $retval )
		{
			// Delete files
			$this->storage_model->deleteFiles($picture['file_id'], 3);

			// Update counters
			$column = $picture['active'] == 1 ? 'total_pictures' : 'total_pictures_i';
			$this->db->query("UPDATE `:prefix:pictures_albums_data` SET `$column`=`$column`-1 WHERE `album_id`=? LIMIT 1", array($albumID));
			$this->db->query("UPDATE `:prefix:users` SET `$column`=`$column`-1 WHERE `user_id`=? LIMIT 1", array($userID));
			$this->db->query("UPDATE `:prefix:pictures_data` SET `order_id`=`order_id`-1 WHERE `album_id`=? AND `order_id`>? LIMIT ?",
				array($albumID, $picture['order_id'], ( $album['total_pictures'] + $album['total_pictures_i'] - $picture['order_id'] )));

			// Is this a cover picture?
			if ( $album['picture_id'] && $album['picture_id'] == $pictureID )
			{
				$this->pictures_albums_model->updateCover($albumID, 0);
			}

			// Delete reports
			loader::model('reports/reports');
			$this->reports_model->deleteReports('picture', $pictureID);

			// Delete comments
			if ( $picture['total_comments'] )
			{
				loader::model('comments/comments');
				$this->comments_model->deleteComments('picture', $pictureID, $picture['total_comments']);
			}

			// Delete likes
			if ( $picture['total_likes'] )
			{
				loader::model('comments/likes');
				$this->likes_model->deleteLikes('picture', $pictureID, $picture['total_likes']);
			}

			// Delete votes
			if ( $picture['total_votes'] )
			{
				loader::model('comments/votes');
				$this->votes_model->deleteVotes('picture', $pictureID, $picture['total_votes']);
			}

			// Delete timeline action
			timeline_helper::delete('picture_post', $userID, $albumID);

			// Action hook
			hook::action('pictures/delete', $pictureID, $picture);
		}

		return $retval;
	}

	public function deletePictures($albumID, $userID, $album)
	{
		// Do we have any pictures?
		if ( !( $album['total_pictures'] + $album['total_pictures_i'] ) )
		{
			return true;
		}

		$pictureIDs = $fileIDs = array();

		// Get picture and file IDs
		$result = $this->db->query("SELECT `picture_id`, `file_id` FROM `:prefix:pictures_data` WHERE `album_id`=? LIMIT ?", array($albumID, ( $album['total_pictures'] + $album['total_pictures_i'] )))->result();
		foreach ( $result as $picture )
		{
			$pictureIDs[] = $picture['picture_id'];
			$fileIDs[] = $picture['file_id'];
		}

		// Delete pictures
		if ( $pictureIDs )
		{
			$retval = $this->fields_model->deleteValues('picture', $albumID, ( $album['total_pictures'] + $album['total_pictures_i'] ), '', 'album_id');

			// Delete pictures
			if ( $retval )
			{
				// Delete files
				$this->storage_model->deleteFiles($fileIDs, 3);

				// Delete reports
				loader::model('reports/reports');
				$this->reports_model->deleteReports('picture', $pictureIDs);

				// Delete comments
				loader::model('comments/comments');
				$this->comments_model->deleteComments('picture', $pictureIDs);

				// Delete likes
				loader::model('comments/likes');
				$this->likes_model->deleteLikes('picture', $pictureIDs);

				// Delete votes
				loader::model('comments/votes');
				$this->votes_model->deleteVotes('picture', $pictureIDs);
			}
		}

		// Action hook
		hook::action('pictures/delete_multiple', $pictureIDs, $album);

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
		loader::model('pictures/albums', array(), 'pictures_albums_model');

		$picture = $this->getPicture($pictureID);

		if ( $picture )
		{
			$album = $this->pictures_albums_model->getAlbum($picture['album_id']);

			if ( $album )
			{
				if ( $action == 'deactivate' )
				{
					$this->togglePictureStatus($pictureID, $picture['album_id'], $picture['user_id'], $picture, $album, 0);
				}
				elseif ( $action == 'delete' )
				{
					$this->deletePicture($pictureID, $picture['album_id'], $picture['user_id'], $picture, $album);
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
			$url = 'cp/plugins/pictures/edit/' . $picture['album_id'] . '/' . $pictureID;
		}

		return $url;
	}

	public function updateDbCounters()
	{
		$offset = uri::segment(6, 0);
		$section = uri::segment(7, 'pictures');
		$step = 50;
		$next = $offset + $step;

		if ( $section == 'pictures' )
		{
			// Count users
			$total = $this->db->query("SELECT COUNT(*) as `total_rows` FROM `:prefix:users`")->row();
			$total = $total['total_rows'];

			// Get users
			$users = $this->db->query("SELECT `user_id` FROM `:prefix:users` ORDER BY `user_id` LIMIT ?, ?", array($offset, $step))->result();

			foreach ( $users as $user )
			{
				// Pictures
				$pictures = array(
					'total_albums' => 0,
					'total_pictures' => 0,
					'total_pictures_i' => 0,
				);

				$item = $this->db->query("SELECT COUNT(*) as `total_rows` FROM `:prefix:pictures_albums_data` WHERE `user_id`=? AND `resource_id`=?", array($user['user_id'], 1))->row();
				$pictures['total_albums'] = $item['total_rows'];

				$items = $this->db->query("SELECT COUNT(*) as `total_rows`, `active` FROM `:prefix:pictures_data` WHERE `user_id`=? GROUP BY `active`", array($user['user_id']))->result();
				foreach ( $items as $item )
				{
					$pictures['total_pictures' . ( $item['active'] ? '' : '_i' )] = $item['total_rows'];
				}

				$this->db->update('users', $pictures, array('user_id' => $user['user_id']), 1);
			}

			$result = array(
				'output' => __('progress_status', 'utilities_counters', array('%1' => ( $offset + $step < $total ? ( $offset + $step ) : $total ), '%2' => $total)),
				'redirect' => $next < $total ? $next : '0/albums',
			);
		}
		else
		{
			// Count albums
			$total = $this->db->query("SELECT COUNT(*) as `total_rows` FROM `:prefix:pictures_albums_data`")->row();
			$total = $total['total_rows'];

			// Get albums
			$albums = $this->db->query("SELECT `album_id` FROM `:prefix:pictures_albums_data` WHERE `resource_id`=? ORDER BY `album_id` LIMIT ?, ?", array(1, $offset, $step))->result();

			foreach ( $albums as $album )
			{
				// Pictures
				$pictures = array(
					'total_pictures' => 0,
					'total_pictures_i' => 0,
				);

				$items = $this->db->query("SELECT COUNT(*) as `total_rows`, `active` FROM `:prefix:pictures_data` WHERE `album_id`=? GROUP BY `active`", array($album['album_id']))->result();
				foreach ( $items as $item )
				{
					$pictures['total_pictures' . ( $item['active'] ? '' : '_i' )] = $item['total_rows'];
				}

				$this->db->update('pictures_albums_data', $pictures, array('album_id' => $album['album_id']), 1);
			}

			$result = array(
				'output' => __('progress_status', 'utilities_counters', array('%1' => ( $offset + $step < $total ? ( $offset + $step ) : $total ), '%2' => $total)),
				'redirect' => $next < $total ? $next . '/albums' : '',
			);
		}

		return $result;
	}
}
