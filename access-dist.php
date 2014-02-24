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

	// TDispatch API
	define('TD_FLEET_API_KEY'	, 'PUT YOUR FLEET API KEY HERE');
	define('TD_API_CLIENT_ID'	, 'PUT YOUR *SEPARATE* CLIENT ID FOR WRAPPER@tdispatch.com');
	define('TD_API_SECRET'		, 'PUT YOUR *SEPARATE* API SECRET FOR WRAPPER');

	// Braintree API
	define('BT_ENV'				, 'production');						// production or sandbox
	define('BT_MERCHANT_ID'		, '');
	define('BT_PUBLIC_KEY'		, '');
	define('BT_PRIVATE_KEY'		, '');



/******** DO NOT ALTER ANYTHING BELOW THIS LINE OR SMALL KITTY DIE **********/


	define('TD_WRAPPER_VERSION', 1);
	define('TD_DEBUG'				, false);
	define('TD_API_URL'			, 'https://api.tdispatch.com');


	if( 	(TD_FLEET_API_KEY == '') || (TD_API_SECRET =='' ) || (TD_API_SECRET == '') ||
			(BT_ENV =='') || (BT_MERCHANT_ID == '') || (BT_PUBLIC_KEY == '') || (BT_PRIVATE_KEY == '')
		) {
		header('X-TD-Response-Code: ' . $result['status_code'], true, $result['status_code']);
		header('Content-Type: application/json');

		exit( json_encode((object)array(	'status_code' => 400,
													'status'	=> 'Server script is not configured!'
												)) );
	}

function l($str) {
	if( TD_DEBUG ) {
		$fh = fopen("./log.txt", "ab+");
		if ($fh) {
			fwrite($fh, $str);
			fclose($fh);
		}
	}
}

