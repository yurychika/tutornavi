<? view::load('header'); ?>

<section class="plugin-users settings-account-email">

	<div class="content-view">

		<article class="item">

			<div class="item-article">
				<?=__('account_cancel?', 'users')?><br/><br/>

				<?=html_helper::anchor('users/settings/cancel/confirm', __('yes', 'system'), array('class' => 'button success'))?>&nbsp;&nbsp;
				<?=html_helper::anchor('users/settings', __('no', 'system'), array('class' => 'button important'))?>
			</div>

		</article>

	</div>

</section>

<? view::load('footer');
