#!/usr/bin/ruby

########################################################################################
# This script based on R.I. Pienaar's urltest mCollective agent
# https://github.com/ripienaar/mcollective-plugins/blob/master/agent/urltest/urltest.rb
#
# It collects URL metrics for a specified URL
#
# Usage:
#
# ruby url_metrics.rb -U http://www.google.com/
#
# DNS Lookup Time = 0.312731 secs
# Time to connect = 0.090912 secs
# Time to send request = 8.1e-05 secs
# Time between request sent and first line of response = 0.114416 secs
# Time to fetch response = 0.188102 secs
# Total time = 0.706251 secs
# Total response size = 28376 Bytes
#
#
# License: Apache License version 2.0 http://www.apache.org/licenses/LICENSE-2.0.html
########################################################################################
require 'net/http'
require 'socket'
require 'openssl'
require "base64"
require 'getoptlong'

# Parse command line options
opts = GetoptLong.new(
    [ '--url', '-U', GetoptLong::REQUIRED_ARGUMENT],
    [ '--username', '-u', GetoptLong::OPTIONAL_ARGUMENT ],
    [ '--password', '-p', GetoptLong::OPTIONAL_ARGUMENT ],
    [ '--ganglia', '-g', GetoptLong::OPTIONAL_ARGUMENT ],
    [ '--metric_prefix', '-m', GetoptLong::OPTIONAL_ARGUMENT ],
    [ '--help', '-h', GetoptLong::OPTIONAL_ARGUMENT ]
)

def showhelp()
    puts "Usage: "
    puts "   --url/-U             URL to test (REQUIRED)"
    puts "   --username/-u        Username for Basic Authentication"
    puts "   --password/-p        Password for Basic Authentication"
    puts "   --ganglia/-g         Send to Ganglia"
    puts "   --metric_prefix/-m   Metric Prefix to use for Ganglia ie. url_google. Defaults to url"
    puts "   --help/-h            Show this help"
end

req_url = nil
username = nil
password = nil
help = nil
send_to_ganglia = nil
metric_prefix = "url"

begin
    opts.each do |opt, arg|
        case opt
            when "--url"
              req_url = arg
            when "--username"
              username = arg
            when "--password"
              password = arg
            when "--ganglia"
              send_to_ganglia = 1
            when "--metric_prefix"
              metric_prefix = arg
            when "--help"
	      help = 1
        end
    end
rescue
    showhelp(connection)
    exit 1
end

if help == 1
   showhelp()
   exit(1)
end

if req_url == nil
   puts "ERROR: URL to test is missing."
   showhelp()
   exit(1)
end

# Parse URL
url = URI.parse(req_url)

# If username is specified we need to encode username and password for Basic Auth
if ! username
  enc = Base64.encode64("#{username}:#{password}")
  addl_http_header = "Authorization: Basic #{enc}\r\n"
else
  addl_http_header = ""
end

times = {}

if url.scheme == "http" or url.scheme == "https"
    
    times["beforedns"] = Time.now
    name = TCPSocket.gethostbyname(url.host)
    times["afterdns"] = Time.now

    times["beforeopen"] = Time.now
    socket = TCPSocket.open(url.host, url.port)
    times["afteropen"] = Time.now
    
    if url.scheme == "https"
      times["beforessl"] = Time.now
      ssl_context = OpenSSL::SSL::SSLContext.new()
      ssl_socket = OpenSSL::SSL::SSLSocket.new(socket, ssl_context)
      ssl_socket.sync_close = true
      ssl_socket.connect
      socket = ssl_socket
      times["afterssl"] = Time.now
    end

    socket.print("GET #{url.request_uri} HTTP/1.0\r\nHost: #{url.host}\r\n#{addl_http_header}User-Agent: Webtester\r\nAccept: */*\r\nConnection: close\r\n\r\n")
    times["afterrequest"] = Time.now

    response = Array.new

    while line = socket.gets
	times["firstline"] = Time.now unless times.include?("firstline")

	response << line
    end

    socket.close

    times["end"] = Time.now

    lookuptime = times["afterdns"] - times["beforedns"]
    ssltime = times["afterssl"] - times["beforessl"] if url.scheme == "https"
    connectime = times["afteropen"] - times["beforeopen"]
    prexfertime = times["afterrequest"] - times["afteropen"]
    startxfer = times["firstline"] - times["afterrequest"]
    txtime = times["end"] - times["firstline"]
    bytesfetched = response.join.length
    totaltime = times["end"] - times["beforedns"]
  
else
    puts "Unsupported url scheme: #{url.scheme}. Only supported scheme currently is http."
    exit(1)
end

if send_to_ganglia

    gmetric_cmd = "gmetric -t float -d 720"
    
    system "#{gmetric_cmd} -u secs -n #{metric_prefix}_dns_lookuptime -v #{lookuptime}"
    system "#{gmetric_cmd} -u secs -n #{metric_prefix}_time_to_connect -v #{connectime}"    
    system "#{gmetric_cmd} -u secs -n #{metric_prefix}_time_to_ssl -v #{ssltime}"  if url.scheme == "https"
    system "#{gmetric_cmd} -u secs -n #{metric_prefix}_time_to_send_req -v #{prexfertime}"
    system "#{gmetric_cmd} -u secs -n #{metric_prefix}_time_to_first_byte -v #{startxfer}"
    system "#{gmetric_cmd} -u secs -n #{metric_prefix}_time_to_fetch_response -v #{txtime}"
    system "#{gmetric_cmd} -u secs -n #{metric_prefix}_total_time -v #{totaltime}"
    system "#{gmetric_cmd} -u Bytes -n #{metric_prefix}_response_size -v #{bytesfetched}"

else
    
    puts "DNS Lookup Time = #{lookuptime} secs"
    puts "Time to connect = #{connectime} secs"
    puts "Time to negotiate SSL = #{ssltime} secs"  if url.scheme == "https"
    puts "Time to send request = #{prexfertime} secs"
    puts "Time between request sent and first line of response = #{startxfer} secs"
    puts "Time to fetch response = #{txtime} secs"
    puts "Total time = #{totaltime} secs"
    puts "Total response size = #{bytesfetched} Bytes"
    
end
