<? view::load('header'); ?>

<section class="plugin-pictures albums-index">

	<? if ( $filters ): ?>
		<? view::load('system/elements/search', array('type' => 'albums', 'fields' => $filters, 'values' => $values)); ?>
	<? endif; ?>

	<? if ( $albums ): ?>

		<ul class="unstyled content-gallery clearfix <?=text_helper::alternate()?>">

			<? foreach ( $albums as $album ): ?>

				<li class="<?=text_helper::alternate('odd','even')?>" id="row-picture-album-<?=$album['album_id']?>">

					<figure class="image pictures-image">

						<? if ( $album['file_id'] ): ?>
							<div class="image thumbnail" style="background-image:url('<?=storage_helper::getFileURL($album['file_service_id'], $album['file_path'], $album['file_name'], $album['file_ext'], 't', $album['file_modify_date'])?>');">
						<? else: ?>
							<div class="image thumbnail no_image">
						<? endif; ?>
							<?=html_helper::anchor('pictures/index/'.$album['album_id'].'/'.text_helper::slug($album['data_title'], 100), '<span></span>', array('class' => 'image'))?>
							<div class="overlay element pictures">
								<?=__('pictures_num'.($album['total_pictures'] == 1 ? '_one' : ''), 'system_info', array('%pictures' => $album['total_pictures']))?>
							</div>
						</div>

						<figcaption class="image-caption">

							<span class="nowrap nooverflow name"><?=html_helper::anchor('pictures/index/'.$album['album_id'].'/'.text_helper::slug($album['data_title'], 100), $album['data_title'])?></span>
							<span class="nowrap nooverflow user"><?=__('author', 'system_info', array('%author' => users_helper::anchor($album['user'])))?></span>

						</figcaption>

					</figure>

				</li>

			<? endforeach; ?>

		</ul>

		<div class="content-footer">
			<? view::load('system/elements/pagination', array('pagination' => $pagination)); ?>
		</div>

	<? endif; ?>

</section>

<? view::load('footer');
