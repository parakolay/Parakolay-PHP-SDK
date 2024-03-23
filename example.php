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

if (isset($_GET['complete'])) {
    $result = $apiClient->complete3DS();
    print_r($result);
} else if (isset($_GET['reverse'])) {
    $result = $apiClient->Reverse("ORDER_ID");
    print_r($result);
} else if (isset($_GET['return'])) {
    $result = $apiClient->Return($amount, "ORDER_ID");
    print_r($result);
} else if (isset($_GET['bininfo'])) {
    $result = $apiClient->bin_info("BIN_NUMBER (First 6 or 8 digits)");
    print_r($result);
} else if (isset($_GET['installment'])) {
    $result = $apiClient->Installment("BIN_NUMBER (First 6 or 8 digits)", $merchantNumber, $amount);
    print_r($result);
} else if (isset($_GET['pointInquiry'])) {
    $result = $apiClient->GetPoints("CARD_NUMBER", "CARDHOLDER_NAME", "EXPIRE_MONTH (MM)", "EXPIRE_YEAR (YY)", "CVV");
    print_r($result);
} else {
    $result = $apiClient->init3DS("CARD_NUMBER", "CARDHOLDER_NAME", "EXPIRE_MONTH (MM)", "EXPIRE_YEAR (YY)", "CVV", $amount, $pointAmount, "http://localhost:8888/parakolay_sdk/example.php?complete=true");
    print_r($result);
}
