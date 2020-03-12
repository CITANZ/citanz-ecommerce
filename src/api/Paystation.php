<?php
namespace Cita\eCommerce\API;

use SilverStripe\Control\Director;
use SilverStripe\Core\Config\Config;
use Cita\eCommerce\eCommerce;
use SilverStripe\Core\Environment;

class Paystation
{
    public static function initiate($amount, $ref)
    {
        $gateway_endpoint   =   Config::inst()->get(eCommerce::class, 'API')['Paystation'];
        $settings           =   Environment::getEnv('Cita_eCommerce_PaymentMethod_Paystation');

        if (empty($settings)) {
            throw new \Exception("Paystation settings not found in .env file. Please create a Cita_eCommerce_PaymentMethod_Paystation envvar in .env file.", 1);
        }

        $settings           =   json_decode($settings);

        $params             =   Config::inst()->get(eCommerce::class, 'GatewaySettings')['Paystation'];
        $endpoint           =   $gateway_endpoint;
        $params['pstn_pi']  =   $settings->pstn_pi;
        $params['pstn_am']  =   $amount * 100;
        $params['pstn_mr']  =   $ref;
        $params['pstn_ms']  =   sha1(mt_rand() . '-' . microtime(true) * 1000 . '-' . session_id());
        $params['pstn_du']  =   Director::absoluteBaseURL() . 'cita-ecommerce/paystation-complete';
        $params['pstn_dp']  =   Director::absoluteBaseURL() . 'cita-ecommerce/paystation-complete';
        $params['pstn_cu']  =   Config::inst()->get(eCommerce::class, 'DefaultCurrency');
        $params['pstn_rf']  =   'JSON';

        // below feature needs to be completed
        // if (!empty($register_future_pay)) {
        //     $params['pstn_fp']      =   't';
        //     if (empty($immediate_future_pay)) {
        //         unset($params['pstn_am']);
        //         $params['pstn_fs']  =   't';
        //     }
        // }

        $time               =   time();
        $hmac               =   $settings->pstn_HMAC;

        $query_params       =   http_build_query($params);

        $params             =   [
                                    'pstn_HMACTimestamp'    =>  $time,
                                    'pstn_HMAC'             =>  hash_hmac('sha512', "{$time}paystation$query_params", $hmac)
                                ];

        $url = $gateway_endpoint . '?' . http_build_query($params);

        $options = [
            'http' => [
                'header' => "Content-Type: application/x-www-form-urlencoded\r\n",
                'method' => 'POST'
            ]
        ];

        if ($query_params) {
            $options['http']['content'] = $query_params;
            $options['http']['header'] .= "Content-Length: " . strlen($query_params) . "\r\n";
        }

        $response = file_get_contents($url, false, stream_context_create($options));

        return $response;
    }

    public static function process($amount, $ref)
    {
        $response   =   static::initiate($amount, $ref);
        return json_decode($response,TRUE);
    }

    public static function fetch($token)
    {
        $endpoint   =   Config::inst()->get(eCommerce::class, 'API')['PaystationLookup'];
        $settings   =   Environment::getEnv('Cita_eCommerce_PaymentMethod_Paystation');


        $ch         =   curl_init($endpoint);
        curl_setopt( $ch, CURLOPT_POST, 1);
        curl_setopt( $ch, CURLOPT_POSTFIELDS, http_build_query(['pi' => $settings->pstn_pi, 'ti' => $token]));
        curl_setopt( $ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt( $ch, CURLOPT_SSL_VERIFYPEER, 0);

        $response   =   curl_exec( $ch );

        curl_close ($ch);

        $xml        =   simplexml_load_string($response);
        $json       =   json_encode($xml);

        return json_decode($json,TRUE);
    }
}
