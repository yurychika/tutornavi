<?php defined('SYSPATH') || die('No direct script access allowed.');

class Comments_Helper
{
	static public function getComments($resource, $userID, $itemID, $total, $privacy = 2, $post = true, $info = true, $static = false)
	{
		// Can we post comments?
		$post = $post && session::permission('comments_view', 'comments') && session::permission('comments_post', 'comments') ? true : false;
		if ( $userID )
		{
			$post = $privacy && codebreeder::instance()->users_model->getPrivacyAccess($userID, $privacy, false) ? true : false;
		}

		loader::controller('comments');

		echo codebreeder::instance()->comments->browse($resource, $itemID, $total, 1, $post, $info, $static);
	}
}