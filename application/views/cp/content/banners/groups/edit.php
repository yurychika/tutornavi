<? view::load('cp/system/elements/template/header'); ?>

<section class="plugin-banners group-edit">

	<?=form_helper::openForm()?>

		<fieldset class="form grid <?=text_helper::alternate()?>">

			<div class="row <?=text_helper::alternate('odd','even')?>" id="input_row_banner_group_name">

				<label for="input_edit_banner_group_name">
					<?=__('name', 'system')?> <span class="required">*</span>
				</label>

				<div class="field">

					<? view::load('system/elements/field/edit', array(
						'prefix' => 'banner_group',
						'field' => array(
							'keyword' => 'name',
							'type' => 'text',
							'class' => 'input-xlarge',
						),
						'value' => $group,
					)) ?>

				</div>

			</div>

			<div class="row <?=text_helper::alternate('odd','even')?>" id="input_row_banner_group_keyword">

				<label for="input_edit_banner_group_keyword">
					<?=__('keyword', 'system')?> <span class="required">*</span>
				</label>

				<div class="field">

					<? view::load('system/elements/field/edit', array(
						'prefix' => 'banner_group',
						'field' => array(
							'keyword' => 'keyword',
							'type' => 'text',
							'maxlength' => 128,
							'class' => 'input-xlarge',
						),
						'value' => $group,
					)) ?>

				</div>

			</div>

			<div class="row actions">
				<? view::load('system/elements/button'); ?>
			</div>

		</fieldset>

	<?=form_helper::closeForm(array('do_save_group' => 1))?>

</section>

<? view::load('cp/system/elements/template/footer'); ?>
