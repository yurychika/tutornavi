<? view::load('header'); ?>

<section class="plugin-messages messages-index">

	<? if ( $conversations ): ?>

		<ul class="unstyled content-list <?=text_helper::alternate()?>">

			<? foreach ( $conversations['threads'] as $conversation ): ?>

				<li class="clearfix <?=text_helper::alternate('odd','even')?> <?=($conversation['new'] ? 'new' :'')?>">

					<article class="item">

						<figure class="image users-image">
							<? if ( $conversation['total_recipients'] > 1 ): ?>
								<? foreach ( $conversations['recipients'][$conversation['conversation_id']] as $userID => $recipient ): ?>
									<? if ( $userID != session::item('user_id') ): ?>
										<? view::load('users/profile/elements/picture', array_merge($conversations['users'][$userID],array('picture_file_suffix' => 't'))); ?>
										<? break; ?>
									<? endif; ?>
								<? endforeach; ?>
							<? elseif ( isset($conversations['users'][$conversations['recipients'][$conversation['conversation_id']]['user_id']]) ): ?>
								<? view::load('users/profile/elements/picture', array_merge($conversations['users'][$conversations['recipients'][$conversation['conversation_id']]['user_id']], array('picture_file_suffix' => 't'))); ?>
							<? else: ?>
								<? view::load('users/profile/elements/picture', array('picture_file_suffix' => 't')); ?>
							<? endif; ?>
						</figure>

						<header class="item-header">
							<h2>
								<?=html_helper::anchor('messages/view/'.$conversation['conversation_id'], $conversation['subject'])?>
							</h2>
						</header>

						<div class="item-article">
							<?=text_helper::truncate($conversation['message'], 120)?>
						</div>

						<footer class="item-footer">

							<ul class="unstyled content-meta clearfix">
								<li class="date">
									<?=__('message_date', 'messages', array('%date' => date_helper::formatDate($conversation['last_post_date'])))?>
								</li>
								<li class="user">
									<? if ( $conversation['message_user_id'] != session::item('user_id') ): ?>
										<?=__('message_from', 'messages')?>
									<? else: ?>
										<?=__('message_to', 'messages')?>
									<? endif; ?>
									<? if ( $conversation['total_recipients'] > 1 ): ?>
										<? foreach ( $conversations['recipients'][$conversation['conversation_id']] as $userID => $recipient ): ?>
											<? if ( $userID != session::item('user_id') ): ?>
												<?=html_helper::anchor($conversations['users'][$userID]['slug'], $conversations['users'][$userID]['name'])?>
												<? break; ?>
											<? endif; ?>
										<? endforeach; ?>
										<?=html_helper::anchor('messages/people/'.$conversation['conversation_id'], '+'.($conversation['total_recipients']-1))?>
									<? else: ?>
										<?=users_helper::anchor($conversations['users'][$conversations['recipients'][$conversation['conversation_id']]['user_id']])?>
									<? endif; ?>
								</li>
								<li class="actions">
									<?=html_helper::anchor('messages/delete/'.$conversation['conversation_id'].'?'.$qstring['url'].'page='.$qstring['page'], __('delete', 'system'), array('data-html' => __('conversation_delete?', 'messages'), 'data-role' => 'confirm'))?>
								</li>
							</ul>

						</footer>

					</article>

				</li>

			<? endforeach; ?>

		</ul>

		<div class="content-footer">
			<? view::load('system/elements/pagination', array('pagination' => $pagination)); ?>
		</div>

	<? endif; ?>

</section>

<? view::load('footer');
