<?php

class Blogs_Timeline_Hook extends Hook
{
	public function postBlog($items, $users)
	{
		$stream = array();

		loader::model('blogs/blogs');

		$params = array(
			'select_users' => false,
		);

		// Get blogs
		$columns = array(
			'`b`.`blog_id` IN (' . implode(',', array_keys($items)) . ')',
		);

		$blogs = codebreeder::instance()->blogs_model->getBlogs('in_list', $columns, array(), false, count($items), $params);

		foreach ( $items as $itemID => $data )
		{
			if ( isset($blogs[$itemID]) && isset($users[$blogs[$itemID]['user_id']]) )
			{
				foreach ( $data as $actionID => $item )
				{
					$stream[$itemID][$actionID]['html'] = view::load(
						'blogs/timeline/blog',
						array('user' => $users[$blogs[$itemID]['user_id']], 'blog' => $blogs[$itemID], 'params' => $item['params']),
						true
					);

					$stream[$itemID][$actionID]['rating']['total_votes'] = $blogs[$itemID]['total_votes'];
					$stream[$itemID][$actionID]['rating']['total_score'] = $blogs[$itemID]['total_score'];
					$stream[$itemID][$actionID]['rating']['total_rating'] = $blogs[$itemID]['total_rating'];
					$stream[$itemID][$actionID]['rating']['total_likes'] = $blogs[$itemID]['total_likes'];
					$stream[$itemID][$actionID]['rating']['type'] = config::item('blog_rating', 'blogs');

					$stream[$itemID][$actionID]['comments']['total_comments'] = $blogs[$itemID]['total_comments'];
					$stream[$itemID][$actionID]['comments']['privacy'] = $blogs[$itemID]['comments'];
					$stream[$itemID][$actionID]['comments']['post'] = $blogs[$itemID]['comments'] && codebreeder::instance()->users_model->getPrivacyAccess($blogs[$itemID]['user_id'], $blogs[$itemID]['comments'], false, $users[$blogs[$itemID]['user_id']]['friends'] ? 1 : 0) ? true : false;
				}
			}
		}

		return $stream;
	}

	public function likeBlog($notice)
	{
		$notice['html'] = __('blog_like', 'timeline_notices', array('%user' => users_helper::anchor($notice['user'])), array('%' => html_helper::anchor('blogs/view/' . $notice['item_id'], '\1')));

		return $notice;
	}

	public function voteBlog($notice)
	{
		$notice['html'] = __('blog_vote', 'timeline_notices', array('%user' => users_helper::anchor($notice['user'])), array('%' => html_helper::anchor('blogs/view/' . $notice['item_id'], '\1')));

		return $notice;
	}

	public function commentBlog($notice)
	{
		$notice['html'] = __('blog_comment', 'timeline_notices', array('%user' => users_helper::anchor($notice['user'])), array('%' => html_helper::anchor('blogs/view/' . $notice['item_id'], '\1')));

		return $notice;
	}
}
