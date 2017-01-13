<?php

namespace common\components\UrlRequest;

class UrlRequest {

    public static function post($url,$params,$timeout=40)
    {
        $headers = [
        	'Expect' => '',
        	'Pragma' => 'no-cache',
        	'Cache-Control' => 'no-cache',
 		'Connection' => 'keep-alive',
        	'Accept' => 'application/json, text/javascript, */*',
        	'Content-Type' => 'application/x-www-form-urlencoded'
        ];

    	$ch = curl_init();

	foreach($headers as $Name=>$val)
		$h[] = "$Name: $val";
	array_unshift($h,"POST / HTTP/1.1");

        curl_setopt($ch, CURLOPT_URL, $url);
	curl_setopt($ch, CURLOPT_HTTPHEADER, $h);

	curl_setopt($ch, CURLOPT_POST, 1);
	curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($params));
 	curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
	curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, $timeout);
	curl_setopt($ch, CURLOPT_TIMEOUT, $timeout);
	curl_setopt($ch, CURLOPT_HEADER, false);
	curl_setopt($ch, CURLOPT_FAILONERROR, true);
	
        $res = curl_exec($ch);
        return $res;
    
    
    }


    public static function post2($url,$params,$timeout=40)
    {
        $params=http_build_query($params);
        $headers = [
        	'Expect' => '',
        	'Pragma' => 'no-cache',
        	'Cache-Control' => 'no-cache',
 		'Connection' => 'keep-alive',
//        	'X-Forwarded-For' => apache_request_headers()['X-Real-IP'],
        	'Accept' => 'application/json, text/javascript, */*',
        	'Content-Type' => 'application/x-www-form-urlencoded',
        	'Content-length' => strlen($params)
        ];
    
        $h='';
        $i=0;
        foreach($headers as $Name=>$val)
        {
        	$h .= "$Name: $val";
        	$i++;
        	if ($i<count($headers))
        		$h .= "\r\n";
        }

    
        $httpopts = ['http' =>
          [
            'method'  => 'POST',
            'header'  => $h,
            'content' => $params,
            'timeout' => $timeout,
	    'request_fulluri' => true,
	    'ignore_errors' => true
          ]
        ];
        
        $context  = stream_context_create($httpopts);
        
        $res = @file_get_contents($url, false, $context);
        
        return $res;
    
    
    }
    
}

