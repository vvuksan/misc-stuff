sub aws4_lambda_sign_request { 

    set req.http.service = "lambda";

    # JSON encoded request parameters
    if ( req.postbody ) {
        set req.http.request_parameters = req.postbody;
    } else {
        set req.http.request_parameters = "";
    }
        
    set req.http.Content-Type = "application/x-amz-json-1.0";

    set req.http.x-amz-date = strftime({"%Y%m%dT%H%M%SZ"}, now);
    set req.http.date_stamp = strftime({"%Y%m%d"}, now);
    
    set req.http.canonical_querystring = "";
    set req.http.canonical_headers = "content-type:" + req.http.Content-Type + LF + "host:" + req.http.host + LF + "x-amz-date:" + req.http.x-amz-date + LF;
    set req.http.signed_headers = "content-type;host;x-amz-date";
    
    set req.http.canonical_request = req.http.method + LF + req.url + LF + req.http.canonical_querystring + LF + req.http.canonical_headers + LF + req.http.signed_headers + LF + regsub(digest.hash_sha256(req.http.request_parameters),"^0x", "");
    
    set req.http.credential_scope = req.http.date_stamp + "/" + req.http.region + "/" + req.http.service + "/aws4_request";
    set req.http.string_to_sign = "AWS4-HMAC-SHA256" + LF + req.http.x-amz-date + LF + req.http.credential_scope + LF  regsub(digest.hash_sha256(req.http.canonical_request),"^0x", "");

    set req.http.kdate = digest.base64_decode(digest.hmac_sha256_base64("AWS4" req.http.secret_key, req.http.date_stamp));
    set req.http.kregion = digest.base64_decode(digest.hmac_sha256_base64(req.http.kdate, req.http.region));
    set req.http.kservice = digest.base64_decode(digest.hmac_sha256_base64(req.http.kregion, req.http.service));
    set req.http.signing_key = digest.base64_decode(digest.hmac_sha256_base64(req.http.kservice, "aws4_request"));
    
    set req.http.signature = digest.hmac_sha256(req.http.signing_key, req.http.string_to_sign);

    set req.http.Authorization = "AWS4-HMAC-SHA256" + " Credential=" + req.http.access_key + "/" + req.http.credential_scope + ", SignedHeaders=" + req.http.signed_headers + ", Signature=" + regsub(req.http.signature,"^0x", "");
    
    # Unset request headers not needed for the response
    unset req.http.Accept-Encoding;
    unset req.http.Accept;
    unset req.http.Accept-Language;
    unset req.http.User-Agent;
    
    unset req.http.date_stamp;
    
    unset req.http.kdate;
    unset req.http.kregion;
    unset req.http.kservice;
    unset req.http.signing_key;
    unset req.http.method;
    unset req.http.canonical_querystring;
    unset req.http.canonical_headers;
    set req.http.request_parameters = req.http.request_parameters + " " + regsub(digest.hash_sha256(req.http.request_parameters),"^0x", "") + "EMPTY=" +  regsub(digest.hash_sha256(""),"^0x", "");
    # unset req.http.request_parameters;
    unset req.http.canonical_request;
    unset req.http.credential_scope;
    unset req.http.string_to_sign;
    unset req.http.signature;
    unset req.http.signed_headers;
    unset req.http.secret_key;
    unset req.http.access_key;
    unset req.http.service;
    unset req.http.region;

}

sub vcl_recv {
#FASTLY recv

    # Must set these
    set req.http.access_key = "CHANGEME";
    set req.http.secret_key = "CHANGEME";
    
    # Available regions us-east-1, us-west-2, eu-west-1 and ap-northeast-1
    set req.http.region = "us-east-1";
    set req.http.method = "POST";
    set req.request = req.http.method;

    # We need to reset the requested host to proper Lambda end point
    set req.http.Host = "lambda." + req.http.region + ".amazonaws.com";

    # This is the canonical URI. We need to lookup incoming request URL to make sure
    # there is a corresponding Lambda method. Otherwise default to /LAMBDA_Not_Found
    set req.url = table.lookup(url_mapping, req.url.path, "/LAMBDA_Not_Found" );

    # If page has not been found we just throw out a 404
    if ( req.url == "/LAMBDA_Not_Found" ) {
        error 404 "Page not found";
    }

    # Let's get 
    call aws4_lambda_sign_request;

    if (req.request != "HEAD" && req.request != "GET" && req.request != "FASTLYPURGE") {
      return(pass);
    }
    
    return(lookup);
}

sub vcl_fetch {

 /* handle 5XX (or any other unwanted status code) */
  if (beresp.status >= 500 && beresp.status < 600) {

    /* deliver stale if the object is available */
    if (stale.exists) {
      return(deliver_stale);
    }

    if (req.restarts < 1 && (req.request == "GET" || req.request == "HEAD")) {
      restart;
    }

    /* else go to vcl_error to deliver a synthetic */
    error 503;

  }
  
  /* set stale_if_error and stale_while_revalidate (customize these values) */
  set beresp.stale_if_error = 86400s;
  set beresp.stale_while_revalidate = 60s;
  
#FASTLY fetch

  if(req.restarts > 0 ) {
    set beresp.http.Fastly-Restarts = req.restarts;
  }

  if (beresp.http.Set-Cookie) {
    set req.http.Fastly-Cachetype = "SETCOOKIE";
    return (pass);
  }

  if (beresp.http.Cache-Control ~ "private") {
    set req.http.Fastly-Cachetype = "PRIVATE";
    return (pass);
  }

  if (beresp.status == 500 || beresp.status == 503) {
    set req.http.Fastly-Cachetype = "ERROR";
    set beresp.ttl = 1s;
    set beresp.grace = 5s;
    return (deliver);
  }

  if (beresp.http.Expires || beresp.http.Surrogate-Control ~ "max-age" || beresp.http.Cache-Control ~"(s-maxage|max-age)") {
    # keep the ttl here
  } else {
    # apply the default ttl
    set beresp.ttl = 15m;
  }

  return(deliver);
}

sub vcl_hit {
#FASTLY hit

  if (!obj.cacheable) {
    return(pass);
  }
  return(deliver);
}

sub vcl_miss {
#FASTLY miss
  return(fetch);
}

sub vcl_deliver {
  if (resp.status >= 500 && resp.status < 600) {

   /* restart if the stale object is available */
   if (stale.exists) {
     restart;
   }
  }
#FASTLY deliver

  unset resp.http.x-amzn-Remapped-Content-Length;
  unset resp.http.x-amzn-RequestId;

  # Never true
  if ( req.url ) {
    set resp.http.request_parameters = req.http.request_parameters;
#    set resp.http.canonical_request = req.http.canonical_request;
#    set resp.http.string_to_sign = req.http.string_to_sign;
#    set resp.http.Auth = req.http.Authorization;
#    set resp.http.x-amz-date = req.http.x-amz-date;
  }

  return(deliver);
}

sub vcl_error {

  if (obj.status >= 500 && obj.status < 600) {

    /* deliver stale object if it is available */
    if (stale.exists) {
      return(deliver_stale);
    }

  }
#FASTLY error

  # Handle redirects
  if (obj.status == 850) {
    set obj.http.Location = obj.response;
    set obj.status = 301;
    return(deliver);
  }
  
  return(deliver);

}

sub vcl_pass {
#FASTLY pass
}
