<?php

class Classifieds_Timeline_Hook extends Hook
{
	public function postClassified($items, $users)
	{
		$stream = array();

		loader::model('classifieds/classifieds');

		$params = array(
			'select_users' => false,
		);

		// Get classified ads
		$columns = array(
			'`a`.`ad_id` IN (' . implode(',', array_keys($items)) . ')',
		);

		$ads = codebreeder::instance()->classifieds_model->getAds('in_list', $columns, array(), false, count($items), $params);

		foreach ( $items as $itemID => $data )
		{
			if ( isset($ads[$itemID]) && isset($users[$ads[$itemID]['user_id']]) )
			{
				foreach ( $data as $actionID => $item )
				{
					$stream[$itemID][$actionID]['html'] = view::load(
						'classifieds/timeline/classified',
						array('user' => $users[$ads[$itemID]['user_id']], 'ad' => $ads[$itemID], 'params' => $item['params']),
						true
					);

					$stream[$itemID][$actionID]['rating']['total_votes'] = $ads[$itemID]['total_votes'];
					$stream[$itemID][$actionID]['rating']['total_score'] = $ads[$itemID]['total_score'];
					$stream[$itemID][$actionID]['rating']['total_rating'] = $ads[$itemID]['total_rating'];
					$stream[$itemID][$actionID]['rating']['total_likes'] = $ads[$itemID]['total_likes'];
					$stream[$itemID][$actionID]['rating']['type'] = config::item('ad_rating', 'classifieds');

					$stream[$itemID][$actionID]['comments']['total_comments'] = $ads[$itemID]['total_comments'];
					$stream[$itemID][$actionID]['comments']['privacy'] = $ads[$itemID]['comments'];
					$stream[$itemID][$actionID]['comments']['post'] = $ads[$itemID]['comments'] && codebreeder::instance()->users_model->getPrivacyAccess($ads[$itemID]['user_id'], $ads[$itemID]['comments'], false, $users[$ads[$itemID]['user_id']]['friends'] ? 1 : 0) ? true : false;
				}
			}
		}

		return $stream;
	}

	public function likeClassified($notice)
	{
		$notice['html'] = __('classified_ad_like', 'timeline_notices', array('%user' => users_helper::anchor($notice['user'])), array('%' => html_helper::anchor('classifieds/view/' . $notice['item_id'], '\1')));

		return $notice;
	}

	public function voteClassified($notice)
	{
		$notice['html'] = __('classified_ad_vote', 'timeline_notices', array('%user' => users_helper::anchor($notice['user'])), array('%' => html_helper::anchor('classifieds/view/' . $notice['item_id'], '\1')));

		return $notice;
	}

	public function commentClassified($notice)
	{
		$notice['html'] = __('classified_ad_comment', 'timeline_notices', array('%user' => users_helper::anchor($notice['user'])), array('%' => html_helper::anchor('classifieds/view/' . $notice['item_id'], '\1')));

		return $notice;
	}
}
