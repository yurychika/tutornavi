<? view::load('header'); ?>

<section class="plugin-classifieds picture-view" style="overflow:hidden;">

	<div class="content-view">

		<article class="item">

			<figure class="image classifieds-image">
				<div class="image view" style="background-image:url('<?=storage_helper::getFileURL($picture['file_service_id'], $picture['file_path'], $picture['file_name'], $picture['file_ext'], '', $picture['file_modify_date'])?>');height:<?=$picture['file_height']?>px">
					<? if ( $previousURL ): ?>
						<div class="overlay arrow left">
							<?=html_helper::anchor($previousURL, '<span class="element"></span>')?>
						</div>
					<? endif; ?>
					<? if ( $nextURL ): ?>
						<div class="overlay arrow right">
							<?=html_helper::anchor($nextURL, '<span class="element"></span>')?>
						</div>
					<? endif; ?>
				</div>
			</figure>

			<? if ( $picture['data_description'] != '' ): ?>
				<div class="item-article">
					<?=$picture['data_description']?>
				</div>
			<? endif; ?>

			<footer class="item-footer">

				<ul class="unstyled content-meta clearfix">
					<li class="date">
						<?=__('post_date', 'system_info', array('%date' => date_helper::formatDate($picture['post_date'])))?>
					</li>
					<? if ( $user['user_id'] == session::item('user_id') ): ?>
						<li class="actions">
							<?=html_helper::anchor('classifieds/pictures/cover/'.$picture['ad_id'].'/'.$picture['picture_id'], __('picture_cover', 'classifieds'), array('class' => 'cover'))?>
							<?=html_helper::anchor('classifieds/pictures/edit/'.$picture['ad_id'].'/'.$picture['picture_id'], __('picture_edit', 'classifieds'), array('class' => 'edit'))?>
							<?=html_helper::anchor('classifieds/pictures/thumbnail/'.$picture['ad_id'].'/'.$picture['picture_id'], __('picture_thumbnail_edit', 'system_files'), array('class' => 'thumbnail'))?>
							<?=html_helper::anchor('classifieds/pictures/rotate/'.$picture['ad_id'].'/'.$picture['picture_id'].'/left', __('picture_rotate_left', 'system_files'), array('class' => 'rotate left'))?>
							<?=html_helper::anchor('classifieds/pictures/rotate/'.$picture['ad_id'].'/'.$picture['picture_id'].'/right', __('picture_rotate_right', 'system_files'), array('class' => 'rotate right'))?>
							<?=html_helper::anchor('classifieds/pictures/delete/'.$picture['ad_id'].'/'.$picture['picture_id'], __('picture_delete', 'classifieds'), array('class' => 'delete', 'data-html' => __('picture_delete?', 'classifieds'), 'data-role' => 'confirm'))?>
						</li>
					<? endif; ?>
					<? if ( config::item('reports_active', 'reports') && users_helper::isLoggedin() && $picture['user_id'] != session::item('user_id') && session::permission('reports_post', 'reports') ): ?>
						<li class="report">
							<?=html_helper::anchor('report/submit/classified_picture/'.$pictureID, __('report', 'system'), array('data-role' => 'modal', 'data-display' => 'iframe', 'data-title' => __('report', 'system')))?>
						</li>
					<? endif; ?>
				</ul>

			</footer>

		</article>

	</div>

</section>

<? view::load('footer');
