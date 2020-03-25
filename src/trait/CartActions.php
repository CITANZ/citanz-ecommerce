<?php
namespace Cita\eCommerce\Traits;
use Cita\eCommerce\eCommerce;
use SilverStripe\SiteConfig\SiteConfig;
use Cita\eCommerce\Model\Order;
use Cita\eCommerce\Model\Freight;
use Cita\eCommerce\Model\Discount;
use Cita\eCommerce\Model\GatewayResponse;
use SilverStripe\Control\Director;
use SilverStripe\Control\HTTPRequest;
use SilverStripe\Security\Member;
use SilverStripe\Core\Config\Config;
use SilverStripe\Core\Environment;
use Cita\eCommerce\API\Stripe;
use Page;

trait CartActions
{
    public function getData()
    {
        $cart   =   eCommerce::get_cart();

        if ($this->request->getVar('mini')) {
            if (!$cart) return $cart;
            return $cart->getData();
        }

        $data               =   Page::create()->getData();
        $data['pagetype']   =   'CartPage';
        $data['title']      =   $this->Title;
        $data['gst_rate']   =   SiteConfig::current_site_config()->GSTRate;
        $data['cart']       =   $cart ? $cart->getData() : $cart;

        return $data;
    }

    private function do_add()
    {
        $cart   =   eCommerce::get_cart();

        if (!$cart) {
            $cart       =   Order::create();
            $cart->write();
            $session    =   $this->request->getSession();
            $session->set('cart_id', $cart->ID);
        }

        $cart->add_to_cart($this->request->postVar('id'), $this->request->postVar('qty'), $this->request->postVar('class'));

        return $cart->getData();
    }

    private function do_update()
    {
        if ($cart = eCommerce::get_cart()) {
            $item   =   $cart->Items()->byID($this->request->postVar('id'));

            if ($this->request->postVar('qty') <= 0) {
                $item->delete();
            } elseif ($item->Quantity != $this->request->postVar('qty')) {
                $item->Quantity =   $this->request->postVar('qty');
                $item->write();
            }

            $cart->UpdateAmountWeight();

            return $cart->getData();
        }

        return $this->httpError(400, 'cannot find cart');
    }

    private function do_delete()
    {
        if ($cart = eCommerce::get_cart()) {
            if ($item = $cart->Items()->byID($this->request->postVar('id'))) {
                $item->delete();
            }

            $cart->UpdateAmountWeight();

            return $cart->getData();
        }

        return $this->httpError(400, 'cannot find cart');
    }

    private function do_estimate_freight()
    {
        if (($freight_id = $this->request->postVar('freight_id')) && ($code = $this->request->postVar('country_code'))) {
            if ($freight = Freight::get()->byID($freight_id)) {

                $cart   =   eCommerce::get_cart();

                if ($cart && ($zone = $freight->find_zone($code))) {
                    return $zone->CalculateOrderCost($cart);
                }
            }
        }

        return null;
    }

    private function do_coupon_validate()
    {
        if ($code = $this->request->postVar('coupon')) {
            if ($coupon = Discount::check_valid($code)) {
                return $coupon->getData();
            }
        }

        return $this->httpError(404, 'No such promo code');
    }

    private function do_checkout()
    {
        $data   =   json_decode($this->request->postVar('data'));
        $method =   $this->request->postVar('method');

        if (!$data || !$method) {
            return $this->httpError(500, 'missing gateway or data');
        }

        $this->validate_fields($data, !$data->same_addr);

        if ($cart = eCommerce::get_cart()) {
            $cart->digest($data);

            if ($cart->PayableTotal == 0) {
                return $this->httpError(500, 'There is nothing to pay!');
            }

            return $this->getPaymentURL($method, $cart);
        }

        return $this->httpError(500, 'Something went wrong');
    }

    private function getPaymentURL($method, &$cart)
    {
        $raw_resp   =   ($method)::process($cart->PayableTotal, $cart->MerchantReference, $cart);
        $response   =   GatewayResponse::create($method, $raw_resp);

        if (!empty($response->error)) {
            return $this->httpError(500, $response->error);
        }

        if ($method == Stripe::class) {
            return [
                'client_secret' =>  $response->client_secret
            ];
        }

        return [
            'url'   =>  $response->uri
        ];
    }

