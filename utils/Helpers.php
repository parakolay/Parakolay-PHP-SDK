<?php

function get_milliseconds()
{
    $chunks = explode(' ', microtime());
    return sprintf('%d%d', $chunks[1], $chunks[0] * 1000);
}

function generate($message, $key)
{
    $hash = hash_hmac('sha256', $message, base64_decode($key), true);
    return base64_encode($hash);
}

function generateSignature($apiKey, $apiSecret,  $nonce, $conversationId)
{
    $message = $apiKey . $nonce;
    $securityData = generate($message, $apiSecret);

    $secondMessage = $apiSecret . $conversationId . $nonce . $securityData;
    $signature = generate($secondMessage, $apiSecret);

    return $signature;
}
