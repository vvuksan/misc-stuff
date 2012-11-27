<?php

#########################################################################
# Check tweet mentions and send it to our IRC bot. I use slightly modified
#
# https://github.com/cluenet/cluemon/blob/master/nagios-bin/nagiosbot.py
#
# Change $search_term below to what you want to be alerted on
#
# Run it every 10 minutes or so.
#
# Author: Vladimir Vuksan
#
# Define $conf['nagios_bot_host'] and  $conf['nagios_bot_port'] in
# conf.php or comment out
#########################################################################
require_once ( dirname(__FILE__) . "/conf.php" );

# Term you are searching for in tweets. It can be a tweet handle e.g. @myaccount
$search_term = "ganglia";

# Save tweets viewed in a file
$conf['status_file'] = "./twitter.json";

if ( is_file( $conf['status_file']) )
    $already_seen = json_decode(file_get_contents($conf['status_file']), TRUE);
else
    $already_seen = array();

$str = file_get_contents("http://search.twitter.com/search.json?q=" . $search_term . "&callback=TWTR.Widget.receiveCallback_1&rpp=50&clientsource=TWITTERINC_WIDGET&include_entities=true&result_type=recent");
$str = preg_replace("/^(TWTR.Widget.receiveCallback_1\()/","", $str);
$str = preg_replace("/\);$/","", $str);

# Loop through results we got from Twitter
$results = json_decode($str,TRUE);
foreach ( $results['results'] as $index => $tweet ) {
    
    if ( ! in_array( $tweet['id'], $already_seen) ) {

	$created = $tweet['created_at'];
	$from_user = $tweet['from_user'];
	$text = $tweet['text'];
	$url = "https://twitter.com/#!/" . $from_user . "/status/" . $tweet['id'];
	$message = "Twitter Mention ($created): " . $from_user . " => " . $text . " ( " . $url . " ) ";
	
	# Send to IRC if configured
	if ( isset($conf['nagios_bot_host']) && isset($conf['nagios_bot_port']) ) {
	 
	   $fp = fsockopen("udp://" . $conf['nagios_bot_host'], $conf['nagios_bot_port'], $errno, $errstr);
	   if ( $fp) {
	      fwrite($fp, $message);
	   }
	   fclose($fp);
      
	}
	
	# Add tweet to the list of seen tweets
	$already_seen[] = $tweet['id'];
	
	print $message . "\n";
    }


}

file_put_contents($conf['status_file'], json_encode($already_seen));

?>
