<?php

$base_dir = dirname(__FILE__);

# Load main config file.
require_once $base_dir . "/conf_default.php";

# Include user-defined overrides if they exist.
if( file_exists( $base_dir . "/conf.php" ) ) {
  include_once $base_dir . "/conf.php";
}

require_once $base_dir . "/nagios_commands.php";
require_once $base_dir . "/tools.php";
require_once $base_dir . "/parse_nagios_status.php";

$cmd_line_array = commandline_arguments($argv);
$nagios = parse_nagios_status_file($conf['nagios_status_file']);

if ( isset($cmd_line_array['host']) ) {

   $host = $cmd_line_array['host'];

   if ( isset($cmd_line_array['disablehost'] )) {
	print "Disabling " . $cmd_line_array['host'];
	disable_host_notifications($cmd_line_array['host']);
	disable_all_service_notifications($cmd_line_array['host']);
   }

   if ( isset($cmd_line_array['disableallservices'] )) {
	foreach ( $nagios['services'][$host] as $service => $array ) {
		print "Service " . $service . "\n";
		disable_service_notifications($host, $service);
	}
   }

   if ( isset($cmd_line_array['enableallservices'] )) {
	foreach ( $nagios['services'][$host] as $service => $array ) {
		print "Service " . $service . "\n";
		enable_service_notifications($host, $service);
	}
   }

} else {
   print "You need to specify a host ie. \n";

   foreach ( $nagios['hosts'] as $key => $array ) {
	print "  " . $key . "\n";
   }

}

?>
