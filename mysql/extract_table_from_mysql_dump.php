<?php

##############################################################################
# Extract a particular table from a mySQL dump.
# 
# Send the mySQL dump as standard input (stdin) then specify a destination
# directory (if it doesn't exist it will be created). Second argument
# is the name of the table to be extracted e.g.
#
# zcat mysqldump.gz | php extract_table_from_mysql_dump /tmp/mydir users
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

$table_to_dump = $argv[2];

$current_table = "";

while (!feof ($file)) {

  $line = fgets ($file, 1024);

  if ( preg_match("/-- Table structure for table `(.*)`/", $line, $out) ) {

    if ( $out[1] != $table_to_dump && $current_table == "" )
      continue;

    if ( $current_table != "" ) {
      fclose($fp);
      fclose($file);
      exit(1);
    }
    

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