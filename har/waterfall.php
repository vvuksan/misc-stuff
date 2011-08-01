<html>
<head>
<title>Page performance</title>
<style>
.bar {
    width: 600px;
    background: white;
    display: block;
}
 
.fill {
    float: left;
}
</head>
<body>
<?php
$results = array( "/var/www/web.har");

foreach ( $results as $index => $harname ) {
    
    $har = json_decode(file_get_contents($harname), true);
    
    # This variable will keep the start time of the whole request chain.
    $min_start_time = 10000000000;
    
    # When did the page load finish
    $max_end_time = 0;
    
    foreach ( $har['log']['entries'] as $key => $request ) {
        
        $started_time = $request['startedDateTime'];
        $request_duration = $request['time'] / 1000;
        $url = $request['request']['url'];
        $resp_code = $request['response']['status'];
        $resp_size = $request['response']['bodySize'];
        
        // Extract the milliseconds since strtotime doesn't seem to keep it in
        preg_match("/(.*)T(.*)\.(.*)(Z)/", $started_time, $out);
        $milli = $out[3];
    
        $start_time = floatval(strtotime($started_time) . "." . $milli);
        $end_time = $start_time + $request_duration;
    
        if ( $start_time < $min_start_time )
            $min_start_time = $start_time;
    
        if ( $end_time > $max_end_time )
            $max_end_time = $end_time;
    
        $requests[] = array("url" => $url, "start_time" => $start_time,
            "duration" => $request_duration, "size" => $resp_size, "resp_code" => $resp_code );
        
    }
    
    $total_time = $max_end_time - $min_start_time;
    
    
    ?>
    
    <div id=results-<?php print $index; ?>>
    <table>
    <tr>
    <td colspan=4 align=center>
        <?php print "Total time for a fully downloaded page is ". sprintf("%.3f", $total_time) . " sec"; ?>
    </td>
    </tr>
        <tr>
            <th>URL</th>
            <th>Duration</th>
            <th>Size (bytes)</th>
            <th></th>
        </tr>
    <?
    
    foreach ( $requests as $key => $request ) {
    
        $time_offset = $request["start_time"] - $min_start_time;
        
        $white_space = ($time_offset / $total_time) * 100;
        $progress_bar = ($request["duration"] / $total_time) * 100;
        
        print "\n<tr><td><a href='" . $request["url"] . "'>" . substr($request["url"],0,50) . '</a></td>' . '
        <td>' . $request["duration"] . '</td>
        <td>' . $request["size"] . '</td>
        <td><span class="bar">' .
        '<span class="fill" style="background: white; width: ' . $white_space .  '%">&nbsp;</span>'.
        '<span class="fill" style="background: #AAB2FF; width: ' . $progress_bar .  '%">&nbsp;</span>'.
        "</span></td></tr>";
    
    }
    
    unset($requests);
    unset($har);
    
    ?>
    </table>
    </div>
<?php
}
?>
</body>
