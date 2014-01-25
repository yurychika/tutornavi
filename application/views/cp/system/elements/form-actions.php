<? if ( isset($actions) && $actions ): ?>

	<div class="actions">
		<?=__('actions', 'system')?>
		<?=form_helper::select('action', $actions, '', array('class' => 'select', 'onchange' => "confirmForm('" . __('action_apply?', 'system') . "', '" . $form . "');this.value=0;"))?>
	</div>

<? endif; ?>