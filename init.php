<?php
/*
 ******************************************************************************
 *
 * Copyright (C) 2013-2014 T Dispatch Ltd
 *
 * See LICENSE file for licensing conditions
 *
 ******************************************************************************
 *
 * @author Marcin Orlowski <marcin.orlowski@webnet.pl>
 *
 ******************************************************************************
*/

	require_once './access.php';

	$curlOpts = array(
		CURLOPT_RETURNTRANSFER 	=> true,
		CURLOPT_USERAGENT 		=> 'T Dispatch Braintree Wrapper Script v' . TD_WRAPPER_VERSION,
		CURLOPT_POST 				=> true,
	);

	$fields = array(
		'key'					=> TD_FLEET_API_KEY,
		'client_id'			=> TD_API_CLIENT_ID,
		'response_type'	=> 'code',
		'response_format'	=> 'json',
		'grant_type'		=> 'anonymous',
		'scope'				=> 'update-payment',
		'redirect_url'		=> ''
	);

	$curl = curl_init();
	curl_setopt_array($curl, $curlOpts);
	curl_setopt($curl, CURLOPT_POSTFIELDS, http_build_query($fields));
	curl_setopt($curl, CURLOPT_URL, sprintf('%s/passenger/oauth2/auth?%s', TD_API_URL,http_build_query($fields)) );
	$curlResponse = curl_exec($curl);
	$curlErrno = curl_errno($curl);
	curl_close($curl);

	l(sprintf('%s/passenger/oauth2/auth?%s', TD_API_URL,http_build_query($fields)) . "\n");

	$resultMsg = 'Unknown error';

	if( $curlErrno == CURLE_OK ) {
		$response = json_decode($curlResponse);

		l($curlResponse);

		switch($response->status_code) {
			case 200: {
				$authCode = $response->auth_code;

				$fields = array(
					'code'				=> $authCode,
					'client_id'			=> TD_API_CLIENT_ID,
					'client_secret'	=> TD_API_SECRET,
					'grant_type'		=> 'authorization_code'
				);

				$curl = curl_init();
				curl_setopt_array($curl, $curlOpts);
				curl_setopt($curl, CURLOPT_POSTFIELDS, http_build_query($fields));
				curl_setopt($curl, CURLOPT_URL, TD_API_URL . '/passenger/oauth2/token');
				$curlResponse = curl_exec($curl);
				$curlErrno = curl_errno($curl);
				curl_close($curl);

				if( $curlErrno == CURLE_OK ) {
					$resultMsg = 'API response: OK, you are ready to go now.';
				}

			}
			break;

			default: {
				if( $response->message->text ) {
					$resultMsg = sprintf('API response: %s (#%d)', $response->message->text, $response->status_code);
				} else {
					$tmp = sprintf('CURL Error #%d. ', $curlErrno);
					$tmp .= print_r($response, true);
					if( trim($tmp) != '' ) {
						$resultMsg = $tmp;
					}
				}
			}
		}
	} else {
		$resultMsg = 'CURL error #' . $curlErrno;
	}

	header('Content-Type: text/plain');
	exit( $resultMsg );



