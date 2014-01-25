<? view::load('header'); ?>

	<ul class="crumbs clearfix">
		<li><a title="Home" href="#">Home</a></li>
		<li>></li>
		<li>Tutor Registration</li>
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
		<p>Please fill the form below to creat your profile, then click “Submit” button.You can also change the setting in "My Page", after you login TutorNavi, <br/>so that your privacy personal information will not be public in TutorNavi.</p>
		<p>*You will be able to change the other information except “Registered Email Address”, "Nickname" and "Gender".</p>
		<div class="must mtb10"><span>Must</span>Field is a Must.</div>
		<h3>About Lesson</h3>
		<form class="tutor_reg_form" method="POST">
			<input type="hidden" name="tutor_reg_step" value="2"/>
			<table>
				<tr>
					<th><div class="must"><span>Must</span></div>Japanese Speaking Level</th>
					<td class="choices" colspan="2">
						<div class="wrap l_fix">
						<input type="checkbox" id="jsl1" /><label for="jsl1">No</label>
						<input type="checkbox" id="jsl2" /><label for="jsl2">Limited</label>
						<input type="checkbox" id="jsl3" /><label for="jsl3">Conversational</label>
						<input type="checkbox" id="jsl4" /><label for="jsl4">Fluent</label>
						</div>
					</td>
				</tr>
				<tr>
					<th><div class="must"><span>Must</span></div>Japanese Writing Skills<p>*For internal message with the Japanese students</p></th>
					<td class="choices" colspan="2">
					<div class="wrap">
						<input type="checkbox" id="jws1" /><label for="jws1">No</label>
						<input type="checkbox" id="jws2" /><label for="jws2">Kanji, Hiragana, Katakana OK</label>
						<input type="checkbox" id="jws3" /><label for="jws3">Hiragana Only</label>
						<input type="checkbox" id="jws4" /><label for="jws4">Roman Characters Only</label>
					</div>
					</td>
				</tr>
				<tr>
					<th><div class="must"><span>Must</span></div>Language Levels You  Teach</th>
					<td class="choices" colspan="2">
					<div class="wrap">
						<input type="checkbox" id="ll1" /><label for="ll1">Elementary</label>
						<input type="checkbox" id="ll2" /><label for="ll2">Intermediate</label>
						<input type="checkbox" id="ll3" /><label for="ll3">High Intermediate</label>
						<input type="checkbox" id="ll4" /><label for="ll4">Advanced</label>
					</div>
						<div class="wrap">
						<input type="checkbox" id="ll5" /><label for="ll5">Exam Preparation</label>
						<input type="checkbox" id="ll6" /><label for="ll6">Business Level</label>
						</div>
					</td>
				</tr>
				<tr>
					<th><div class="must"><span>Must</span></div>Teaching Field</th>
					<td class="choices" colspan="2">
					<div class="wrap">
						<input type="checkbox" id="tf1" /><label for="tf1">Conversation </label>
						<input type="checkbox" id="tf2" /><label for="tf2">Presentation </label>
						<input type="checkbox" id="tf3" /><label for="tf3">Business Letter</label>
						<input type="checkbox" id="tf4" /><label for="tf4">Business Conversation </label>
						
					</div>
						<div class="wrap">
						<input type="checkbox" id="tf5" /><label for="tf5">Speech </label>
							<input type="checkbox" id="tf6" /><label for="tf6">Travel Purpose</label>
							<input type="checkbox" id="tf7" /><label for="tf7">Writing Skills</label>
							<input type="checkbox" id="tf8" /><label for="tf8">Reading</label>
							<input type="checkbox" id="tf9" /><label for="tf9">Career Path</label>
						</div>
					</td>
				</tr>
				<tr>
					<th><div class="must"><span>Must</span></div>Types of Lesson</th>
					<td class="choices" colspan="2">
					<div class="wrap">
						<input type="checkbox" id="tl1" /><label for="tl1">Private </label>
						<input type="checkbox" id="tl2" /><label for="tl2">Semi-Group </label>
						<input type="checkbox" id="tl3" /><label for="tl3">Company Training</label>
						<input type="checkbox" id="tl4" /><label for="tl4">School Teaching</label>
					</div>
					</td>
				</tr>
				<tr>
					<th><div class="must"><span>Must</span></div>Lessons Delivery</th>
					<td class="choices" colspan="2">
					<div class="wrap">
						<input type="checkbox" id="ld1" /><label for="ld1">Live in Person </label>
						<input type="checkbox" id="ld2" /><label for="ld2">Telephone </label>
						<input type="checkbox" id="ld3" /><label for="ld3">Email  </label>
						<input type="checkbox" id="ld4" /><label for="ld4">Voice Chat</label>
						<input type="checkbox" id="ld5" /><label for="ld5">Video Chat</label>
					</div>
					</td>
				</tr>
				<tr>
					<th><div class="must"><span>Must</span></div>Preferred Students</th>
					<td class="choices" colspan="2">
					<div class="wrap">
						<input type="checkbox" id="ps1" /><label for="ps1">Adults </label>
						<input type="checkbox" id="ps2" /><label for="ps2">Kids </label>
						<input type="checkbox" id="ps3" /><label for="ps3">School Students  </label>
					</div>
					</td>
				</tr>
				<tr>
					<th><div class="must"><span>Must</span></div>Location<p>※Your preferred lesson location</p></th>
					<td class="choices" colspan="2">
						<input type="checkbox" id="loc1" /><label for="loc1">Cafe </label>
						<input type="checkbox" id="loc2" /><label for="loc2">Company </label>
						<input type="checkbox" id="loc3" /><label for="loc3">Student's House </label>
						<input type="checkbox" id="loc4" /><label for="loc4">Tutor's House </label>
					</td>
				</tr>
				<tr>
					<th><div class="must"><span>Must</span></div>Teaching Area</th>
					<td class="teach_area" colspan="2">
						<select><option></option></select>
					</td>
				</tr>
				<tr>
					<th><div class="must"><span>Must</span></div>Teaching Method</th>
					<td class="choices" colspan="2">
						<div class="wrap">
						<input type="checkbox" id="tm1" /><label for="tm1">Textbook </label>
						<input type="checkbox" id="tm2" /><label for="tm2">Newspaper Articles </label>
						<input type="checkbox" id="tm3" /><label for="tm3">Videos or CDs </label>
						<input type="checkbox" id="tm4" /><label for="tm4">Tutor’s Materials</label>
						</div>
						<div class="wrap">
						<input type="checkbox" id="tm5" /><label for="tm5">Free Conversation </label>
						<input type="checkbox" id="tm6" /><label for="tm6">Role Play </label>
						<input type="checkbox" id="tm7" /><label for="tm7">Game </label>
						<label class="ml10">Others</label><input type="text" class="others" />
						</div>
					</td>
				</tr>
				<tr>
					<th><div class="must"><span>Must</span></div>Teaching Certificate/Qualification</th>
					<td class="choices" colspan="2">
						English Teaching Certificate
						<div class="wrap">
							<input type="checkbox" id="etc1" /><label for="etc1">TESL </label>
							<input type="checkbox" id="etc2" /><label for="etc2">TEFL </label>
							<input type="checkbox" id="etc3" /><label for="etc3">TESOL </label>
							<input type="checkbox" id="etc4" /><label for="etc4">CELTA <a class="ml5" title="details" href="#">? Details about these 4 English Teaching Certificate</a></label>
						</div>
						
						<div class="wrap">
							<span>Chinese Teaching Certificate</span>
							<input type="radio" name="tcsol" id="jtc1" /><label for="jtc1">Yes</label>
							<input type="radio" name="tcsol" id="jtc2" /><label for="jtc2">No</label>
						</div>
						
						<div class="wrap">
							<span>Japanese Teaching Certificate</span>
							<input type="radio" name="jtc" id="jtc3" /><label for="jtc3">Yes</label>
							<input type="radio" name="jtc" id="jtc4" /><label for="jtc4">No</label>
						</div>
					</td>
				</tr>
				<tr>
					<th><div class="must"><span>Must</span></div>Work Experience at Language Schools</th>
					<td class="choices" colspan="2">
						<input type="radio" name="wels" id="wels1" /><label for="wels1">Yes</label>
						<input type="radio" name="wels" id="wels2" /><label for="wels2">No</label>
					</td>
				</tr>
				<tr>
					<th>Work Visa in HK<p>※Are you a work permit holder in HK?</p></th>
					<td class="choices" colspan="2">
						<input type="radio" name="wv" id="wv1" /><label for="wv1">Yes</label>
						<input type="radio" name="wv" id="wv2" /><label for="wv2">No</label>
					</td>
				</tr>
				<tr>
					<th><div class="must"><span>Must</span></div>Available Date &amp; Time<p>※Please mark your available date time for teaching.</p></th>
					<td colspan="2" id="sched_mark">
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
								<td><input type="checkbox" /></td>
								<td><input type="checkbox" /></td>
								<td><input type="checkbox" /></td>
								<td><input type="checkbox" /></td>
								<td><input type="checkbox" /></td>
								<td class="right"><input type="checkbox" /></td>
							</tr>
							<tr>
								<th>Mon</th>
								<td><input type="checkbox" /></td>
								<td><input type="checkbox" /></td>
								<td><input type="checkbox" /></td>
								<td><input type="checkbox" /></td>
								<td><input type="checkbox" /></td>
								<td class="right"><input type="checkbox" /></td>
							</tr>
							<tr>
								<th>Tue</th>
								<td><input type="checkbox" /></td>
								<td><input type="checkbox" /></td>
								<td><input type="checkbox" /></td>
								<td><input type="checkbox" /></td>
								<td><input type="checkbox" /></td>
								<td class="right"><input type="checkbox" /></td>
							</tr>
							<tr>
								<th>Wed</th>
								<td><input type="checkbox" /></td>
								<td><input type="checkbox" /></td>
								<td><input type="checkbox" /></td>
								<td><input type="checkbox" /></td>
								<td><input type="checkbox" /></td>
								<td class="right"><input type="checkbox" /></td>
							</tr>
							<tr>
								<th>Thu</th>
								<td><input type="checkbox" /></td>
								<td><input type="checkbox" /></td>
								<td><input type="checkbox" /></td>
								<td><input type="checkbox" /></td>
								<td><input type="checkbox" /></td>
								<td class="right"><input type="checkbox" /></td>
							</tr>
							<tr>
								<th>Fri</th>
								<td><input type="checkbox" /></td>
								<td><input type="checkbox" /></td>
								<td><input type="checkbox" /></td>
								<td><input type="checkbox" /></td>
								<td><input type="checkbox" /></td>
								<td class="right"><input type="checkbox" /></td>
							</tr>
							<tr class="bot">
								<th>Sat</th>
								<td><input type="checkbox" /></td>
								<td><input type="checkbox" /></td>
								<td><input type="checkbox" /></td>
								<td><input type="checkbox" /></td>
								<td><input type="checkbox" /></td>
								<td class="right"><input type="checkbox" /></td>
							</tr>
						</table>
					</td>
				</tr>
				<tr>
					<td class="break btn" colspan="3"><input type="submit" class="next" value="Next" /></td>
				</tr>
			</table>
		</form>
		<a class="back-top" title="Back to Home" href="#">Back to Home</a>
	</div><!--END OF REGISRAION WRAP-->
			
<? view::load('footer'); ?>