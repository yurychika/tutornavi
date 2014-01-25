<?php

class Blogs_Blogs_Model extends Model
{
	public function saveBlogData($blogID, $userID, $blogOld, $fields, $extra = array())
	{
		// Is this a new blog?
		if ( !$blogID )
		{
			$extra['post_date'] = date_helper::now();
		}

		// Do we have user ID?
		if ( $userID )
		{
			$extra['active'] = session::permission('blogs_approve', 'blogs') ? 1 : 9;
			$extra['user_id'] = $userID;
		}

		// Save blog
		if ( !( $newBlogID = $this->fields_model->saveValues('blog', $blogID, $blogOld, $fields, $extra) ) )
		{
			return 0;
		}

		// Is this a new blog?
		if ( !$blogID && $userID )
		{
			$column = $extra['active'] == 1 ? 'total_blogs' : 'total_blogs_i';
			$this->db->query("UPDATE `:prefix:users` SET `$column`=`$column`+1 WHERE `user_id`=? LIMIT 1", array($userID));
		}

		// Did blog status change?
		if ( $blogID && $extra['active'] != $blogOld['active'] )
		{
			// Did we approve this blog?
			if ( $extra['active'] == 1 )
			{
				$this->db->query("UPDATE `:prefix:users` SET `total_blogs`=`total_blogs`+1, `total_blogs_i`=`total_blogs_i`-1 WHERE `user_id`=? LIMIT 1", array($blogOld['user_id']));
			}
			// Did we deactivate this blog?
			elseif ( $blogID && $blogOld['active'] == 1 )
			{
				$this->db->query("UPDATE `:prefix:users` SET `total_blogs`=`total_blogs`-1, `total_blogs_i`=`total_blogs_i`+1 WHERE `user_id`=? LIMIT 1", array($blogOld['user_id']));
			}
		}

		// Did we add a new blog or privacy setting changed?
		if ( !$blogID || $extra['privacy'] != $blogOld['privacy'] )
		{
			// Clean up counters
			$this->counters_model->deleteCounters('user', ( $blogID ? $blogOld['user_id'] : $userID ));
		}

		if ( $blogID )
		{
			// Update timeline action
			timeline_helper::update(true, 'blog_post', $blogOld['user_id'], $newBlogID, $extra['active'], $extra['privacy']);

			// Action hook
			hook::action('blogs/update', $newBlogID, $extra);
		}
		else
		{
			// Save timeline action
			if ( session::item('timeline_blog_post', 'config') === false || session::item('timeline_blog_post', 'config') )
			{
				timeline_helper::save('blog_post', $userID, $newBlogID, $extra['active'], $extra['privacy']);
			}

			// Action hook
			hook::action('blogs/insert', $newBlogID, $extra);
		}

		return $newBlogID;
	}

	public function updateViews($blogID)
	{
		$retval = $this->db->query("UPDATE `:prefix:blogs_data` SET `total_views`=`total_views`+1 WHERE `blog_id`=? LIMIT 1", array($blogID));

		return $retval;
	}

	public function getBlog($blogID, $fields = false, $params = array())
	{
		$blog = $this->fields_model->getRow('blog', $blogID, $fields, $params);

		return $blog;
	}

	public function countBlogs($columns = array(), $items = array(), $params = array())
	{
		$params['count'] = true;

		$total = $this->getBlogs(false, $columns, $items, false, 0, $params);

		return $total;
	}

	public function getBlogs($fields = false, $columns = array(), $items = array(), $order = false, $limit = 15, $params = array())
	{
		// Do we need to validate privacy settings?
		if ( isset($params['privacy']) && $params['privacy'] )
		{
			$friend = $this->users_friends_model->getFriend($params['privacy']);

			// Are users friends?
			if ( $friend )
			{
				$columns[] = '`b`.`privacy`<=3';
			}
			// Is user logged in?
			elseif ( users_helper::isLoggedin() )
			{
				$columns[] = '`b`.`privacy`<=2';
			}
			else
			{
				$columns[] = '`b`.`privacy`=1';
			}
		}

		// Set resource ID?
		$columns[] = '`b`.`resource_id`=' . ( isset($params['resource_id']) ? $params['resource_id'] : 1 );

		// Set custom ID?
		$columns[] = '`b`.`custom_id`=' . ( isset($params['custom_id']) ? $params['custom_id'] : 0 );

		// Do we need to count blogs?
		if ( isset($params['count']) && $params['count'] )
		{
			$total = $this->fields_model->countRows('blog', ( !isset($params['select_users']) || $params['select_users'] ? true : false ), $columns, $items, $params);

			return $total;
		}

		// Get blogs
		$blogs = $this->fields_model->getRows('blog', ( !isset($params['select_users']) || $params['select_users'] ? true : false ), $fields, $columns, $items, $order, $limit, $params);

		return $blogs;
	}

