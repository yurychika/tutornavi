<? view::load('cp/system/elements/template/header'); ?>

<section class="plugin-system language-translate">

	<?=form_helper::openForm()?>

		<fieldset class="form break <?=text_helper::alternate()?>">

			<div class="row <?=text_helper::alternate('odd','even')?>" id="input_row_language_translate_section">

				<label for="input_edit_language_translate_section">
					<?=__('language_section', 'system_languages')?>
				</label>

				<div class="field">

					<? view::load('system/elements/field/edit', array(
						'prefix' => 'language_translate',
						'field' => array(
							'keyword' => 'section',
							'type' => 'select',
							'items' => $sections,
						),
						'value' => array(),
					)) ?>

				</div>

			</div>

		</fieldset>

		<? foreach ( $language as $section => $groups ): ?>

			<? foreach ( $groups as $group => $types ): ?>

				<fieldset class="form section hidden <?=text_helper::alternate()?>" data-role="frame" data-frame="<?=$section.'_'.$group?>">

					<? foreach ( $types as $type => $data ): ?>

						<? foreach ( $data as $keyword => $value ): ?>

							<div class="row <?=text_helper::alternate('odd','even')?>" id="input_row_language_<?=$keyword?>">

								<label for="input_edit_<?=$group?>_<?=$keyword?>">
									<?=(isset($default[$section][$group][$type][$keyword]) ? $default[$section][$group][$type][$keyword] : "!$section-$group-$keyword!")?>
								</label>
								<div class="field" <?=(config::item('devmode', 'system') == 2 ? 'style="position:relative"' : '')?>>

									<div class="input-wrap">
										<?=form_helper::text($group.'_'.$keyword, form_helper::setValue($group.'_'.$keyword, ( isset($value) ? $value : '' )), array('class' => 'text input-wide '.(uri::segment(6) == 'english' || isset($default[$section][$group][$type][$keyword]) && utf8::strcasecmp($default[$section][$group][$type][$keyword], $value) ? 'translated' : 'untranslated'), 'id' => 'input_edit_'.$group.'_'.$keyword)) ?>
										<? if ( config::item('devmode', 'system') == 2 ): ?>
											<?=form_helper::text($group.'_'.$keyword.'___key', $keyword, array('class' => 'text', 'style' => 'width:130px;position:absolute;right:-160px;top:0;')) ?>
										<? endif; ?>
									</div>

									<? if ( form_helper::error($group.'_'.$keyword) ): ?>
										<?=form_helper::error($group.'_'.$keyword)?>
										<script type="text/javascript">
										if ( typeof(current) == 'undefined' )
										{
											var current = '<?=$section.'_'.$group?>';
										}
										</script>
									<? endif; ?>

								</div>

							</div>

						<? endforeach; ?>

					<? endforeach; ?>

					<div class="row actions">
						<? view::load('system/elements/button'); ?>
					</div>

				</fieldset>

			<? endforeach; ?>

		<? endforeach; ?>

	<?=form_helper::closeForm(array('do_save_language' => 1))?>

</section>

<script type="text/javascript">
$(function(){
	$('#input_edit_language_translate_section').change(function(obj){
		$('[data-role="frame"]').hide();
		$('[data-frame="' + $('#input_edit_language_translate_section').val() + '"]').show();
	})

	if ( typeof(current) != 'undefined' )
	{
		$('#input_edit_language_translate_section').val(current)
		$('[data-frame="' + current + '"]').show();
	}
	else
	{
		$('[data-frame="' + $('#input_edit_language_translate_section').val() + '"]').show();
	}
});
</script>

<? view::load('cp/system/elements/template/footer'); ?>
