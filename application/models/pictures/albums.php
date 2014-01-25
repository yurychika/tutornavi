<?php

class Pictures_Albums_Model extends Model
{
	public function saveAlbumData($albumID, $userID, $albumOld, $fields, $extra = array())
	{
		// Is this a new album?
		if ( !$albumID )
		{
			$extra['post_date'] = date_helper::now();
		}

		// Do we have user id?
		if ( $userID )
		{
			$extra['user_id'] = $userID;
		}

		// Save album
		if ( !( $newAlbumID = $this->fields_model->saveValues('picture_album', $albumID, $albumOld, $fields, $extra) ) )
		{
			return 0;
		}

		// Update counters
		if ( $userID )
		{
			// Is this a new album?
			if ( !$albumID )
			{
				$this->db->query("UPDATE `:prefix:users` SET `total_albums`=`total_albums`+1 WHERE `user_id`=? LIMIT 1", array($userID));
			}
		}

		// Did we add a new album or privacy setting changed?
		if ( !$albumID || $extra['privacy'] != $albumOld['privacy'] )
		{
			// Clean up counters
			$this->counters_model->deleteCounters('user', ( $albumID ? $albumOld['user_id'] : $userID ));
		}

		if ( $albumID )
		{
			// Update timeline action
			timeline_helper::update(true, 'picture_post', $albumOld['user_id'], $newAlbumID, false, $extra['privacy']);

			// Action hook
			hook::action('pictures/albums/update', $newAlbumID, $extra);
		}
		else
		{
			// Action hook
			hook::action('pictures/albums/insert', $newAlbumID, $extra);
		}

		return $newAlbumID;
	}

	public function updateCover($albumID, $pictureID)
	{
		$retval = $this->db->query("UPDATE `:prefix:pictures_albums_data` SET `picture_id`=? WHERE `album_id`=? LIMIT 1", array($pictureID, $albumID));

		// Action hook
		hook::action('pictures/albums/cover/update', $albumID, $pictureID);

		return $retval;
	}

	public function updateViews($albumID)
	{
		$retval = $this->db->query("UPDATE `:prefix:pictures_albums_data` SET `total_views`=`total_views`+1 WHERE `album_id`=? LIMIT 1", array($albumID));

		return $retval;
	}

	public function updateModifyDate($albumID)
	{
		$retval = $this->db->update('pictures_albums_data', array('modify_date' => date_helper::now()), array('album_id' => $albumID), 1);

		// Action hook
		hook::action('pictures/albums/date/update', $albumID);

		return $retval;
	}

	public function getAlbum($albumID, $fields = false, $params = array())
	{
		$params['select_columns'] = "`p`.`active` AS `picture_active`, `p`.`file_id`, `f`.`service_id` AS `file_service_id`, `f`.`path` AS `file_path`, `f`.`name` AS `file_name`, `f`.`extension` AS `file_ext`,
			`f`.`size` AS `file_size`, `f`.`post_date` AS `file_post_date`, `f`.`modify_date` AS `file_modify_date`";
		$params['join_tables'] = "LEFT JOIN `:prefix:pictures_data` AS `p` ON `a`.`picture_id`=`p`.`picture_id` LEFT JOIN `:prefix:storage_files` AS `f` ON `p`.`file_id`=`f`.`file_id`";
		$params['type_id'] = 1;

		$album = $this->fields_model->getRow('picture_album', $albumID, $fields, $params);

		return $album;
	}

	public function countAlbums($columns = array(), $items = array(), $params = array())
	{
		$params['count'] = true;

		$total = $this->getAlbums(false, $columns, $items, false, 0, $params);

		return $total;
	}

