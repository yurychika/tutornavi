<!DOCTYPE html>
<html <?=( input::isAjaxRequest() || input::get('modal') ) ? 'class="modal"' : ''?>>
<head>
<meta http-equiv="content-type" content="text/html; charset=utf-8" />
<title><?=(view::getMetaTitle() ? view::getMetaTitle().(uri::getURI() != '' ? ' - ' : '') : '')?><?=(uri::getURI() != '' ? text_helper::entities(config::item('site_title', 'system')) : '')?></title>
<?=html_helper::style(html_helper::siteURL('load/css/'.session::item('template')))?>
<?=view::getStylesheets()?>
<?=html_helper::script(html_helper::siteURL('load/javascript'))?>
<?=view::getJavascripts()?>
<meta name="description" content="<?=view::getMetaDescription()?>" />
<meta name="keywords" content="<?=view::getMetaKeywords()?>" />
</head>
<body <?=( input::isAjaxRequest() || input::get('modal') ) ? 'class="modal"' : ''?>>
	<? if ( input::isAjaxRequest() || input::get('modal') ): ?>
	<? else: ?>
		<header id="header">
			<div class="inner clearfix">
				<?=html_helper::anchor('', '<span>'.text_helper::entities(config::item('site_title', 'system')).'</span>', array('class' => 'title'))?>
				<div class="float-right">
					<?=banners_helper::showBanner('header')?>
				</div>
			</div>
		</header>
		<nav id="site-nav">
			<div class="inner">
				<ul class="unstyled clearfix">
					<? foreach ( config::item('site_top_nav', 'lists') as $list ): ?>
						<li><?=html_helper::anchor($list['uri'], $list['name'], $list['attr'])?></li>
					<? endforeach; ?>
					<? if ( !users_helper::isLoggedin() ): ?>
						<li class="signup"><?=html_helper::anchor('users/signup', __('signup', 'system_navigation'), array('class' => 'users-signup'))?></li>
						<li class="login"><?=html_helper::anchor('users/login', __('login', 'system_navigation'), array('class' => 'users-login'))?></li>
					<? endif; ?>
				</ul>
			</div>
		</nav>
		<? if ( uri::getURI() == '' && ((users_helper::isLoggedin() && config::item('homepage_user', 'users') == 'default') || (!users_helper::isLoggedin() && config::item('homepage_public', 'users') == 'default')) ): ?>
			<section class="homepage-top">
				<div class="splash clearfix">
					<div class="promo-text">
						<div class="text">
							<?=pages_helper::getPage(array('location' => 'site/homepage'))?>
						</div>
						<div class="action">
							<? if ( !users_helper::isLoggedin() ): ?>
								<?=html_helper::anchor('users/signup', __('signup', 'system_navigation'), array('class' => 'button large success users-signup'))?>
								<?=html_helper::anchor('users/login', __('login', 'system_navigation'), array('class' => 'button large important users-login'))?>
							<? else: ?>
								<?=html_helper::anchor(session::item('slug'), __('my_profile', 'system_navigation'), array('class' => 'button large success users-profile'))?>
								<?=html_helper::anchor('users', __('search', 'system'), array('class' => 'button large important users-search'))?>
							<? endif; ?>
						</div>
					</div>
					<div class="promo-image"><div class="image"></div></div>
				</div>
			</section>
		<? endif; ?>
		<section id="container" <?=( uri::getURI() == '' && ((users_helper::isLoggedin() && config::item('homepage_user', 'users') == 'default') || (!users_helper::isLoggedin() && config::item('homepage_public', 'users') == 'default')) ? 'class="one"' : '')?>>
			<div class="inner clearfix">

				<? if ( uri::getURI() != '' || users_helper::isLoggedin() && config::item('homepage_user', 'users') != 'default' || !users_helper::isLoggedin() && config::item('homepage_public', 'users') != 'default' ): ?>

					<aside id="sidebar">

						<? if ( !users_helper::isLoggedin() ): ?>

							<div class="login">

								<? if ( config::item('auth_methods', 'users', 'default') ): ?>

									<?=form_helper::openForm('users/login')?>

										<fieldset class="form <?=text_helper::alternate()?>">
											<div class="row <?=text_helper::alternate('odd','even')?> text" id="input_row_quick_login_email">
												<label for="input_edit_quick_login_email">
													<?=__('email', 'users')?>
												</label>
												<div class="field">
													<? view::load('system/elements/field/edit', array(
														'prefix' => 'quick_login',
														'field' => array(
															'keyword' => 'email',
															'type' => 'text',
															'class' => 'email',
														),
														'value' => '',
														'error' => false,
													)) ?>
												</div>
											</div>
											<div class="row <?=text_helper::alternate('odd','even')?> text" id="input_row_quick_login_password">
												<label for="input_edit_quick_login_password">
													<?=__('password', 'users')?>
												</label>
												<div class="field">
													<? view::load('system/elements/field/edit', array(
														'prefix' => 'quick_login',
														'field' => array(
															'keyword' => 'password',
															'type' => 'password',
															'maxlength' => 128,
															'class' => 'password',
														),
														'value' => '',
														'error' => false,
													)) ?>
												</div>
											</div>
											<div class="row <?=text_helper::alternate('odd','even')?>" id="input_row_quick_login_remember">
												<div class="field">
													<? view::load('system/elements/field/edit', array(
														'prefix' => 'quick_login',
														'field' => array(
															'name' => __('remember_me', 'users'),
															'keyword' => 'remember',
															'type' => 'checkmark',
														),
														'value' => '',
													)) ?>
												</div>
											</div>
											<div class="row actions">
												<? view::load('system/elements/button', array('class' => 'small', 'value' => __('login', 'system_navigation'))); ?>
												<div class="remote-connect extra">
													<? foreach ( users_helper::authButtons('login', 'small') as $button ): ?>
														<?=$button?>
													<? endforeach; ?>
												</div>
											</div>
										</fieldset>

									<?=form_helper::closeForm(array('do_login' => 1))?>

								<? elseif ( uri::getURI() != 'users/login' ): ?>

									<div class="remote-connect single">
										<? foreach ( users_helper::authButtons('login') as $button ): ?>
											<?=$button?>
										<? endforeach; ?>
									</div>

								<? endif; ?>

							</div>

						<? else: ?>

							<nav <?=(config::item('userbar_icons', 'template') ? 'class="icons"' : '')?>>
								<ul class="unstyled clearfix">
									<? foreach ( config::item('site_user_nav', 'lists') as $list ): ?>
										<? switch ( $list['keyword'] ):
											case 'user/profile': ?>
												<li class="dropdown">
													<?=html_helper::anchor(session::item('slug'), $list['name'], $list['attr'])?>
													<?=(config::item('timeline_active', 'timeline') ? html_helper::anchor('timeline/notices', '<span>'.(session::item('total_notices_new') ? '+'.session::item('total_notices_new') : '0').'</span>', array('id' => 'timeline-notices-recent-action', 'onclick' => 'timelineNoticesToggle();return false;', 'class' => 'badge-icon icon '.(session::item('total_notices_new') ? 'icon-timeline-notices-new' : 'icon-timeline-notices'))) : '')?>
													<div class="timeline-notices dropdown content-box nobreak" style="display:none" id="timeline-notices-recent-popup">
														<div class="header">
															<div class="header">
																<span><?=html_helper::anchor('timeline/notices', __('my_timeline_notifications', 'system_navigation'))?></span>
															</div>
														</div>
														<ul class="unstyled"></ul>
													</div>
												</li>
											<? break; case 'users/friends/manage': ?>
												<? if ( config::item('friends_active', 'users') ): ?>
													<li>
														<?=html_helper::anchor($list['uri'], $list['name'], $list['attr'])?>
														<?=(session::item('total_friends_i') ? html_helper::anchor('users/friends/requests', '+'.session::item('total_friends_i'), array('class' => 'badge small info')) : '')?>
													</li>
												<? endif; ?>
											<? break; case 'users/visitors/manage': ?>
												<? if ( config::item('visitors_active', 'users') ): ?>
													<li>
														<?=html_helper::anchor($list['uri'], $list['name'], $list['attr'])?>
														<?=(session::item('total_visitors_new') ? html_helper::anchor('users/visitors/manage', '+'.session::item('total_visitors_new'), array('class' => 'badge small info')) : '')?>
													</li>
												<? endif; ?>
											<? break; case 'users/cp': ?>
												<? if ( session::permission('site_access_cp', 'system') ): ?>
													<li><?=html_helper::anchor($list['uri'], $list['name'], $list['attr'])?></li>
												<? endif; ?>
											<? break; case 'messages/manage': ?>
												<? if ( config::item('messages_active', 'messages') ): ?>
													<li>
														<?=html_helper::anchor($list['uri'], $list['name'], $list['attr'])?>
														<?=(session::item('total_conversations_new') ? html_helper::anchor('messages/manage', '+'.session::item('total_conversations_new'), array('class' => 'badge small info')) : '')?>
													</li>
												<? endif; ?>
											<? break; case 'gifts/manage': ?>
												<? if ( config::item('gifts_active', 'gifts') ): ?>
													<li>
														<?=html_helper::anchor($list['uri'], $list['name'], $list['attr'])?>
														<?=(session::item('total_gifts_new') ? html_helper::anchor('gifts/manage', '+'.session::item('total_gifts_new'), array('class' => 'badge small info')) : '')?>
													</li>
												<? endif; ?>
											<? break; case 'timeline/manage': ?>
												<? if ( config::item('timeline_active', 'timeline') && (config::item('timeline_public_feed', 'timeline') || config::item('timeline_user_feed', 'timeline')) ): ?>
													<li><?=html_helper::anchor($list['uri'], $list['name'], $list['attr'])?></li>
												<? endif; ?>
											<? break; default: ?>
												<li><?=html_helper::anchor($list['uri'], $list['name'], $list['attr'])?></li>
										<? endswitch; ?>
									<? endforeach; ?>
								</ul>
							</nav>

						<? endif; ?>

						<?=banners_helper::showBanner('sidebar')?>

					</aside>

				<? endif; ?>

				<section id="content">

					<? if ( view::getTrail() ): ?>

						<nav id="trail">
							<ul class="unstyled clearfix">
								<? foreach ( view::getTrail() as $index => $item ): ?>

									<? if ( $item['uri'] !== false ): ?>
										<? if ( $index && ( !isset($item['attr']['side']) || !$item['attr']['side'] ) ): ?>
											<li>&#187;</li>
										<? endif ?>
										<li <?=(isset($item['attr']['side']) && $item['attr']['side'] ? 'class="side"' : '')?>><?=html_helper::anchor($item['uri'], text_helper::truncate($item['name'], 40))?></li>
									<? else: ?>
										<li <?=(isset($item['attr']['side']) && $item['attr']['side'] ? 'class="side"' : '')?>><?=$item['name']?></li>
									<? endif; ?>

								<? endforeach; ?>
							</ul>
						</nav>

					<? endif; ?>

					<? if ( view::getTitle() || view::getActions() ): ?>

						<hgroup class="content clearfix">

							<? if ( view::getTitle() ): ?>
								<h1><?=view::getTitle()?></h1>
							<? endif; ?>

							<? if ( view::getActions() ): ?>
								<nav>
									<ul class="unstyled clearfix">
										<? foreach ( view::getActions() as $item ): ?>
											<li>
												<? if ( $item['uri'] !== false ): ?>
													<?=html_helper::anchor($item['uri'], $item['name'], $item['attr'])?>
												<? else: ?>
													<?=$item['name']?>
												<? endif; ?>
											</li>
										<? endforeach; ?>
									</ul>
								</nav>
							<? endif; ?>

						</hgroup>

					<? endif; ?>

					<? if ( view::getTabs() && count(view::getTabs()) > 1 ): ?>
						<nav id="tabs">
							<ul class="unstyled clearfix" data-role="tabs" data-target="tabs-frames">
								<? foreach ( view::getTabs() as $item ): ?>
									<li>
										<? if ( $item['uri'] !== false ): ?>
											<?=html_helper::anchor($item['uri'], $item['name'], $item['attr'])?>
										<? else: ?>
											<span><?=$item['name']?></span>
										<? endif; ?>
									</li>
								<? endforeach; ?>
							</ul>
						</nav>
					<? endif; ?>

	<? endif; ?>

	<? if ( !isset($message) || $message ): ?>
		<? view::load('message'); ?>
	<? endif; ?>
