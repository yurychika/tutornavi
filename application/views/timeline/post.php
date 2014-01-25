<?=form_helper::openForm()?>

	<fieldset class="form">
		<div class="row">
			<div class="field">

				<? view::load('system/elements/field/edit', array(
					'prefix' => 'timeline',
					'field' => array(
						'keyword' => 'message',
						'type' => 'textarea',
						'class' => 'input-wide input-small preview',
						'placeholder' => __('message_placeholder', 'timeline'),
					),
				)); ?>

			</div>
		</div>
		<div class="row actions clearfix" style="display:none">
			<? view::load('system/elements/button', array(
				'onclick' => "timelinePost('".config::siteURL('timeline')."',{'post':1,'user_id':".($user ? $user['user_id'] : 0)."});return false;",
				'class' => 'small'
			)); ?>
			<span class="icon icon-system-ajax ajax" style="display:none" id="ajax-timeline-post"></span>
		</div>
	</fieldset>

<?=form_helper::closeForm(array('do_save_message' => 1))?>
