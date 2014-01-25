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
						<td><?=$email ?></td>
					</tr>
					<tr>
						<th>User Account</th>
						<td><?=$username?></td>
					</tr>
					<!-- Surname & Firstname -->
					<tr>
						<th>Surname</th>
						<td colspan="2"><?=session::item('profile', 'signup', 'data_surname')?></td>
						</td>
					</tr>
					<tr>
						<th>Firstname</th>
						<td colspan="2"><?=session::item('profile', 'signup', 'data_firstname')?></td>
						</td>
					</tr>
					<tr>
						<th>gender</th>
						<td colspan="2"><?=session::item('profile', 'signup', 'data_gender')?></td>
						</td>
					</tr>
					<tr>
						<th>age</th>
						<td colspan="2"><?=session::item('profile', 'signup', 'data_sge')?></td>
						</td>
					</tr>	
					<tr>
						<th>Surname</th>
						<td colspan="2"><?=session::item('profile', 'signup', 'data_surname')?></td>
						</td>
					</tr>	
					<tr>
						<th>Surname</th>
						<td colspan="2"><?=session::item('profile', 'signup', 'data_surname')?></td>
						</td>
					</tr>	
					<tr>
						<th>Surname</th>
						<td colspan="2"><?=session::item('profile', 'signup', 'data_surname')?></td>
						</td>
					</tr>						
					<!-- Gender -->
					<tr>
						<th><div class="must"><span>Must</span></div>Gender </th>
						<td class="input_wrap" id="gender"  colspan="2">
							<? view::load('system/elements/field/edit', array(
								'prefix' => 'user_profile',
								'field' => $fields['gender'],
								'value' => session::item('profile', 'signup'),
							)) ?>
						</td>
					</tr>
					<!-- age -->
					<tr>
						<th><div class="must"><span>Must</span></div>Age </th>
						<td id="age" class="input_wrap"  colspan="2">
							<div class="wrap">
							<? view::load('system/elements/field/edit', array(
								'prefix' => 'user_profile',
								'field' => $fields['age'],
								'value' => session::item('profile', 'signup'),
							)) ?>
							</div>
						</td>
					</tr>
					<!-- nationality -->
					<tr>
						<th><div class="must"><span>Must</span></div>Nationality</th>
						<td colspan="2" class="sel1">
							<? view::load('system/elements/field/edit', array(
								'prefix' => 'user_profile',
								'field' => $fields['nationality'],
								'value' => session::item('profile', 'signup'),
							)) ?>
						</td>
					</tr>
					<!-- education -->
					<tr>
						<th><div class="must"><span>Must</span></div>Education </th>
						<td colspan="2" class="sel1">
							<? view::load('system/elements/field/edit', array(
								'prefix' => 'user_profile',
								'field' => $fields['education'],
								'value' => session::item('profile', 'signup'),
							)) ?>
						</td>
					</tr>
					<!-- job -->
					<tr>
						<th>Former/Curren Job</th>
						<td colspan="2" class="input_wrap">
							<? view::load('system/elements/field/edit', array(
								'prefix' => 'user_profile',
								'field' => $fields['job'],
								'value' => session::item('profile', 'signup'),
							)) ?>
						</td>
					</tr>
					<!-- Telephone -->
					<tr>
						<th><div class="must"><span>Must</span></div>Telephone </th>
						<td colspan="2" class="input_wrap phone">
						<div class="wrap">
							<? view::load('system/elements/field/edit', array(
								'prefix' => 'user_profile',
								'field' => $fields['teleType'],
								'value' => session::item('profile', 'signup'),
							)) ?>
							<? view::load('system/elements/field/edit', array(
								'prefix' => 'user_profile',
								'field' => $fields['teleNum'],
								'value' => session::item('profile', 'signup'),
							)) ?>
							</div>
						</td>
					</tr>
					<!-- emergency contact number -->
					<tr>
						<th>Emergency Contact Phone Number</th>
						<td colspan="2" class="input_wrap">
						<? view::load('system/elements/field/edit', array(
								'prefix' => 'user_profile',
								'field' => $fields['emergNum'],
								'value' => session::item('profile', 'signup'),
							)) ?>
						</td>
					</tr>
					<!-- Address -->
					<tr>
						<th><div class="must"><span>Must</span></div>Address<p>(Confidential)</p></th>
						<td colspan="2" class="add">
							<div class="wrap"><label for="flat">Flat/Room</label>
								<? view::load('system/elements/field/edit', array(
									'prefix' => 'user_profile',
									'field' => $fields['room'],
									'value' => session::item('profile', 'signup'),
								)) ?>
							</div>
							<div class="wrap"><label for="bldg">Building/Mansion</label>
								<? view::load('system/elements/field/edit', array(
									'prefix' => 'user_profile',
									'field' => $fields['building'],
									'value' => session::item('profile', 'signup'),
								)) ?>
							</div>
							<div class="wrap"><label for="strt">Street/District</label>
								<? view::load('system/elements/field/edit', array(
									'prefix' => 'user_profile',
									'field' => $fields['street'],
									'value' => session::item('profile', 'signup'),
								)) ?>
							</div>
							<div class="wrap"><label>Area</label>
							<? view::load('system/elements/field/edit', array(
								'prefix' => 'user_profile',
								'field' => $fields['area'],
								'value' => session::item('profile', 'signup'),
							)) ?>
							</div>
							<p>*Address is information that allows you to be identified as a particular person. Please enter correct address</p>
						</td>
					</tr>
					<!-- receive letter -->
					<tr>
						<th><div class="must"><span>Must</span></div>Receive Newsletters  from TutorNavi? </th>
						<td class="choices " id="letter"  colspan="2">
								<? view::load('system/elements/field/edit', array(
								'prefix' => 'user_profile',
								'field' => $fields['receiveLetter'],
								'value' => session::item('profile', 'signup'),
								)) ?>
						</td>
					</tr>
					<!-- how to find us -->
					<tr>
						<th><div class="must"><span>Must</span></div>How did you find us?</th>
						<td class="choices" id="adv" colspan="2">
								<? view::load('system/elements/field/edit', array(
								'prefix' => 'user_profile',
								'field' => $fields['howFindUs'],
								'value' => session::item('profile', 'signup'),
								)) ?>
						</td>
					</tr>
					<!-- native language -->
					<tr>
						<th><div class="must"><span>Must</span></div>Native Language</th>
						<td class="input_wrap" id="lang" colspan="2">
							<div class="wrap choices">
								<? view::load('system/elements/field/edit', array(
								'prefix' => 'user_profile',
								'field' => $fields['nativeLan'],
								'value' => session::item('profile', 'signup'),
								)) ?>
							</div>								
						</td>
					</tr>					
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