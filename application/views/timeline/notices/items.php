<? text_helper::alternate('odd','even'); ?>
<? if ( $notices ): ?>

	<? foreach ( $notices as $noticeID => $notice ): ?>

		<li class="clearfix <?=text_helper::alternate('odd','even')?> <?=($notice['new'] ? 'new' :'')?>" id="row-timeline-notice-<?=$noticeID?>">

			<article class="item">

				<figure class="image users-image">
					<? view::load('users/profile/elements/picture', array_merge($notice['user'], array('picture_file_suffix' => 't'))); ?>
				</figure>

				<div class="item-article">
					<?=$notice['html']?>
				</div>

				<footer class="item-footer">
					<ul class="unstyled content-meta clearfix">
						<li class="date">
							<?=__('author_date', 'system_info', array('%author' => users_helper::anchor($notice['user']), '%date' => date_helper::formatDate($notice['post_date'])))?>
						</li>
					</ul>
				</footer>

			</article>

		</li>

	<? endforeach; ?>

	<li class="clearfix loader <?=text_helper::alternate('odd','even')?>">

		<article class="item">
			<?=html_helper::anchor('timeline/notices', __('actions_load', 'timeline'), array('id' => 'ajax-timeline-load', 'class' => 'icon icon-text icon-timeline-load', 'onclick' => "timelineNoticesUpdate(this.href,".$noticeID.");return false;"))?>
			<span class="icon icon-system-ajax ajax" id="ajax-timeline-load" style="display:none"></span>
		</article>

	</li>

<? else: ?>

	<? if ( input::isAjaxRequest() ): ?>

		<li class="clearfix <?=text_helper::alternate('odd','even')?> shift" id="row-action-0">

			<article class="item">
				<?=__('actions_load_last', 'timeline')?>
			</article>

		</li>

	<? else: ?>

		<li class="clearfix <?=text_helper::alternate('odd','even')?> shift" id="row-action-0">

			<article class="item">
				<?=__('notices_none', 'timeline_notices')?>
			</article>

		</li>

	<? endif; ?>

<? endif; ?>
