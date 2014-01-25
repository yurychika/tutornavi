<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
    <head>
        <meta http-equiv="content-type" content="text/html; charset=utf-8" />
        <title><?= (view::getMetaTitle() ? view::getMetaTitle() . (uri::getURI() != '' ? ' - ' : '') : '') ?><?= (uri::getURI() != '' ? text_helper::entities(config::item('site_title', 'system')) : '') ?></title>
        <?= html_helper::style(html_helper::siteURL('load/css/' . session::item('template'))) ?>
        <?= html_helper::style('assets/css/main.css') ?>

        <?= view::getStylesheets() ?>
        <!-- <?= html_helper::script(html_helper::siteURL('load/javascript')) ?> -->
        <?= view::getJavascripts() ?>
        <meta name="description" content="<?= view::getMetaDescription() ?>" />
        <meta name="keywords" content="<?= view::getMetaKeywords() ?>" />

        <!--[if lt IE 7]>
        <link rel="stylesheet" type="text/css" media="screen, projection" href="css/pngfix.css">
        <link rel="stylesheet" type="text/css" media="screen, projection" href="css/ie6.css">
        <![endif]-->
        <!--[if IE 7]>
                <link rel="stylesheet" type="text/css" media="screen, projection" href="css/ie7.css">
        <![endif]-->
        <!--[if IE]>
                <link rel="stylesheet" type="text/css" media="screen, projection" href="css/form.css">
        <![endif]-->
        <!--[if !IE]><!-->
    	<script type="text/javascript">
            if (/*@cc_on!@*/false) {
                document.documentElement.className += 'ie10';
            }
        </script><!--<![endif]--> 
   		<script type='text/javascript' src='/assets/js/all.js'>
		</script>
		<script type='text/javascript' src='/assets/js/site/scripts.js'>
		</script>
    </head>

    <body>
        <div class="wrapper clearfix">
            <div id="header" class="clearfix">
                <div class="header-top">
                    <div class="inner-wrap clearfix">
                        <p>Hong Kong Language Tutors Reviews Site</p>
                        <ul class="clearfix">
							<li class="signup"><?=html_helper::anchor('users/signup', 'Signup', array('class' => 'users-signup'))?></li>
							<li class="signup"><?=html_helper::anchor('users/login', 'Login', array('class' => 'users-signup'))?></li>


                            <!-- <li><a title="link2" href="#">Login</a></li>
                            <li><a title="link1" href="#">Register</a></li> -->
                        </ul>
                    </div>
                </div><!--END OF HEADER TOP-->
                <div class="header-mid clearfix">
                    <div class="inner-wrap clearfix">
                        <a id="logo" title="logo" href="#"><img alt="logo" src="<?php echo html_helper::baseURL('assets/images/tutor_front/logo.jpg'); ?>" /></a>
                        <p class="greeting">Hello,  <span>Visitor</span</p>
                        <div class="right">
                            <ul class="quick-links clearfix">
                                <li><a id="fave" title="FAVE" href="#">My Favorite</a></li>
                                <li><a id="contact" title="CONTACT" href="#">Contact Us</a></li>
                                <li><a id="map" title="SITE MAP" href="#">Site Map</a></li>
                                <li><a id="faq" title="FAQ" href="#">FAQ</a></li>
                            </ul><!--END OF QUICK LINKS-->
                            <ul class="language clearfix">									
                                <li><a title="Eng" href="#">ENG</a></li>
                                <li><a title="Jap" href="#">日本語</a></li>
                            </ul><!--END OF LANGUAGE-->
                            <ul class="char-size clearfix">
                                <li><a title="Bold" href="#">B</a></li>
                                <li><a class="current" title="Medium" href="#">M</a></li>
                                <li><a title="Small" href="#">S</a></li>
                                <li class="title">Font Size:</li>
                            </ul><!--END OF CHAR SIZE-->
                            <a id="mypage" title="My Page" href="#">My Page</a>
                        </div>
                    </div>
                </div><!--END OF HEADER MID-->
                <div id="nav">
                    <div class="inner-wrap clearfix">
                        <ul class="clearfix">
                            <li><a class="current" title="menu1" href="#">Home</a></li>
                            <li><a title="menu2" href="#">Tutor Search</a></li>
                            <li><a title="menu3" href="#">About Service</a></li>
                            <li><a title="menu4" href="#">Community</a></li>
                            <li><a title="menu5" href="#">Price List</a></li>
                            <li class="last"><a title="menu6" href="#">Information</a></li>
                        </ul>
                    </div>
                </div><!--END OF NAV-->
            </div><!--END OF HEADER-->

            <!--START OF PAGE CONTENT-->
            <div class="content-wrap">
                <div id="content" class="clearfix">