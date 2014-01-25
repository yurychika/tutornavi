	<? view::load('cp/system/elements/template/header'); ?>

<section class="plugin-newsletters newsletter-review">

	<table class="data <?=text_helper::alternate()?>">

		<tr class="<?=text_helper::alternate('odd','even')?>" id="input_row_newsletter_subject">
			<td class="name">
				<?=__('newsletter_subject', 'newsletters')?>
			</td>
			<td class="value">
				<?=$newsletter['subject']?>
			</td>
		</tr>

		<tr class="<?=text_helper::alternate('odd','even')?>" id="input_row_newsletter_message_html">
			<td class="name">
				<?=__('newsletter_message_html', 'newsletters')?>
			</td>
			<td class="value">
				<div class="box">
					<?=$newsletter['message_html']?>
				</div>
			</td>
		</tr>

		<tr class="<?=text_helper::alternate('odd','even')?>" id="input_row_newsletter_message_text">
			<td class="name">
				<?=__('newsletter_message_text', 'newsletters')?>
			</td>
			<td class="value">
				<div class="box">
					<p><?=str_replace(array("\r", "\n\n", "\n"), array('', '</p><p>', '<br />'), text_helper::entities($newsletter['message_text']))?></p>
				</div>
			</td>
		</tr>

		<tr class="<?=text_helper::alternate('odd','even')?>" id="input_row_newsletter_recipients">
			<td class="name">
				<?=__('newsletter_recipients', 'newsletters')?>
			</td>
			<td class="value">
				<?=$newsletter['total_users']?> - <?=html_helper::anchor('cp/content/newsletters/recipients/' . $newsletterID . '/view', __('view', 'system'), array('target' => '_blank'))?>
			</td>
		</tr>

		<? if ( $newsletter['total_sent'] ): ?>
			<tr class="<?=text_helper::alternate('odd','even')?>" id="input_row_newsletter_recipients">
				<td class="name">
					<?=__('status', 'system')?>
				</td>
				<td class="value">
					<?=__('newsletter_sending_status', 'newsletters', array('%1' => $newsletter['total_sent'], '%2' => $newsletter['total_users']))?>
				</td>
			</tr>
		<? endif; ?>

	</table>

	<div class="actions">
		<?=html_helper::anchor('cp/content/newsletters/edit/' . $newsletterID . '/review', __('newsletter_edit', 'newsletters'), array('class' => 'button'))?>
		<?=html_helper::anchor('cp/content/newsletters/recipients/' . $newsletterID, __('newsletter_recipients_edit', 'newsletters'), array('class' => 'button'))?>

		<? if ( $newsletter['total_sent'] ): ?>
			<?=html_helper::anchor('cp/content/newsletters/reset/' . $newsletterID, __('newsletter_reset', 'newsletters'), array('data-html' => __('newsletter_reset?', 'newsletters'), 'data-role' => 'confirm', 'class' => 'button'))?>
			<?=html_helper::anchor('cp/content/newsletters/send/' . $newsletterID . '/' . $newsletter['total_sent'], __('newsletter_resume', 'newsletters'), array('data-html' => __('newsletter_resume?', 'newsletters'), 'data-role' => 'confirm', 'class' => 'button'))?>
		<? else: ?>
		<?=html_helper::anchor('cp/content/newsletters/send/' . $newsletterID . '/0/test', __('newsletter_test', 'newsletters'), array('data-html' => __('newsletter_test?', 'newsletters'), 'data-role' => 'confirm', 'class' => 'button'))?>
			<?=html_helper::anchor('cp/content/newsletters/send/' . $newsletterID, __('newsletter_send', 'newsletters'), array('data-html' => __('newsletter_send?', 'newsletters'), 'data-role' => 'confirm', 'class' => 'button'))?>
		<? endif; ?>
	</div>

</section>

<? view::load('cp/system/elements/template/footer'); ?>
