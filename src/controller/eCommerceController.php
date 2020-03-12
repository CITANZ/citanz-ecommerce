<?php

namespace Cita\eCommerce\Controller;
use SilverStripe\CMS\Controllers\ContentController;
use Cita\eCommerce\Model\Order;
use SilverStripe\Core\Config\Config;
use Cita\eCommerce\eCommerce;

class eCommerceController extends ContentController
{
    protected function route($result)
    {
        $state      =   $result['state'];
        $orderID    =   $result['order_id'];
        $url        =   $this->get_returning_url($state);
        $queries    =   [
                            'order_id'  =>  $orderID,
                            'state'     =>  strtolower($state)
                        ];

        $url        .=  ('?' . http_build_query($queries));

        return $this->redirect($url);
    }

    protected function route_data($state, $order_id)
    {
        return  [
                    'order_id'      =>  $order_id
                ];
    }

    protected function handle_postback($data)
    {
        user_error("Please implement handle_postback() on " . __CLASS__, E_USER_ERROR);
    }

    public function getOrder($merchant_reference)
    {
        return Order::get()->filter(['MerchantReference' => $merchant_reference])->first();
    }

    protected function get_returning_url($status)
    {
        if ($status == 'Success') {
            return Config::inst()->get(eCommerce::class, 'MerchantSettings')['SuccessURL'];
        } elseif ($status == 'Cancelled') {
            return Config::inst()->get(eCommerce::class, 'MerchantSettings')['CancellationURL'];
        } elseif ($status == 'CardSavedOnly') {
            return Config::inst()->get(eCommerce::class, 'MerchantSettings')['CardSavedURL'];
        }

        return Config::inst()->get(eCommerce::class, 'MerchantSettings')['FailureURL'];
    }
}
