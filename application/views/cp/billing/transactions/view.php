<? view::load('cp/system/elements/template/header'); ?>

<section class="plugin-billing transaction-view">

	<table class="data <?=text_helper::alternate()?>">

		<tr class="<?=text_helper::alternate('odd','even')?>" id="input_row_transaction_transaction_id">
			<td class="name">
				<?=__('transaction_id', 'billing_transactions')?>
			</td>
			<td class="value">
				<?=$transaction['transaction_id']?>
			</td>
		</tr>

		<tr class="<?=text_helper::alternate('odd','even')?>" id="input_row_transaction_name">
			<td class="name">
				<?=__('product', 'billing')?>
			</td>
			<td class="value">
				<?=$transaction['name']?>
			</td>
		</tr>

		<tr class="<?=text_helper::alternate('odd','even')?>" id="input_row_transaction_amount">
			<td class="name">
				<?=__('amount', 'billing')?>
			</td>
			<td class="value">
				<?=money_helper::symbol(config::item('currency', 'billing')).$transaction['amount']?>
			</td>
		</tr>

		<tr class="<?=text_helper::alternate('odd','even')?>" id="input_row_transaction_user">
			<td class="name">
				<?=__('user', 'system')?>
			</td>
			<td class="value">
				<?=users_helper::anchor($user)?>
			</td>
		</tr>

		<tr class="<?=text_helper::alternate('odd','even')?>" id="input_row_transaction_payment_date">
			<td class="name">
				<?=__('payment_date', 'billing')?>
			</td>
			<td class="value">
				<?=date_helper::formatDate($transaction['post_date'])?>
			</td>
		</tr>

		<tr class="<?=text_helper::alternate('odd','even')?>" id="input_row_transaction_gateway_id">
			<td class="name">
				<?=__('payment_gateway', 'billing')?>
			</td>
			<td class="value">
				<?=$gateways[$transaction['gateway_id']]['name']?>
			</td>
		</tr>

		<tr class="<?=text_helper::alternate('odd','even')?>" id="input_row_transaction_receipt_id">
			<td class="name">
				<?=__('receipt_id', 'billing_transactions')?>
			</td>
			<td class="value">
				<?=$transaction['receipt_id']?>
			</td>
		</tr>

	</table>

</section>

<? view::load('cp/system/elements/template/footer'); ?>
