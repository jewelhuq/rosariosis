<?php

include('modules/Scheduling/includes/calcSeats0.fnc.php');

include_once('modules/Scheduling/functions.inc.php');

if(!$_REQUEST['modfunc'] && $_REQUEST['search_modfunc']!='list')
	unset($_SESSION['MassSchedule.php']);

if($_REQUEST['modfunc']!='choose_course')
{
	DrawHeader(ProgramTitle());
}

if(isset($_REQUEST['modfunc']) && $_REQUEST['modfunc']=='save' && AllowEdit())
{
	if($_SESSION['MassSchedule.php'])
	{
		if (!empty($_REQUEST['student']))
		{
			$start_date = $_REQUEST['day'].'-'.$_REQUEST['month'].'-'.$_REQUEST['year'];
			if(VerifyDate($start_date))
			{
				$course_period_RET = DBGet(DBQuery("SELECT MARKING_PERIOD_ID, TOTAL_SEATS, COURSE_PERIOD_ID, CALENDAR_ID FROM COURSE_PERIODS WHERE COURSE_PERIOD_ID='".$_SESSION['MassSchedule.php']['course_period_id']."'"));

				$course_mp = $course_period_RET[1]['MARKING_PERIOD_ID'];
				$course_mp_table = GetMP($course_mp,'MP');

				if($course_mp_table=='FY' || $course_mp==$_REQUEST['marking_period_id'] || mb_strpos(GetChildrenMP($course_mp_table,$course_mp),"'".$_REQUEST['marking_period_id']."'")!==false)
				{
					//get available seats:
					if($course_period_RET[1]['TOTAL_SEATS'])
					{
						$seats = calcSeats0($course_period_RET[1],$start_date);

						if($seats!='' && $seats>=$course_period_RET[1]['TOTAL_SEATS'])
							$warnings[] = _('The number of selected students exceeds the available seats.');
					}

					//modif Francois: check if Available Seats < selected students
					if(empty($warnings) || Prompt('Confirm', _('There is a conflict.').' '._('Are you sure you want to add this section?'),ErrorMessage($warnings,'note')))
					{

						$mp_table = GetMP($_REQUEST['marking_period_id'],'MP');

						$current_RET = DBGet(DBQuery("SELECT STUDENT_ID FROM SCHEDULE WHERE COURSE_PERIOD_ID='".$_SESSION['MassSchedule.php']['course_period_id']."' AND SYEAR='".UserSyear()."' AND (('".$start_date."' BETWEEN START_DATE AND END_DATE OR END_DATE IS NULL) AND '".$start_date."'>=START_DATE)"),array(),array('STUDENT_ID'));
						foreach($_REQUEST['student'] as $student_id=>$yes)
						{
							if(!$current_RET[$student_id])
							{
								$sql = "INSERT INTO SCHEDULE (SYEAR,SCHOOL_ID,STUDENT_ID,COURSE_ID,COURSE_PERIOD_ID,MP,MARKING_PERIOD_ID,START_DATE)
											values('".UserSyear()."','".UserSchool()."','".$student_id."','".$_SESSION['MassSchedule.php']['course_id']."','".$_SESSION['MassSchedule.php']['course_period_id']."','".$mp_table."','".$_REQUEST['marking_period_id']."','".$start_date."')";
								DBQuery($sql);
						
								//hook
								do_action('Scheduling/MassSchedule.php|schedule_student');
							}
						}
						$note[] = _('This course has been added to the selected students\' schedules.');
					}
					else
						exit();
				}
				else
					$error[] = _('You cannot schedule a student into this course during this marking period.').' '.sprintf(_('This course meets on %s.'),GetMP($course_mp));
			}
			else
				$error[] = _('The date you entered is not valid');
		}
		else
			$error[] = _('You must choose at least one student.');
	}
	else
		$error[] = _('You must choose a course.');
		
	unset($_SESSION['_REQUEST_vars']['modfunc']);
	unset($_REQUEST['modfunc']);
	unset($_SESSION['MassSchedule.php']);
}

if (isset($error))
	echo ErrorMessage($error);
if(isset($note))
	echo ErrorMessage($note, 'note');
		
