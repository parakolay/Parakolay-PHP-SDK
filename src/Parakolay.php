<?php

require 'vendor/autoload.php';
require_once './utils/Helpers.php';

use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;


class Parakolay
{
    private $version = "v1.0.1";

    private $multipartClient;
    private $jsonClient;

    private $apiKey;
    private $merchantNumber;
    private $conversationId;

    private $nonce;
    private $signature;

    private $amount;
    private $currency;
    private $cardholderName;
    private $cardToken;
    private $threeDSessionID;

    public function __construct($baseUrl, $apiKey, $apiSecret, $merchantNumber, $conversationId)
    {
        $this->apiKey = $apiKey;
        $this->merchantNumber = $merchantNumber;
        $this->conversationId = $conversationId;

        $this->nonce = isset($_SESSION['nonce']) ? $_SESSION['nonce'] : get_milliseconds();
        $this->signature = generateSignature($apiKey, $apiSecret, $this->nonce, $conversationId);

        $this->multipartClient = new Client([
            'base_uri' => $baseUrl,
            'headers' => [
                'User-Agent' => "Parakolay PHP SDK " . $this->version,
            ]
        ]);

        $this->jsonClient = new Client([
            'base_uri' => $baseUrl,
            'headers' => [
                'User-Agent' => "Parakolay PHP SDK " . $this->version,
                'publicKey' => $apiKey,
                'nonce'  =>  $this->nonce,
                'signature' => $this->signature,
                'conversationId' => $conversationId,
                'clientIpAddress' => $_SERVER['REMOTE_ADDR'],
                'merchantNumber' => $merchantNumber,
            ]
        ]);
    }

    public function init3DS($cardNumber, $cardholderName, $expireMonth, $expireYear, $cvc, $amount, $pointAmout, $callbackURL, $currency = "TRY", $languageCode = "TR")
    {
        $this->cardToken = $this->getCardToken($cardNumber, $cardholderName, $expireMonth, $expireYear, $cvc);
        $this->threeDSessionID = $this->get3DSession($amount, $pointAmout, $currency, $languageCode);
        $threedDinitResult = $this->get3DInit($callbackURL, $languageCode);

        $_SESSION['cardholderName'] = $cardholderName;
        $_SESSION['threeDSessionId'] = $this->threeDSessionID;
        $_SESSION['nonce'] = $this->nonce;
        $_SESSION['currency'] = $this->currency;
        $_SESSION['cardToken'] = $this->cardToken;
        $_SESSION['amount'] = $this->amount;

        return $threedDinitResult;
    }

    public function complete3DS()
    {
        $result = $this->get3DSessionResult();
        if ($result == "VerificationFinished")
            return $this->provision();
        else
            return false;
    }

    public function getPoints($cardNumber, $cardholderName, $expireMonth, $expireYear, $cvc)
    {
        $this->cardToken = $this->getCardToken($cardNumber, $cardholderName, $expireMonth, $expireYear, $cvc);
        $pointResult = $this->point_inquiry($this->cardToken);

        return $pointResult;
    }

    private function getCardToken($cardNumber, $cardholderName, $expireMonth, $expireYear, $cvc)
    {
        $this->cardholderName = $cardholderName;

        $data = [
            [
                'name' => 'CardNumber',
                'contents' => $cardNumber
            ],
            [
                'name' => 'ExpireMonth',
                'contents' => $expireMonth
            ],
            [
                'name' => 'ExpireYear',
                'contents' => $expireYear
            ],
            [
                'name' => 'Cvv',
                'contents' => $cvc
            ],
            [
                'name' => 'PublicKey',
                'contents' => $this->apiKey
            ],
            [
                'name' => 'Nonce',
                'contents' => $this->nonce
            ],
            [
                'name' => 'Signature',
                'contents' => $this->signature
            ],
            [
                'name' => 'ConversationId',
                'contents' => $this->conversationId
            ],
            [
                'name' => 'MerchantNumber',
                'contents' => $this->merchantNumber
            ],
            [
                'name' => 'CardHolderName',
                'contents' => $cardholderName
            ]
        ];

        try {
            $response = $this->multipartClient->request('POST', '/v1/Tokens', [
                'multipart' => $data
            ]);

            $decodedResponse = json_decode($response->getBody()->getContents());

            if ($this->checkError($decodedResponse))
                return $decodedResponse->cardToken;
            else
                return ['error' => $decodedResponse->errorMessage];
        } catch (GuzzleException $e) {
            return ['error' => $e->getMessage()];
        }
    }

