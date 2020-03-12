<?php

namespace Cita\eCommerce\Controller;
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
class CartController extends PageController
{
    use CartActions;

    public function index(HTTPRequest $request)
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

        if ($action = $this->request->Param('action')) {
            if ($this->request->isAjax()) {
                if ($this->request->isPost()) {
                    return json_encode($this->{'do_' . $action}());
                }

                return json_encode($this->{'get_' . $action . '_data'}());
            }
        }

        if (method_exists(get_parent_class($this), 'index')) {
            return parent::index($request);
        }
    }

    protected function init()
    {
        parent::init();
        Requirements::javascript('https://js.stripe.com/v3/');
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
            }
        }

        return 'Cart';
    }

}
