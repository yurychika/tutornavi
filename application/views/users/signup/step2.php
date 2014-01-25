<? view::load('header'); ?>

    <ul class="crumbs clearfix">
        <li><a title="Home" href="#">Home</a></li>
        <li>></li>
        <li>Email Registration</li>
    </ul>

    <div class="registration_wrap content_box clearfix">
        <h2 class="c_title">Email Registration</h2>
        <div class="reg-bar">
            <ul class="clearfix">
                <li class="li-0 current">Email Registration</li>
                <li class="li-1">Email Verification</li>
                <li class="li-2">Profile Creation</li>
                <li class="li-3">Profile Confirmation</li>
                <li class="li-4">Registration Finish</li>
            </ul>
        </div>
        
        <div class='container' style='min-height: 300px'>
	        <form class="form1" action="<?= html_helper::siteURL('users/signup/step1') ?>" method='POST'>
	    		<p>The registration mail was sent successfully, please check!</p>
	        </form>
        </div>
        <a class="back-top" title="Back to Home" href="#">Back to Home</a>
    </div><!--END OF REGISRAION WRAP-->

    <? view::load('footer'); ?>