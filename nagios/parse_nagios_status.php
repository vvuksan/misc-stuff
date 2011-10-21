<?php


#######################################################################
# Modified from Jason Antman's script
#######################################################################

// Script to parse Nagios status.dat and present it as XML, to allow inclusion
//   of status info in a web page served from a separate machine.
//
// +----------------------------------------------------------------------+
// | PHP EMS Tools      http://www.php-ems-tools.com                      |
// +----------------------------------------------------------------------+
// | Copyright (c) 2006, 2007 Jason Antman.                               |
// |                                                                      |
// | This program is free software; you can redistribute it and/or modify |
// | it under the terms of the GNU General Public License as published by |
// | the Free Software Foundation; either version 3 of the License, or    |
// | (at your option) any later version.                                  |
// |                                                                      |
// | This program is distributed in the hope that it will be useful,      |
// | but WITHOUT ANY WARRANTY; without even the implied warranty of       |
// | MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the        |
// | GNU General Public License for more details.                         |
// |                                                                      |
// | You should have received a copy of the GNU General Public License    |
// | along with this program; if not, write to:                           |
// |                                                                      |
// | Free Software Foundation, Inc.                                       |
// | 59 Temple Place - Suite 330                                          |
// | Boston, MA 02111-1307, USA.                                          |
// +----------------------------------------------------------------------+
// | Authors: Jason Antman <jason@jasonantman.com>                        |
// +----------------------------------------------------------------------+
//      $Id: statusXML.php,v 1.6 2009/02/07 03:32:14 jantman Exp $
//      $Source: /usr/local/cvsroot/admin-portal/nagios/statusXML.php,v $


function parse_nagios_status_file($statusFile = "/var/log/nagios/status.dat") {

  //$nag_version = getFileVersion($statusFile); // returns integer 2 or 3
  $nag_version = 3;
  $created_ts = 0;

  $debug = false;

  if($nag_version == 3)
    {
      $data = getData3($statusFile); // returns an array
    }
  else
    {
      $data = getData2($statusFile); // returns an array
    }

  return $data;

//   $hosts = $data['hosts'];
//   $services = $data['services'];
//   $program = "";
//   if(array_key_exists("program", $data))
//     {
//       $program = $data['program'];
//     }
// 
//   print_r($services);

}

// figure out what version the file is
function getFileVersion($statusFile)
{
  global $created_ts;
  $version = 2;

  $fh = fopen($statusFile, 'r');
  $inInfo = false;
  while($line = fgets($fh))
    {
      if(trim($line) == "info {")
	{
	  $inInfo = true;
	}
      elseif(trim($line) == "}")
	{
	  $inInfo = false;
	  break;
	}
      elseif($inInfo)
	{
	  $vals = explode("=", $line);
	  if(trim($vals[0]) == "created")
	    {
	      $created = $vals[1];
	    }
	  elseif(trim($vals[0]) == "version")
	    {
	      if(substr($vals[1], 0, 1) == "3")
		{
		  $version = 3;
		}
	    }
	}
    }
  return $version;
}

