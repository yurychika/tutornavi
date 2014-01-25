<div class="item-article">

	<div class="action-header">
		<?=__('picture_post', 'timeline', array(
			'[name]' => users_helper::anchor($user),
			'[params.count]' => $params['count'],
			'[album]' => html_helper::anchor('pictures/index/' . $album['album_id'] . '/' . text_helper::slug($album['data_title'], 100), $album['data_title']),
		))?>
	</div>

	<div class="target-article gallery">
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
	</div>

</div>
