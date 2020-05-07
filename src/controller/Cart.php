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
use Cita\eCommerce\Traits\CartTemplateActions;
use Cita\eCommerce\Traits\CartTemplateVariables;
use SilverStripe\Control\Cookie;
use SilverStripe\Core\Config\Config;
use SilverStripe\View\Requirements;
use SilverStripe\Security\SecurityToken;

/**
 * Description
 *
 * @package silverstripe
 * @subpackage mysite
 */
class Cart extends PageController
{
    use CartActions, CartTemplateActions, CartTemplateVariables;

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
        'read'              =>  true,
        'estimate_freight'  =>  true,
        'coupon_validate'   =>  true,
        // For forms
        'CartUpdateForm'    =>  true,
        'CheckoutForm'      =>  true,
        'DeleteCartItem'    =>  true
    ];

    protected function handleAction($request, $action)
    {
        if ($this->request->httpMethod() === 'OPTIONS' ) {
            // create direct response without requesting any controller
            $response   =   $this->getResponse();
            // set CORS header from config
            $response   =   $this->addCORSHeaders($response);
            $response->output();
            exit;
        }

        if (!$this->request->isAjax()) {

            if (($this->request->Param('action') == 'complete') && empty(eCommerce::get_last_processed_cart($this->request->param('id')))) {
                return $this->httpError(404);
            }

            return parent::handleAction($request, $action);
        }

        $header     =   $this->getResponse();
        $this->addCORSHeaders($header);

        if ($this->request->getVar('mini')) {
            if ($cart = eCommerce::get_cart()) {
                return json_encode($cart->getData());
            }

            return null;
        }

        if ($action = $this->request->Param('action')) {

            if ($this->request->isPost()) {
                return json_encode($this->{'do_' . $action}());
            }

            return json_encode(array_merge($this->{'get_' . $action . '_data'}(), [
                'session' => ['csrf' => SecurityToken::inst()->getSecurityID()]
            ]));
        }

        return parent::handleAction($request, $action);
    }

    protected function init()
    {
        parent::init();

        if (in_array('Stripe', eCommerce::get_available_payment_methods())) {
            Requirements::javascript('https://js.stripe.com/v3/');
        }
    }

    public function Link($action = NULL)
    {
        return '/cart/';
    }

    public function getTitle()
    {
        if ($this->request) {
            if ($action = $this->request->param('action')) {
                if (($action == 'complete') && ($id = $this->request->param('id'))) {
                    if ($cart = eCommerce::get_last_processed_cart($id)) {
                        if ($cart->Payments()->exists()) {
                            return 'Payment: ' . $cart->Payments()->first()->TranslatedStatus;
                        }
                    }
                }

                if ($action == 'checkout') {
                    return 'Checkout';
                }
            }
        }

        return _t(__CLASS__ . '.TITLE', 'Cart');
    }

}
