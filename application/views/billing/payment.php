<? view::load('header'); ?>

<section class="plugin-billing billing-payment">

	<div class="content-view">

		<article class="item">

			<header class="item-header">
				<h2>
					<?=html_helper::anchor($location, $product['name'].' - '.money_helper::symbol(config::item('currency', 'billing')).$product['price'])?>
				</h2>
			</header>

			<? if ( isset($product['description']) && $product['description'] ): ?>
				<div class="item-article">
					<?=$product['description']?>
				</div>
			<? endif; ?>

			<? if ( count($gateways) > 1 ): ?>
				<header class="item-header">
					<h2><?=__('payment_method', 'billing_transactions')?></h2>
				</header>
			<? endif; ?>

			<div class="checkout <?=text_helper::alternate()?>">

				<? foreach ( $gateways as $gateway ): ?>

					<div class="gateway <?=text_helper::alternate('odd','even')?>" id="row-gateway-<?=$gateway['keyword']?>">

						<? if ( isset($gateway['settings']['button']) ): ?>

							<?=html_helper::anchor('billing/' . $product['type'] . '/checkout/' . $product['product_id'] . '/' . $gateway['keyword'], html_helper::image($gateway['settings']['button']))?>

						<? else: ?>

							<?=html_helper::anchor('billing/' . $product['type'] . '/checkout/' . $product['product_id'] . '/' . $gateway['keyword'], __('payment_button', 'billing_transactions', array('%s' => $gateway['name'])))?>

						<? endif; ?>

					</div>

				<? endforeach; ?>

			</div>

		</article>

	</div>

</section>

<? view::load('footer');
