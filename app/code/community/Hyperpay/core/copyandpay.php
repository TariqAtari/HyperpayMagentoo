<?php

	define('tokenUrlLive', 'https://ctpe.net/frontend/GenerateToken');
	define('tokenUrlTest', 'https://test.ctpe.net/frontend/GenerateToken');

	define('executeUrlLive', 'https://ctpe.net/frontend/ExecutePayment');
	define('executeUrlTest', 'https://test.ctpe.net/frontend/ExecutePayment');

	define('statusUrlLive', 'https://ctpe.net/frontend/GetStatus;jsessionid=');
	define('statusUrlTest', 'https://test.ctpe.net/frontend/GetStatus;jsessionid=');

	define('jsUrlLive', 'https://ctpe.net/frontend/widget/v3/widget.js?style=card&version=beautified&language=');
	define('jsUrlTest', 'https://test.ctpe.net/frontend/widget/v3/widget.js?style=card&version=beautified&language=');

	//+++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++=

	if(!function_exists('getPostParameter')){
		function getPostParameter($dataCust,$dataTransaction) 
		{
			$data = "SECURITY.SENDER=" . $dataTransaction['sender'] .
					"&TRANSACTION.CHANNEL=" . $dataTransaction['channel_id'] .
					"&USER.LOGIN=" . $dataTransaction['login'] .
					"&USER.PWD=" . $dataTransaction['password'] .
					"&TRANSACTION.MODE=" . $dataTransaction['tx_mode'] .
					"&IDENTIFICATION.TRANSACTIONID=" . $dataTransaction['orderId'].					
					"&PAYMENT.TYPE=" . $dataTransaction['payment_type'] .
					"&PRESENTATION.AMOUNT=" . $dataCust['amount'] .
					"&PRESENTATION.CURRENCY=" . $dataCust['currency'] .
					"&ADDRESS.STREET=" . $dataCust['street'] .
					"&ADDRESS.ZIP=" . $dataCust['zip'] .
					"&ADDRESS.CITY=" . $dataCust['city'] .
					"&ADDRESS.COUNTRY=" . $dataCust['country_code'] .
					"&CONTACT.EMAIL=" . $dataCust['email'] .
					"&NAME.GIVEN=" . $dataCust['first_name'] .
					"&NAME.FAMILY=" . $dataCust['last_name'];			
				
			return $data;
		}	
	}
	
	if(!function_exists('getTokenUrl')){
		function getTokenUrl($server)
		{
			if ($server=="LIVE")
			{
				$url = tokenUrlLive;
				}
			else
			{
				$url =  tokenUrlTest;
			}
			
			return $url;
		}
	}
	
	if(!function_exists('getToken')){
		function getToken($postData,$url)
		{
			
			$params = array('http' => array(
				'method' => 'POST',
				'header' => "Content-Type: application/x-www-form-urlencoded",
				'content' => $postData
			));
			$ctx = stream_context_create($params);
			$fp = @fopen($url, 'rb', false, $ctx);
			if (!$fp) {
				throw new Exception("Problem with $url, $php_errormsg");
			}
			$response = @stream_get_contents($fp);
			if ($response === false) {
				throw new Exception("Problem reading data from $url, $php_errormsg");
			}

			$obj=json_decode($response);
			return $obj->{'transaction'}->{'token'};
		}
	}
	
	if(!function_exists('getExecuteUrl')){
		function getExecuteUrl($server)
		{
			if ($server=="LIVE")
				$url = executeUrlLive;
			else
				$url = executeUrlTest;
			
			return $url;
		}
	}
	
	if(!function_exists('getPostCapture')){
		function getPostCapture($refId, $amount, $currency, $dataTransaction)
		{
			$data = "IDENTIFICATION.REFERENCEID=". $refId ."&" .
					"PAYMENT.METHOD=CC&" .
					"PAYMENT.TYPE=CP&" .
					"PRESENTATION.AMOUNT=". $amount ."&" .
					"PRESENTATION.CURRENCY=". $currency ."&" .
					"SECURITY.SENDER=". $dataTransaction['sender'] ."&" .
					"TRANSACTION.CHANNEL=". $dataTransaction['channel_id'] ."&" .
					"TRANSACTION.MODE=" . $dataTransaction['tx_mode'] ."&" .
					"USER.LOGIN=". $dataTransaction['login'] ."&" .
					"USER.PWD=". $dataTransaction['password'];

			return $data;
		}
	}
	
	if(!function_exists('executePayment')){
		function executePayment($postData, $url)
		{
			$params = array('http' => array(
				'method' => 'POST',
				'header' => "Content-Type: application/x-www-form-urlencoded",
				'content' => $postData
			));

			$ctx = stream_context_create($params);
			$fp = @fopen($url, 'rb', false, $ctx);
			if (!$fp) {
				throw new Exception("Problem with $url, $php_errormsg");
			}
			$response = @stream_get_contents($fp);
			if ($response === false) {
				throw new Exception("Problem reading data from $url, $php_errormsg");
			}
			return $response;
		}
	}
	
	if(!function_exists('buildResponseArray')){
		function buildResponseArray($response)
		{
			$result = array();
			$entries = explode("&", $response);
			foreach ($entries as $entry) {
				$pair = explode("=", $entry);
				$result[$pair[0]] = urldecode($pair[1]);
			}
			return $result;
		}
	}
	
	if(!function_exists('getStatusUrl')){
		function getStatusUrl($server, $token){
			if ($server=="LIVE")
				$url = statusUrlLive . $token;
			else
				$url = statusUrlTest . $token;
			return $url;
		}
	}
	
	if(!function_exists('checkStatusPayment')){
		function checkStatusPayment($url)
		{
			/*		
			$ch = curl_init();
			curl_setopt($ch, CURLOPT_URL, $url);
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
			curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
			$resultPayment = curl_exec($ch);
			curl_close($ch);
			$resultJson = json_decode($resultPayment, true);
			*/
			$str = file_get_contents($url); 

			$resultJson = json_decode($str, true);
			
			return $resultJson;
		}
	}

	if(!function_exists('getJsUrl')){
		function getJsUrl($server, $lang){
			if ($server=="LIVE")
				$url = jsUrlLive . $lang;
			else
				$url = jsUrlTest . $lang;
			return $url;
		}
	}

?>
