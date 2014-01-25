<? if ( isset($static) && $static ): ?>

	<div class="likes clearfix" id="like-container-<?=$resource?>-<?=$itemID?>" data-role="likes">
		<span class="like"></span>
		<span class="blurb">
			<? if ( $likes > 0 ): ?>
				<?=__('likes_num'.($likes == 1 ? '_one' : ''), 'system_info', array('%likes' => $likes))?>
			<? else: ?>
				<?=__('likes_none', 'system_info')?>
			<? endif; ?>
		</span>
	</div>

<? else: ?>

	<div class="likes clearfix" id="like-container-<?=$resource?>-<?=$itemID?>" data-role="likes">
		<? if ( $liked ): ?>
			<?=html_helper::anchor('comments/like', '<span>'.__('likes_unlike', 'system_info').'</span>', array('onclick' => "submitLike(this.href,{'resource':'$resource','item_id':'$itemID','like':'0'});return false;", 'class' => 'action unlike', 'data-tooltip' => 'default', 'title' => __('likes_liked', 'system_info')))?>
		<? elseif ( users_helper::isLoggedin() ): ?>
			<?=html_helper::anchor('comments/like', '<span>'.__('likes_like', 'system_info').'</span>', array('onclick' => "submitLike(this.href,{'resource':'$resource','item_id':'$itemID','like':'1'});return false;", 'class' => 'action like', 'data-tooltip' => 'default', 'title' => __('likes_like', 'system_info')))?>
		<? else: ?>
			<?=html_helper::anchor('comments/like', '<span>'.__('likes_like', 'system_info').'</span>', array('onclick' => 'return false;', 'class' => 'action like', 'data-tooltip' => 'default', 'title' => __('no_login', 'system_info')))?>
		<? endif; ?>
		<span class="blurb">
			<? if ( $likes > 0 ): ?>
				<?=__('likes_num'.($likes == 1 ? '_one' : ''), 'system_info', array('%likes' => $likes))?>
			<? else: ?>
				<?=__('likes_none', 'system_info')?>
			<? endif; ?>
		</span>
	</div>

<? endif; ?>
