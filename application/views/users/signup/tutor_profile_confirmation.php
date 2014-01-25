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
				<table style='margin: 0'>
					<tr>
						<th>Email Address</th>
						<td style='padding: 10px'><?=$email?></td>
					</tr>
					<tr>
						<th>User Account</th>
						<td style='padding: 10px'><?=$username?></td>
					</tr>
					<tr>
						<th><div class="must"><span>Must</span></div>Name (Confidential)</th>
						<td colspan="2">
							<div class="cntact">
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
						<th><div class="must"><span>Must</span></div>Date of Birth </th>
						<td id="age2" class="input_wrap"  colspan="2">
									<? view::load('system/elements/field/edit', array(
								'prefix' => 'user_profile',
								'field' => $fields['birthday'],
								'value' => session::item('profile', 'signup'),
							)) ?>
						</td>
					</tr>
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
					<tr>
						<th><div class="must"><span>Must</span></div>Telephone </th>
						<td colspan="2" class="input_wrap phone">
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
						</td>
					</tr>
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
							<? view::load('system/elements/field/edit', array(
								'prefix' => 'user_profile',
								'field' => $fields['howFindUs'],
								'value' => session::item('profile', 'signup'),
							)) ?>
						</td>
					</tr>
					<tr>
						<th><div class="must"><span>Must</span></div>Language Ability<p>*Native Language is the language you teach (Allow multiple selection)</p></th>
						<td class="input_wrap" id="lang" colspan="2">
							<div class="wrap choices">
								<? view::load('system/elements/field/edit', array(
									'prefix' => 'user_profile',
									'field' => $fields['lan1'],
									'value' => session::item('profile', 'signup'),
								)) ?>
								<? view::load('system/elements/field/edit', array(
									'prefix' => 'user_profile',
									'field' => $fields['lanAbility1'],
									'value' => session::item('profile', 'signup'),
								)) ?>
								<a title="hide" class="hide" href="#"><img alt="hide" src="images/btn_hide.jpg" /></a>
							</div>
							<div class="wrap choices">
								<? view::load('system/elements/field/edit', array(
								'prefix' => 'user_profile',
								'field' => $fields['lan2'],
								'value' => session::item('profile', 'signup'),
								)) ?>
								<? view::load('system/elements/field/edit', array(
								'prefix' => 'user_profile',
								'field' => $fields['lanAbility2'],
								'value' => session::item('profile', 'signup'),
							)) ?>
							<a title="show" class="show" href="#"><img alt="show" src="images/btn_show.jpg" /></a>
							</div>										
						</td>
					</tr>
					<tr>
						<th><div class="must"><span>Must</span></div>Teaching Experience</th>
						<td class="choices" id="exp" colspan="2">
							<? view::load('system/elements/field/edit', array(
								'prefix' => 'user_profile',
								'field' => $fields['teachExp'],
								'value' => session::item('profile', 'signup'),
							)) ?>
						</td>
					</tr>
					<tr>
						<th><div class="must"><span>Must</span></div>Familiar Field</th>
						<td class="choices" id="ff" colspan="2">
							<? view::load('system/elements/field/edit', array(
								'prefix' => 'user_profile',
								'field' => $fields['famField'],
								'value' => session::item('profile', 'signup'),
							)) ?>
						</td>
					</tr>
					<tr>
						<th><div class="must"><span>Must</span></div>Japanese Speaking Level</th>
						<td class="choices" colspan="2">
							<div class="wrap l_fix">
								<? view::load('system/elements/field/edit', array(
								'prefix' => 'user_profile',
								'field' => $fields['japSpeakLevel'],
								'value' => session::item('profile', 'signup'),
							)) ?>
							</div>
						</td>
					</tr>
					<tr>
						<th><div class="must"><span>Must</span></div>Japanese Writing Skills<p>*For internal message with the Japanese students</p></th>
						<td class="choices" colspan="2">
						<div class="wrap">
								<? view::load('system/elements/field/edit', array(
								'prefix' => 'user_profile',
								'field' => $fields['japWriteSkill'],
								'value' => session::item('profile', 'signup'),
							)) ?>
						</div>
						</td>
					</tr>
					<tr>
						<th><div class="must"><span>Must</span></div>Language Levels You  Teach</th>
						<td class="choices" colspan="2">
							<? view::load('system/elements/field/edit', array(
								'prefix' => 'user_profile',
								'field' => $fields['langLevelYouTeach'],
								'value' => session::item('profile', 'signup'),
							)) ?>
						</td>
					</tr>
					<tr>
						<th><div class="must"><span>Must</span></div>Teaching Field</th>
						<td class="choices" colspan="2">
						<div class="wrap">
								<? view::load('system/elements/field/edit', array(
								'prefix' => 'user_profile',
								'field' => $fields['teachField'],
								'value' => session::item('profile', 'signup'),
							)) ?>
							
						</div>
						</td>
					</tr>
					<tr>
						<th><div class="must"><span>Must</span></div>Types of Lesson</th>
						<td class="choices" colspan="2">
						<div class="wrap">
								<? view::load('system/elements/field/edit', array(
								'prefix' => 'user_profile',
								'field' => $fields['typesOfLesson'],
								'value' => session::item('profile', 'signup'),
							)) ?>
						</div>
						</td>
					</tr>
					<tr>
						<th><div class="must"><span>Must</span></div>Lessons Delivery</th>
						<td class="choices" colspan="2">
						<div class="wrap">
								<? view::load('system/elements/field/edit', array(
								'prefix' => 'user_profile',
								'field' => $fields['lessonsDeliv'],
								'value' => session::item('profile', 'signup'),
							)) ?>
						</div>
						</td>
					</tr>
					<tr>
						<th><div class="must"><span>Must</span></div>Preferred Students</th>
						<td class="choices" colspan="2">
						<div class="wrap">
								<? view::load('system/elements/field/edit', array(
								'prefix' => 'user_profile',
								'field' => $fields['preStudents'],
								'value' => session::item('profile', 'signup'),
							)) ?>
						</div>
						</td>
					</tr>
					<tr>
						<th><div class="must"><span>Must</span></div>Location<p>*Your preferred lesson location</p></th>
						<td class="choices" colspan="2">
								<? view::load('system/elements/field/edit', array(
								'prefix' => 'user_profile',
								'field' => $fields['teachLocat'],
								'value' => session::item('profile', 'signup'),
							)) ?>
						</td>
					</tr>
					<tr>
						<th><div class="must"><span>Must</span></div>Teaching Area</th>
						<td class="teach_area" colspan="2">
								<? view::load('system/elements/field/edit', array(
								'prefix' => 'user_profile',
								'field' => $fields['teachArea'],
								'value' => session::item('profile', 'signup'),
							)) ?>
						</td>
					</tr>
					<tr>
						<th><div class="must"><span>Must</span></div>Teaching Method</th>
						<td class="choices" colspan="2">
							<div class="wrap">
								<? view::load('system/elements/field/edit', array(
								'prefix' => 'user_profile',
								'field' => $fields['teachMethod'],
								'value' => session::item('profile', 'signup'),
							)) ?>
							</div>
						</td>
					</tr>
					<tr>
						<th><div class="must"><span>Must</span></div>Teaching Certificate/Qualification</th>
						<td class="choices" colspan="2">
							English Teaching Certificate
							<div class="wrap">
								<? view::load('system/elements/field/edit', array(
								'prefix' => 'user_profile',
								'field' => $fields['engTeachCert'],
								'value' => session::item('profile', 'signup'),
							)) ?>
							</div>
							
							<div class="wrap">
								<span>Chinese Teaching Certificate</span>
								<? view::load('system/elements/field/edit', array(
								'prefix' => 'user_profile',
								'field' => $fields['chinTeachCert'],
								'value' => session::item('profile', 'signup'),
							)) ?>
							</div>
							
							<div class="wrap">
								<span>Japanese Teaching Certificate</span>
								<? view::load('system/elements/field/edit', array(
								'prefix' => 'user_profile',
								'field' => $fields['japTeachCert'],
								'value' => session::item('profile', 'signup'),
							)) ?>
							</div>
						</td>
					</tr>
					<tr>
						<th><div class="must"><span>Must</span></div>Work Experience at Language Schools</th>
						<td class="choices" colspan="2">
								<? view::load('system/elements/field/edit', array(
								'prefix' => 'user_profile',
								'field' => $fields['workExpAtLanSchool'],
								'value' => session::item('profile', 'signup'),
							)) ?>
						</td>
					</tr>
					<tr>
						<th>Work Visa in HK<p>*Are you a work permit holder in HK?</p></th>
						<td class="choices" colspan="2">
								<? view::load('system/elements/field/edit', array(
								'prefix' => 'user_profile',
								'field' => $fields['workVisaInHk'],
								'value' => session::item('profile', 'signup'),
							)) ?>
						</td>
					</tr>
					<tr>
						<th><div class="must"><span>Must</span></div>Available Date &amp; Time<p>*Please mark your available date time for teaching.</p></th>
						<td colspan="2" id="sched_mark">
								<? view::load('system/elements/field/edit', array(
								'prefix' => 'user_profile',
								'field' => $fields['avaiDateTime'],
								'value' => session::item('profile', 'signup'),
							)) ?>							
							<table>
								<tr class="top">
									<th rowspan="2">Time</th>
									<td colspan="2">Morning</td>
									<td colspan="2">Afternoon</td>
									<td colspan="2" class="right">Night</td>
								</tr>
								<tr class="time">
									<td>（7:00～9:00）</td>
									<td>(9:00～12:00)</td>
									<td>(12:00～15:00)</td>
									<td>(15:00～18:00)</td>
									<td>（18:00～20:00）</td>
									<td class="right">(20:00～23:00)</td>
								</tr>
								<tr>
									<th>Sun</th>
									<td id="sun1"><input  type="checkbox" /></td>
									<td id="sun2"><input id="sun2" type="checkbox" /></td>
									<td id="sun3"><input id="sun3" type="checkbox" /></td>
									<td id="sun4"><input id="sun4" type="checkbox" /></td>
									<td id="sun5"><input id="sun5" type="checkbox" /></td>
									<td id="sun6" class="right"><input id="sun6" type="checkbox" /></td>
								</tr>
								<tr>
									<th>Mon</th>
									<td id="m1"><input type="checkbox" /></td>
									<td id="m2"><input type="checkbox" /></td>
									<td id="m3"><input type="checkbox" /></td>
									<td id="m4"><input type="checkbox" /></td>
									<td id="m5"><input type="checkbox" /></td>
									<td id="m6"class="right"><input type="checkbox" /></td>
								</tr>
								<tr>
									<th>Tue</th>
									<td id="t1"><input type="checkbox" /></td>
									<td id="t2"><input type="checkbox" /></td>
									<td id="t3"><input type="checkbox" /></td>
									<td id="t4"><input type="checkbox" /></td>
									<td id="t5"><input type="checkbox" /></td>
									<td id="t6"class="right"><input type="checkbox" /></td>
								</tr>
								<tr>
									<th>Wed</th>
									<td id="w1"><input type="checkbox" /></td>
									<td id="w2"><input type="checkbox" /></td>
									<td id="w3"><input type="checkbox" /></td>
									<td id="w4"><input type="checkbox" /></td>
									<td id="w5"><input type="checkbox" /></td>
									<td id="w6"class="right"><input type="checkbox" /></td>
								</tr>
								<tr>
									<th>Thu</th>
									<td id="th1"><input type="checkbox" /></td>
									<td id="th2"><input type="checkbox" /></td>
									<td id="th3"><input type="checkbox" /></td>
									<td id="th4"><input type="checkbox" /></td>
									<td id="th5"><input type="checkbox" /></td>
									<td id="th6"class="right"><input type="checkbox" /></td>
								</tr>
								<tr>
									<th>Fri</th>
									<td id="f1"><input type="checkbox" /></td>
									<td id="f2"><input type="checkbox" /></td>
									<td id="f3"><input type="checkbox" /></td>
									<td id="f4"><input type="checkbox" /></td>
									<td id="f5"><input type="checkbox" /></td>
									<td id="f6"class="right"><input type="checkbox" /></td>
								</tr>
								<tr class="bot">
									<th>Sat</th>
									<td id="sat1"><input type="checkbox" /></td>
									<td id="sat2"><input type="checkbox" /></td>
									<td id="sat3"><input type="checkbox" /></td>
									<td id="sat4"><input type="checkbox" /></td>
									<td id="sat5"><input type="checkbox" /></td>
									<td id="sat6"class="right"><input type="checkbox" /></td>
								</tr>
							</table>
						</td>
					</tr>
					<script type="text/javascript">
						$('#sched_mark label').each(function(){
							var label, input;
							if(this.childNodes[2].nodeType === 3){
								label = this.childNodes[2].nodeValue.trim();
								input = this.childNodes[1];
								
								this.parentNode.removeChild(this);
								var td = $('#' + label)[0];
								td.removeChild(td.firstChild);
								td.appendChild(input);
							}
						})
						
					</script>
					<tr>
						<th>
							The way of Teaching
							<p>*Please do not input your personal contact, URL, Email Address into the blanks.</p>
						</th>
						<td class="x_field" colspan="2">
							<? view::load('system/elements/field/edit', array(
								'prefix' => 'user_profile',
								'field' => $fields['wayOfTeach'],
								'value' => session::item('profile', 'signup'),
							)) ?>
						</td>
					</tr>
					<tr>
						<th>
							Teaching Experience
							<p>*Please do not input your personal contact, URL, Email Address into the blanks.</p>
						</th>
						<td class="x_field" colspan="2">
								<? view::load('system/elements/field/edit', array(
								'prefix' => 'user_profile',
								'field' => $fields['teachingExp'],
								'value' => session::item('profile', 'signup'),
							)) ?>
						</td>
					</tr>
					<tr>
						<th>
							Teaching Training
							<p>*Please do not input your personal contact, URL, Email Address into the blanks.</p>
						</th>
						<td class="x_field" colspan="2">
									<? view::load('system/elements/field/edit', array(
								'prefix' => 'user_profile',
								'field' => $fields['teachTraining'],
								'value' => session::item('profile', 'signup'),
							)) ?>
						</td>
					</tr>
					<tr>
						<th>
							<div class="must"><span>Must</span></div>Self Introduction
							<p>（You can write your character, hobby and other information）</p>
							<p>*Please do not input your personal contact, URL, Email Address into the blanks.</p>
						</th>
						<td class="x_field2" colspan="2">
								<? view::load('system/elements/field/edit', array(
								'prefix' => 'user_profile',
								'field' => $fields['selfIntro'],
								'value' => session::item('profile', 'signup'),
							)) ?>
						</td>
					</tr>
					<tr>
						<th>
							Additional Information about Lesson
							<p>* Please do not input your personal contact,URL,Email Address into the blanks.</p>
						</th>
						<td class="x_field" colspan="2">
							<? view::load('system/elements/field/edit', array(
								'prefix' => 'user_profile',
								'field' => $fields['adiInfo'],
								'value' => session::item('profile', 'signup'),
							)) ?>
						</td>
					</tr>
					<tr>
						<th>
							Tags
							<p>*You can add tags by separating them with a comma.</p>
						</th>
						<td colspan="2">
							<? view::load('system/elements/field/edit', array(
								'prefix' => 'user_profile',
								'field' => $fields['tags'],
								'value' => session::item('profile', 'signup'),
							)) ?>
							<p>*Your Profile will be at the search result, when the other member search for these tags</p>
						</td>
					</tr>
					<tr>
						<th>
							<div class="must"><span>Must</span></div>Lesson Tuition Fee and other Costs of Lesson (1 Hour)
							<p>*Students might feel confortable to contact you if you show the tuition fee of the lesson you provide.</p>
						</th>
						<td id="tuition" colspan="2">
							<table>
								<tr class="top">
									<th>Types of Lesson (Private)</th>
									<td class="wbg">Tuition Fee (Person/HKD)</td>
								</tr>
								<tr>
									<th>Trial Lesson</th>
									<td></td>
								</tr>
								<tr>
									<th>Lesson</th>
									<td></td>
								</tr>
								<tr>
									<th>Semi-group (up to 4)</th>
									<td></td>
								</tr>
								<tr>
									<th>Company Training</th>
									<td></td>
								</tr>
								<tr>
									<th>Language School Teaching</th>
									<td></td>
								</tr>
								<tr>
									<th>Translation Fee (per 1000 words)</th>
									<td></td>
								</tr>
								<tr>
									<th>Transportation Fee</th>
									<td class="choices" colspan="2">
										<input type="checkbox" id="inc1" /><label for="inc1">Included</label>
										<input type="checkbox" id="inc2" /><label for="inc2">Not Included</label>
										<input type="text" class="others noreq" id="inc3" /><label for="inc3">HKD</label>
									</td>
								</tr>
								<tr class="bot">
									<th>Spending at Café Shop</th>
									<td class="choices" colspan="2">
										<input type="checkbox" id="pay1" /><label for="pay1">Student Pay</label>
										<input type="checkbox" id="pay2" /><label for="pay2">Tutor Pay</label>
									</td>
								</tr>
							</table>
						</td>
					</tr>
					<tr>
						<th>
							Photo Upload
						</th>
						<td class="upload" colspan="2">
							<div class="div-left"><input type="text" class='noreq'/><a title="upload" href="#">Browse</a>
							<p>It is not necessary to upload your photo, but the tutor with the photo will much easier to get attention from the students. Please noted that the photo up to 200 K is not allowed to be uploaded.</p>
							</div>
						</td>
					</tr>
					<tr>
						<td class="break" colspan="2">
							<h3>Translation/Interpreter Service</h3>
						</td>
					</tr>
					<tr>
						<th>Business Document Translation Skills</th>
						<td colspan="2">
							<div class="wrap translate">
								<? view::load('system/elements/field/edit', array(
									'prefix' => 'user_profile',
									'field' => $fields['busDocTransSkil11'],
									'value' => session::item('profile', 'signup'),
								)) ?>
								<div class="arrow">></div>
								<? view::load('system/elements/field/edit', array(
									'prefix' => 'user_profile',
									'field' => $fields['busDocTransSkil12'],
									'value' => session::item('profile', 'signup'),
								)) ?>
								<a href="#" class="hide" title="hide"><img src="images/btn_hide.jpg" alt="hide" /></a>
							</div>
							<div  class="wrap translate">
								<? view::load('system/elements/field/edit', array(
									'prefix' => 'user_profile',
									'field' => $fields['busDocTransSkil21'],
									'value' => session::item('profile', 'signup'),
								)) ?>
								<div class="arrow">></div>
								<? view::load('system/elements/field/edit', array(
									'prefix' => 'user_profile',
									'field' => $fields['busDocTransSkil22'],
									'value' => session::item('profile', 'signup'),
								)) ?>			
								<a href="#" class="show" title="show"><img src="images/btn_show.jpg" alt="show" /></a>
							</div>
						</td>
					</tr>
					<tr>
						<th>Tourism/Business Interpreter Service</th>
						<td class="choices" colspan="2">
								<? view::load('system/elements/field/edit', array(
									'prefix' => 'user_profile',
									'field' => $fields['interService'],
									'value' => session::item('profile', 'signup'),
								)) ?>
						</td>
					</tr>
					<tr>
						<th>Previous Experience/Achievement <p>*Please do not input your personal contact, URL, Email Address into the blanks.</p></th>
						<td class="x_field2" colspan="2">
								<? view::load('system/elements/field/edit', array(
									'prefix' => 'user_profile',
									'field' => $fields['preExp'],
									'value' => session::item('profile', 'signup'),
								)) ?>							
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