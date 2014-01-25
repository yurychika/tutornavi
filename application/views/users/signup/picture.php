<? view::load('header'); ?>

<section class="plugin-users signup-picture">

	<?=form_helper::openForm()?>

		<fieldset class="form grid <?=text_helper::alternate()?>">

			<div class="row">
				<label>
					<?=__('picture_current', 'users_picture')?>
				</label>
				<div class="field">

					<figure class="image users-image" id="uploader_picture_view">

						<? if ( session::item('picture', 'signup', 'file_id') ): ?>

							<? view::load('users/profile/elements/picture', array(
								'picture_file_service_id' => session::item('picture', 'signup', 'service_id'),
								'picture_file_path' => session::item('picture', 'signup', 'path'),
								'picture_file_name' => session::item('picture', 'signup', 'name'),
								'picture_file_ext' => session::item('picture', 'signup', 'extension'),
								'picture_file_modify_date' => date_helper::now(),
								'picture_file_suffix' => 'p',
								'picture_active' => 1,
								'picture_url' => true,
							)); ?>

							<figcaption class="image-caption">
								<? /*<?=html_helper::anchor('#', __('picture_change', 'users_picture'), array('onclick' => "\$('#signup_uploader_form').toggle();return false;"))?> -*/ ?>
								<?=html_helper::anchor('users/signup/thumbnail', __('picture_thumbnail_edit', 'system_files'))?> -
								<?=html_helper::anchor('users/signup/picture/delete', __('picture_delete', 'users_picture'), array('data-html' => __('picture_delete?', 'users_picture'), 'data-role' => 'confirm'))?>
							</figcaption>

						<? else: ?>

							<? view::load('users/profile/elements/picture', array('picture_url' => true, 'picture_file_suffix' => 'p')); ?>

						<? endif; ?>
					</figure>

				</div>
			</div>

		</fieldset>

	<?=form_helper::closeForm()?>

	<div id="signup_uploader_form" <?=(session::item('picture', 'signup', 'file_id') ? 'style="display:none"' : '')?>>
		<?=view::load('system/elements/storage/upload', array(
			'action' => 'users/signup/picture',
			'keyword' => 'picture',
			'maxsize' => config::item('picture_max_size', 'users'),
			'extensions' => 'jpg,jpeg,png,gif',
			'limit' => 1,
		))?>
	</div>

	<? if ( !config::item('signup_require_picture', 'users') || config::item('signup_require_picture', 'users') && session::item('picture', 'signup', 'file_id') ): ?>

		<?=form_helper::openForm()?>

			<fieldset class="form <?=text_helper::alternate()?>">

				<div class="row actions">
					<? view::load('system/elements/button', array('value' => session::item('picture', 'signup', 'file_id') ? __('next', 'system') : __('skip', 'system'))); ?>
				</div>

			</fieldset>

		<?=form_helper::closeForm(array('do_save_picture' => 1))?>

	<? endif; ?>

</section>

<? view::load('footer');
