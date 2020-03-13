<?php

namespace Cita\eCommerce\Layout;
use PageController;
use SilverStripe\SiteConfig\SiteConfig;
use Cita\eCommerce\eCommerce;
use Cita\eCommerce\Model\Order;
use Cita\eCommerce\Model\Freight;
use SilverStripe\Control\HTTPRequest;
use SilverStripe\Control\Director;
use Cita\eCommerce\Traits\CartActions;
use SilverStripe\Control\Cookie;
use SilverStripe\Core\Config\Config;
use SilverStripe\View\Requirements;

/**
 * Description
 *
 * @package silverstripe
 * @subpackage mysite
 */
class Cart extends PageController
{
    use CartActions;

    /**
     * Defines methods that can be called directly
     * @var array
     */
    private static $allowed_actions = [
        'checkout'          =>  true,
        'complete'          =>  true,
        'add'               =>  true,
        'update'            =>  true,
        'delete'            =>  true,
        'estimate_freight'  =>  true,
        'coupon_validate'   =>  true
    ];

    protected function handleAction($request, $action)
    {
        if (!$this->request->isAjax()) {
            return parent::handleAction($request, $action);
        }

        if ($this->request->httpMethod() === 'OPTIONS' ) {
            // create direct response without requesting any controller
            $response   =   $this->getResponse();
            // set CORS header from config
            $response   =   $this->addCORSHeaders($response);
            $response->output();
            exit;
        }

        $header     =   $this->getResponse();

        $this->addCORSHeaders($header);

        if ($action = $this->request->Param('action')) {

            if ($this->request->isPost()) {
                return json_encode($this->{'do_' . $action}());
            }

            return json_encode($this->{'get_' . $action . '_data'}());
        }

        return json_encode($this->getData());
    }

    protected function init()
    {
        parent::init();

        Requirements::javascript('https://js.stripe.com/v3/');
    }

    private function handle_preflight()
    {
        // check for CORS options request
        if ($this->request->httpMethod() === 'OPTIONS' ) {
            // create direct response without requesting any controller
            $response   =   $this->getResponse();
            // set CORS header from config
            $response   =   $this->addCORSHeaders($response);
            $response->output();
            exit;
        }
    }

    public function Link($action = NULL)
    {
        return '/cart/';
    }

    public function Title($cart = null)
    {
        if ($this->request) {
            if ($action = $this->request->param('action')) {
                if ($action == 'complete') {
                    if ($cart && ($payment = $cart->Payments()->first())) {
                        return 'Payment: ' . $payment->Status;
                    } elseif ($status = $this->request->param('status')) {
                        return 'Payment: ' . ucwords($status);
                    }

                    return 'Payment';
                }

                if ($action == 'checkout') {
                    return 'Checkout';
                }
            }
        }

        return 'Cart';
    }

}
