<html>
<head>
<title>Page performance</title>
<link type="text/css" href="css/flick/jquery-ui-1.8.14.custom.css" rel="stylesheet" />
<script type="text/javascript" src="js/jquery-1.5.1.min.js"></script>
<script type="text/javascript" src="js/jquery-ui-1.8.14.custom.min.js"></script>
<style>
body{ font: 62.5% "Trebuchet MS", sans-serif; margin: 10px;}
.bar {
    width: 600px;
    background: white;
    display: block;
}
 
.fill {
    float: left;
}

.harview {
   font-size: 12px;
}
</style>
</head>
<body>
<div id=results>
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
</div>
</body>
</html>