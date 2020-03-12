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

    /**
     * Defines URL patterns.
     * @var array
     */
    private static $url_handlers = [
        'checkout'          =>  'render_checkout_page',
        'complete/$status'  =>  'render_complete_page',
        'add'               =>  'index',
        'delete'            =>  'index',
        'update'            =>  'index',
        'estimate_freight'  =>  'index',
        'coupon_validate'   =>  'index'
    ];

    /**
     * Defines methods that can be called directly
     * @var array
     */
    private static $allowed_actions = [
        'render_checkout_page'  =>  true,
        'render_complete_page'  =>  true
    ];

    public function index(HTTPRequest $request)
    {
        $this->handle_preflight();

        if ($this->request->isAjax()) {

            if ($action = $this->request->Param('action')) {

                if ($this->request->isPost()) {
                    return json_encode($this->{'do_' . $action}());
                }

                return json_encode($this->{'get_' . $action . '_data'}());
            }

            return json_encode($this->getData());
        }

        return $this->renderWith(['Cita\eCommerce\Controller\Cart', 'Page']);
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

    public function render_checkout_page()
    {
        $this->handle_preflight();

        if ($this->request->isAjax()) {

            if ($this->request->isPost()) {
                return json_encode($this->do_checkout());
            }

            return json_encode($this->get_checkout_data());
        }

        return $this->renderWith(['Cita\eCommerce\Controller\Checkout', 'Page']);
    }

    public function render_complete_page()
    {
        $this->handle_preflight();

        if ($this->request->isAjax()) {

            if ($this->request->isPost()) {
                return json_encode($this->do_complete());
            }

            return json_encode($this->get_complete_data());
        }

        return $this->renderWith(['Cita\eCommerce\Controller\Complete', 'Page']);
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
