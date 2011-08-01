<?php

if (isset($_GET['url'])) {

    require_once('./tools.php');
    
    print get_har($_GET['url']);
    
} else {

?>

<form action="waterfall.php">

URL <input name="url" size=60>

<input type=submit>
</form>

<?php

}

?>