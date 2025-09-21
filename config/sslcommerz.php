<?php

use App\Models\SslInfo;

$apiDomain = env('SSLCZ_TESTMODE') ? "https://sandbox.sslcommerz.com" : "https://securepay.sslcommerz.com";
$storeId = env('SSLCZ_TESTMODE') ? "dalal68cf8f54ed4a0" : env("SSLCZ_STORE_ID");
$storePassword = env('SSLCZ_TESTMODE') ? "dalal68cf8f54ed4a0@ssl" : env("SSLCZ_STORE_PASSWORD");
return [
	'apiCredentials' => [
		'store_id' => $storeId,
		'store_password' => $storePassword,
	],
	'apiUrl' => [
		'make_payment' => "/gwprocess/v4/api.php",
		'transaction_status' => "/validator/api/merchantTransIDvalidationAPI.php",
		'order_validate' => "/validator/api/validationserverAPI.php",
		'refund_payment' => "/validator/api/merchantTransIDvalidationAPI.php",
		'refund_status' => "/validator/api/merchantTransIDvalidationAPI.php",
	],
	'apiDomain' => $apiDomain,
	'connect_from_localhost' => env("IS_LOCALHOST", true), // For Sandbox, use "true", For Live, use "false"
	'success_url' => '/api/sslcz/success',
	'failed_url' => '/api/sslcz/fail',
	'cancel_url' => '/api/sslcz/cancel',
	'ipn_url' => '/api/sslcz/ipn',
];
