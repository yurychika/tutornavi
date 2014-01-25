<? if ( $pictures ): ?>

	<div class="content-box helper-pictures">

		<? if ( isset($user) && $user ): ?>
			<div class="header">
				<span><?=html_helper::anchor('pictures/user/'.$user['slug_id'], __('pictures', 'system_navigation'))?></span>
				<div class="header">
					<span><?=html_helper::anchor('pictures/user/'.$user['slug_id'], __('pictures_num'.($user['total_pictures'] == 1 ? '_one' : ''), 'system_info', array('%pictures' => $user['total_pictures'])))?></span>
				</div>
			</div>
		<? endif; ?>

		<ul class="unstyled content-gallery clearfix <?=text_helper::alternate()?>">

			<? foreach ( $pictures as $picture ): ?>

				<li class="<?=text_helper::alternate('odd','even')?>" id="row-helper-picture-<?=$picture['picture_id']?>">

					<figure class="image pictures-image">

						<? if ( $picture['file_id'] ): ?>
							<div class="image thumbnail" style="background-image:url('<?=storage_helper::getFileURL($picture['file_service_id'], $picture['file_path'], $picture['file_name'], $picture['file_ext'], 't', $picture['file_modify_date'])?>');">
						<? else: ?>
							<div class="image thumbnail no_image">
						<? endif; ?>
							<?=html_helper::anchor('pictures/view/'.$picture['picture_id'].'/'.text_helper::slug($picture['data_description'], 100), '<span></span>', array('class' => 'image'))?>
						</div>

					</figure>

				</li>

			<? endforeach; ?>

		</ul>

	</div>

<? endif; ?>
