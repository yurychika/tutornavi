<? view::load('cp/system/elements/template/header'); ?>

<section class="plugin-users pictures-browse">

	<? if ( $filters ): ?>
		<? view::load('system/elements/search', array('type' => 'pictures', 'fields' => $filters, 'values' => $values)); ?>
		<script type="text/javascript">
			$(function(){
				$('#input_search_pictures_type_id').change(function() {
					$('.search-types').hide();
					$('#search-types-'+this.value).show();
				});
				if ( $('#input_search_pictures_type_id').val() )
				{
					$('#search-types-'+$('#input_search_pictures_type_id').val()).show();
				}
			});
		</script>
	<? endif; ?>

	<? if ( $users ): ?>

		<?=form_helper::openForm('cp/users/pictures/browse?'.$qstring['url'].'page='.$qstring['page'], array('id' => 'form_pictures'))?>

			<ul class="unstyled gallery clearfix">

				<? foreach ( $users as $user ): ?>

					<li>
						<figure class="image">

							<div class="image thumbnail" style="background-image:url('<?=storage_helper::getFileURL($user['picture_file_service_id'], $user['picture_file_path'], $user['picture_file_name'], $user['picture_file_ext'], 'p')?>');"></div>

							<div class="overlay top-left check hidden">
								<?=form_helper::checkbox('user_id[]', $user['user_id'], '', array('class' => 'checkbox hidden item-picture-checker', 'id' => 'field-picture-checker-'.$user['user_id']))?>
							</div>

							<div class="overlay top-right actions hidden">
								<ul class="unstyled">
									<li><?=html_helper::anchor('cp/users/edit/'.$user['user_id'], __('edit', 'system'), array('class' => 'edit'))?></li>
									<li><?=html_helper::anchor('cp/users/pictures/delete/'.$user['user_id'].'?'.$qstring['url'].'page='.$qstring['page'], __('delete', 'system'), array('data-html' => __('picture_delete?', 'users_picture'), 'data-role' => 'confirm', 'class' => 'delete'))?></li>
								</ul>
							</div>

							<div class="overlay bottom-right">
								<? if ( $user['picture_active'] == 1 ): ?>
									<?=html_helper::anchor('cp/users/pictures/decline/'.$user['user_id'].'?'.$qstring['url'].'page='.$qstring['page'], __('active', 'system'), array('class' => 'label small success'))?>
								<? else: ?>
									<?=html_helper::anchor('cp/users/pictures/approve/'.$user['user_id'].'?'.$qstring['url'].'page='.$qstring['page'], $user['picture_active'] ? __('pending', 'system') : __('inactive', 'system'), array('class' => 'label small '.($user['picture_active'] ? 'info' : 'important')))?>
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
