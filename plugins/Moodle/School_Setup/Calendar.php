<?php
//modif Francois: Moodle integrator

//core_calendar_create_calendar_events function
function core_calendar_create_calendar_events_object()
{
	//first, gather the necessary variables
	global $_REQUEST;
	
	//then, convert variables for the Moodle object:
/*
list of ( 
	  //event
	object {
		name string   //event name
		description string  Default to "null" //Description
		format int  Default to "1" //description format (1 = HTML, 0 = MOODLE, 2 = PLAIN or 4 = MARKDOWN)
		courseid int  Default to "0" //course id
		groupid int  Default to "0" //group id
		repeats int  Default to "0" //number of repeats
		eventtype string  Default to "user" //Event type
		timestart int  Default to "1370827707" //timestart
		timeduration int  Default to "0" //time duration (in minutes)
		visible int  Default to "1" //visible
		sequence int  Default to "1" //sequence
	} 
)
*/
		
	$name = $_REQUEST['values']['TITLE'];
	$description = $_REQUEST['values']['DESCRIPTION'];
	$format = 2;
	$courseid = 1;
	$eventtype = 'site';
	$timestart = strtotime($_REQUEST['values']['SCHOOL_DATE']);
	
	$events = array(
				array(
					'name' => $name,
					'description' => $description,
					'format' => $format,
					'courseid' => $courseid,
					'timestart' => $timestart,
					'eventtype' => $eventtype,
				)
			);
	
	return array($events);
}


function core_calendar_create_calendar_events_response($response)
{
	//first, gather the necessary variables
	global $calendar_event_id, $_REQUEST;
	
	//then, save the ID in the moodlexrosario cross-reference table if no error:
/*
object {
	events list of ( 
		  //event
		object {
			id int   //event id
			name string   //event name
			description string  Optional //Description
			format int   //description format (1 = HTML, 0 = MOODLE, 2 = PLAIN or 4 = MARKDOWN)
			courseid int   //course id
			groupid int   //group id
			userid int   //user id
			repeatid int  Optional //repeat id
			modulename string  Optional //module name
			instance int   //instance id
			eventtype string   //Event type
			timestart int   //timestart
			timeduration int   //time duration
			visible int   //visible
			uuid string  Optional //unique id of ical events
			sequence int   //sequence
			timemodified int   //time modified
			subscriptionid int  Optional //Subscription id
		} 
		)warnings  Optional //list of warnings
		list of ( 
		  //warning
		object {
			item string  Optional //item
			itemid int  Optional //item id
			warningcode string   //the warning code can be used by the client app to implement specific behaviour
			message string   //untranslated english message to explain the warning
		} 
)} 
*/
	if (is_array($response['warnings'][0]))
	{
		global $error;

		$error = 'Code: '.$response['warnings'][0]['warningcode'].' - '.$response['warnings'][0]['message'];

		return false;
	}
	
	if (empty($calendar_event_id)) //case: update event
		$calendar_event_id = $_REQUEST['event_id'];
		
	DBQuery("INSERT INTO MOODLEXROSARIO (\"column\", rosario_id, moodle_id) VALUES ('calendar_event_id', '".$calendar_event_id."', ".$response['events'][0]['id'].")");
	return null;
}


//core_calendar_delete_calendar_events function
function core_calendar_delete_calendar_events_object()
{
	//first, gather the necessary variables
	global $_REQUEST;
	
	//then, convert variables for the Moodle object:
/*
list of ( 
	  //List of events to delete
	object {
		eventid int   //Event ID
		repeat int   //Delete comeplete series if repeated event
	} 
)
*/
	
	//gather the Moodle Event ID
	$eventid = DBGet(DBQuery("SELECT moodle_id FROM moodlexrosario WHERE rosario_id='".$_REQUEST['event_id']."' AND \"column\"='calendar_event_id'"));
	if (count($eventid))
	{
		$eventid = (int)$eventid[1]['MOODLE_ID'];
	}
	else
	{
		return null;
	}
	$repeat = 0;
	
	$events = array(
				array(
					'eventid' => $eventid,
					'repeat' => $repeat,
				)
			);
	
	return array($events);
}


function core_calendar_delete_calendar_events_response($response)
{
	//first, gather the necessary variables
	global $_REQUEST;
	
	$rosario_id = $_REQUEST['event_id'];
	
	//delete the reference the moodlexrosario cross-reference table:
	DBQuery("DELETE FROM MOODLEXROSARIO WHERE \"column\" = 'calendar_event_id' AND rosario_id = '".$rosario_id."'");
	
	return null;
}
?>
