<?php

##############################################################################
# Copied from
# http://www.php.net/features.commandline
#If the argument is of the form –NAME=VALUE it will be represented in the array as an element 
#with the key NAME and the value VALUE. I the argument is a  flag of the form -NAME it will be 
#represented as a boolean with the name NAME with a value of true in the associative array.
#Example:
#
#<?php print_r(arguments($argv));
# php5 myscript.php --user=nobody --password=secret -p
#
#Array
#(
#    [user] => nobody
#    [password] => secret
#    [p] => true
#)
function commandline_arguments($argv) {
    $_ARG = array();
    foreach ($argv as $arg) {
        if (ereg('--[a-zA-Z0-9]*=.*',$arg)) {
            $str = split("=",$arg); $arg = '';
            $key = ereg_replace("--",'',$str[0]);
            for ( $i = 1; $i < count($str); $i++ ) {
                $arg .= $str[$i];
            }
                        $_ARG[$key] = $arg;
        } elseif(ereg('-[a-zA-Z0-9]',$arg)) {
            $arg = ereg_replace("-",'',$arg);
            $_ARG[$arg] = 'true';
        }
   
    }
return $_ARG;
}



# # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # #
#                                                                             #
# B00zy's timespan script v1.2                                               #
#                                                                             #
# timespan -- get the exact time span between any two moments in time.        #
#                                                                             #
# Description:                                                                #
#                                                                             #
#        class timespan, function calc ( int timestamp1, int timestamp2)      #
#                                                                             #
#        The purpose of this script is to be able to return the time span     #
#        between any two specific moments in time AFTER the Unix Epoch        #
#        (January 1 1970) in a human-readable format. You could, for example, #
#        determine your age, how long you have been married, or the last time #
#        you... you know. ;)                                                  #
#                                                                             #
#        The class, "timespan", will produce variables within the class       #
#        respectively titled years, months, weeks, days, hours, minutes,      #
#        seconds.                                                             #
#                                                                             #
# # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # #
#                                                                             #
# Example 1. B00zy's age.                                                     #
#                                                                             #
#        $t = new timespan( time(), mktime(0,13,0,8,28,1982));                #
#        print "B00zy is $t->years years, $t->months months, ".               #
#                "$t->days days, $t->hours hours, $t->minutes minutes, ".     #
#                "and $t->seconds seconds old.\n";                            #
#                                                                             #
# # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # #

define('day', 60*60*24 );
define('hour', 60*60 );
define('minute', 60 );

class timespan
    {
    var $years;
    var $months;
    var $weeks;
    var $days;
    var $hours;
    var $minutes;
    var $seconds;

    function leap($time)
        {
        if (date('L',$time) and (date('z',$time) > 58))
            return (double)(60*60*24*366);
        else
            {
            $de = getdate($time);
            $mkt = mktime(0,0,0,$de['mon'],$de['mday'],($de['year'] - 1));
            if ((date('z',$time) <= 58) and date('L',$mkt))
                return (double)(60*60*24*366);
            else
                return (double)(60*60*24*365);
            }
        }
    function readable()
        {
        $values = array('years','months','weeks','days','hours','minutes','seconds');
        foreach ($values as $k => $v)
            if ($this->{$v}) $fmt .= ( $fmt? ', ': '') . $this->{$v} . " $v";
        return $fmt . ( $fmt? '.': '') ;
        }

    function timespan($after,$before)
        {
        # Set variables to zero, instead of null.
        
        $this->years = 0;
        $this->months = 0;
        $this->weeks = 0;
        $this->days = 0;
        $this->hours = 0;
        $this->minutes = 0;
        $this->seconds = 0;

        $duration = $after - $before;

        # 1. Number of years
        $dec = $after;

        $year = $this->leap($dec);

        while (floor($duration / $year) >= 1)
            {
	    # We don't need this VV
            #print date("F j, Y\n",$dec);

            $this->years += 1;
            $duration -= (int)$year;
            $dec -= (int)$year;
            
            $year = $this->leap($dec);
            }

        # 2. Number of months
        $dec = $after;
        $m = date('n',$after);
        $d = date('j',$after);

        while (($duration - day) >= 0)
            {
            $duration -= day;
            $dec -= day;
            $this->days += 1;

            if ( (date('n',$dec) != $m) and (date('j',$dec) <= $d) )
                {
                $m = date('n',$dec);
                $d = date('j',$dec);

                $this->months += 1;
                $this->days = 0;
                }
            }
        # 3. Number of weeks.
        $this->weeks = floor($this->days / 7);
        $this->days %= 7;

        # 4. Number of hours, minutes, and seconds.
        $this->hours = floor($duration / (60*60));
        $duration %= (60*60);

        $this->minutes = floor($duration / 60);
        $duration %= 60;

        $this->seconds = $duration;
        }
    }


function our_date_format($date) {
    date_default_timezone_set('UTC');
    return date("r", strtotime($date));
}


?>