<? view::load('header'); ?>

<section class="plugin-pictures pictures-index">

	<? if ( $pictures ): ?>

		<ul class="unstyled content-gallery clearfix <?=text_helper::alternate()?>">

			<? foreach ( $pictures as $picture ): ?>

				<li class="<?=text_helper::alternate('odd','even')?>" id="row-picture-<?=$picture['picture_id']?>">

					<figure class="image pictures-image">
						<div class="image thumbnail" style="background-image:url('<?=storage_helper::getFileURL($picture['file_service_id'], $picture['file_path'], $picture['file_name'], $picture['file_ext'], 't', $picture['file_modify_date'])?>');">
							<?=html_helper::anchor('pictures/view/'.$picture['picture_id'].'/'.text_helper::slug($picture['data_description'], 100), '<span class="name">'.$picture['data_description'].'</span>', array('class' => 'image'))?>
						</div>
					</figure>

				</li>

			<? endforeach; ?>

		</ul>

		<div class="content-view">

			<article class="item">

				<? if ( $album['data_description'] != '' ): ?>
					<div class="item-article">
						<?=$album['data_description']?>
					</div>
				<? endif; ?>

				<footer class="item-footer">

					<ul class="unstyled content-meta clearfix">
						<li class="date">
							<?=__('post_date', 'system_info', array('%date' => date_helper::formatDate($album['post_date'])))?>
						</li>
						<? if ( config::item('album_views', 'pictures') ): ?>
							<li class="views">
								<? if ( $album['total_views'] > 0 ): ?>
									<?=__('views_num'.($album['total_views'] == 1 ? '_one' : ''), 'system_info', array('%views' => $album['total_views']))?>
								<? else: ?>
									<?=__('views_none', 'system_info')?>
								<? endif; ?>
							</li>
						<? endif; ?>
						<? if ( $user['user_id'] == session::item('user_id') ): ?>
							<li class="actions">
								<?=html_helper::anchor('pictures/albums/edit/'.$album['album_id'], __('album_edit', 'pictures'), array('class' => 'edit'))?>
								<?=html_helper::anchor('pictures/albums/delete/'.$album['album_id'].'?'.$qstring['url'].'page='.$qstring['page'], __('album_delete', 'pictures'), array('class' => 'delete', 'data-html' => __('album_delete?', 'pictures'), 'data-role' => 'confirm'))?>
							</li>
						<? endif; ?>
						<? if ( config::item('reports_active', 'reports') && users_helper::isLoggedin() && $album['user_id'] != session::item('user_id') && session::permission('reports_post', 'reports') ): ?>
							<li class="report">
								<?=html_helper::anchor('report/submit/picture_album/'.$albumID, __('report', 'system'), array('data-role' => 'modal', 'data-display' => 'iframe', 'data-title' => __('report', 'system')))?>
							</li>
						<? endif; ?>
						<? if ( config::item('album_rating', 'pictures') == 'stars' ): ?>
							<li class="votes">
								<? view::load('comments/rating', array('resource' => 'picture_album', 'itemID' => $album['album_id'], 'votes' => $album['total_votes'], 'score' => $album['total_score'], 'rating' => $album['total_rating'], 'voted' => $album['user_vote']['score'], 'date' => $album['user_vote']['post_date'])); ?>
							</li>
						<? endif; ?>
						<? if ( config::item('album_rating', 'pictures') == 'likes' ): ?>
							<li class="likes">
								<? view::load('comments/likes', array('resource' => 'picture_album', 'itemID' => $album['album_id'], 'likes' => $album['total_likes'], 'liked' => ($album['user_vote']['post_date'] ? 1 : 0), 'date' => $album['user_vote']['post_date'])); ?>
							</li>
						<? endif; ?>
					</ul>

				</footer>

			</article>

		</div>

		<div class="content-footer">
			<? view::load('system/elements/pagination', array('pagination' => $pagination)); ?>
		</div>

	<? endif; ?>

</section>

<? view::load('footer');
