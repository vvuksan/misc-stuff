<?php

##############################################################################
# Splits a mySQL dump into separate table files named <table_name>.sql
# 
# Send the mySQL dump as standard input (stdin) and destination directory
# where you want the resulting SQL files stored. If directory doesn't exist
# it will be created
#
##############################################################################

# Open standard in
$file = fopen("php://stdin", 'r');

if ( isset( $argv[1] ) ) {
  $db_dir = $argv[1];
  if ( ! is_dir ( $db_dir ) )
    mkdir($db_dir);
} else {
  die("You need to supply destination directory where to store table SQL files");
}

$current_table = "";

while (!feof ($file)) {

  $line = fgets ($file, 1024);

  if ( preg_match("/-- Table structure for table `(.*)`/", $line, $out) ) {

    if ( $current_table != "" )
      fclose($fp);
    
    $current_table = $out[1];
    $fp = fopen($db_dir . "/" . $current_table . ".sql", "w+");
    print "Processing table " . $current_table . "\n";

  } else {

    if ( $current_table != "" ) {
      fwrite($fp, $line);
    }


  }

}

fclose($fp);
# Close standard in
fclose($file);


?>
