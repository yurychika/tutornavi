<? text_helper::alternate('odd','even'); ?>

<? if ( $notices ): ?>

	<? foreach ( $notices as $noticeID => $notice ): ?>

		<li class="<?=text_helper::alternate('odd','even')?> <?=($notice['new'] ? 'new' :'')?>">
			<?=$notice['html']?>
		</li>

	<? endforeach; ?>

	<li class="loader <?=text_helper::alternate('odd','even')?>">
		<?=html_helper::anchor('timeline/notices', __('actions_load', 'timeline'))?>
	</li>

<? else: ?>

	<li class="<?=text_helper::alternate('odd','even')?>">
		<?=__('notices_none', 'timeline_notices')?>
	</li>

<? endif; ?>
