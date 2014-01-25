<? view::load('header'); ?>

<section class="plugin-pictures picture-view" style="overflow:hidden;">

	<div class="content-view">

		<article class="item">

			<figure class="image pictures-image">
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
					<? if ( config::item('picture_views', 'pictures') ): ?>
						<li class="views">
							<? if ( $picture['total_views'] > 0 ): ?>
								<?=__('views_num'.($picture['total_views'] == 1 ? '_one' : ''), 'system_info', array('%views' => $picture['total_views']))?>
							<? else: ?>
								<?=__('views_none', 'system_info')?>
							<? endif; ?>
						</li>
					<? endif; ?>
					<? if ( $user['user_id'] == session::item('user_id') ): ?>
						<li class="actions">
							<?=html_helper::anchor('pictures/cover/'.$picture['album_id'].'/'.$picture['picture_id'], __('picture_cover', 'pictures'), array('class' => 'cover'))?>
							<?=html_helper::anchor('pictures/edit/'.$picture['album_id'].'/'.$picture['picture_id'], __('picture_edit', 'pictures'), array('class' => 'edit'))?>
							<?=html_helper::anchor('pictures/thumbnail/'.$picture['album_id'].'/'.$picture['picture_id'], __('picture_thumbnail_edit', 'system_files'), array('class' => 'thumbnail'))?>
							<?=html_helper::anchor('pictures/rotate/'.$picture['album_id'].'/'.$picture['picture_id'].'/left', __('picture_rotate_left', 'system_files'), array('class' => 'rotate left'))?>
							<?=html_helper::anchor('pictures/rotate/'.$picture['album_id'].'/'.$picture['picture_id'].'/right', __('picture_rotate_right', 'system_files'), array('class' => 'rotate right'))?>
							<?=html_helper::anchor('pictures/delete/'.$picture['album_id'].'/'.$picture['picture_id'], __('picture_delete', 'pictures'), array('class' => 'delete', 'data-html' => __('picture_delete?', 'pictures'), 'data-role' => 'confirm'))?>
						</li>
					<? endif; ?>
					<? if ( config::item('reports_active', 'reports') && users_helper::isLoggedin() && $picture['user_id'] != session::item('user_id') && session::permission('reports_post', 'reports') ): ?>
						<li class="report">
							<?=html_helper::anchor('report/submit/picture/'.$pictureID, __('report', 'system'), array('data-role' => 'modal', 'data-display' => 'iframe', 'data-title' => __('report', 'system')))?>
						</li>
					<? endif; ?>
					<? if ( config::item('picture_rating', 'pictures') == 'stars' ): ?>
						<li class="votes">
							<? view::load('comments/rating', array('resource' => 'picture', 'itemID' => $picture['picture_id'], 'votes' => $picture['total_votes'], 'score' => $picture['total_score'], 'rating' => $picture['total_rating'], 'voted' => $picture['user_vote']['score'], 'date' => $picture['user_vote']['post_date'])); ?>
						</li>
					<? endif; ?>
					<? if ( config::item('picture_rating', 'pictures') == 'likes' ): ?>
						<li class="likes">
							<? view::load('comments/likes', array('resource' => 'picture', 'itemID' => $picture['picture_id'], 'likes' => $picture['total_likes'], 'liked' => ($picture['user_vote']['post_date'] ? 1 : 0), 'date' => $picture['user_vote']['post_date'])); ?>
						</li>
					<? endif; ?>
				</ul>

			</footer>

		</article>

	</div>

	<? if ( session::permission('comments_view', 'comments') && config::item('picture_comments', 'pictures') && $album['comments'] ): ?>
		<? loader::helper('comments/comments'); ?>
		<? comments_helper::getComments('picture', $picture['user_id'], $picture['picture_id'], $picture['total_comments'], $album['comments']); ?>
	<? endif; ?>

</section>

<? view::load('footer');
