<? view::load('header'); ?>

	<ul class="crumbs clearfix">
		<li><a title="Home" href="#">Home</a></li>
		<li>></li>
		<li>Client Registration</li>
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
		<p>Please fill the form below to create your company profile,then click "Submit" button. </p>
		<p>You can change the company information and create the "Staff Wanted" request in "mypage" after you login TutorNavi.</p>
		<p>*You will be able to change the other information except "Registered Email Address", "Nickname" and  "Gender"</p>
		<div class="must mtb10"><span>Must</span>Field is a Must.</div>
		<h3>Basic Information</h3>
		<form class="tutor_reg_form" method="POST">
			<table cellspacing="0" cellpadding="0">
				<tr>
					<th>Your Registered Email Address</th>
					<td class="input_wrap">
						<?=$email;?>
					</td>
					<td>
						<p class="wide">* Registered Email Address is for Login TutorNavi.</p>
						<p>* This will not be changed when you complete the registration.</p>
					</td>
				</tr>
				<tr>
					<th><div class="must"><span>Must</span></div>Nickname<br/>
						<p>*Visible to all the members</p></th>
					<td class="input_wrap"><input type="text" id='username' name='username'/></td>
					<td>
						<p>* Min. 6 alpha-numerical characters</p>
						<p>* This will not be changed when you complete the registration.</p>
					</td>
				</tr>
				<tr>
					<th><div class="must"><span>Must</span></div>Password</th>
					<td class="input_wrap"><input type="password" id='password' name='password'/></td>
					<td><p>* Min.6 alpha-numerical characters</p></td>
				</tr>
				<tr>
					<th><div class="must"><span>Must</span></div>Re-enter Password</th>
					<td class="input_wrap"><input type="password" id='password2' name='password2'/></td>
					<td><p>* For Confirmation Purpose</p></td>
				</tr>
				<tr>
					<td class="break">*Personal information</td>
				</tr>
				<tr>
					<th><div class="must"><span>Must</span></div>Company Name (Confidential)</th>
					<td class="input_wrap" colspan="2">
						<? view::load('system/elements/field/edit', array(
							'prefix' => 'user_profile',
							'field' => $fields['company'],
							'value' => session::item('profile', 'signup'),
						)) ?>
						
					</td>
				</tr>
				<tr>
					<th><div class="must"><span>Must</span></div>Contact Person</th>
					<td colspan="2">
						<div class="cntact"><label for="sname">Surname </label>
						<? view::load('system/elements/field/edit', array(
							'prefix' => 'user_profile',
							'field' => $fields['surname'],
							'value' => session::item('profile', 'signup'),
						)) ?>
						<label for="fname">First Name </label>
						<? view::load('system/elements/field/edit', array(
							'prefix' => 'user_profile',
							'field' => $fields['firstname'],
							'value' => session::item('profile', 'signup'),
						)) ?>										
						<label class="note">eg) Surname: Takaki First Name: Naogo</label></div>
					</td>
				</tr>
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
				<tr>
					<th><div class="must"><span>Must</span></div>Business Industry </th>
					<td colspan="2" class="sel1">
						<? view::load('system/elements/field/edit', array(
							'prefix' => 'user_profile',
							'field' => $fields['industry'],
							'value' => session::item('profile', 'signup'),
						)) ?>				
					</td>
				</tr>
				<tr>
					<th><div class="must"><span>Must</span></div>Company Tel</th>
					<td colspan="2" class="input_wrap">
						<? view::load('system/elements/field/edit', array(
							'prefix' => 'user_profile',
							'field' => $fields['companytele'],
							'value' => session::item('profile', 'signup'),
						)) ?>
					</td>
				</tr>
				<tr>
					<th><div class="must"><span>Must</span></div>Company Fax</th>
					<td colspan="2" class="input_wrap">
						<? view::load('system/elements/field/edit', array(
								'prefix' => 'user_profile',
								'field' => $fields['companyfax'],
								'value' => session::item('profile', 'signup'),
							)) ?>
					</td>
				</tr>
				<tr>
					<th><div class="must"><span>Must</span></div>Company Address<p>(Confidential)</p></th>
					<td colspan="2" class="add">
						<div class="wrap">
							<label for="flat">Flat/Room</label>
							<? view::load('system/elements/field/edit', array(
								'prefix' => 'user_profile',
								'field' => $fields['room'],
								'value' => session::item('profile', 'signup'),
							)) ?>											
						</div>
						<div class="wrap">
							<label for="bldg">Building/Mansion</label>
								<? view::load('system/elements/field/edit', array(
									'prefix' => 'user_profile',
									'field' => $fields['building'],
									'value' => session::item('profile', 'signup'),
								)) ?>
						</div>
						<div class="wrap">
							<label for="strt">Street/District</label>
							<? view::load('system/elements/field/edit', array(
								'prefix' => 'user_profile',
								'field' => $fields['street'],
								'value' => session::item('profile', 'signup'),
							)) ?>
						</div>
						<div class="wrap">
						<label>Area</label>
							<? view::load('system/elements/field/edit', array(
									'prefix' => 'user_profile',
									'field' => $fields['area'],
									'value' => session::item('profile', 'signup'),
								)) ?>									
						</div>
						<p>*Address is information that allows you to be identified as a particular person. Please enter correct address</p>
					</td>
				</tr>
				<tr>
					<th><div class="must"><span>Must</span></div>Company Discription
						<p>*Please do not input your company contact,URL,Email Address into the blanks.</p>
					</th>
					<td class="choices" colspan="2">
						<? view::load('system/elements/field/edit', array(
									'prefix' => 'user_profile',
									'field' => $fields['companydesc'],
									'value' => session::item('profile', 'signup'),
						)) ?>	
					</td>
				</tr>
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
				<tr>
					<th><div class="must"><span>Must</span></div>How did you find us?</th>
					<td class="choices" id="adv" colspan="2">
						<div class="wrap">
							<? view::load('system/elements/field/edit', array(
									'prefix' => 'user_profile',
									'field' => $fields['howFindUs'],
									'value' => session::item('profile', 'signup'),
								)) ?>							
						</div>
						<!-- <div class="wrap">
							<input type="checkbox" id="adv5" />
							<label for="adv5">Others</label>
							<input type="text" class="others" />
						</div> -->
					</td>
				</tr>
				<tr>
					<td class="break btn" colspan="3"><input type="submit" class="next" value="Submit" /></td>
				</tr>
			</table>
		</form>
		<div class="link_wrap clearfix">
			<a class="back-top" href="#" title="Back to Home">Back to Home</a>
			<a title="Back to Home" href="#" class="to-top">Back to Top</a>
			</div>
	</div><!--END OF REGISRAION WRAP-->
					
<? view::load('footer'); ?>