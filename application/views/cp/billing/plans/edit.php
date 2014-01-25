<? view::load('cp/system/elements/template/header'); ?>

<section class="plugin-billing plan-edit">

	<?=form_helper::openForm()?>

		<fieldset class="form grid <?=text_helper::alternate()?>">

			<div class="row <?=text_helper::alternate('odd','even')?>" id="input_row_plan_name">

				<label for="input_edit_plan_name">
					<?=__('name', 'system')?> <span class="required">*</span>
				</label>

				<div class="field">

					<? foreach ( config::item('languages', 'core', 'keywords') as $language ): ?>
						<div class="translate_item_<?=$language?> <?=( $language != session::item('language') ? 'hidden ' : '' )?>">
							<? view::load('system/elements/field/edit', array(
								'prefix' => 'plan',
								'name_suffix' => '_' . $language,
								'language' => $language,
								'field' => array(
									'keyword' => 'name',
									'type' => 'text',
									'multilang' => true,
								),
								'error' => false,
								'value' => $plan,
							)) ?>
						</div>
					<? endforeach; ?>
					<? foreach ( config::item('languages', 'core', 'keywords') as $language ): ?>
						<?=form_helper::error('name_'.$language)?>
					<? endforeach; ?>

				</div>

			</div>

			<div class="row <?=text_helper::alternate('odd','even')?>" id="input_row_plan_description">

				<label for="input_edit_plan_description">
					<?=__('description', 'system')?> <span class="required">*</span>
				</label>

				<div class="field">

					<? foreach ( config::item('languages', 'core', 'keywords') as $language ): ?>
						<div class="translate_item_<?=$language?> <?=( $language != session::item('language') ? 'hidden ' : '' )?>">
							<? view::load('system/elements/field/edit', array(
								'prefix' => 'plan',
								'name_suffix' => '_' . $language,
								'language' => $language,
								'field' => array(
									'keyword' => 'description',
									'type' => 'textarea',
									'class' => 'input-wide input-medium-y',
									'multilang' => true,
								),
								'error' => false,
								'value' => $plan,
							)) ?>
						</div>
					<? endforeach; ?>
					<? foreach ( config::item('languages', 'core', 'keywords') as $language ): ?>
						<?=form_helper::error('description_'.$language)?>
					<? endforeach; ?>

				</div>

			</div>

			<div class="row <?=text_helper::alternate('odd','even')?>" id="input_row_plan_cycle">

				<label for="input_edit_plan_duration">
					<?=__('plan_cycle', 'billing_plans')?> <span class="required">*</span>
				</label>

				<div class="field">

					<? view::load('system/elements/field/edit', array(
						'prefix' => 'plan',
						'field' => array(
							'keyword' => 'duration',
							'type' => 'text',
							'class' => 'input-small',
						),
						'value' => $plan,
						'error' => false,
						'wrap' => false,
					)) ?>

					<? view::load('system/elements/field/edit', array(
						'prefix' => 'plan',
						'field' => array(
							'keyword' => 'cycle',
							'type' => 'select',
							'items' => array('' => __('select', 'system')) + $cycles,
						),
						'value' => $plan,
						'error' => false,
					)) ?>

					<?=(form_helper::error('duration') ? form_helper::error('duration') : form_helper::error('cycle'))?>

				</div>

			</div>

			<div class="row <?=text_helper::alternate('odd','even')?>" id="input_row_plan_price">

				<label for="input_edit_plan_price">
					<?=__('price', 'billing')?> <span class="required">*</span>
				</label>

				<div class="field">

					<? view::load('system/elements/field/edit', array(
						'prefix' => 'plan',
						'field' => array(
							'keyword' => 'price',
							'type' => 'text',
							'class' => 'input-small',
						),
						'value' => $plan,
					)) ?>

				</div>

			</div>

			<div class="row <?=text_helper::alternate('odd','even')?>" id="input_row_plan_group_id">

				<label for="input_edit_plan_group_id">
					<?=__('user_group', 'users')?> <span class="required">*</span>
				</label>

				<div class="field">

					<? view::load('system/elements/field/edit', array(
						'prefix' => 'plan',
						'field' => array(
							'keyword' => 'group_id',
							'type' => 'select',
							'items' => array('' => __('select', 'system')) + $groups,
						),
						'value' => $plan,
					)) ?>

				</div>

			</div>

			<div class="row <?=text_helper::alternate('odd','even')?>" id="input_row_plan_signup">

				<label for="input_edit_plan_signup">
					<?=__('plan_show_signup', 'billing_plans')?>
				</label>

				<div class="field">

					<? view::load('system/elements/field/edit', array(
						'prefix' => 'plan',
						'field' => array(
							'keyword' => 'signup',
							'type' => 'boolean',
						),
						'value' => $plan,
					)) ?>

				</div>

			</div>

			<div class="row <?=text_helper::alternate('odd','even')?>" id="input_row_plan_active">

				<label for="input_edit_plan_active">
					<?=__('active', 'system')?>
				</label>

				<div class="field">

					<? view::load('system/elements/field/edit', array(
						'prefix' => 'plan',
						'field' => array(
							'keyword' => 'active',
							'type' => 'boolean',
						),
						'value' => $plan,
					)) ?>

				</div>

			</div>

			<div class="row actions">
				<? view::load('system/elements/button'); ?>
			</div>

		</fieldset>

	<?=form_helper::closeForm(array('do_save_plan' => 1))?>

</section>

<? view::load('cp/system/fields/multilang') ?>

<? view::load('cp/system/elements/template/footer');