    private function get_complete_data()
    {
        $cart   =   eCommerce::get_last_processed_cart($this->request->param('id'));

        if (!$cart) {
            return $this->httpError(404, 'Not found');
        }

        // fugly hack to work with Stripe's Element code behaviour
        if ($this->request->getVar('mini')) {
            if (!$cart->Payments()->first()) return false;
            return true;
        }

        $data               =   Page::create()->getData();
        $data['pagetype']   =   'PaymentResult';
        $data['title']      =   $this->Title($cart);
        $data['catalog']    =   eCommerce::get_catalog_url();
        $data['payment']    =   $cart->Payments()->first() ? $cart->Payments()->first()->getData() : null;
        $data['cart']       =   $cart->getData();
        $data['shipping']   =   $cart->getShippingData();
        $data['billing']    =   $cart->getBillingData();
        $data['email']      =   $cart->Email;
        $data['freight']    =   $cart->Freight()->getData();
        $data['freight']['price']   =   $cart->ShippingCost;
        return $data;
    }

    private function get_checkout_data()
    {
        if ($cart = eCommerce::get_cart()) {
            $cart->UpdateAmountWeight();

            $checkout   =   [
                'email'             =>  $cart->Email ?
                                        $cart->Email :
                                        (Member::currentUser() ? Member::currentUser()->Email : null),
                'amount'            =>  (float) $cart->TotalAmount,
                'amounts'           =>  [
                                            'discoutable_taxable'           =>  $cart->DiscountableTaxable,
                                            'discoutable_nontaxable'        =>  $cart->DiscountableNonTaxalbe,
                                            'nondiscountable_taxable'       =>  $cart->NonDiscountableTaxable,
                                            'nondiscountable_nontaxable'    =>  $cart->NonDiscountableNonTaxable
                                        ],
                'grand_total'       =>  $cart->PayableTotal,
                'weight'            =>  $cart->TotalWeight,
                'items'             =>  $cart->ItemCount(),
                'freight'           =>  !empty($cart->FreightID) ? $cart->FreightID :
                                        (eCommerce::get_freight_options()->count() > 0 ?
                                        eCommerce::get_freight_options()->first()->ID : null),
                'freight_data'      =>  $cart->get_freight_data(),
                'comment'           =>  $cart->Comment,
                'discount'          =>  $cart->Discount()->getData(),
                'shipping'          =>  $cart->getShippingData(false),
                'same_addr'         =>  $cart->SameBilling ? 1 : 0,
                'billing'           =>  $cart->getBillingData(false),
                'is_freeshipping'   =>   $cart->is_freeshipping(),
                'amount'            =>  $cart->TotalAmount,
                'shipping_cost'     =>  $cart->ShippingCost
            ];
        }

        $data                       =   Page::create()->getData();
        $data['id']                 =   !empty($cart) ? $cart->ID : 0;
        $data['pagetype']           =   'CheckoutPage';
        $data['title']              =   'Checkout';
        $data['countries']          =   eCommerce::get_all_countries();
        $data['freight_options']    =   eCommerce::get_freight_options()->getData();
        $data['payment_methods']    =   eCommerce::get_available_payment_methods()->getData();
        $data['gst_rate']           =   SiteConfig::current_site_config()->GSTRate;
        $data['checkout']           =   !empty($checkout) ? $checkout : null;
        $data['stripe_key']         =   Director::isDev() ? eCommerce::get_stripe_settings()->key_dev : eCommerce::get_stripe_settings()->key;
        $data['session_oid']        =   $this->request->getSession()->get('cart_id');

        return $data;
    }

