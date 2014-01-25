<div class="item-article">

	<div class="action-header">
		<?=__('classified_ad_post', 'timeline', array('[name]' => users_helper::anchor($user)))?>
	</div>

	<div class="target-article media">
		<div class="target-header">
			<?=html_helper::anchor('classifieds/view/' . $ad['ad_id'] . '/' . text_helper::slug($ad['data_title'], 100), $ad['data_title'])?> - <?=money_helper::symbol(config::item('ad_currency', 'classifieds')).$ad['data_price']?>
		</div>
		<? if ( $ad['file_service_id'] && $ad['total_pictures'] ): ?>
			<figure class="image classifieds-image">
				<div class="image thumbnail" style="background-image:url('<?=storage_helper::getFileURL($ad['file_service_id'], $ad['file_path'], $ad['file_name'], $ad['file_ext'], 't', $ad['file_modify_date'])?>');">
					<?=html_helper::anchor('classifieds/view/'.$ad['ad_id'].'/'.text_helper::slug($ad['data_title'], 100), '<span class="name">'.$ad['data_title'].'</span>', array('class' => 'image'))?>
				</div>
			</figure>
		<? endif; ?>
	</div>

</div>
