<? view::load('header'); ?>

<section class="plugin-pictures albums-manage">

	<? if ( $filters ): ?>
		<? view::load('system/elements/search', array('type' => 'albums', 'fields' => $filters, 'values' => $values)); ?>
	<? endif; ?>

	<? if ( $albums ): ?>

		<ul class="unstyled content-list <?=text_helper::alternate()?>">

			<? foreach ( $albums as $album ): ?>

				<li class="clearfix <?=text_helper::alternate('odd','even')?>" id="row-picture-album-<?=$album['album_id']?>">

					<article class="item">

						<figure class="image pictures-image">
							<? if ( $album['file_service_id'] ): ?>
								<div class="image thumbnail" style="background-image:url('<?=storage_helper::getFileURL($album['file_service_id'], $album['file_path'], $album['file_name'], $album['file_ext'], 't', $album['file_modify_date'])?>');">
							<? else: ?>
								<div class="image thumbnail no_image">
							<? endif; ?>
								<?=html_helper::anchor('pictures/index/'.$album['album_id'].'/'.text_helper::slug($album['data_title'], 100), '<span class="name">'.$album['data_title'].'</span>', array('class' => 'image'))?>
								<div class="overlay element pictures">
									<?=__('pictures_num'.($album['total_pictures'] == 1 ? '_one' : ''), 'system_info', array('%pictures' => $album['total_pictures']))?>
								</div>
							</div>
						</figure>

						<header class="item-header">
							<h2>
								<?=html_helper::anchor('pictures/index/'.$album['album_id'].'/'.text_helper::slug($album['data_title'], 100), $album['data_title'])?>
							</h2>
						</header>

						<div class="item-article">
							<? if ( $album['data_description'] ): ?>
								<?=$album['data_description']?>
							<? endif; ?>
						</div>

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
								<? if ( config::item('album_rating', 'pictures') == 'stars' ): ?>
									<li class="votes">
										<? view::load('comments/rating', array('resource' => 'picture_album', 'itemID' => $album['album_id'], 'votes' => $album['total_votes'], 'score' => $album['total_score'], 'rating' => $album['total_rating'], 'static' => 1)); ?>
									</li>
								<? endif; ?>
								<? if ( config::item('album_rating', 'pictures') == 'likes' ): ?>
									<li class="likes">
										<? view::load('comments/likes', array('resource' => 'picture_album', 'itemID' => $album['album_id'], 'likes' => $album['total_likes'], 'static' => 1)); ?>
									</li>
								<? endif; ?>
								<li class="actions">
									<?=html_helper::anchor('pictures/albums/edit/'.$album['album_id'], __('album_edit', 'pictures'), array('class' => 'edit'))?>
									<?=html_helper::anchor('pictures/albums/delete/'.$album['album_id'].'?'.$qstring['url'].'page='.$qstring['page'], __('album_delete', 'pictures'), array('class' => 'delete', 'data-html' => __('album_delete?', 'pictures'), 'data-role' => 'confirm'))?>
								</li>
							</ul>

						</footer>

					</article>

				</li>

			<? endforeach; ?>

		</ul>

		<div class="content-footer">
			<? view::load('system/elements/pagination', array('pagination' => $pagination)); ?>
		</div>

	<? endif; ?>

</section>

<? view::load('footer');
