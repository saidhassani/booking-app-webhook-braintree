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
	require_once './braintree_php/lib/Braintree.php';

	Braintree_Configuration::environment(BT_ENV);
	Braintree_Configuration::merchantId(BT_MERCHANT_ID);
	Braintree_Configuration::publicKey(BT_PUBLIC_KEY);
	Braintree_Configuration::privateKey(BT_PRIVATE_KEY);

	ob_start();

	l("\n\n----[ Request made: " . date('D, d M Y H:i:s') . " ]-------------------\n");
	l("\n\n----[ GET  ]-----------------------------------------\n" . print_r($_GET, true));
	l("\n\n----[ POST ]-----------------------------------------\n" . print_r($_POST, true));
	l("\n\n-----------------------------------------------------\n");

	$result = array(	'status_code' => 400,
							'status'	=> 'Unknown error'
						);

	if ( isset( $_GET['access_token']) ) {
		if ( isAccessTokenValid( $_GET['access_token'] )) {
			$result['status_code'] = 200;
			$result['status'] = 'OK';
		} else {
			$result['status_code'] = 401;
			$result['status'] = 'Access denied';
		}
	} else {
		$result['status_code'] = 412;
		$result['status'] = 'Missing access_token';
	}


	$minVersionRequired = isset($_GET['version']) ? $_GET['version'] : 0;
	if( $minVersionRequired > TD_VERSION ) {
		$result['status_code'] = 400;
		$result['status'] = 'Unsupported payment gateway version. Please update your application to proceed.';
	}


	if ( $result['status_code'] == 200 ) {
		$cmd = isset( $_GET['cmd'] ) ? $_GET['cmd'] : '';
		switch( $cmd ) {

			case 'version': {
				$result['status_code'] = 200;
				$result['version'] = TD_WRAPPER_VERSION;
			}
			break;

			case 'card-create': {
				$params = array('customer_pk', 'card_holder_name', 'card_number', 'card_expiration_month', 'card_expiration_year');

				if ( validParams( $_POST, $params ) ) {
					$customerPk = $_POST['customer_pk'];
					$cardHolderName = $_POST['card_holder_name'];
					$cardNumber	= $_POST['card_number'];
					$cardExpireMonth = $_POST['card_expiration_month'];
					$cardExpireYear = $_POST['card_expiration_year'];
					$cardCvv = isset($_POST['card_cvv']) ? $_POST['card_cvv'] : null;

					createCustomer( $customerPk );

					$cardData = array('customerId' => $customerPk,
											'number' => $cardNumber,
											'expirationMonth' => $cardExpireMonth,
											'expirationYear' => $cardExpireYear,
											'cardholderName' => $cardHolderName,

											'options' => array(
//																	'failOnDuplicatePaymentMethod' => true,
															)
										);

					if( BT_ENV == 'production' ) {
						$cardData['options'] = array('verifyCard' => true);
					}

					if( $cardCvv !== null ) {
						$cardData['cvv'] = $cardCvv;
					}

					$cardResult = Braintree_CreditCard::create( $cardData );

					if ( $cardResult->success ) {
						$result['status_code'] = 200;

						$fields = array('expirationDate','maskedNumber','default','expired','token','cardType','cardholderName');

						$card = array();
						foreach( $fields as $field ) {
							$card[$field] = $cardResult->creditCard->$field;
						}
						$result['card'] = (object)$card;

					} else {
						$result['status_code'] = 400;

						$msg = ''; $sep = '';
						if( property_exists($cardResult, 'errors') ) {
							foreach ( $cardResult->errors->deepAll() as $error ) {
								$msg .= $sep . $error->message;
								$sep = '; ';
							}
						}
						if( trim($msg) == '' ) {
							$msg = 'Card creation failed';
						}

						$result['status'] = trim($msg);
						l("Error: " . $msg);
					}

				} else {
					$result['status_code'] = 400;
					$result['status'] = 'Missing arguments';
				}
			}
			break;

			case 'card-delete': {
				$params = array('card_token');
				if ( validParams( $_POST, $params ) ) {
					try {
						Braintree_CreditCard::delete( $_POST['card_token'] );
						$result['status_code'] = 200;
					} catch ( Exception $e ) {
						$result['status_code'] = 404;
						$msg = trim($e->getMessage());

						if( $msg == '' ) {
							$msg = 'Failed to delete card';
						}

						$result['status'] = $msg;
					}
				} else {
					$result['status_code'] = 400;
					$result['status'] = 'Missing arguments';
				}
			}
			break;

			case 'card-list': {
				$params = array('customer_pk');
				if ( validParams( $_POST, $params ) ) {
					try {
						$searchResult = Braintree_Customer::find( $_POST['customer_pk'] );
						$cards = $searchResult->creditCards;

						$c = array();
						$fields = array('expirationDate','maskedNumber','default','expired','token','cardType','cardholderName');
						foreach( $cards as $card ) {
							$i = array();
							foreach( $fields as $field ) {
								$i[$field] = $card->$field;
							}
							$c[] = $i;
						}

						$result['cards'] = $c;

					} catch( Exception $e ) {
						$result['cards'] = array();
					}
				} else {
					$result['status_code'] = 400;
					$result['status'] = 'Missing arguments';
				}
			}
			break;

			case 'transaction-create': {
				$params = array('card_token', 'booking_pk', 'booking_key', 'amount');
				if( validParams( $_POST, $params ) ) {
					$bookingPk = $_POST['booking_pk'];
					$bookingKey = $_POST['booking_key'];
					$cardToken = $_POST['card_token'];
					$amount = $_POST['amount'];

					$curlOpts = array(CURLOPT_RETURNTRANSFER  => true,
											CURLOPT_USERAGENT       => 'T Dispatch Braintree Wrapper Script v' . TD_WRAPPER_VERSION,
											CURLOPT_POST            => true,
					);

					$fields = array(	'key'					=> TD_FLEET_API_KEY,
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
					curl_setopt($curl, CURLOPT_URL, sprintf('%s/passenger/oauth2/auth?%s', TD_API_URL, http_build_query($fields)));
					$curlResponse = curl_exec($curl);
					$curlErrno = curl_errno($curl);
					curl_close($curl);

					if( $curlErrno == CURLE_OK ) {
						$response = json_decode($curlResponse);

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
								curl_setopt($curl, CURLOPT_URL, sprintf('%s/passenger/oauth2/token', TD_API_URL));
								$curlResponse = curl_exec($curl);
								$curlErrno = curl_errno($curl);
								curl_close($curl);

								if( $curlErrno == CURLE_OK ) {
									$response = json_decode($curlResponse);

									$updateAccessToken = $response->access_token;

									$order = array('orderId' => $bookingPk,
														'amount' => $amount,
														'paymentMethodToken' => $cardToken,
														'options' => array( 'submitForSettlement' => true )
									);
									$saleResult = Braintree_Transaction::sale( $order );

									$braintreePaymentRef = '';
									$braintreeCustomerCharged = false;
									if ( $saleResult->success ) {
										$result['status_code'] = 200;
										$transaction = array('id' => $saleResult->transaction->id,
																	'status' => $saleResult->transaction->status,
																	'type' => $saleResult->transaction->type,
																	'currencyIsoCode' => $saleResult->transaction->currencyIsoCode,
																	'amount' => $saleResult->transaction->amount,
																	'processorAuthorizationCode' => $saleResult->transaction->processorAuthorizationCode,
																	'processorResponseCode' => $saleResult->transaction->processorResponseCode,
																	'processorResponseText' => $saleResult->transaction->processorResponseText
															);
										$result['transaction'] = (object)$transaction;

										$braintreePaymentRef = $saleResult->transaction->id;
										$braintreeCustomerCharged = true;
									} else {
										$result['status_code'] = 400;
										$msg = ''; $sep = '';
										if( property_exists($saleResult, 'errors') ) {
											foreach ( $saleResult->errors->deepAll() as $error ) {
												$msg .= $sep . $error->message;
												$sep  = '; ';
											}
										}
										if(trim($msg) == '') {
											$msg = 'Transaction declined.';
										}
										$result['status'] = $msg;
										l('Error: ' . $msg);
									}

									// update booking
									$fields = array('payment_ref'		=> $braintreePaymentRef,
														 'card_token'		=> $cardToken,
														 'payment_status'	=> ($saleResult->success) ? 'success' : 'error'
									);

									$curl = curl_init();
									curl_setopt_array($curl, $curlOpts);
									curl_setopt($curl, CURLOPT_POSTFIELDS, json_encode($fields));
									curl_setopt($curl, CURLOPT_URL, sprintf('%s/passenger/v1/bookings/%s/update-payment?access_token=%s', TD_API_URL, $bookingPk, $updateAccessToken));

									$curlResponse = curl_exec($curl);
									$curlErrno = curl_errno($curl);
									curl_close($curl);

									if( $curlErrno == CURLE_OK ) {
										$tmp = json_decode($curlResponse);

										if( $tmp->status_code != 200 ) {	
											if( $braintreeCustomerCharged ) {
												$errorMsg = sprintf('API booking update failed: "%s", but your card has been charged. Contact cab office and use "%s" as booking reference.',
																					isset($tmp->message->text) ? $tmp->message->text : '',
																					$bookingKey );
											}

											$result = array();
											$result['status_code'] = 400;
											$result['status'] = $errorMsg;

											l($errorMsg);
										}
									} else {
										$errorMsg = sprintf('API booking update failed, but your card has been charged. Contact cab office and use "%s" as booking reference.', $bookingKey );

										$result = array();
										$result['status_code'] = 400;
										$result['status'] = $errorMsg;

										l($errorMsg);
									}

								} else {
									$result['status_code'] = 400;
									$result['status'] = sprintf('Failed to obtain API access token (#%d)', $curlErrno);
								}
							}
							break;

							default: {
								l('API Result Code: ' . $response->status_code);
								$errorMsg = 'Unknown error';

								if( isset($response->message->text) ) {
									$errorMsg = sprintf('API access rejected: %s (#%d)', $response->message->text, $response->status_code);
								}

								$result['status_code'] = 400;
								$result['status'] = $errorMsg;
							}
						}

					} else {
						$result['status_code'] = 400;
						$result['status'] = sprintf('Failed to connect to API (#%d)', $curlErrno);
					}
				} else {
					$result['status_code'] = 400;
					$result['status'] = 'Missing arguments';
				}
			}
			break;

			default: {
				$result['status_code'] = 404;
				$result['status'] = 'Unknown method';
			}
			break;
		}
	}


	$result['success'] = ($result['status_code'] == 200);

	if ( ($result['status_code'] != 200) ) {
		$result['message'] = (object)array('text' => ($result['status'] != '') ? $result['status'] : '');
	} else {
		$result['status'] = 'OK';
	}

	header('X-TD-Response-Code: ' . $result['status_code'], true, $result['status_code']);
	header('Content-Type: application/json');

	$json = json_encode((object)$result);
	l($json . "\n");

	exit( $json );



function createCustomer( $customerPk ) {
	try {
		Braintree_Customer::create( array( 'id' => $customerPk ) );
	} catch ( Exception $e ) {}
}

function validParams( $src, $params ) {
	$errors = 0;

	foreach ( $params as $param ) {
		$errors += isset($src[$param]) ? 0 : 1;
	}

	return ($errors == 0);
}

function isAccessTokenValid( $accessToken ) {
	return true;
}