// parse nagios2 status.dat
function getData2($statusFile)
{
    // the keys to get from host status:
    $host_keys = array('host_name', 'has_been_checked', 'check_execution_time', 'check_latency', 'check_type', 'current_state', 'current_attempt', 'state_type', 'last_state_change', 'last_time_up', 'last_time_down', 'last_time_unreachable', 'last_notification', 'next_notification', 'no_more_notifications', 'current_notification_number', 'notifications_enabled', 'problem_has_been_acknowledged', 'acknowledgement_type', 'active_checks_enabled', 'passive_checks_enabled', 'last_update');

    // keys to get from service status:
    $service_keys = array('host_name', 'service_description', 'has_been_checked', 'check_execution_time', 'check_latency', 'current_state', 'state_type', 'last_state_change', 'last_time_ok', 'last_time_warning', 'last_time_unknown', 'last_time_critical', 'plugin_output', 'last_check', 'notifications_enabled', 'active_checks_enabled', 'passive_checks_enabled', 'problem_has_been_acknowledged', 'acknowledgement_type', 'last_update', 'is_flapping');

    # open the file
    $fh = fopen($statusFile, 'r');
    
    # variables to keep state
    $inSection = false;
    $sectionType = "";
    $lineNum = 0;
    $sectionData = array();

    $hostStatus = array();
    $serviceStatus = array();

    #variables for total hosts and services
    $typeTotals = array();
    
    # loop through the file
    while($line = fgets($fh))
    {
	$lineNum++; // increment counter of line number, mainly for debugging
	$line = trim($line); // strip whitespace
	if($line == ""){ continue;} // ignore blank line
	if(substr($line, 0, 1) == "#"){	continue;} // ignore comment
	
	// ok, now we need to deal with the sections
	
	if(! $inSection)
	{
	    // we're not currently in a section, but are looking to start one
	    if(strstr($line, " ") && (substr($line, -1) == "{")) // space and ending with {, so it's a section header
	    {
		$sectionType = substr($line, 0, strpos($line, " ")); // first word on line is type
		$inSection = true;
		// we're now in a section
		$sectionData = array();

		// increment the counter for this sectionType
		if(isset($typeTotals[$sectionType])){$typeTotals[$sectionType]=$typeTotals[$sectionType]+1;}else{$typeTotals[$sectionType]=1;}
		
	    }
	}
    
	if($inSection && $line == "}") // closing a section
	{
	    if($sectionType == "service")
	    {
		$serviceStatus[$sectionData['host_name']][$sectionData['service_description']] = $sectionData;
	    }
	    if($sectionType == "host")
	    {
		$hostStatus[$sectionData["host_name"]] = $sectionData;
	    }
	    $inSection = false;
	    $sectionType = "";
	    continue;
	}
	else
	{
	    // we're currently in a section, and this line is part of it
	    $lineKey = substr($line, 0, strpos($line, "="));
	    $lineVal = substr($line, strpos($line, "=")+1);
	    
	    // add to the array as appropriate
	    if($sectionType == "service")
	    {
		if(in_array($lineKey, $service_keys))
		{
		    $sectionData[$lineKey] = $lineVal;
		}
	    }
	    elseif($sectionType == "host")
	    {
		if(in_array($lineKey, $host_keys))
		{
		    $sectionData[$lineKey] = $lineVal;
		}
	    }
	    // else continue on, ignore this section, don't save anything
	}

    }
    
    fclose($fh);
    
    $retArray = array("hosts" => $hostStatus, "services" => $serviceStatus);
    
    return $retArray;    
}

