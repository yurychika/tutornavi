<? if ( isset($static) && $static || $voted || !session::item('user_id') ): ?>

	<div class="rating static clearfix" data-role="rating" id="rating-container-<?=$resource?>-<?=$itemID?>" data-tooltip="default" title="<? if ( $rating > 0 ): ?><?=__('rating_votes'.($votes == 1 ? '_one' : ''), 'system_info', array('%votes' => $votes))?><? else: ?><?=__('rating_none', 'system_info')?><? endif; ?>">
		<div class="score" style="width: <?=($rating*20)?>%"></div>
	</div>

<? else: ?>

	<div class="rating clearfix" data-role="rating" data-rating="<?=round($rating)?>" id="rating-container-<?=$resource?>-<?=$itemID?>">
		<span class="icon icon-system-ajax ajax" id="ajax-rating-<?=$resource?>-<?=$itemID?>" style="display:none"></span>
		<? for ( $i = 1; $i <= 5; $i++ ): ?>
			<a class="star star-<?=$i?>" href="#" onclick="submitVote('<?=html_helper::siteURL('comments/vote')?>',{'resource':'<?=$resource?>','item_id':'<?=$itemID?>','score':'<?=$i?>'});return false;" data-tooltip="default" title="<?=__('rating_'.$i, 'system_info')?>"></a>
		<? endfor; ?>
	</div>

<? endif; ?>