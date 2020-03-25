<?php

namespace Cita\eCommerce\Model;
use Cita\eCommerce\API\Paystation;
use Cita\eCommerce\API\POLi;
use Cita\eCommerce\API\DPS;
use Cita\eCommerce\API\Invoice;
use Cita\eCommerce\API\DirectDebit;
use Cita\eCommerce\API\Stripe;
use Cita\eCommerce\API\Paypal;

class GatewayResponse
{
    public static function create(String $method, Array $response)
    {
        $uri            =   null;
        $error          =   null;
        $client_secret  =   null;

        if ($method == POLi::class) {
            if (!empty($response['Success'])) {
                $uri      =   $response['NavigateURL'];
            } else {
                $error    =   $response['ErrorMessage'];
            }
        }

        if ($method == DPS::class) {
            if (!empty($response['URI'])) {
                $uri      =   $response['URI'];
            } elseif (!empty($response['ResponseText'])) {
                $error    =   $response['ResponseText'];
            }
        }

        if ($method == Paystation::class) {
            if (!empty($response['InitiationRequestResponse']['DigitalOrder'])) {
                $uri      =   $response['InitiationRequestResponse']['DigitalOrder'];
            } elseif (!empty($response['response']['em'])) {
                $error    =   $response['response']['em'];
            }
        }

        if ($method == Invoice::class || $method == DirectDebit::class) {
            $uri  =   $response['URI'];
        }

        if ($method == Stripe::class) {
            $client_secret    =   $response['client_secret'];
        }

        if ($method == Paypal::class) {
            $uri      =   !empty($response['URL']) ? $response['URL'] : null;
            $error    =   !empty($response['error']) ? $response['error'] : null;
        }

        return json_decode(json_encode([
            'uri'   =>  $uri,
            'error' =>  $error,
            'client_secret' =>  $client_secret
        ]));
    }
}
