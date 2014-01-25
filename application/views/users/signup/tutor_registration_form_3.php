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
		<p>Please fill the form below to creat your profile, then click “Submit” button.You can also change the setting in “My Page”, after you login TutorNavi, <br/>so that your privacy personal information will not be public in TutorNavi.</p>
		<p>※You will be able to change the other information except “Registered Email Address”, “Nickname” and  “Gender”.</p>
		<div class="must mtb10"><span>Must</span>Field is a Must.</div>
		<h3>Other Information</h3>
		<h3>Frequently questions from the students(FAQ)</h3>
		<form class="tutor_reg_form" method="POST">
			<input type="hidden" name="tutor_reg_step" value="3"/>
			<table>
				<tr>
					<th>
						The way of Teaching
						<p>※Please do not input your personal contact, URL, Email Address into the blanks.</p>
					</th>
					<td class="x_field" colspan="2">
						<textarea cols="50" rows="5"></textarea>
					</td>
				</tr>
				<tr>
					<th>
						Teaching Experience
						<p>※Please do not input your personal contact, URL, Email Address into the blanks.</p>
					</th>
					<td class="x_field" colspan="2">
						<textarea cols="50" rows="5"></textarea>
					</td>
				</tr>
				<tr>
					<th>
						Teaching Training
						<p>※Please do not input your personal contact, URL, Email Address into the blanks.</p>
					</th>
					<td class="x_field" colspan="2">
						<textarea cols="50" rows="5"></textarea>
					</td>
				</tr>
				<tr>
					<th>
						<div class="must"><span>Must</span></div>Self Introduction
						<p>（You can write your character, hobby and other information）</p>
						<p>※Please do not input your personal contact, URL, Email Address into the blanks.</p>
					</th>
					<td class="x_field2" colspan="2">
						<textarea cols="50" rows="15"></textarea>
					</td>
				</tr>
				<tr>
					<th>
						Additional Information about Lesson
						<p>※ Please do not input your personal contact,URL,Email Address into the blanks.</p>
					</th>
					<td class="x_field" colspan="2">
						<textarea cols="50" rows="5"></textarea>
					</td>
				</tr>
				<tr>
					<th>
						Tags
						<p>※You can add tags by separating them with a comma.</p>
					</th>
					<td colspan="2">
						<input type="text" class="tags" />
						<p>*Your Profile will be at the search result, when the other member search for these tags</p>
					</td>
				</tr>
				<tr>
					<th>
						<div class="must"><span>Must</span></div>Lesson Tuition Fee and other Costs of Lesson (1 Hour)
						<p>※Students might feel confortable to contact you if you show the tuition fee of the lesson you provide.</p>
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
									<input type="text" class="others" id="inc3" /><label for="inc3">HKD</label>
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
						<div class="div-left"><input type="text" /><a title="upload" href="#">Browse</a>
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
							<select><option>English</option></select>
							<div class="arrow">></div>
							<select><option>Cantonese</option></select>
							<a href="#" class="hide" title="hide"><img src="images/btn_hide.jpg" alt="hide" /></a>
						</div>
						<div  class="wrap translate">
							<select><option>Cantonese</option></select>
							<div class="arrow">></div>
							<select><option>English</option></select>
							<a href="#" class="show" title="show"><img src="images/btn_show.jpg" alt="show" /></a>
						</div>
					</td>
				</tr>
				<tr>
					<th>Tourism/Business Interpreter Service</th>
					<td class="choices" colspan="2">
						<div class="wrap">
							<input type="checkbox" id="tbs1" /><label for="tbs1">City Tour Guide</label>
							<input type="checkbox" id="tbs2" /><label for="tbs2">Business Meeting	</label>
							<input type="checkbox" id="tbs3" /><label for="tbs3">Exhibition Interpreter</label>
						</div>
						<div class="wrap">
							<input type="checkbox" id="tbs4" /><label for="tbs4">Factory Visit</label>
							<input type="checkbox" id="tbs5" /><label for="tbs5">Good Purchase Attendance</label>
						</div>
					</td>
				</tr>
				<tr>
					<th>Previous Experience/Achievement <p>※Please do not input your personal contact, URL, Email Address into the blanks.</p></th>
					<td class="x_field2" colspan="2">
						<textarea cols="50" rows="15"></textarea>
					</td>
				</tr>
				<tr>
					<td class="break btn" colspan="3"><input type="submit" class="next" value="Submit" /></td>
				</tr>
			</table>
		</form>
		<div class="link_wrap clearfix">
		<a class="back-top" href="#" title="Back to Home">Back to Home</a>
		<a class="to-top" href="#" title="Back to Home">Back to Top</a>
		</div>
	</div><!--END OF REGISRAION WRAP-->
			
<? view::load('footer'); ?>