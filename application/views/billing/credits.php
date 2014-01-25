<? view::load('header'); ?>

<section class="plugin-billing billing-credits">

	<? if ( $packages ): ?>

		<ul class="unstyled list-grid <?=text_helper::alternate()?> clearfix">

			<? foreach ( $packages as $package ): ?>

				<li class="<?=text_helper::alternate('odd','even')?> clearfix" id="row-plan-<?=$package['package_id']?>">
					<span class="name">
						<?=html_helper::anchor('billing/credits/payment/'.$package['package_id'], __('credits_info', 'billing_credits', array('%s' => $package['credits'])))?>
					</span>
					<span class="amount">
						<?=money_helper::symbol(config::item('currency', 'billing')).$package['price']?>
					</span>
				</li>

			<? endforeach; ?>

		</ul>

	<? endif; ?>

</section>

<? view::load('footer');
