<? if ( $ads ): ?>

	<div class="content-box helper-classifieds">

		<? if ( isset($user) && $user ): ?>
			<div class="header">
				<span><?=html_helper::anchor('classifieds/user/'.$user['slug_id'], __('classifieds', 'system_navigation'))?></span>
				<div class="header">
					<span><?=html_helper::anchor('classifieds/user/'.$user['slug_id'], __('classifieds_num'.($user['total_classifieds'] == 1 ? '_one' : ''), 'system_info', array('%ads' => $user['total_classifieds'])))?></span>
				</div>
			</div>
		<? endif; ?>

		<ul class="unstyled content-list <?=text_helper::alternate()?>">

			<? foreach ( $ads as $ad ): ?>

				<li class="clearfix <?=text_helper::alternate('odd','even')?>" id="row-classified-ad-<?=$ad['ad_id']?>">

					<article class="item">

						<figure class="image classifieds-image">
							<? if ( $ad['file_id'] ): ?>
								<div class="image thumbnail" style="background-image:url('<?=storage_helper::getFileURL($ad['file_service_id'], $ad['file_path'], $ad['file_name'], $ad['file_ext'], 't', $ad['file_modify_date'])?>');">
							<? else: ?>
								<div class="image thumbnail no_image">
							<? endif; ?>
								<?=html_helper::anchor('classifieds/view/'.$ad['ad_id'].'/'.text_helper::slug($ad['data_title'], 100), '<span class="name">'.$ad['data_title'].'</span>', array('class' => 'image'))?>
							</div>
						</figure>

						<header class="item-header">
							<h3>
								<?=html_helper::anchor('classifieds/view/'.$ad['ad_id'].'/'.text_helper::slug($ad['data_title'], 100), $ad['data_title'])?> - <?=money_helper::symbol(config::item('ad_currency', 'classifieds')).$ad['data_price']?>
							</h3>
						</header>

						<div class="item-article">
							<?=text_helper::truncate($ad['data_body'], isset($params['truncate']) && $params['truncate'] ? $params['truncate'] : config::item('ad_preview_chars', 'classifieds'))?>
							<?=html_helper::anchor('classifieds/view/'.$ad['ad_id'].'/'.text_helper::slug($ad['data_title'], 100), '&raquo;')?>
						</div>

					</article>

				</li>

			<? endforeach; ?>

		</ul>

	</div>

<? endif; ?>