    private function get3DSession($amount, $pointAmout, $currency, $languageCode)
    {
        $this->amount = $amount;
        $this->currency = $currency;

        $data = array(
            'amount' => $amount,
            'pointAmount' => $pointAmout,
            'cardToken' => $this->cardToken,
            'currency' => $currency,
            'paymentType' => "Auth",
            'installmentCount' => 1,
            'languageCode' => $languageCode
        );

        try {
            $response = $this->jsonClient->request('POST', '/v1/threeds/getthreedsession', [
                'json' => $data
            ]);

            $decodedResponse = json_decode($response->getBody()->getContents());

            if ($this->checkError($decodedResponse))
                return $decodedResponse->threeDSessionId;
            else
                return ['error' => $decodedResponse->errorMessage];
        } catch (GuzzleException $e) {
            return ['error' => $e->getMessage()];
        }
    }

    private function get3DInit($callbackURL, $languageCode)
    {
        $data = [
            [
                'name' => 'ThreeDSessionId',
                'contents' => $this->threeDSessionID
            ],
            [
                'name' => 'CallbackUrl',
                'contents' => $callbackURL
            ],
            [
                'name' => 'LanguageCode',
                'contents' => $languageCode
            ],
            [
                'name' => 'ClientIpAddress',
                'contents' => $_SERVER['REMOTE_ADDR']
            ],
            [
                'name' => 'PublicKey',
                'contents' => $this->apiKey
            ],
            [
                'name' => 'Nonce',
                'contents' => $this->nonce
            ],
            [
                'name' => 'Signature',
                'contents' => $this->signature
            ],
            [
                'name' => 'ConversationId',
                'contents' => $this->conversationId
            ],
            [
                'name' => 'MerchantNumber',
                'contents' => $this->merchantNumber
            ]
        ];

        try {
            $response = $this->multipartClient->request('POST', '/v1/threeds/init3ds', [
                'multipart' => $data
            ]);

            $decodedResponse = json_decode($response->getBody()->getContents());

            if ($this->checkError($decodedResponse))
                return $decodedResponse->htmlContent;
            else
                return ['error' => $decodedResponse->errorMessage];
        } catch (GuzzleException $e) {
            return ['error' => $e->getMessage()];
        }
    }

    private function get3DSessionResult($languageCode = 'TR')
    {
        $data = array(
            'threeDSessionId' => $_SESSION['threeDSessionId'],
            'languageCode' => $languageCode
        );

        try {
            $response = $this->jsonClient->request('POST', '/v1/threeds/getthreedsessionresult', [
                'json' => $data
            ]);

            $decodedResponse = json_decode($response->getBody()->getContents());

            if ($this->checkError($decodedResponse))
                return $decodedResponse->currentStep;
            else
                return ['error' => $decodedResponse->errorMessage];
        } catch (GuzzleException $e) {
            return ['error' => $e->getMessage()];
        }
    }

