<? view::load('cp/system/elements/template/header'); ?>

<section class="plugin-banners banner-edit">

	<?=form_helper::openForm()?>

		<fieldset class="form grid <?=text_helper::alternate()?>">

			<div class="row <?=text_helper::alternate('odd','even')?>" id="input_row_banner_name">

				<label for="input_edit_banner_name">
					<?=__('name', 'system')?> <span class="required">*</span>
				</label>

				<div class="field">

					<? view::load('system/elements/field/edit', array(
						'prefix' => 'banner',
						'field' => array(
							'keyword' => 'name',
							'type' => 'text',
							'class' => 'input-xlarge',
						),
						'value' => $banner,
					)) ?>

				</div>

			</div>

			<div class="row <?=text_helper::alternate('odd','even')?>" id="input_row_banner_keyword">

				<label for="input_edit_banner_keyword">
					<?=__('keyword', 'system')?> <span class="required">*</span>
				</label>

				<div class="field">

					<? view::load('system/elements/field/edit', array(
						'prefix' => 'banner',
						'field' => array(
							'keyword' => 'keyword',
							'type' => 'text',
							'maxlength' => 128,
							'class' => 'input-xlarge',
						),
						'value' => $banner,
					)) ?>

				</div>

			</div>

			<div class="row <?=text_helper::alternate('odd','even')?>" id="input_row_banner_code">

				<label for="input_edit_banner_code">
					<?=__('banner_code', 'banners')?> <span class="required">*</span>
				</label>

				<div class="field">

					<? view::load('system/elements/field/edit', array(
						'prefix' => 'banner',
						'field' => array(
							'keyword' => 'code',
							'type' => 'textarea',
						),
						'value' => $banner,
					)) ?>

				</div>

			</div>

			<div class="row <?=text_helper::alternate('odd','even')?>" id="input_row_banner_count_views">

				<label for="input_edit_banner_count_views">
					<?=__('banner_count_views', 'banners')?>
				</label>

				<div class="field">

					<? view::load('system/elements/field/edit', array(
						'prefix' => 'banner',
						'field' => array(
							'keyword' => 'count_views',
							'type' => 'boolean',
						),
						'value' => $banner,
					)) ?>

				</div>

			</div>

			<div class="row <?=text_helper::alternate('odd','even')?>" id="input_row_banner_total_views">

				<label for="input_edit_banner_total_views">
					<?=__('banner_views', 'banners')?>
				</label>

				<div class="field">

					<? view::load('system/elements/field/edit', array(
						'prefix' => 'banner',
						'field' => array(
							'keyword' => 'total_views',
							'type' => 'text',
							'maxlength' => 10,
							'class' => 'input-small',
						),
						'value' => $banner,
					)) ?>

				</div>

			</div>

			<div class="row <?=text_helper::alternate('odd','even')?>" id="input_row_banner_count_clicks">

				<label for="input_edit_banner_count_clicks">
					<?=__('banner_count_clicks', 'banners')?>
				</label>

				<div class="field">

					<? view::load('system/elements/field/edit', array(
						'prefix' => 'banner',
						'field' => array(
							'keyword' => 'count_clicks',
							'type' => 'boolean',
						),
						'value' => $banner,
					)) ?>

				</div>

			</div>

			<div class="row <?=text_helper::alternate('odd','even')?>" id="input_row_banner_total_clicks">

				<label for="input_edit_banner_total_clicks">
					<?=__('banner_clicks', 'banners')?>
				</label>

				<div class="field">

					<? view::load('system/elements/field/edit', array(
						'prefix' => 'banner',
						'field' => array(
							'keyword' => 'total_clicks',
							'type' => 'text',
							'maxlength' => 10,
							'class' => 'input-small',
						),
						'value' => $banner,
					)) ?>

				</div>

			</div>

			<div class="row <?=text_helper::alternate('odd','even')?>" id="input_row_banner_secure_mode">

				<label for="input_edit_banner_secure_mode">
					<?=__('banner_show_secure', 'banners')?>
				</label>

				<div class="field">

					<? view::load('system/elements/field/edit', array(
						'prefix' => 'banner',
						'field' => array(
							'keyword' => 'secure_mode',
							'type' => 'boolean',
						),
						'value' => $banner,
					)) ?>

				</div>

			</div>

			<div class="row <?=text_helper::alternate('odd','even')?>" id="input_row_banner_active">

				<label for="input_edit_banner_active">
					<?=__('active', 'system')?>
				</label>

				<div class="field">

					<? view::load('system/elements/field/edit', array(
						'prefix' => 'banner',
						'field' => array(
							'keyword' => 'active',
							'type' => 'boolean',
						),
						'value' => $banner,
					)) ?>

				</div>

			</div>

			<div class="row actions">
				<? view::load('system/elements/button'); ?>
			</div>

		</fieldset>

	<?=form_helper::closeForm(array('do_save_banner' => 1))?>

</section>

<? view::load('cp/system/elements/template/footer'); ?>
