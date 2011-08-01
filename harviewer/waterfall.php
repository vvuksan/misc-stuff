<?php

require_once("./tools.php");

if ( isset($_GET['url'])) {

    $url = validate_url($_GET['url']);
    
    $har = get_har($url);

    $har_array = json_decode($har, true);
    
    print generate_waterfall($har_array);

} else {
?>
  No URL supplied
<?php
}
?>
