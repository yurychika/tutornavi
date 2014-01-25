<? view::load('header'); ?>

		<ul class="crumbs clearfix">
				<li><a title="Home" href="#">Home</a></li>
				<li>></li>
				<li>Account</li>
			</ul>
			
			<div class="registration_wrap content_box clearfix">
				<h2 class="c_title">Profile Create</h2>
				<div class="reg-bar">
					<ul class="regbar3 clearfix">
						<li class="li-0 done">Email Registration</li>
						<li class="li-1 done everify">Email Verification</li>
						<li class="li-2 current">Profile Creation</li>
						<li class="li-3">Profile Confirmation</li>
						<li class="li-4">Registration Finish</li>
					</ul>
				</div>
				<p>Select user account, then click "Next" button.</p>
				<form class="form3" action="<?= html_helper::siteURL('users/signup/step3') ?>" method='POST'>
					<div class="box clearfix">
						<label>User Account</label>
						<select name='account_type'>
							<option value='2'>Client</option>
							<option value='3'>Student</option>
							<option value='4'>Tutor</option>
						</select>
						<div class="btn-wrap">
						<input type="submit" id="next" value="Next" />
					</div>
					</div>
				</form>
				<a class="back-top" title="Back to Home" href="#">Back to Home</a>
			</div><!--END OF REGISRAION WRAP-->
			
<? view::load('footer'); ?>