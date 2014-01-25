<? view::load('header'); ?>

<section class="plugin-messages message-send">

	<?=form_helper::openForm()?>

		<fieldset class="form <?=text_helper::alternate()?>">

			<div class="row <?=text_helper::alternate('odd','even')?>" id="input_row_message_edit_recipients">

				<label for="input_edit_message_edit_recipients">
					<?=__('message_send_to', 'messages')?> <? if ( !$user ): ?><span class="required">*</span><? endif; ?>
				</label>

				<div class="field">

					<? if ( $user ): ?>

						<span class="static">
							<?=users_helper::anchor($user)?>
						</span>

					<? else: ?>

						<? view::load('system/elements/field/edit', array(
							'prefix' => 'message_edit',
							'field' => array(
								'keyword' => 'recipients',
								'type' => 'text',
							),
							'value' => '',
						)) ?>

					<? endif; ?>

				</div>

			</div>

			<div class="row <?=text_helper::alternate('odd','even')?>" id="input_row_message_edit_subject">

				<label for="input_edit_message_edit_subject">
					<?=__('message_subject', 'messages')?> <span class="required">*</span>
				</label>

				<div class="field">

					<? view::load('system/elements/field/edit', array(
						'prefix' => 'message_edit',
						'field' => array(
							'keyword' => 'subject',
							'type' => 'text',
						),
						'value' => '',
					)) ?>

				</div>

			</div>

			<div class="row <?=text_helper::alternate('odd','even')?>" id="input_row_message_edit_message">

				<label for="input_edit_message_edit_message">
					<?=__('message', 'messages')?> <span class="required">*</span>
				</label>

				<div class="field">

					<? view::load('system/elements/field/edit', array(
						'prefix' => 'message_edit',
						'field' => array(
							'keyword' => 'message',
							'type' => 'textarea',
							'class' => 'input-wide input-large-y',
						),
						'value' => '',
					)) ?>

				</div>

			</div>

			<div class="row actions clearfix">
				<? view::load('system/elements/button', array('value' => __('send', 'system'))); ?>

				<? if ( config::item('templates_active', 'messages') && $templates ): ?>
					<?=html_helper::anchor('#', __('message_template_help', 'messages'), array('class' => 'template-help', 'onclick' => "\$('#messages-templates').slideToggle('fast');return false;"))?>
				<? endif; ?>
			</div>

		</fieldset>

	<?=form_helper::closeForm(array('do_save_conversation' => 1))?>

	<? if ( config::item('templates_active', 'messages') && $templates ): ?>

		<div id="messages-templates" class="hidden">

			<h3><?=__('message_template_select', 'messages')?></h3>

			<ul class="unstyled icon-list arrow">
				<? foreach ( $templates as $template ): ?>
					<li id="ajax-messages-template-<?=$template['template_id']?>"><?=html_helper::anchor('messages/send#', $template['name'], array('onclick' => 'selectMessageTemplate(' . $template['template_id'] . ');return false;'))?></li>
				<? endforeach; ?>
			</ul>

		</div>

	<? endif; ?>

</section>

<? if ( config::item('templates_active', 'messages') && $templates ): ?>

	<script type="text/javascript">
		function selectMessageTemplate(templateID)
		{
			if ( $('#input_edit_message_edit_subject').val() == '' && $('#input_edit_message_edit_message').val() == '' || confirm('<?=__('message_template_overwrite', 'messages', array('\'' => '\\\''), array(), false)?>') )
			{
				runAjax('<?=html_helper::siteURL('messages/template')?>/'+templateID, {}, function(message){
						$('#input_edit_message_edit_subject').val(message.subject);
						$('#input_edit_message_edit_message').val(message.message);
					},
					null, 'ajax-messages-template-'+templateID, 'icon-system-ajax'
				);
			}
		}
	</script>

<? endif; ?>

<? view::load('footer');
