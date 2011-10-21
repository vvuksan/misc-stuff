<?php

# Location of the nagios status file. Check value of status_file in nagios.cfg
$conf['nagios_status_file'] = "/var/log/nagios/status.dat";

# Location of Nagios command file. Check value of command_file in nagios.cfg
$conf['nagios_command_file'] = "/var/log/nagios/rw/nagios.cmd";

# Cache Nagios status data for 300 seconds
$conf['nagios_status_cache_time'] = 300;

# Default downtime period is 300 seconds
$conf['default_downtime_period'] = 300; 

$conf['from_address'] = "Nagios <nagios@domain.com>";
?>
