<? view::load('cp/system/elements/template/header'); ?>

<section class="plugin-billing credits-edit">

	<?=form_helper::openForm()?>

		<fieldset class="form grid <?=text_helper::alternate()?>">

			<div class="row <?=text_helper::alternate('odd','even')?>" id="input_row_package_credits">

				<label for="input_edit_package_credits">
					<?=__('credits', 'billing_credits')?> <span class="required">*</span>
				</label>

				<div class="field">

					<? view::load('system/elements/field/edit', array(
						'prefix' => 'package',
						'field' => array(
							'keyword' => 'credits',
							'type' => 'text',
							'max' => 9999999999,
							'class' => 'input-small',
						),
						'value' => $package,
					)) ?>

				</div>

			</div>

			<div class="row <?=text_helper::alternate('odd','even')?>" id="input_row_package_price">

				<label for="input_edit_package_price">
					<?=__('price', 'billing')?> <span class="required">*</span>
				</label>

				<div class="field">

					<? view::load('system/elements/field/edit', array(
						'prefix' => 'package',
						'field' => array(
							'keyword' => 'price',
							'type' => 'text',
							'maxlength' => 10,
							'class' => 'input-small',
						),
						'value' => $package,
					)) ?>

				</div>

			</div>

			<div class="row <?=text_helper::alternate('odd','even')?>" id="input_row_package_active">

				<label for="input_edit_package_active">
					<?=__('active', 'system')?>
				</label>

				<div class="field">

					<? view::load('system/elements/field/edit', array(
						'prefix' => 'package',
						'field' => array(
							'keyword' => 'active',
							'type' => 'boolean',
						),
						'value' => $package,
					)) ?>

				</div>

			</div>

			<div class="row actions">
				<? view::load('system/elements/button'); ?>
			</div>

		</fieldset>

	<?=form_helper::closeForm(array('do_save_package' => 1))?>

</section>

<? view::load('cp/system/elements/template/footer'); ?>
