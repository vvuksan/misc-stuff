<?

$current_dir = dirname(__FILE__); 
$debug = 0;

require_once($current_dir . "/tools.php");

#
$conf['overlay_events_file'] = "/var/lib/ganglia/conf/events.json";
$conf['from_address'] = "Nagios <nagios@domain.com>";
$conf['nagios_bot_host'] = "127.0.0.1";
$conf['nagios_bot_port'] = 3843;

// Parse the command line arguments
$cmds = commandline_arguments($argv);

#########################################################################################################################
# These are the proper command line invocations for Nagios/Icinga
#
# Service alert command
#
# command_line	/usr/bin/php /opt/icinga/send_nagios_email.php --type="$NOTIFICATIONTYPE$" --servicedesc="$SERVICEDESC$" -
#-host="$HOSTALIAS$" --hostaddress="$HOSTADDRESS$" --servicestate="$SERVICESTATE$"   --email="$CONTACTEMAIL$" 
# --time="$LONGDATETIME$" --additionalinfo="$SERVICEOUTPUT$"

# Host alert command
# 
# command_line	/usr/bin/php /opt/icinga/send_nagios_email.php --type="$NOTIFICATIONTYPE" --host="$HOSTNAME" 
# --hostaddress="$HOSTADDRESS$" --additionalinfo="$HOSTOUTPUT$" --time="$LONGDATETIME" --email="$CONTACTEMAIL$"
# --hoststate="$HOSTSTATE$"


# Send to IRC if configured
if ( isset($conf['nagios_bot_host']) && isset($conf['nagios_bot_port'])) {

  $fp = fsockopen("udp://" . $conf['nagios_bot_host'], $conf['nagios_bot_port'], $errno, $errstr);
  if ( $fp) {
    if ( isset($cmds['servicestate'] )) 
      fwrite($fp, "service||~||" . $cmds['type'] . "||~||" . $cmds['host']  . "||~||" .$cmds['servicedesc'] 
      . "||~||" . $cmds['additionalinfo']);
    else
      fwrite($fp, "host||~||" . $cmds['type'] . "||~||" . $cmds['host']  . "||~||" . $cmds['hoststate'] . "||~||" . $cmds['additionalinfo']);

  }
  fclose($fp);

}

$matching_events = array();

$start = time() - 86400;

if ( isset($conf['overlay_events_file'] )) {
    $events_json = file_get_contents($conf['overlay_events_file']);
    $events_array = json_decode($events_json, TRUE);
    
    if (!empty($events_array)) {

        foreach ($events_array as $key => $row) {
          $timestamp[$key]  = $row['start_time'];
        }
    
        // Sort events in reverse chronological order
        array_multisort($timestamp, SORT_DESC, $events_array);

        foreach ( $events_array as $id => $event) {

            $timestamp = $event['start_time'];
            // Make sure it's a number
            if ( ! is_numeric($timestamp) ) {
              continue;
            }

            // If timestamp is less than start bail out of the loop since there is nothing more to do since
            // events are sorted in reverse chronological order and these events are not gonna show up in the graph
            if ( $timestamp < $start ) {
              //error_log("Time $timestamp earlier than start [$start]");
              break;
            }
            
            if ( preg_match("/" . $cmds['host'] .  "/", $event["host_regex"])) {
                $matching_events[] = $event;
            }
            
        } // end of foreach ( $events_array as $id

    } // if (!empty($events_array)) {
    
} // end of if ( isset($conf['overlay_events']) {


///////////////////////////////////////////////////////////////////////////////
// Set proper subject
///////////////////////////////////////////////////////////////////////////////
if ( isset($cmds['servicestate'] )) {
  $subject = $cmds['type'] . " SVC: " . $cmds['host'] . "/" .
      $cmds['servicedesc'] . " is " . $cmds['servicestate'] . " **";
} else {
  $subject = $cmds['type'] . " HOST: " . $cmds['host'] . " is " . $cmds['hoststate'];
}

$message = '<html>
  <body bgcolor="#DCEEFC">
    <h3>****** Alerter *****</h3><br />
    ';
 
// Do we have any matching events   
if ( sizeof($matching_events) > 0 ) {
    
    $message .= '
    <h3>Recent events connected to this host</h3><p>    
    <table border=1>
        <tr><th>Date</th><th>How long ago</th><th>Event Summary</th></tr>';
        
    foreach ( $matching_events as $index => $event ) {
        
        $t = new timespan( time(), $event['start_time']);
        if ( $t->hours > 0 )
            $how_long_ago[] = $t->hours . " hrs";        
        if ( $t->minutes > 0 )
            $how_long_ago[] = $t->minutes . " min";
        if ( $t->seconds > 0 )
            $how_long_ago[] = $t->seconds . " seconds";

        $message .= "<tr><td>" . date("Y-m-d H:i:s", $event['start_time']) . "</td><td>" .
         join(",", $how_long_ago) . "</td><td>" . $event['summary'] . "</td></tr>";

        unset($t);
        unset($how_long_ago);

    }
    
    $message .= "</table>";
    
}

if ( isset($cmds['servicestate'] )) {

  $message .= '
  <p>
    <table border=1>
        <tr><th>Notification Type:</th><td>' . $cmds['type'] . '</td></tr>        
        <tr><th>Service:</th><td>' . $cmds['servicedesc'] . '</td></tr>
        <tr><th>Host (IP):</th><td>' . $cmds['host'] . '(' . $cmds['hostaddress'] . ')</td></tr>
        <tr><th>Date/Time:</th><td>' . $cmds['time'] . '</td></tr>
        <tr><th>Additional Info:</th><td>' . $cmds['additionalinfo'] . '</td></tr>
    </table>';

} else {

  $message .= '
  <p>
    <table border=1>
        <tr><th>Notification Type:</th><td>' . $cmds['type'] . '</td></tr>        
        <tr><th>Host (IP):</th><td>' . $cmds['host'] . '(' . $cmds['hostaddress'] . ')</td></tr>
        <tr><th>Date/Time:</th><td>' . $cmds['time'] . '</td></tr>
        <tr><th>Additional Info:</th><td>' . $cmds['additionalinfo'] . '</td></tr>
    </table>';

}

$message .= '</body>
</html>
';

$headers = "From: " . $conf['from_address'] . "\r\n";
$headers = "MIME-Version: 1.0\r\n";
$headers.= "Content-type: text/html; charset=utf-8\r\n";

//options to send to cc+bcc
//$headers .= "Cc: [email]maa@p-i-s.cXom[/email]";
//$headers .= "Bcc: [email]email@maaking.cXom[/email]";
 
 // now lets send the email.
if ( $debug == 0 ) {
    mail($cmds['email'], $subject, $message, $headers);
} else {
    print $message;
}

?>
