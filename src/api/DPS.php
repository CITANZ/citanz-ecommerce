<?php

namespace Cita\eCommerce\API;

use SilverStripe\Control\Director;
use SilverStripe\Core\Config\Config;
use Cita\eCommerce\eCommerce;
use SilverStripe\Core\Environment;

class DPS
{
    public static function initiate($amount, $ref)
    {
        if (Director::isDev()) {
            $amount =   round($amount);
        }

        $endpoint   =   Config::inst()->get(eCommerce::class, 'API')['DPS'];
        $settings   =   Environment::getEnv('Cita_eCommerce_PaymentMethod_DPS');

        if (empty($settings)) {
            throw new \Exception("Payment Express settings not found in .env file. Please create a Cita_eCommerce_PaymentMethod_DPS envvar in .env file.", 1);
        }

        $settings   =   json_decode($settings);
        $id         =   $settings->ID;
        $key        =   $settings->Key;
        $currency   =   Config::inst()->get(eCommerce::class, 'DefaultCurrency');

        $request    =   '<GenerateRequest>
                            <PxPayUserId>' . $id . '</PxPayUserId>
                            <PxPayKey>' . $key . '</PxPayKey>
                            <TxnType>Purchase</TxnType>
                            <AmountInput>' . $amount . '</AmountInput>
                            <CurrencyInput>' . $currency . '</CurrencyInput>
                            <MerchantReference>' . $ref . '</MerchantReference>
                            <UrlSuccess>' . Director::absoluteBaseURL() . 'cita-ecommerce/dps-complete</UrlSuccess>
                            <UrlFail>' . Director::absoluteBaseURL() . 'cita-ecommerce/dps-complete</UrlFail>
                        </GenerateRequest>';

        $ch         =   curl_init($endpoint);
        curl_setopt( $ch, CURLOPT_POST, 1);
        curl_setopt( $ch, CURLOPT_POSTFIELDS, $request);
        curl_setopt( $ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt( $ch, CURLOPT_SSL_VERIFYPEER, 0);

        $response   =   curl_exec( $ch );
        curl_close ($ch);

        return $response;
    }

    public static function process($amount, $ref)
    {
        $response   =   static::initiate($amount, $ref);
        $xml        =   simplexml_load_string($response);
        $json       =   json_encode($xml);

        return json_decode($json,TRUE);
    }

    public static function fetch($token)
    {
        $endpoint   =   Config::inst()->get(eCommerce::class, 'API')['DPS'];
        $settings   =   Environment::getEnv('Cita_eCommerce_PaymentMethod_DPS');

        if (empty($settings)) {
            throw new \Exception("Payment Express settings not found in .env file. Please create a Cita_eCommerce_PaymentMethod_DPS envvar in .env file.", 1);
        }

        $settings   =   json_decode($settings);
        $id         =   $settings->ID;
        $key        =   $settings->Key;

        $request    =   '<ProcessResponse>
                            <PxPayUserId>' . $id . '</PxPayUserId>
                            <PxPayKey>' . $key . '</PxPayKey>
                            <Response>' . $token . '</Response>
                        </ProcessResponse>';

        $ch         =   curl_init($endpoint);
        curl_setopt( $ch, CURLOPT_POST, 1);
        curl_setopt( $ch, CURLOPT_POSTFIELDS, $request);
        curl_setopt( $ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt( $ch, CURLOPT_SSL_VERIFYPEER, 0);

        $response   =   curl_exec( $ch );

        curl_close ($ch);

        $xml        =   simplexml_load_string($response);
        $json       =   json_encode($xml);

        return json_decode($json,TRUE);

    }
}
