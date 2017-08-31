<?php

use \Slim\Http\Request;
use \Slim\Http\Response;

/** @var Slim\Container $container */

$this->get('[/]', function (Request $request, Response $response, array $args) {
    $result = [
        'result' => 'success',
        'message' => 'How to use',
        'url' => 'https://' . $_SERVER['SERVER_NAME'] . '/api/{path}?{args}',
        'method' => 'GET',
        'promptPay' => [
            'path' => 'prompt-pay',
            'args' => [
                'responsetype' => [
                    'default' => 'redirect',
                    'data' => [
                        'json',
                        'redirect'
                    ]
                ],
                'payload' => [
                    'id' => '00',
                    'length' => '02',
                    'data' => [
                        'require' => false,
                        'default' => '01'
                    ]
                ],
                'mai' => [
                    'description' => 'Merchant Account Information',
                    'name' => 'mai',
                    'id' => '29',
                    'length' => '37',
                    'data' => [
                        'description' => 'Prompt Pay = A000000677010111',
                        'require' => false,
                        'default' => 'A000000677010111',
                    ]
                ],
                'maidata' => [
                    'description' => 'Merchant Account Information data must be phone or Prompt Pay ID',
                    'name' => 'maidata',
                    'id' => '00',
                    'length' => '16',
                    'data' => [
                        'require' => true,
                    ]
                ],
                'country' => [
                    'id' => '58',
                    'length' => '02',
                    'data' => [
                        'require' => false,
                        'default' => 'TH'
                    ]
                ],
                'amount' => [
                    'id' => '54',
                    'length' => '00|13',
                    'data' => [
                        'require' => false,
                        'default' => '0'
                    ]
                ],
                'paytype' => [
                    'description' => 'For static QR code payment 11, dynamic payment 12',
                    'id' => '01',
                    'length' => '02',
                    'data' => [
                        'require' => false,
                        'message' => 'If has amount greater than 0 will be 12 else 11'
                    ]
                ],
                'currency' => [
                    'description' => 'Currency code ISO 4217',
                    'id' => '53',
                    'length' => '03',
                    'data' => [
                        'description' => 'THB = 764',
                        'require' => false,
                        'default' => '764'
                    ]
                ],
            ]
        ],
    ];

    return $response->withJson($result);

});

$this->get('/prompt-pay', function (Request $request, Response $response, array $args) {
    require_once $_SERVER['DOCUMENT_ROOT'] . '/controllers/Utilities.php';

    if (empty($request->getQueryParam('maidata'))) {

        return $response->withRedirect('/api');
    }

    /** EMVCo QR Specification
     ** https://www.emvco.com/emv-technologies/qrcodes/
     **/

    // Format fieldID + fieldDataLength + fieldData
    // Payload Format Indicator
    $payload = '00' . '02' . $request->getQueryParam('payload', '01');
    // Merchant Account Information
    // max length 99
    $mai = '29' . '37';
    //PromptPay = A000000677010111
    $mai .= '00' . '16' . $request->getQueryParam('mai', 'A000000677010111');
    // Merchant Account data phone/PromptPay ID
    $maiData = $request->getQueryParam('maidata');

    // Detect phone/PromptPay ID
    if (strlen($maiData) == 10) {
        $appType = '01';
        $appLen = 13;
        $maiData = '66' . substr($maiData, 1);
    } else {
        $appType = '02';
        $appLen = 15;
    }
    // Leading data with zero
    $appLen = str_pad($appLen, 2, 0, STR_PAD_LEFT);
    $maiData = str_pad($maiData, $appLen, 0, STR_PAD_LEFT);

    // Country code
    // max length 2
    $country = '58' . '02' . $request->getQueryParam('country', 'TH');

    // Transaction Amount
    // max length 13
    $amount = $request->getQueryParam('amount', '0');
    $amount = substr($amount, 0, 13);
    // Leading data with zero
    $amountLen = str_pad(strlen($amount), 2, 0, STR_PAD_LEFT);
    $amount = '54' . $amountLen . $amount;

    // Point of Initiation Method static = 11, dynamic = 12
    if ($amount > 0) {
        $payType = '01' . '02' . '12';
    } else {
        $payType = '01' . '02' . '11';
    }

    // Currency code ISO 4217
    // THB = 764
    $currency = '53' . '03' . $request->getQueryParam('currency', '764');

    // Data to generate QR
    $data = $payload;
    $data .= $payType;
    $data .= $mai;
    $data .= $appType . $appLen . $maiData;
    $data .= $country;
    if ($amount > 0) {
        $data .= $amount;
    }
    $data .= $currency;
    $data .= '63' . '04';
    // CRC16 start with 0xFFFF and 0x1021 as polynomial
    // max length 4
    $crc = strtoupper(substr(Utilities::CRC16($data, 0xFFFF, 0x1021, true), -4));

    // Just for safe url
    $data = urlencode($data . $crc);

    // Use google API to generate QR code ^_^
    $url = 'https://chart.googleapis.com/chart?cht=qr&chs=256x256&chld=M|0&chl=' . $data;

    $responseType = $request->getParsedBodyParam('responsetype', 'redirect');

    switch ($responseType) {
        case 'json' : {
            $result = [
                'result' => true,
                'data' => $data,
                'url' => $url
            ];

            return $response->withJson($result);
            break;
        }
        default : {
            return $response->withRedirect($url);
        }
    }

});