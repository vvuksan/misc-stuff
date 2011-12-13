class ganglia-client {

  $deaf_yesno = "yes";

  case $ganglia_cluster {
      engprod: { 
	$ganglia_cluster_name = "Prod"
	$ganglia_port = 8649
	$ganglia_host = "ganglia_server.domain.com"
	$ganglia_send_metadata_interval = 60
      }
      default: { 
	$ganglia_cluster_name = "Test"
	$ganglia_port = 9005
	$ganglia_host = "ganglia_server.domain.com"
	$ganglia_send_metadata_interval = 600
      }
  }

  package { 
    [ ganglia-gmond, ganglia-gmond-modules-python ] : ensure => latest;
  }

  file { 
    gmond-conf:
      path => "/etc/ganglia/gmond.conf",
      owner => root,
      group => root,
      backup => false,
      mode => 755,
      require => Package["ganglia-gmond"],
      content => template("$file_base/templates/gmond.conf.erb");

    "/usr/bin/gmetric":
      require => Package["ganglia-gmond"],
      mode => 755;

    "/var/lib/ganglia-logtailer":
      mode => 755,
      ensure => directory;

  }

  service {
    gmond:
      ensure => running,
      enable => true,
      require => Package["ganglia-gmond"],
      subscribe => [ File[gmond-conf],Package["ganglia-gmond"] , Package["ganglia-gmond-modules-python" ]]
  }

}


class ganglia-server {

  package { 
    [ ganglia-gmond, ganglia-gmond-modules-python ] : ensure => latest;
  }

  $deaf_yesno = "yes";
  $ganglia_cluster_name = "Prod"
  $ganglia_port = 8649
  $ganglia_send_metadata_interval = 60

 file { 
    gmond-conf:
      path => "/etc/ganglia/gmond.conf",
      owner => root,
      group => root,
      backup => false,
      mode => 755,
      require => Package["ganglia-gmond"],
      content => template("$file_base/templates/gmond-aggregator.conf.erb");
  }


  package { 
	ganglia-gmetad: 
		ensure => latest;
  }

  service {
	gmetad:
		enable => true,
    		ensure => running;
  }

}
