<? if ( $ads ): ?>

	<div class="content-box helper-classifieds classifieds-list">

		<? if ( isset($user) && $user ): ?>
			<div class="header">
				<span><?=html_helper::anchor('classifieds/user/'.$user['slug_id'], __('classifieds', 'system_navigation'))?></span>
				<div class="header">
					<span><?=html_helper::anchor('classifieds/user/'.$user['slug_id'], __('classifieds_num'.($user['total_classifieds'] == 1 ? '_one' : ''), 'system_info', array('%ads' => $user['total_classifieds'])))?></span>
				</div>
			</div>
		<? endif; ?>

		<ul class="unstyled item-list icon-list narrow arrow <?=text_helper::alternate()?>">

			<? foreach ( $ads as $ad ): ?>

				<li class="<?=text_helper::alternate('odd','even')?> nowrap nooverflow" id="row-helper-classified-ad-<?=$ad['ad_id']?>">
					<?=html_helper::anchor('classifieds/view/'.$ad['ad_id'].'/'.text_helper::slug($ad['data_title'], 100), $ad['data_title'], array('title' => $ad['data_title']))?>
				</li>

			<? endforeach; ?>

		</ul>

	</div>

<? endif; ?>
