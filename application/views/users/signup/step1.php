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
                <li class="current li-0">Email Registration</li>
                <li class="li-1">Email Verification</li>
                <li class="li-2">Profile Creation</li>
                <li class="li-3">Profile Confirmation</li>
                <li class="li-4">Registration Finish</li>
            </ul>
        </div>
        <p>Your registration to TutorNavi membership is FREE !</p>
        <h3>Email Registration</h3>
        <p>Welcome to TutorNavi！Please enter your email address below, then click “Send” button.You will get an email from us.</p>
        <p>Follow email instructions to complete membership registration.</p>
        <form class="form1" action="<?= html_helper::siteURL('users/signup/step1') ?>" method='POST'>
            <div class="box clearfix">
                <span class="note">Must Enter</span>
                <span>PC Email Address</span>
                <input type="email" class="mail" name='email' placeholder="Please enter your email address here"/>
                <input type='hidden' value='1' name='reg_step1'>
                <span>eg)：akiko@yahoo.co.jp</span>
				<p class='error'><?=(isset($error)? $error : ''); ?></p>
            </div>
            <div class="btn-wrap">
                <input type="submit" id="send" value="Send" />
            </div>
        </form>
        <ul class="reminders clearfix">
            <li>※If you did not receive an email from us, please check your spam folder or please feel free to contact us.</li>
            <li>※Your registered email address will never be given or sold to the third parties for marketing purposes.</li>
            <li>※You will get Newsletters from us, such as job information for tutors and  tutor blogs for students. <br/> （If you do not want to get Newsletter from us, please kindly click “No” when you create your profile information） </li>
            <li>※We do collect some personal information when a tutor or student signs up to TutorNavi. Please read our <a title="Policy" href="#">Privacy Policy</a> for more information.</li>
        </ul>
        <a class="back-top" title="Back to Home" href="#">Back to Home</a>
    </div><!--END OF REGISRAION WRAP-->

    <? view::load('footer'); ?>