    private function provision()
    {
        $data = array(
            'amount' => $_SESSION['amount'],
            'cardToken' => $_SESSION['cardToken'],
            'currency' => $_SESSION['currency'],
            'paymentType' => 'Auth',
            'cardHolderName' => $_SESSION['cardholderName'],
            "threeDSessionId" => $_SESSION['threeDSessionId'],
        );

        try {
            $response = $this->jsonClient->request('POST', '/v1/Payments/provision', [
                'json' => $data
            ]);

            $decodedResponse = json_decode($response->getBody()->getContents());

            if ($this->checkError($decodedResponse)) {
                unset($_SESSION['nonce']);
                unset($_SESSION['threeDSessionId']);
                unset($_SESSION['cardholderName']);
                unset($_SESSION['currency']);
                unset($_SESSION['cardToken']);
                unset($_SESSION['amount']);

                return $decodedResponse;
            } else
                return ['error' => $decodedResponse->errorMessage];
        } catch (GuzzleException $e) {
            return ['error' => $e->getMessage()];
        }
    }

    public function reverse($orderid, $languageCode = "TR")
    {
        $data = array(
            'orderid' => $orderid,
            'languageCode' => $languageCode,
        );

        try {
            $response = $this->jsonClient->request('POST', '/v1/Payments/reverse', [
                'json' => $data
            ]);

            $decodedResponse = json_decode($response->getBody()->getContents());

            if ($this->checkError($decodedResponse)) {
                return $decodedResponse;
            } else
                return ['error' => $decodedResponse->errorMessage];
        } catch (GuzzleException $e) {
            return ['error' => $e->getMessage()];
        }
    }

    public function return($amount, $orderid, $languageCode = "TR")
    {
        $data = array(
            'amount' => $amount,
            'orderid' => $orderid,
            'languageCode' => $languageCode,
        );

        try {
            $response = $this->jsonClient->request('POST', '/v1/Payments/return', [
                'json' => $data
            ]);

            $decodedResponse = json_decode($response->getBody()->getContents());

            if ($this->checkError($decodedResponse)) {
                return $decodedResponse;
            } else
                return ['error' => $decodedResponse->errorMessage];
        } catch (GuzzleException $e) {
            return ['error' => $e->getMessage()];
        }
    }

    public function bin_info($binNumber, $languageCode = "TR")
    {
        $data = array(
            'binNumber' => $binNumber,
            'languageCode' => $languageCode,
        );

        try {
            $response = $this->jsonClient->request('POST', '/v1/Payments/bin-information', [
                'json' => $data
            ]);

            $decodedResponse = json_decode($response->getBody()->getContents());

            if ($this->checkError($decodedResponse)) {
                return $decodedResponse;
            } else
                return ['error' => $decodedResponse->errorMessage];
        } catch (GuzzleException $e) {
            return ['error' => $e->getMessage()];
        }
    }

    public function installment($binNumber, $merchantNumber, $amount)
    {
        try {
            $response = $this->jsonClient->request('GET', '/v1/Installment?binNumber=' . $binNumber . '&amount=' . $amount . '&merchantNumber=' . $merchantNumber);

            $decodedResponse = json_decode($response->getBody()->getContents());

            if ($this->checkError($decodedResponse)) {
                return $decodedResponse;
            } else
                return ['error' => $decodedResponse->errorMessage];
        } catch (GuzzleException $e) {
            return ['error' => $e->getMessage()];
        }
    }

    private function point_inquiry($cardToken, $languageCode = "TR", $currency = "TRY")
    {
        $data = array(
            'cardToken' => $cardToken,
            'languageCode' => $languageCode,
            'currency' => $currency,
        );

        try {
            $response = $this->jsonClient->request('POST', '/v1/Payments/pointInquiry', [
                'json' => $data
            ]);

            $decodedResponse = json_decode($response->getBody()->getContents());

            if ($this->checkError($decodedResponse)) {
                return $decodedResponse;
            } else
                return ['error' => $decodedResponse->errorMessage];
        } catch (GuzzleException $e) {
            return ['error' => $e->getMessage()];
        }
    }

    private function checkError($data)
    {
        if ($data->isSucceed == 1)
            return true;
        else {
            return false;
        }
    }
}
