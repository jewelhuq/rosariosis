<?php
if($_REQUEST['modfunc']=='save' && AllowEdit())
{
	if (isset($_REQUEST['staff']) && is_array($_REQUEST['staff']))
	{
		$current_RET = DBGet(DBQuery("SELECT STAFF_ID FROM STUDENTS_JOIN_USERS WHERE STUDENT_ID='".UserStudentID()."'"),array(),array('STAFF_ID'));
		foreach($_REQUEST['staff'] as $staff_id=>$yes)
		{
			if(!$current_RET[$staff_id])
			{
				$sql = "INSERT INTO STUDENTS_JOIN_USERS (STAFF_ID,STUDENT_ID) values('".$staff_id."','".UserStudentID()."')";
				DBQuery($sql);

				//hook
				do_action('Students/AddUsers.php|user_assign_role');
			}
		}
		$note[] = _('The selected user\'s profile now includes access to the selected students.');
	}
	else
		$error[] = _('You must choose at least one user');

	unset($_REQUEST['modfunc']);
	unset($_SESSION['_REQUEST_vars']['modfunc']);
}

DrawHeader(ProgramTitle());

if($_REQUEST['modfunc']=='delete' && AllowEdit())
{
	if(DeletePrompt(_('student from that user'),_('remove access to')) && !empty($_REQUEST['staff_id']))
	{
		DBQuery("DELETE FROM STUDENTS_JOIN_USERS WHERE STAFF_ID='".$_REQUEST['staff_id']."' AND STUDENT_ID='".UserStudentID()."'");

		//hook
		do_action('Students/AddUsers.php|user_unassign_role');

		unset($_REQUEST['modfunc']);
	}
}

if(isset($note))
	echo ErrorMessage($note,'note');

if(isset($error))
	echo ErrorMessage($error);

if($_REQUEST['modfunc']!='delete')
{
	$extra['SELECT'] = ",(SELECT count(u.STAFF_ID) FROM STUDENTS_JOIN_USERS u,STAFF st WHERE u.STUDENT_ID=s.STUDENT_ID AND st.STAFF_ID=u.STAFF_ID AND st.SYEAR=ssm.SYEAR) AS ASSOCIATED";
	$extra['columns_after'] = array('ASSOCIATED'=>'# '._('Associated'));
	Search('student_id',$extra);

	if(UserStudentID())
	{
		if($_REQUEST['search_modfunc']=='list')
		{
			echo '<FORM action="Modules.php?modname='.$_REQUEST['modname'].'&modfunc=save" method="POST">';
			DrawHeader('',SubmitButton(_('Add Selected Parents')));
		}

		echo '<TABLE class="center"><TR><TD>';

		$current_RET = DBGet(DBQuery("SELECT u.STAFF_ID,s.LAST_NAME||', '||s.FIRST_NAME AS FULL_NAME,s.LAST_LOGIN FROM STUDENTS_JOIN_USERS u,STAFF s WHERE s.STAFF_ID=u.STAFF_ID AND u.STUDENT_ID='".UserStudentID()."' AND s.SYEAR='".UserSyear()."'"),array('LAST_LOGIN'=>'makeLogin'));

		$link['remove'] = array('link'=>'Modules.php?modname='.$_REQUEST['modname'].'&modfunc=delete','variables'=>array('staff_id'=>'STAFF_ID'));

		ListOutput($current_RET,array('FULL_NAME'=>_('Parents'),'LAST_LOGIN'=>_('Last Login')),'Associated Parent','Associated Parents',$link,array(),array('search'=>false));

		echo '</TD></TR><TR><TD>';

		if(AllowEdit())
		{
			unset($extra);
			$extra['link'] = array('FULL_NAME'=>false);
			$extra['SELECT'] = ",CAST (NULL AS CHAR(1)) AS CHECKBOX";
			$extra['functions'] = array('CHECKBOX'=>'_makeChooseCheckbox');
			$extra['columns_before'] = array('CHECKBOX'=>'</A><INPUT type="checkbox" value="Y" name="controller" onclick="checkAll(this.form,this.form.controller.checked,\'staff\');" /><A>');
			$extra['new'] = true;
			$extra['options']['search'] = false;
			$extra['profile'] = 'parent';

			Search('staff_id',$extra);
		}

		echo '</TD></TR></TABLE>';

		if($_REQUEST['search_modfunc']=='list')
			echo '<BR /><span class="center">'.SubmitButton(_('Add Selected Parents')).'</span></FORM>';
	}
}

function _makeChooseCheckbox($value,$title)
{	global $THIS_RET;

	return '<INPUT type="checkbox" name="staff['.$THIS_RET['STAFF_ID'].']" value="Y" />';
}
?>
