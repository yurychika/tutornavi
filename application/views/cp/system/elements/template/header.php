<!DOCTYPE html>
<html<?=( input::isAjaxRequest() || input::get('modal') ) ? ' class="modal"' : ''?>>
<head>
<meta http-equiv="content-type" content="text/html; charset=utf-8" />
<title><?=(view::getMetaTitle() ? text_helper::truncate(view::getMetaTitle(), 100).' - ' : '')?>SocialScript</title>
<?=html_helper::style(html_helper::siteURL('load/css/cp'))?>
<?=view::getStylesheets()?>
<?=html_helper::script(html_helper::siteURL('load/javascript/cp'))?>
<?=view::getJavascripts()?>
</head>
<body <?=( input::isAjaxRequest() || input::get('modal') ) ? 'class="modal"' : ''?>>
	<? if ( input::isAjaxRequest() || input::get('modal') ): ?>
	<? else: ?>
		<header id="header">
			<div class="row-container">
				<div class="row">
					<?=html_helper::anchor('cp', '<span>SocialScript</span>', array('class' => 'title'))?>
					<? if ( users_helper::isLoggedin(1) ): ?>
						<nav id="quick-navigation">
							<ul class="unstyled clearfix">
								<li class="website"><?=__('logged_in_user', 'users', array('%name' => html_helper::anchor('cp/users/edit/' . session::item('user_id'), session::item('name'))))?></li>
								<li class="website"><?=html_helper::anchor('', __('website_view', 'system'), array('target' => '_blank'))?></li>
								<li class="logout"><?=html_helper::anchor('cp/users/logout', __('logout', 'system_navigation'))?></li>
							</ul>
						</nav>
					<? endif; ?>
				</div>
			</div>
		</header>
		<? if ( users_helper::isLoggedin(1) ): ?>
			<nav id="navigation">
				<div class="row-container">
					<div class="row">
						<ul class="unstyled clearfix">
							<li class="out<?=(uri::segment(2) == '' ? ' active' : '')?>"><?=html_helper::anchor('cp', __('home', 'system_navigation'), array('class' => 'out home'))?></li>
							<? foreach ( config::item('cp_top_nav', 'lists') as $list ): $list['attr']['class'] = isset($list['attr']['class']) ? $list['attr']['class'].' out' : 'out'; ?>
								<li class="out<?=(uri::segment(2) != '' && view::getCustomParam('section') == $list['keyword'] ? ' active' : '')?>" data-dropdown="menu-<?=$list['keyword']?>" data-dropdown-action="hover">
									<?=html_helper::anchor('cp/'.$list['uri'], $list['name'], $list['attr'])?>
									<? if ( $list['items'] ): ?>
										<ul class="unstyled" style="display:none" data-dropdown-menu="menu-<?=$list['keyword']?>">
											<? foreach ( $list['items'] as $item ): ?>
												<li><?=html_helper::anchor('cp/'.$item['uri'], $item['name'], $item['attr'])?></li>
											<? endforeach; ?>
										</ul>
									<? endif; ?>
								</li>
							<? endforeach; ?>
							<? if ( config::item('help_link_active', 'system') ): ?>
								<li class="out<?=(uri::segment(2) == 'help' ? ' active' : '')?>" data-dropdown="menu-help" data-dropdown-action="hover">
									<?=html_helper::anchor('cp/help/license', __('help', 'system_navigation'), array('class' => 'help out'))?>
									<ul class="unstyled" style="display:none" data-dropdown-menu="menu-help">
										<li><?=html_helper::anchor('cp/help/documentation', __('help_documentation', 'system_navigation'), array('class' => 'help documentation', 'target' => '_blank'))?></li>
										<li><?=html_helper::anchor('cp/help/forum', __('help_forum', 'system_navigation'), array('class' => 'help forum', 'target' => '_blank'))?></li>
										<li><?=html_helper::anchor('cp/help/support', __('help_support', 'system_navigation'), array('class' => 'help support', 'target' => '_blank'))?></li>
										<? if ( !input::demo(0) ): ?>
											<li><?=html_helper::anchor('cp/help/license', __('help_license', 'system_navigation'), array('class' => 'help license'))?></li>
										<? endif; ?>
									</ul>
								</li>
							<? endif; ?>
						</ul>
					</div>
				</div>
			</nav>
			<? if ( view::getCustomParam('options') ): ?>
				<nav id="sub-navigation">
					<div class="row-container">
						<div class="row">
							<ul class="unstyled clearfix">
								<? foreach ( view::getCustomParam('options') as $option ): ?>
									<li><?=html_helper::anchor('cp/'.$option['uri'], $option['name'], $option['attr'])?></li>
								<? endforeach; ?>
							</ul>
						</div>
					</div>
				</nav>
			<? endif; ?>
		<? endif; ?>
		<section id="container">
			<div class="row-container">
				<div class="row">
					<? if ( users_helper::isLoggedin(1) && view::getTrail() ): ?>
						<nav id="trail">
							<ul class="unstyled clearfix">
								<? foreach ( view::getTrail() as $index => $item ): ?>

									<? if ( $index ): ?>
										<li>&#187;</li>
									<? endif ?>
									<li><?=html_helper::anchor($item['uri'], text_helper::truncate($item['name'], 40))?></li>

								<? endforeach; ?>
							</ul>
						</nav>
					<? endif; ?>

					<hgroup class="content clearfix">

						<? if ( view::getTitle() ): ?>
							<h1><?=view::getTitle()?></h1>
						<? endif; ?>

						<? if ( view::getActions() ): ?>
							<nav id="actions">
								<ul class="unstyled clearfix">
									<? foreach ( view::getActions() as $item ): ?>
										<li <?=( $item['uri'] == 'translate' ? 'data-dropdown="menu-actions-translate" data-dropdown-action="hover"' : '' )?>>
											<? if ( $item['uri'] !== false ): ?>
												<? if ( $item['uri'] == 'translate' ): ?>
													<?=html_helper::anchor('#', __('translate', 'system'), array('class' => 'icon-text icon-system-translate'))?>
													<div style="display:none" data-dropdown-menu="menu-actions-translate" class="dropdown">
														<ul class="unstyled">
															<? foreach ( config::item('languages', 'core', 'keywords') as $languageID => $languageKeyword ): ?>
																<li><?=html_helper::anchor('#', config::item('languages', 'core', 'names', $languageID), array('onclick' => "switchLanguage('" . $languageKeyword . "');return false;"))?></li>
															<? endforeach; ?>
														</ul>
													</div>
												<? else: ?>
													<?=html_helper::anchor($item['uri'], $item['name'], $item['attr'])?>
												<? endif; ?>
											<? else: ?>
												<?=$item['name']?>
											<? endif; ?>
										</li>
									<? endforeach; ?>
								</ul>
							</nav>
						<? endif; ?>

					</hgroup>

					<? if ( view::getTabs() ): ?>
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
		<? view::load('cp/system/elements/message'); ?>
	<? endif; ?>
