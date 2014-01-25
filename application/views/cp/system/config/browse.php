<? view::load('cp/system/elements/template/header'); ?>

<section class="plugin-system settings-edit">

	<script type="text/javascript">var currentTab = '';</script>

	<?=form_helper::openForm('', array('data-role' => 'tabs-frames'))?>

		<? foreach ( $groups as $keyword => $group ): ?>

			<? if ( isset($settings[$keyword]) ): ?>

				<fieldset class="form grid <?=text_helper::alternate()?>" id="input_row_setting_<?=$keyword?>" data-role="frame" data-frame="<?=$keyword?>">

					<? foreach ( $settings[$keyword] as $setting ): ?>

						<div class="row <?=text_helper::alternate('odd','even')?>" id="input_row_setting_<?=$setting['keyword']?>">

							<label for="input_edit_setting_<?=$setting['keyword']?>">
								<?=$setting['name']?> <? if ( isset($setting['required']) && $setting['required'] ): ?><span class="required">*</span><? endif; ?>
							</label>

							<div class="field" <?=(config::item('devmode', 'system') == 2 ? 'style="position:relative"' : '')?>>

								<? view::load('system/elements/field/edit', array(
									'prefix' => 'setting',
									'field' => $setting,
									'value' => array($setting['keyword'] => $setting['value']),
									'error' => false,
								)) ?>

								<? if ( config::item('devmode', 'system') == 2 ): ?>
									<?=form_helper::text($setting['keyword'].'___order', $setting['order_id'], array('class' => 'text', 'style' => 'width:25px;position:absolute;right:-55px;top:0;')) ?>
									<?=form_helper::text($setting['keyword'].'___key', $setting['keyword'], array('class' => 'text', 'style' => 'width:130px;position:absolute;right:-200px;top:0;')) ?>
								<? endif; ?>

								<? if ( form_helper::error($setting['keyword']) ): ?>
									<?=form_helper::error($setting['keyword'])?>
									<script type="text/javascript">var currentTab = '<?=$keyword?>';</script>
								<? endif; ?>

							</div>

						</div>

					<? endforeach; ?>

					<div class="row actions">
						<? view::load('system/elements/button'); ?>
					</div>

				<? endif; ?>

			</fieldset>

		<? endforeach; ?>

	<?=form_helper::closeForm(array('do_save_settings' => 1))?>

</section>

<script type="text/javascript">
$(function(){
	$("#tabs ul").tabs({current:currentTab});
});
</script>

<? view::load('cp/system/elements/template/footer'); ?>
