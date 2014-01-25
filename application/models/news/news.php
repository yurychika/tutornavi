<?php

class News_News_Model extends Model
{
	public function saveEntryData($newsID, $newsOld, $fields, $extra = array())
	{
		// Is this a new entry
		if ( !$newsID )
		{
			$extra['post_date'] = date_helper::now();
		}

		// Save entry
		if ( !( $newNewsID = $this->fields_model->saveValues('news', $newsID, $newsOld, $fields, $extra) ) )
		{
			return 0;
		}

		if ( $newsID )
		{
			// Action hook
			hook::action('news/update', $newNewsID, $extra);
		}
		else
		{
			// Action hook
			hook::action('news/insert', $newNewsID, $extra);
		}

		return $newNewsID;
	}

	public function updateViews($newsID)
	{
		$retval = $this->db->query("UPDATE `:prefix:news_data` SET `total_views`=`total_views`+1 WHERE `news_id`=? LIMIT 1", array($newsID));

		return $retval;
	}

	public function getEntry($newsID, $fields = false, $params = array())
	{
		$entry = $this->fields_model->getRow('news', $newsID, $fields, $params);

		return $entry;
	}

	public function countEntries($columns = array(), $items = array(), $params = array())
	{
		$params['count'] = 1;

		$total = $this->getEntries(false, $columns, $items, false, 0, $params);

		return $total;
	}

	public function getEntries($fields = false, $columns = array(), $items = array(), $order = false, $limit = 15, $params = array())
	{
		// Do we need to count news?
		if ( isset($params['count']) && $params['count'] )
		{
			$total = $this->fields_model->countRows('news', false, $columns, $items, $params);

			return $total;
		}

		// Get entries
		$entries = $this->fields_model->getRows('news', false, $fields, $columns, $items, $order, $limit, $params);

		return $entries;
	}

	public function deleteEntry($newsID, $news)
	{
		// Delete entry
		$retval = $this->fields_model->deleteValues('news', $newsID);
		if ( $retval )
		{
			// Delete comments
			if ( $news['total_comments'] )
			{
				loader::model('comments/comments');
				$this->comments_model->deleteComments('news', $newsID, $news['total_comments']);
			}

			// Delete likes
			if ( $news['total_likes'] )
			{
				loader::model('comments/likes');
				$this->likes_model->deleteLikes('news', $newsID, $news['total_likes']);
			}

			// Delete votes
			if ( $news['total_votes'] )
			{
				loader::model('comments/votes');
				$this->votes_model->deleteVotes('news', $newsID, $news['total_votes']);
			}

			// Action hook
			hook::action('news/delete', $newsID, $news);
		}

		return $retval;
	}
}
