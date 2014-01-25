<? view::load('header'); ?>

<section class="plugin-billing billing-plans">

	<? if ( $plans ): ?>

		<div class="content-view">

			<article class="post">

				<ul class="unstyled list-grid <?=text_helper::alternate()?> clearfix">

					<? foreach ( $plans as $plan ): ?>

						<li class="<?=text_helper::alternate('odd','even')?> clearfix" id="row-plan-<?=$plan['plan_id']?>">
							<span class="name">
								<?=html_helper::anchor('billing/plans/payment/'.$plan['plan_id'], $plan['name'])?>
							</span>
							<span class="amount">
								<?=money_helper::symbol(config::item('currency', 'billing')).$plan['price']?>
							</span>
						</li>

					<? endforeach; ?>

				</ul>

			</article>

		</div>

	<? endif; ?>

</section>

<? view::load('footer');
