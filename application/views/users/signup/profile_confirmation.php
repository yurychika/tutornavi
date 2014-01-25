<? view::load('header'); ?>

		<ul class="crumbs clearfix">
			<li><a title="Home" href="#">Home</a></li>
			<li>></li>
			<li>Profile Confirmation</li>
		</ul>
		
		<div class="registration_wrap content_box clearfix registration_wrap_ie6">
			<h2 class="c_title">Profile Confirmation</h2>
			<div class="reg-bar">
				<ul class="regbar4 clearfix">
					<li class="li-0 done">Email Registration</li>
					<li class="li-1 done sec">Email Verification</li>
					<li class="li-2 done thir">Profile Creation</li>
					<li class="li-3 current">Profile Confirmation</li>
					<li class="li-4">Registration Finish</li>
				</ul>
			</div>
			<p>This is the confirmation page of your profile, please enter the security code in the box below.</p>
			<p>Click "Accept" button after read our <a class="in-link" title="terms" href="#">Terms &amp; Conditions</a> and <a class="in-link" title="policy" href="#">Privacy Policy</a>.</p>
			<form class="confirm_form" method="POST">
				<table>
					<tr>
						<th>Email Address</th>
						<td style='padding: 10px'><?=$email ?></td>
					</tr>
					<tr>
						<th>User Account</th>
						<td style='padding: 10px'><?=$username?></td>
					</tr>
					<? foreach($fields as $field): ?>
					<tr>
						<th><?=$field['name']?></th>
						<td style='padding: 10px'>
							<? view::load('system/elements/field/edit', array(
								'prefix' => 'user_profile',
								'field' => $field,
								'value' => session::item('profile', 'signup'),
							)) ?>
						</td>
					</tr>
					<? endforeach;?>
				</table>
				<script type='text/javascript'>
					$('input, textarea, select').attr('disabled', 'disabled');
				</script>
				
				<h2>Register Security Code</h2>
				<div class="sec_code">
					<div>
					    <input type="text" id="sec_code" value="898909" />
					    <input type="text" id="sec_code_input" value="" />
					    Please re-enter the asecurity code on the left 
					</div>
				</div>
				<div class="agree clearfix">
					<input type="checkbox" name="agree" id="agree_term"/>
					I agree with <a class="in-link" href="#" title="Terms">Terms &amp; Conditions</a> and <a class="in-link" href="#" title="Policy">Privacy Policy</a>.
				</div>
				<div class="btn_wrap">
					<input id="modify" type="submit" value="Modify Profile" />
					<input id="accept" type="submit" value="Accept" />
				</div>
			</form>
			<div class="link_wrap">
			<a class="back-top" title="Back to Home" href="#">Back to Home</a>
			</div>
		</div><!--END OF REGISRAION WRAP-->
					

<? view::load('footer'); ?>