if(empty($_REQUEST['modfunc']))
{
	if($_REQUEST['search_modfunc']=='list')
	{
		echo '<FORM action="Modules.php?modname='.$_REQUEST['modname'].'&modfunc=save" method="POST">';
		DrawHeader('',SubmitButton(_('Add Course to Selected Students')));

		echo '<BR />';

		PopTable('header', _('Course to Add'));

		echo '<TABLE><TR><TD colspan="2"><DIV id=course_div>';

		if($_SESSION['MassSchedule.php'])
		{
			$course_title = DBGet(DBQuery("SELECT TITLE FROM COURSES WHERE COURSE_ID='".$_SESSION['MassSchedule.php']['course_id']."'"));
			$course_title = $course_title[1]['TITLE'];
			$period_title = DBGet(DBQuery("SELECT TITLE FROM COURSE_PERIODS WHERE COURSE_PERIOD_ID='".$_SESSION['MassSchedule.php']['course_period_id']."'"));
			$period_title = $period_title[1]['TITLE'];

			echo $course_title.'<BR />'.$period_title;
		}
		echo '</DIV>'.'<A HREF="#" onclick=\'window.open("Modules.php?modname='.$_REQUEST['modname'].'&modfunc=choose_course","","scrollbars=yes,resizable=yes,width=800,height=400");\'>'._('Choose a Course').'</A></TD></TR>';
		echo '<TR class="st"><TD>'._('Start Date').'</TD><TD>'.PrepareDate(DBDate(),'').'</TD></TR>';

		echo '<TR class="st"><TD>'._('Marking Period').'</TD>';
		$mp_RET = DBGet(DBQuery("SELECT MARKING_PERIOD_ID,TITLE,".db_case(array('MP',"'FY'","'0'","'SEM'","'1'","'QTR'","'2'"))." AS TBL FROM SCHOOL_MARKING_PERIODS WHERE (MP='FY' OR MP='SEM' OR MP='QTR') AND SCHOOL_ID='".UserSchool()."' AND SYEAR='".UserSyear()."' ORDER BY TBL,SORT_ORDER"));
		echo '<TD><SELECT name="marking_period_id">';
		foreach($mp_RET as $mp)
			echo '<OPTION value="'.$mp['MARKING_PERIOD_ID'].'">'.$mp['TITLE'].'</OPTION>';
		echo '</SELECT>';
		echo '</TD></TR></TABLE>';

		PopTable('footer');

		echo '<BR />';
	}

	if($_REQUEST['search_modfunc']!='list')
		unset($_SESSION['MassSchedule.php']);

	$extra['link'] = array('FULL_NAME'=>false);
	$extra['SELECT'] = ",CAST (NULL AS CHAR(1)) AS CHECKBOX";
	$extra['functions'] = array('CHECKBOX'=>'_makeChooseCheckbox');
	$extra['columns_before'] = array('CHECKBOX'=>'</A><INPUT type="checkbox" value="Y" name="controller" onclick="checkAll(this.form,this.form.controller.checked,\'student\');"><A>');
	$extra['new'] = true;

	Widgets('course');
	Widgets('request');
	MyWidgets('ly_course');
	//Widgets('activity');

	Search('student_id',$extra);

	if($_REQUEST['search_modfunc']=='list')
	{
		echo '<BR /><span class="center">'.SubmitButton(_('Add Course to Selected Students')).'</span>';
		echo '</FORM>';
	}

}

if($_REQUEST['modfunc']=='choose_course')
{

	if(!$_REQUEST['course_period_id'])
		include 'modules/Scheduling/Courses.php';
	else
	{
		$_SESSION['MassSchedule.php']['subject_id'] = $_REQUEST['subject_id'];
		$_SESSION['MassSchedule.php']['course_id'] = $_REQUEST['course_id'];
		$_SESSION['MassSchedule.php']['course_period_id'] = $_REQUEST['course_period_id'];

		$course_title = DBGet(DBQuery("SELECT TITLE FROM COURSES WHERE COURSE_ID='".$_SESSION['MassSchedule.php']['course_id']."'"));
		$course_title = $course_title[1]['TITLE'];
		$period_title = DBGet(DBQuery("SELECT TITLE FROM COURSE_PERIODS WHERE COURSE_PERIOD_ID='".$_SESSION['MassSchedule.php']['course_period_id']."'"));
		$period_title = $period_title[1]['TITLE'];

		echo '<script>opener.document.getElementById("course_div").innerHTML = '.json_encode($course_title.'<BR />'.$period_title).'; window.close();</script>';
		
	}
}

function _makeChooseCheckbox($value,$title)
{	global $THIS_RET;

	return '<INPUT type="checkbox" name="student['.$THIS_RET['STUDENT_ID'].']" value="Y" />';
}

?>
