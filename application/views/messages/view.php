<? view::load('header'); ?>

<section class="plugin-messages message-view">

	<ul class="unstyled content-list <?=text_helper::alternate()?>">

		<? foreach ( $conversation['messages'] as $message ): ?>

			<li class="clearfix <?=text_helper::alternate('odd','even')?>">

				<article class="item">

					<figure class="image users-image">
						<? if ( isset($conversation['users'][$message['user_id']]) ): ?>
							<? view::load('users/profile/elements/picture', array_merge($conversation['users'][$message['user_id']], array('picture_file_suffix' => 't'))); ?>
						<? else: ?>
							<? view::load('users/profile/elements/picture', array('picture_file_suffix' => 't')); ?>
						<? endif; ?>
					</figure>

					<header class="item-header">
						<h2>
							<?=users_helper::anchor($conversation['users'][$message['user_id']])?>
						</h2>
					</header>

					<div class="item-article">
						<?=nl2br($message['message'])?>
					</div>

					<footer class="item-footer">
						<ul class="unstyled content-meta clearfix">
							<li class="date">
								<?=__('message_date', 'messages', array('%date' => date_helper::formatDate($message['post_date'])))?>
							</li>
							<? if ( config::item('reports_active', 'reports') && users_helper::isLoggedin() && $message['user_id'] != session::item('user_id') && session::permission('reports_post', 'reports') ): ?>
								<li class="report">
									<?=html_helper::anchor('report/submit/message/'.$message['message_id'], __('report', 'system'), array('data-role' => 'modal', 'data-display' => 'iframe', 'data-title' => __('report', 'system')))?>
								</li>
							<? endif; ?>
						</ul>
					</footer>

				</article>

			</li>

		<? endforeach; ?>

	</ul>

	<?=form_helper::openForm()?>

		<fieldset class="form post <?=text_helper::alternate()?>">

			<div class="row <?=text_helper::alternate('odd','even')?>" id="input_row_message_edit_message">

				<label for="input_edit_message_edit_message">
					<?=__('message_new', 'messages')?> <span class="required">*</span>
				</label>

				<div class="field">

					<? view::load('system/elements/field/edit', array(
						'prefix' => 'message_edit',
						'field' => array(
							'keyword' => 'message',
							'type' => 'textarea',
							'class' => 'input-wide input-medium-y',
						),
						'value' => '',
					)) ?>

				</div>

			</div>

			<div class="row actions">
				<? view::load('system/elements/button', array('value' => __('send', 'system'))); ?>
			</div>

		</fieldset>

	<?=form_helper::closeForm(array('do_save_message' => 1))?>

</section>

<? view::load('footer');