	public function getAlbums($fields = false, $columns = array(), $items = array(), $order = false, $limit = 15, $params = array())
	{
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

		// Do we need to count albums?
		if ( isset($params['count']) && $params['count'] )
		{
			$total = $this->fields_model->countRows('picture_album', ( !isset($params['select_users']) || $params['select_users'] ? true : false ), $columns, $items, $params);

			return $total;
		}

		$params['select_columns'] = "`p`.`active` AS `picture_active`, `p`.`file_id`, `f`.`service_id` AS `file_service_id`, `f`.`path` AS `file_path`, `f`.`name` AS `file_name`, `f`.`extension` AS `file_ext`,
			`f`.`size` AS `file_size`, `f`.`post_date` AS `file_post_date`, `f`.`modify_date` AS `file_modify_date`";
		$params['join_tables'] = "LEFT JOIN `:prefix:pictures_data` AS `p` ON `a`.`picture_id`=`p`.`picture_id` LEFT JOIN `:prefix:storage_files` AS `f` ON `p`.`file_id`=`f`.`file_id`";
		$params['type_id'] = 1; // fetch album fields

		// Get albums
		$albums = $this->fields_model->getRows('picture_album', ( !isset($params['select_users']) || $params['select_users'] ? true : false ), $fields, $columns, $items, $order, $limit, $params);

		return $albums;
	}

	public function deleteAlbum($albumID, $userID, $album)
	{
		// Load pictures model
		loader::model('pictures/pictures');

		// Delete pictures
		$this->pictures_model->deletePictures($albumID, $userID, $album);

		// Delete album
		$retval = $this->fields_model->deleteValues('picture_album', $albumID);
		if ( $retval )
		{
			// Update counters
			$this->db->query("UPDATE `:prefix:users` SET `total_albums`=`total_albums`-1 WHERE `user_id`=? LIMIT 1", array($userID));
			$this->db->query("UPDATE `:prefix:users` SET `total_pictures`=`total_pictures`-?, `total_pictures_i`=`total_pictures_i`-? WHERE `user_id`=? LIMIT 1", array($album['total_pictures'], $album['total_pictures_i'], $userID));

			// Delete reports
			loader::model('reports/reports');
			$this->reports_model->deleteReports('picture_album', $albumID);

			if ( $album['total_likes'] )
			{
				loader::model('comments/likes');
				$this->likes_model->deleteLikes('picture_album', $albumID, $album['total_likes']);
			}

			// Delete votes
			if ( $album['total_votes'] )
			{
				loader::model('comments/votes');
				$this->votes_model->deleteVotes('picture_album', $albumID, $album['total_votes']);
			}

			// Delete timeline action
			timeline_helper::delete('picture_post', $userID, $albumID);

			// Action hook
			hook::action('pictures/albums/delete', $albumID, $album);

			// Clean up counters
			$this->counters_model->deleteCounters('user', $userID);
		}

		return $retval;
	}

	public function deleteUser($userID, $user, $update = false)
	{
		// Load pictures model
		loader::model('pictures/pictures');

		// Get album IDs
		$result = $this->db->query("SELECT * FROM `:prefix:pictures_albums_data` WHERE `user_id`=? LIMIT ?", array($userID, $user['total_albums']))->result();
		foreach ( $result as $album )
		{
			// Delete pictures
			$this->pictures_model->deletePictures($album['album_id'], $userID, $album);
		}

		$retval = $this->fields_model->deleteValues('picture_album', $userID, $user['total_albums'], '', 'user_id');

		if ( $update )
		{
			// Update user counters
			$this->db->update('users', array('total_albums' => 0, 'total_pictures' => 0, 'total_pictures_i' => 0), array('user_id' => $userID), 1);
		}

		// Action hook
		hook::action('pictures/albums/delete_user', $userID, $user);

		return $retval;
	}

	public function getReportedActions()
	{
		$actions = array(
			'delete' => __('report_item_delete', 'reports'),
		);

		return $actions;
	}

	public function runReportedAction($albumID, $action)
	{
		$album = $this->pictures_albums_model->getAlbum($albumID);

		if ( $album )
		{
			if ( $action == 'delete' )
			{
				$this->deleteAlbum($albumID, $album['user_id'], $album);
			}
		}

		return true;
	}

	public function getReportedURL($albumID)
	{
		$url = 'cp/plugins/pictures/albums/edit/' . $albumID;

		return $url;
	}
}
