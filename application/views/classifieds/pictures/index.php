<? view::load('header'); ?>

<section class="plugin-classifieds pictures-index">

	<? if ( $pictures ): ?>

		<ul class="unstyled content-gallery clearfix <?=text_helper::alternate()?>">

			<? foreach ( $pictures as $picture ): ?>

				<li class="<?=text_helper::alternate('odd','even')?>" id="row-picture-<?=$picture['picture_id']?>">

					<figure class="image classifieds-image">
						<div class="image thumbnail" style="background-image:url('<?=storage_helper::getFileURL($picture['file_service_id'], $picture['file_path'], $picture['file_name'], $picture['file_ext'], 't', $picture['file_modify_date'])?>');">
							<?=html_helper::anchor('classifieds/pictures/view/'.$picture['picture_id'].'/'.text_helper::slug($picture['data_description'], 100), '<span class="name">'.$picture['data_description'].'</span>', array('class' => 'image'))?>
						</div>
					</figure>

				</li>

			<? endforeach; ?>

		</ul>

	<? endif; ?>

</section>

<? view::load('footer');