// parse nagios3 status.dat
function getData3($statusFile)
{
  global $debug;
    // the keys to get from host status:
  $host_keys = array('host_name', 'modified_attributes', 'check_command', 'check_period', 'notification_period', 'check_interval', 'retry_interval', 'event_handler', 'has_been_checked', 'should_be_scheduled', 'check_execution_time', 'check_latency', 'check_type', 'current_state', 'last_hard_state', 'last_event_id', 'current_event_id', 'current_problem_id', 'last_problem_id', 'plugin_output', 'long_plugin_output', 'performance_data', 'last_check', 'next_check', 'check_options', 'current_attempt', 'max_attempts', 'state_type', 'last_state_change', 'last_hard_state_change', 'last_time_up', 'last_time_down', 'last_time_unreachable', 'last_notification', 'next_notification', 'no_more_notifications', 'current_notification_number', 'current_notification_id', 'notifications_enabled', 'problem_has_been_acknowledged', 'acknowledgement_type', 'active_checks_enabled', 'passive_checks_enabled', 'event_handler_enabled', 'flap_detection_enabled', 'failure_prediction_enabled', 'process_performance_data', 'obsess_over_host', 'last_update', 'is_flapping', 'percent_state_change', 'scheduled_downtime_depth');
  // keys to get from service status:
  $service_keys = array('host_name', 'service_description', 'modified_attributes', 'check_command', 'check_period', 'notification_period', 'check_interval', 'retry_interval', 'event_handler', 'has_been_checked', 'should_be_scheduled', 'check_execution_time', 'check_latency', 'check_type', 'current_state', 'last_hard_state', 'last_event_id', 'current_event_id', 'current_problem_id', 'last_problem_id', 'current_attempt', 'max_attempts', 'state_type', 'last_state_change', 'last_hard_state_change', 'last_time_ok', 'last_time_warning', 'last_time_unknown', 'last_time_critical', 'plugin_output', 'long_plugin_output', 'performance_data', 'last_check', 'next_check', 'check_options', 'current_notification_number', 'current_notification_id', 'last_notification', 'next_notification', 'no_more_notifications', 'notifications_enabled', 'active_checks_enabled', 'passive_checks_enabled', 'event_handler_enabled', 'problem_has_been_acknowledged', 'acknowledgement_type', 'flap_detection_enabled', 'failure_prediction_enabled', 'process_performance_data', 'obsess_over_service', 'last_update', 'is_flapping', 'percent_state_change', 'scheduled_downtime_depth');

    # open the file
    $fh = fopen($statusFile, 'r');

    # variables to keep state
    $inSection = false;
    $sectionType = "";
    $lineNum = 0;
    $sectionData = array();

    $hostStatus = array();
    $serviceStatus = array();
    $programStatus = array();

    #variables for total hosts and services
    $typeTotals = array();
    
    # loop through the file
    while($line = fgets($fh))
    {
	$lineNum++; // increment counter of line number, mainly for debugging
	$line = trim($line); // strip whitespace
	if($line == ""){ continue;} // ignore blank line
	if(substr($line, 0, 1) == "#"){	continue;} // ignore comment
	
	// ok, now we need to deal with the sections
	if(! $inSection)
	{
	    // we're not currently in a section, but are looking to start one
	  if(substr($line, strlen($line)-1, 1) == "{") // space and ending with {, so it's a section header
	    {
		$sectionType = substr($line, 0, strpos($line, " ")); // first word on line is type
		$inSection = true;
		// we're now in a section
		$sectionData = array();

		// increment the counter for this sectionType
		if(isset($typeTotals[$sectionType])){$typeTotals[$sectionType]=$typeTotals[$sectionType]+1;}else{$typeTotals[$sectionType]=1;}
		
	    }
	}
    	elseif($inSection && trim($line) == "}") // closing a section
	{
	    if($sectionType == "servicestatus")
	    {
		$serviceStatus[$sectionData['host_name']][$sectionData['service_description']] = $sectionData;
	    }
	    elseif($sectionType == "hoststatus")
	    {
		$hostStatus[$sectionData["host_name"]] = $sectionData;
	    }
	    elseif($sectionType == "programstatus")
	      {
		$programStatus = $sectionData;
	      }
	    $inSection = false;
	    $sectionType = "";
	    continue;
	}
	else
	{
	    // we're currently in a section, and this line is part of it
	    $lineKey = substr($line, 0, strpos($line, "="));
	    $lineVal = substr($line, strpos($line, "=")+1);

	    // add to the array as appropriate
	    if($sectionType == "servicestatus" || $sectionType == "hoststatus" || $sectionType == "programstatus")
	    {
	      if($debug){ echo "LINE ".$lineNum.": lineKey=".$lineKey."= lineVal=".$lineVal."=\n";}
	      $sectionData[$lineKey] = $lineVal;
	    }
	    // else continue on, ignore this section, don't save anything
	}

    }
    
    fclose($fh);

    $retArray = array("hosts" => $hostStatus, "services" => $serviceStatus, "program" => $programStatus);
    return $retArray;    
}


// this formats the age of a check in seconds into a nice textual description
function ageString($seconds)
{
	$age = "";
	if($seconds > 86400)
	{
		$days = (int)($seconds / 86400);
		$seconds = $seconds - ($days * 86400);
		$age .= $days." days ";
	}
	if($seconds > 3600)
	{
		$hours = (int)($seconds / 3600);
		$seconds = $seconds - ($hours * 3600);
		$age .= $hours." hours ";
	}
	if($seconds > 60)
	{
		$minutes = (int)($seconds / 60);
		$seconds = $seconds - ($minutes * 60);
		$age .= $minutes." minutes ";
	}
	$age .= $seconds." seconds ";
	return $age;
}


/*
    // the keys to get from host status:
    $host_keys = array('host_name', 'has_been_checked', 'check_execution_time', 'check_latency', 'check_type', 'current_state', 'current_attempt', 'state_type', 'last_state_change', 'last_time_up', 'last_time_down', 'last_time_unreachable', 'last_notification', 'next_notification', 'no_more_notifications', 'current_notification_number', 'notifications_enabled', 'problem_has_been_acknowledged', 'acknowledgement_type', 'active_checks_enabled', 'passive_checks_enabled', 'last_update');

    // keys to get from service status:
    $service_keys = array('host_name', 'service_description', 'has_been_checked', 'check_execution_time', 'check_latency', 'current_state', 'state_type', 'last_state_change', 'last_time_ok', 'last_time_warning', 'last_time_unknown', 'last_time_critical', 'plugin_output', 'last_check', 'notifications_enabled', 'active_checks_enabled', 'passive_checks_enabled', 'problem_has_been_acknowledged', 'acknowledgement_type', 'last_update', 'is_flapping');

*/


?>
