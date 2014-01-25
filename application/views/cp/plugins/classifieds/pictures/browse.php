<? view::load('cp/system/elements/template/header'); ?>

<section class="plugin-classifieds pictures-browse">

	<? if ( $filters ): ?>
		<? view::load('system/elements/search', array('type' => 'pictures', 'fields' => $filters, 'values' => $values)); ?>
	<? endif; ?>

	<? if ( $pictures ): ?>

		<?=form_helper::openForm('cp/plugins/classifieds/pictures/browse/'.$adID.'?'.$qstring['url'].'page='.$qstring['page'], array('id' => 'form_pictures'))?>

			<ul class="unstyled gallery clearfix">

				<? foreach ( $pictures as $picture ): ?>

					<li>
						<figure class="image">

							<div class="image thumbnail" style="background-image:url('<?=storage_helper::getFileURL($picture['file_service_id'], $picture['file_path'], $picture['file_name'], $picture['file_ext'], 't')?>');"></div>

							<div class="overlay top-left check hidden">
								<?=form_helper::checkbox('picture_id[]', $picture['picture_id'], '', array('class' => 'checkbox item-picture-checker', 'id' => 'field-picture-checker-'.$picture['picture_id']))?>
							</div>

							<div class="overlay top-right actions hidden">
								<ul class="unstyled">
									<li><?=html_helper::anchor('cp/plugins/classifieds/pictures/edit/'.$picture['ad_id'].'/'.$picture['picture_id'], __('edit', 'system'), array('class' => 'edit'))?></li>
									<li><?=html_helper::anchor('cp/plugins/classifieds/pictures/delete/'.$picture['ad_id'].'/'.$picture['picture_id'].'?'.$qstring['url'].'page='.$qstring['page'], __('delete', 'system'), array('data-html' => __('picture_delete?', 'pictures'), 'data-role' => 'confirm', 'class' => 'delete'))?></li>
								</ul>
							</div>

							<div class="overlay bottom-right">
								<? if ( $picture['active'] == 1 ): ?>
									<?=html_helper::anchor('cp/plugins/classifieds/pictures/decline/'.$adID.'/'.$picture['picture_id'].'?'.$qstring['url'].'page='.$qstring['page'], __('active', 'system'), array('class' => 'label small success'))?>
								<? else: ?>
									<?=html_helper::anchor('cp/plugins/classifieds/pictures/approve/'.$adID.'/'.$picture['picture_id'].'?'.$qstring['url'].'page='.$qstring['page'], $picture['active'] ? __('pending', 'system') : __('inactive', 'system'), array('class' => 'label small '.($picture['active'] ? 'info' : 'important')))?>
								<? endif; ?>
							</div>

						</figure>
					</li>

				<? endforeach; ?>

			</ul>

			<? if ( isset($pagination) && $pagination || isset($actions) && $actions ): ?>

				<div class="footer-section clearfix">

					<? if ( isset($pagination) && $pagination ): ?>
						<? view::load('cp/system/elements/pagination', array('pagination' => $pagination)); ?>
					<? endif; ?>

					<? if ( isset($actions) && $actions ): ?>
						<? view::load('cp/system/elements/form-actions', array('form' => 'form_pictures', 'actions' => $actions)); ?>
					<? endif; ?>

				</div>

			<? endif; ?>

		<?=form_helper::closeForm(array('do_action' => 1))?>

	<? endif; ?>

</section>

<? view::load('cp/system/elements/template/footer'); ?>
