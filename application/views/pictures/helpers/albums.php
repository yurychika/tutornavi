<? if ( $albums ): ?>

	<div class="content-box helper-pictures helper-pictures-albums">

		<? if ( isset($user) && $user ): ?>
			<div class="header">
				<span><?=html_helper::anchor('pictures/user/'.$user['slug_id'], __('pictures_albums', 'system_navigation'))?></span>
				<div class="header">
					<span><?=html_helper::anchor('pictures/user/'.$user['slug_id'], __('pictures_albums_num'.($user['total_albums'] == 1 ? '_one' : ''), 'system_info', array('%albums' => $user['total_albums'])))?></span>
				</div>
			</div>
		<? endif; ?>

		<ul class="unstyled content-gallery clearfix <?=text_helper::alternate()?>">

			<? foreach ( $albums as $album ): ?>

				<li class="<?=text_helper::alternate('odd','even')?>" id="row-helper-picture-album-<?=$album['album_id']?>">

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
							<span class="nowrap nooverflow"><?=html_helper::anchor('pictures/index/'.$album['album_id'].'/'.text_helper::slug($album['data_title'], 100), $album['data_title'], array('title' => $album['data_title']))?></span>
						</figcaption>

					</figure>

				</li>

			<? endforeach; ?>

		</ul>

	</div>

<? endif; ?>