	public function toggleBlogStatus($blogID, $userID, $blog, $status = 1)
	{
		// Are you trying to assign the same status?
		if ( $blog['active'] == $status )
		{
			return true;
		}

		// Update status
		$this->db->query("UPDATE `:prefix:blogs_data` SET `active`=? WHERE `blog_id`=? LIMIT 1", array($status, $blogID));

		// Update counters
		if ( $status == 1 )
		{
			$this->db->query("UPDATE `:prefix:users` SET `total_blogs`=`total_blogs`+1, `total_blogs_i`=`total_blogs_i`-1 WHERE `user_id`=? LIMIT 1", array($userID));
		}
		elseif ( $blog['active'] != 9 )
		{
			$this->db->query("UPDATE `:prefix:users` SET `total_blogs`=`total_blogs`-1, `total_blogs_i`=`total_blogs_i`+1 WHERE `user_id`=? LIMIT 1", array($userID));
		}

		// Update timeline action
		timeline_helper::update(true, 'blog_post', $blog['user_id'], $blogID, $status);

		// Action hook
		hook::action('blogs/status/update', $blogID, $status);

		// Clean up counters
		$this->counters_model->deleteCounters('user', $userID);

		return true;
	}

	public function deleteBlog($blogID, $userID, $blog)
	{
		// Delete blog
		$retval = $this->fields_model->deleteValues('blog', $blogID);
		if ( $retval )
		{
			// Update counters
			$column = $blog['active'] == 1 ? 'total_blogs' : 'total_blogs_i';
			$this->db->query("UPDATE `:prefix:users` SET `$column`=`$column`-1 WHERE `user_id`=? LIMIT 1", array($userID));

			// Delete reports
			loader::model('reports/reports');
			$this->reports_model->deleteReports('blog', $blogID);

			// Delete comments
			if ( $blog['total_comments'] )
			{
				loader::model('comments/comments');
				$this->comments_model->deleteComments('blog', $blogID, $blog['total_comments']);
			}

			// Delete likes
			if ( $blog['total_likes'] )
			{
				loader::model('comments/likes');
				$this->likes_model->deleteLikes('blog', $blogID, $blog['total_likes']);
			}

			// Delete votes
			if ( $blog['total_votes'] )
			{
				loader::model('comments/votes');
				$this->votes_model->deleteVotes('blog', $blogID, $blog['total_votes']);
			}

			// Clean up counters
			$this->counters_model->deleteCounters('user', $userID);

			// Delete timeline action
			timeline_helper::delete('blog_post', $userID, $blogID);

			// Action hook
			hook::action('blogs/delete', $blogID, $blog);
		}

		return $retval;
	}

	public function deleteUser($userID, $user, $update = false)
	{
		$retval = $this->fields_model->deleteValues('blog', $userID, ( $user['total_blogs'] + $user['total_blogs_i'] ), '', 'user_id');

		if ( $update )
		{
			// Update user counters
			$this->db->update('users', array('total_blogs' => 0, 'total_blogs_i' => 0), array('user_id' => $userID), 1);
		}

		// Action hook
		hook::action('blogs/delete_user', $userID, $user);

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

	public function runReportedAction($blogID, $action)
	{
		$blog = $this->getBlog($blogID);

		if ( $blog )
		{
			if ( $action == 'deactivate' )
			{
				$this->toggleBlogStatus($blogID, $blog['user_id'], $blog, 0);
			}
			elseif ( $action == 'delete' )
			{
				$this->deleteBlog($blogID, $blog['user_id'], $blog);
			}
		}

		return true;
	}

	public function getReportedURL($blogID)
	{
		$url = 'cp/plugins/blogs/edit/' . $blogID;

		return $url;
	}

	public function updateDbCounters()
	{
		$offset = uri::segment(6, 0);
		$step = 50;
		$next = $offset + $step;

		// Count users
		$total = $this->db->query("SELECT COUNT(*) as `total_rows` FROM `:prefix:users`")->row();
		$total = $total['total_rows'];

		// Get users
		$users = $this->db->query("SELECT `user_id` FROM `:prefix:users` ORDER BY `user_id` LIMIT ?, ?", array($offset, $step))->result();

		foreach ( $users as $user )
		{
			// Blogs
			$blogs = array(
				'total_blogs' => 0,
				'total_blogs_i' => 0,
			);

			$items = $this->db->query("SELECT COUNT(*) as `total_rows`, `active` FROM `:prefix:blogs_data` WHERE `user_id`=? AND `resource_id`=? GROUP BY `active`", array($user['user_id'], 1))->result();
			foreach ( $items as $item )
			{
				$blogs['total_blogs' . ( $item['active'] ? '' : '_i' )] = $item['total_rows'];
			}

			$this->db->update('users', $blogs, array('user_id' => $user['user_id']), 1);
		}

		$result = array(
			'output' => __('progress_status', 'utilities_counters', array('%1' => ( $offset + $step < $total ? ( $offset + $step ) : $total ), '%2' => $total)),
			'redirect' => $next < $total ? $next : '',
		);

		return $result;
	}
}
