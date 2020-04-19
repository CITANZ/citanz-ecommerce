<?php

namespace Cita\eCommerce\Controller;
use SilverStripe\CMS\Controllers\ContentController;
use Cita\eCommerce\Model\Order;
use SilverStripe\Control\HTTPRequest;

class eCommerceController extends ContentController
{
    /**
     * Defines URL patterns.
     * @var array
     */
    private static $url_handlers = [
        '$ID' => 'index'
    ];

    public function index(HTTPRequest $request)
    {
        if ($order = Order::get()->byID($request->param('ID'))) {
            return $this->route($order);
        }

        return $this->httpError(404);
    }

    protected function route(&$order)
    {
        if (!$order->Payments()->first()) {
            return $this->httpError(400, 'Payment did not happen!');
        }

        $url = $this->config()->CompleteURL . $order->ID;

        return $this->redirect($url);
    }
}
