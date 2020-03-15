<?php

namespace Cita\eCommerce\Controller;
use SilverStripe\CMS\Controllers\ContentController;
use Cita\eCommerce\Model\Order;
use SilverStripe\Core\Config\Config;
use Cita\eCommerce\eCommerce;

class eCommerceController extends ContentController
{
    protected function route(&$order)
    {
        if (!$order->Payments()->first()) {
            return $this->httpError(400, 'Payment did not happen!');
        }

        $url    =   Config::inst()->get(eCommerce::class, 'MerchantSettings')['CompleteURL'] . $order->ID;

        return $this->redirect($url);
    }

    protected function handle_postback($data)
    {
        user_error("Please implement handle_postback() on " . __CLASS__, E_USER_ERROR);
    }

    public function getOrder($merchant_reference)
    {
        return Order::get()->filter(['MerchantReference' => $merchant_reference])->first();
    }
}
