<? view::load('header'); ?>

<ul class="crumbs clearfix">
						<li><a title="Home" href="#">Home</a></li>
						<li>></li>
						<li>Student Registration</li>
					</ul>
					
					<!-- <? var_dump($fields) ?> -->
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
						<p>Please fill the form below to creat your profile, then click "Submit" button.You can also change the setting in "My Page" after you login TutorNavi, <br/>so that your privacy personal information will not be public in TutorNavi.</p>
						<p>*You will be able to change the other information except "Registered Email Address", "Nickname" and "Gender"</p>
						<p class='error'><? if(isset($error)){echo $error;} ?></p>
						
						
						<div class="must mtb10"><span>Must</span>Field is a Must.</div>
						<h3>Basic Information</h3>
						<form class="tutor_reg_form" method='POST'>
							<table>
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
									<td class="input_wrap">
										<input type="text" name="username" id="username"/>
									</td>
									<td>
										<p>* Min. 6 alpha-numerical characters</p>
										<p>* This will not be changed when you complete the registration.</p>
									</td>
								</tr>
								<tr>
									<th><div class="must"><span>Must</span></div>Password</th>
									<td class="input_wrap">
										<input type="password" name='password' id='password'/>
									</td>
									<td><p>* Min.6 alpha-numerical characters</p></td>
								</tr>
								<tr>
									<th><div class="must"><span>Must</span></div>Re-enter Password</th>
									<td class="input_wrap">
										<input type="password" name='password2' id='password2'/>
									</td>
									<td><p>* For Confirmation Purpose</p></td>
								</tr>
								<tr>
									<td class="break">*Personal information</td>
								</tr>
								<!-- Surname & Firstname -->
								<tr>
									<th><div class="must"><span>Must</span></div>Name (Confidential)</th>
									<td colspan="2">
										<div class="cntact" style='height: 20px'>
										<label for="sname">Surname</label>
										<? view::load('system/elements/field/edit', array(
											'prefix' => 'user_profile',
											'field' => $fields['surname'],
											'value' => session::item('profile', 'signup'),
										)) ?>
										<label for="fname">First Name</label>
										<? view::load('system/elements/field/edit', array(
											'prefix' => 'user_profile',
											'field' => $fields['firstname'],
											'value' => session::item('profile', 'signup'),
										)) ?>
										</div>
										<label class="note">eg) Surname: Takaki First Name: Naogo</label>
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
								<tr>
									<td class="break btn" colspan="3">
										<input type="submit" class="next" value="Next" />
									</td>
								</tr>
							</table>
						</form>
						<a class="back-top" title="Back to Home" href="#">Back to Home</a>
					</div><!--END OF REGISRAION WRAP-->
			
<? view::load('footer'); ?>