<?php

namespace Cita\eCommerce\Model;
use Cita\eCommerce\API\Paystation;
use Cita\eCommerce\API\POLi;
use Cita\eCommerce\API\DPS;
use Cita\eCommerce\API\Invoice;
use Cita\eCommerce\API\DirectDebit;
use Cita\eCommerce\API\Stripe;

class GatewayResponse
{
    private $uri;
    private $error;
    private $client_secret;

    public function __construct(String $gateway, Array $response)
    {
        if ($gateway == POLi::class) {
            if (!empty($response['Success'])) {
                $this->uri      =   $response['NavigateURL'];
            } else {
                $this->error    =   $response['ErrorMessage'];
            }
        }

        if ($gateway == DPS::class) {
            if (!empty($response['URI'])) {
                $this->uri      =   $response['URI'];
            } elseif (!empty($response['ResponseText'])) {
                $this->error    =   $response['ResponseText'];
            }
        }

        if ($gateway == Paystation::class) {
            if (!empty($response['InitiationRequestResponse']['DigitalOrder'])) {
                $this->uri      =   $response['InitiationRequestResponse']['DigitalOrder'];
            } elseif (!empty($response['response']['em'])) {
                $this->error    =   $response['response']['em'];
            }
        }

        if ($gateway == Invoice::class || $gateway == DirectDebit::class) {
            $this->uri  =   $response['URI'];
        }

        if ($gateway == Stripe::class) {
            $this->client_secret    =   $response['client_secret'];
        }
    }

    public function __get($property)
    {
        if (property_exists($this, $property)) {
            return $this->$property;
        }
    }

    public function __set($property, $value)
    {
        if (property_exists($this, $property)) {
            $this->$property = $value;
        }

        return $this;
    }
}
