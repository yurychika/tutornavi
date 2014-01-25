<?php

class Pages_Pages_Model extends Model
{
	public function savePageData($pageID, $parentID, $pageOld, $fields, $extra = array())
	{
		// Is this a new page
		if ( !$pageID )
		{
			// Get last page
			$lastPage = $this->db->query("SELECT `order_id` FROM `:prefix:pages_data` WHERE `parent_id`=? ORDER BY `order_id` DESC LIMIT 1", array($parentID))->row();
			$extra['order_id'] = $lastPage ? ($lastPage['order_id'] + 1) : 1;
			$extra['post_date'] = date_helper::now();
		}

		// Save page
		if ( !( $newPageID = $this->fields_model->saveValues('page', $pageID, $pageOld, $fields, $extra) ) )
		{
			return 0;
		}

		// Is this an existing page?
		if ( $pageID )
		{
			$children = $this->getChildren($pageID);
			foreach ( $children as $child )
			{
				if ( $location = $this->getLocation($child['page_id']) )
				{
					$this->db->update('pages_data', array('location' => $location), array('page_id' => $child['page_id']), 1);
				}
			}

			// Action hook
			hook::action('pages/update', $newPageID, $extra);
		}
		else
		{
			// Action hook
			hook::action('pages/insert', $newPageID, $extra);
		}

		return $newPageID;
	}

	public function getLocation($pageID)
	{
		$location = array();

		$pages = $this->getParents($pageID);

		foreach ( $pages as $page )
		{
			$location[] = $page['keyword'];
		}

		return implode('/', $location);
	}

	public function isUniqueKeyword($keyword, $parentID, $pageID)
	{
		$page = $this->db->query("SELECT COUNT(*) AS `totalrows`
			FROM `:prefix:pages_data`
			WHERE `parent_id`=? AND `page_id`!=? AND `keyword`=?
			LIMIT 1", array($parentID, $pageID, $keyword))->row();

		return $page['totalrows'] ? false : true;
	}

	public function isValidKeyword($keyword, $parentID, $pageID)
	{
		if ( is_numeric($keyword) )
		{
			return 'numeric';
		}
		elseif ( preg_match('/[^0-9\p{L}\-\.\_]+/u', $keyword) )
		{
			return 'invalid';
		}
		elseif ( !$this->isUniqueKeyword($keyword, $parentID, $pageID) )
		{
			return 'duplicate';
		}

		// Scan root folders/files
		if ( !$parentID )
		{
			$files = @array_merge(@scandir(BASEPATH), @scandir(DOCPATH.'controllers'));
			if ( is_array($files) && in_array(strtolower($keyword), array_map('strtolower', $files)) )
			{
				return 'reserved';
			}
		}

		return true;
	}

	public function getPage($pageID, $fields = false, $params = array())
	{
		$params['condition_column'] = ( is_numeric($pageID) ? 'page_id' : 'location' );

		$page = $this->fields_model->getRow('page', $pageID, $fields, $params);

		if ( $page && isset($params['replace']) && $params['replace'] )
		{
			$page['data_body'] = str_replace('[base_url]', config::baseURL(), $page['data_body']);
		}

		return $page;
	}

	public function updateViews($pageID)
	{
		$retval = $this->db->query("UPDATE `:prefix:pages_data` SET `total_views`=`total_views`+1 WHERE `page_id`=? LIMIT 1", array($pageID));

		return $retval;
	}

	public function getPages($parentID, $fields = false, $columns = array(), $items = array(), $order = false, $params = array())
	{
		// Sorting
		if ( !$order )
		{
			$order = '`p`.`data_title_' . session::item('language') . '` ASC';
			$order = '`p`.`order_id` ASC';
		}

		// Get pages
		$pages = $this->fields_model->getRows('page', false, $fields, $columns, $items, $order, 1000, $params);

		return $pages;
	}

	public function getParents($pageID, $flat = true, $level = 1)
	{
		// Prevent unlimited loop
		if ( $level > 10 )
		{
			return false;
		}

		// Pages array
		$pages = array();

		// Get page
		$page = $this->fields_model->getRow('page', $pageID, 'in_list');

		$pages[] = $page;

		// Do we have a parent?
		if ( $page['parent_id'] )
		{
			// Get parent page
			if ( $flat )
			{
				$pages = array_merge($pages, $this->getParents($page['parent_id'], $flat, ( $level + 1 )));
			}
			else
			{
				$pages[] = $this->getParents($page['parent_id'], $flat, ( $level + 1 ));
			}
		}

		if ( $level == 1 && $flat )
		{
			$pages = array_reverse($pages);
		}

		return $pages;
	}

	public function getChildren($pageID, $flat = true, $level = 1)
	{
		// Prevent unlimited loop
		if ( $level > 10 )
		{
			return false;
		}

		// Pages array
		$pages = array();

		// Get pages
		$result = $this->fields_model->getRows('page', false, 'in_list', array("`p`.`parent_id`=" . $pageID), array(), false, 1000);

		// Get pages
		foreach ( $result as $page )
		{
			$pages[] = $page;

			// Do we have a parent?
			if ( $page['parent_id'] )
			{
				if ( $page = $this->getChildren($page['page_id'], $flat, ( $level + 1 )) )
				{
					// Get parent page
					if ( $flat )
					{
						$pages = array_merge($pages, $page);
					}
					else
					{
						$pages[] = $page;
					}
				}
			}
		}

		return $pages;
	}

	public function deletePage($pageID, $page)
	{
		// Get children IDs
		$pageIDs = array($pageID);
		foreach ( $this->getChildren($pageID) as $page )
		{
			$pageIDs[] = $page['page_id'];
		}

		// Delete pages
		$retval = $this->fields_model->deleteValues('page', $pageIDs, count($pageIDs));
		if ( $retval )
		{
			// Update order IDs
			$this->db->query("UPDATE `:prefix:pages_data` SET `order_id`=`order_id`-1 WHERE `parent_id`=? AND `order_id`>?", array($page['parent_id'], $page['order_id']));

			// Load models
			loader::model('comments/comments');
			loader::model('comments/likes');
			loader::model('comments/votes');

			// Delete comments, like, votes
			$this->comments_model->deleteComments('page', $pageIDs);
			$this->likes_model->deleteLikes('page', $pageIDs);
			$this->votes_model->deleteVotes('page', $pageIDs);

			// Action hook
			hook::action('pages/delete_multiple', $pageIDs);
		}

		return $retval;
	}
}
