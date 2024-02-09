<?php

require 'src/Parakolay.php';
session_set_cookie_params([
    'lifetime' => 0,
    'path' => '/',
    'domain' => 'YOUR_DOMAIN',
    'secure' => true,
    'httponly' => true,
    'samesite' => 'None',
]);
session_start();

$apiKey = 'YOUR_API_KEY';
$apiSecret = 'YOUR_API_SECRET';
$merchantNumber = 'YOUR_MERCHANT_NUMBER';
$conversationId = 'CURRENT_ORDER_ID';

$baseUrl = 'https://api.parakolay.com';
$testUrl = 'https://api-test.parakolay.com';

$amount = 1;
$pointAmount = 0;

$apiClient = new Parakolay($baseUrl, $apiKey, $apiSecret, $merchantNumber, $conversationId);

if (!isset($_GET['return'])) {
    $result = $apiClient->init3DS("CARD_NUMBER", "CARDHOLDER_NAME", "EXPIRE_MONTH (MM)", "EXPIRE_YEAR (YY)", "CVV", $amount, $pointAmount, "YOUR_CALLBACK_URL");
    print_r($result);
} else {
    $result = $apiClient->complete3DS();
    print_r($result);
}
