<?php defined('SYSPATH') || die('No direct script access allowed.');

class Banners_Helper
{
	static public function showBanner($group, $banner = '')
	{
		if ( !config::item('plugins', 'core', 'banners') || !session::permission('banners_show', 'banners') )
		{
			return '';
		}

		loader::model('banners/banners');

		$banner = codebreeder::instance()->banners_model->getBanner($banner, $group);

		if ( !$banner || input::protocol() == 'https' && !$banner['secure_mode'] )
		{
			return '';
		}

		if ( $banner['count_views'] )
		{
			codebreeder::instance()->banners_model->updateViews($banner['banner_id']);
		}

		if ( $banner['count_clicks'] )
		{
			$banner['code'] = '<div style="display:block" onclick="$(\'#banner_id_' . $banner['banner_id'] . '\').attr(\'src\',\'' . html_helper::siteURL('banners/click/' . $banner['banner_id']) . '\');return true;">' . $banner['code'] .
				'<img src="' . html_helper::baseURL('assets/images/banners/blank.gif') . '" border="0" style="width:0px;height:0px;" alt="" id="banner_id_' . $banner['banner_id'] . '" /></div>';
		}

		return $banner['code'];
	}
}