    private function validate_fields(&$data, $check_billing)
    {
        if (empty($data->email)) {
            if ($this->request->isAjax()) {
                throw new \Exception('Missing email address!');
            }
            return $this->httpError(400, 'Missing email address!');
        }

        if (empty($data->shipping)) {
            if ($this->request->isAjax()) {
                throw new \Exception('Shipping section is missing');
            }
            return $this->httpError(400, 'Shipping section is missing');
        }

        if (empty($data->shipping->firstname)) {
            if ($this->request->isAjax()) {
                throw new \Exception('Shipping: First name is missing');
            }
            return $this->httpError(400, 'Shipping: First name is missing');
        }

        if (empty($data->shipping->surname)) {
            if ($this->request->isAjax()) {
                throw new \Exception('Shipping: Surname is missing');
            }
            return $this->httpError(400, 'Shipping: Surname is missing');
        }

        if (empty($data->shipping->address)) {
            if ($this->request->isAjax()) {
                throw new \Exception('Shipping: Address is missing');
            }
            return $this->httpError(400, 'Shipping: Address is missing');
        }

        if (empty($data->shipping->suburb)) {
            if ($this->request->isAjax()) {
                throw new \Exception('Shipping: Suburb is missing');
            }
            return $this->httpError(400, 'Shipping: Suburb is missing');
        }

        if (empty($data->shipping->town)) {
            if ($this->request->isAjax()) {
                throw new \Exception('Shipping: City is missing');
            }
            return $this->httpError(400, 'Shipping: City is missing');
        }

        if (empty($data->shipping->region)) {
            if ($this->request->isAjax()) {
                throw new \Exception('Shipping: Region/State is missing');
            }
            return $this->httpError(400, 'Shipping: Region/State is missing');
        }

        if (empty($data->shipping->country)) {
            if ($this->request->isAjax()) {
                throw new \Exception('Shipping: Country is missing');
            }
            return $this->httpError(400, 'Shipping: Country is missing');
        } else {
            if (empty(eCommerce::get_all_countries()[$data->shipping->country])) {
                if ($this->request->isAjax()) {
                    throw new \Exception('Shipping: Country: ' . $data->shipping->country . ' is not allowed!');
                }
                return $this->httpError(400, 'Shipping: Country: ' . $data->shipping->country . ' is not allowed!');
            }
        }

        if (empty($data->shipping->postcode)) {
            if ($this->request->isAjax()) {
                throw new \Exception('Shipping: Postcode/ZIP is missing');
            }
            return $this->httpError(400, 'Shipping: Postcode/ZIP is missing');
        }

        if (empty($data->shipping->phone)) {
            if ($this->request->isAjax()) {
                throw new \Exception('Shipping: Phone is missing');
            }
            return $this->httpError(400, 'Shipping: Phone is missing');
        }

        if ($check_billing) {
            if (empty($data->billing)) {
                if ($this->request->isAjax()) {
                    throw new \Exception('Billing section is missing');
                }
                return $this->httpError(400, 'Billing section is missing');
            }

            if (empty($data->billing->firstname)) {
                if ($this->request->isAjax()) {
                    throw new \Exception('Billing: First name is missing');
                }
                return $this->httpError(400, 'Billing: First name is missing');
            }

            if (empty($data->billing->surname)) {
                if ($this->request->isAjax()) {
                    throw new \Exception('Billing: Surname is missing');
                }
                return $this->httpError(400, 'Billing: Surname is missing');
            }

            if (empty($data->billing->address)) {
                if ($this->request->isAjax()) {
                    throw new \Exception('Billing: Address is missing');
                }
                return $this->httpError(400, 'Billing: Address is missing');
            }

            if (empty($data->billing->suburb)) {
                if ($this->request->isAjax()) {
                    throw new \Exception('Billing: Suburb is missing');
                }
                return $this->httpError(400, 'Billing: Suburb is missing');
            }

            if (empty($data->billing->town)) {
                if ($this->request->isAjax()) {
                    throw new \Exception('Billing: City is missing');
                }
                return $this->httpError(400, 'Billing: City is missing');
            }

            if (empty($data->billing->region)) {
                if ($this->request->isAjax()) {
                    throw new \Exception('Billing: Region/State is missing');
                }
                return $this->httpError(400, 'Billing: Region/State is missing');
            }

            if (empty($data->billing->country)) {
                if ($this->request->isAjax()) {
                    throw new \Exception('Billing: Country is missing');
                }
                return $this->httpError(400, 'Billing: Country is missing');
            } else {
                if (empty(eCommerce::get_all_countries()[$data->billing->country])) {
                    if ($this->request->isAjax()) {
                        throw new \Exception('Billing: Country "' . $data->billing->country . '" is not allowed!');
                    }
                    return $this->httpError(400, 'Billing: Country "' . $data->billing->country . '" is not allowed!');
                }
            }

            if (empty($data->billing->postcode)) {
                if ($this->request->isAjax()) {
                    throw new \Exception('Billing: Postcode/ZIP is missing');
                }
                return $this->httpError(400, 'Billing: Postcode/ZIP is missing');
            }

            if (empty($data->billing->phone)) {
                if ($this->request->isAjax()) {
                    throw new \Exception('Billing: Phone is missing');
                }
                return $this->httpError(400, 'Billing: Phone is missing');
            }
        }
    }
}
