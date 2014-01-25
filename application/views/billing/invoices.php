<? view::load('header'); ?>

<section class="plugin-billing billing-invoices">

	<? if ( $invoices ): ?>

		<ul class="unstyled list-grid table <?=text_helper::alternate()?> clearfix">

			<li class="header clearfix">
				<span class="name"><?=__('product', 'billing')?></span>
				<span class="date"><?=__('payment_date', 'billing')?></span>
				<span class="amount"><?=__('amount', 'billing')?></span>
			</li>

			<? foreach ( $invoices as $invoice ): ?>

				<li class="<?=text_helper::alternate('odd','even')?> clearfix" id="row-invoice-<?=$invoice['transaction_id']?>">
					<span class="name">
						<?=$invoice['name']?>
					</span>
					<span class="date">
						<?=date_helper::formatDate($invoice['post_date'])?>
					</span>
					<span class="amount">
						<?=money_helper::symbol(config::item('currency', 'billing')).$invoice['amount']?>
					</span>
				</li>

			<? endforeach; ?>

		</ul>

		<div class="content-footer">
			<? view::load('system/elements/pagination', array('pagination' => $pagination)); ?>
		</div>

	<? endif; ?>

</section>

<? view::load('footer');
