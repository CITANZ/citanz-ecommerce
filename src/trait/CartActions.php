<?php
namespace Cita\eCommerce\Traits;
use SilverStripe\Dev\Debug;
use Cita\eCommerce\eCommerce;
use SilverStripe\SiteConfig\SiteConfig;
use Cita\eCommerce\Model\Order;
use Cita\eCommerce\Model\Freight;
use Cita\eCommerce\Model\Discount;
use SilverStripe\Control\Director;
use SilverStripe\Control\HTTPRequest;
use SilverStripe\Security\Member;
use SilverStripe\Core\Config\Config;
use SilverStripe\Core\Environment;
use Cita\eCommerce\API\Stripe;
use Cita\eCommerce\Service\PaymentService;
use Page;
use Cita\eCommerce\Model\Variant;
use Leochenftw\Util;
use SilverStripe\Core\Convert;

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
        $data['cart']       =   ($cart && ($cart->Variants()->exists() || $cart->Bundles()->exists())) ? $cart->getData() : null;
        $data['shipping_reminder'] = Util::preprocess_content(SiteConfig::current_site_config()->ShippingReminder);

        return $data;
    }

    private function do_read()
    {
        $cart   =   eCommerce::get_cart();

        if ($cart && ($id = $this->request->param('id'))) {
            if ($message = $cart->Messages()->byID($id)) {
                $message->Displayed = true;
                $message->write();
                return $cart->getData();
            }
        }

        return $this->httpError(500, 'Cannot close message!');
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

        $vid = Convert::raw2sql($this->request->postVar('id'));
        $qty = Convert::raw2sql($this->request->postVar('qty'));

        $cart->AddToCart($vid, $qty);

        return $cart->getData();
    }

    private function do_update()
    {
        if ($cart = eCommerce::get_cart()) {
            $vid = Convert::raw2sql($this->request->postVar('id'));
            $qty = Convert::raw2sql($this->request->postVar('qty'));

            $cart->AddToCart($vid, $qty, true);

            $cart->UpdateAmountWeight();

            return $cart->getData();
        }

        return $this->httpError(400, 'cannot find cart');
    }

    private function do_delete()
    {
        if ($cart = eCommerce::get_cart()) {
            $vid = Convert::raw2sql($this->request->postVar('id'));
            $type = Convert::raw2sql($this->request->postVar('type'));

            if (!empty($type) && $type == 'bundle') {
                if ($entry = $cart->Bundles()->byID($vid)) {
                    $entry->delete();
                }
            } else {
                $cart->Variants()->removeByID($vid);
            }

            if ($cart->Discount()->exists()) {
                $cart->DiscountID = 0;
            }

            $cart->UpdateAmountWeight();

            return $cart->getData();
        }

        return $this->httpError(400, 'cannot find cart');
    }

    private function do_estimate_freight()
    {
        if ($payload = $this->request->postVar('payload')) {
            $payload = json_decode($payload);
            if ($cart = eCommerce::get_cart()) {
                $cart->digest($payload, false);
                if ($cart->is_freeshipping()) {
                    return [
                        'title' => 'Free shipping',
                        'cost' => 0
                    ];
                } elseif ($freight = $cart->Freight()) {
                    try {
                        $result = $freight->Calculate($cart);
                        return $result;
                    } catch (\Exception $e) {
                        return $this->httpError(400, $e->getMessage());
                    }
                }
            }
        }

        return $this->httpError(400, "Missing payload!");
    }

    private function do_coupon_validate()
    {
        if ($code = $this->request->postVar('coupon')) {
            if ($coupon = Discount::check_valid($code)) {
                $order = eCommerce::get_cart();
                $result = $coupon->calc_discount(0, $order);

                if (is_array($result)) {
                    return array_merge($coupon->Data, $coupon->calc_discount(0, $order));
                }

                return $coupon->Data;
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
                if (!$cart->Discount()->exists()) {
                    return $this->httpError(500, 'There is nothing to pay!');
                }

                return $this->ProcessFreeOrder($cart);
            }

            return [
                'url' => PaymentService::initiate($method, $cart, !empty($data->stripe_token) ? $data->stripe_token : null)
            ];
        }

        return $this->httpError(500, 'Something went wrong');
    }

    private function ProcessFreeOrder(&$cart)
    {
        $cart->completePayment('Free Order');

        return [
            'url' => "/cart/complete/$cart->ID"
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
        $data['title']      =   $this->Title;
        $data['catalog']    =   eCommerce::get_catalog_url();
        $data['status']     =   $cart->Status;
        $data['payment']    =   $cart->Payments()->first() ? $cart->Payments()->first()->Data : null;
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
                'email'             =>  $cart->Email ?: (Member::currentUser() ? (strpos(Member::currentUser()->Email, '@') !== false ? Member::currentUser()->Email : null) : null),
                'amount'            =>  (float) $cart->TotalAmount,
                'amounts'           =>  [
                                            'discountable_taxable' => $cart->DiscountableTaxable,
                                            'discountable_nontaxable' => $cart->DiscountableNonTaxable,
                                            'nondiscountable_taxable' => $cart->NonDiscountableTaxable,
                                            'nondiscountable_nontaxable' => $cart->NonDiscountableNonTaxable,
                                            'gst_included_amount' => $cart->TaxIncludedTotal
                                        ],
                'grand_total'       =>  $cart->PayableTotal,
                'weight'            =>  $cart->TotalWeight,
                'items'             =>  $cart->ItemCount(),
                'gst_included'      =>  $cart->IncludedGST,
                'freight'           =>  !empty($cart->FreightID) ? $cart->FreightID :
                                        (eCommerce::get_freight_options()->count() > 0 ?
                                        eCommerce::get_freight_options()->first()->ID : null),
                'freight_data'      =>  $cart->get_freight_data(),
                'comment'           =>  $cart->Comment,
                'discount'          =>  $this->getDiscountData($cart),
                'shipping'          =>  $cart->getShippingData(false),
                'same_addr'         =>  $cart->SameBilling ? 1 : 0,
                'billing'           =>  $cart->getBillingData(false),
                'is_freeshipping'   =>  $cart->is_freeshipping(),
                'amount'            =>  $cart->TotalAmount,
                'shipping_cost'     =>  $cart->ShippingCost
            ];

            if ($cart->hasMethod('extraCheckoutData')) {
                $checkout = array_merge($checkout, $cart->extraCheckoutData);
            }
        }

        $data                       =   Page::create()->getData();
        $data['id']                 =   !empty($cart) ? $cart->ID : 0;
        $data['pagetype']           =   'CheckoutPage';
        $data['title']              =   $this->Title;
        $data['countries']          =   eCommerce::get_all_countries();
        $data['freight_options']    =   eCommerce::get_freight_options()->getData();
        $data['payment_methods']    =   eCommerce::get_available_payment_methods();
        $data['gst_rate']           =   SiteConfig::current_site_config()->GSTRate;
        $data['checkout']           =   !empty($checkout) ? $checkout : null;

        $data['cart']               =   [
            'items' => $cart ? $cart->getData()['items'] : null
        ];

        if (in_array('Stripe', array_keys(eCommerce::get_available_payment_methods()))) {
            if ($config = Environment::getEnv('Stripe')) {
                $config = json_decode($config);
                $data['stripe_key'] = $config->privateKey;
            }
        }

        $data['session_oid']        =   $this->request->getSession()->get('cart_id');
        \SilverStripe\i18n\i18n::get_locale();
        return $data;
    }

    private function getDiscountData(&$cart)
    {
        if ($cart->Discount()->exists()) {
            $discount_result = $cart->Discount()->calc_discount($cart->DiscountableTaxable + $cart->DiscountableNonTaxable, $cart);

            if (is_array($discount_result)) {
                return array_merge($cart->Discount()->Data, $discount_result);
            }

            return $cart->Discount()->Data;
        }

        return null;
    }

    private function validate_fields(&$data, $check_billing)
    {
        if (empty($data->email)) {
            if (!$this->request->isAjax()) {
                throw new \Exception('Missing email address!');
            }
            return $this->httpError(400, 'Missing email address!');
        }

        if (empty($data->shipping)) {
            if (!$this->request->isAjax()) {
                throw new \Exception('Shipping section is missing');
            }
            return $this->httpError(400, 'Shipping section is missing');
        }

        if (empty($data->shipping->firstname)) {
            if (!$this->request->isAjax()) {
                throw new \Exception('Shipping: First name is missing');
            }
            return $this->httpError(400, 'Shipping: First name is missing');
        }

        if (empty($data->shipping->surname)) {
            if (!$this->request->isAjax()) {
                throw new \Exception('Shipping: Surname is missing');
            }
            return $this->httpError(400, 'Shipping: Surname is missing');
        }

        if (empty($data->shipping->address)) {
            if (!$this->request->isAjax()) {
                throw new \Exception('Shipping: Address is missing');
            }
            return $this->httpError(400, 'Shipping: Address is missing');
        }

        if (empty($data->shipping->town)) {
            if (!$this->request->isAjax()) {
                throw new \Exception('Shipping: City is missing');
            }
            return $this->httpError(400, 'Shipping: City is missing');
        }

        if (empty($data->shipping->region)) {
            if (!$this->request->isAjax()) {
                throw new \Exception('Shipping: Region/State is missing');
            }
            return $this->httpError(400, 'Shipping: Region/State is missing');
        }

        if (empty($data->shipping->country)) {
            if (!$this->request->isAjax()) {
                throw new \Exception('Shipping: Country is missing');
            }
            return $this->httpError(400, 'Shipping: Country is missing');
        } else {
            if (empty(eCommerce::get_all_countries()[$data->shipping->country])) {
                if (!$this->request->isAjax()) {
                    throw new \Exception('Shipping: Country: ' . $data->shipping->country . ' is not allowed!');
                }
                return $this->httpError(400, 'Shipping: Country: ' . $data->shipping->country . ' is not allowed!');
            }
        }

        if (empty($data->shipping->postcode)) {
            if (!$this->request->isAjax()) {
                throw new \Exception('Shipping: Postcode/ZIP is missing');
            }
            return $this->httpError(400, 'Shipping: Postcode/ZIP is missing');
        }

        if ($check_billing) {
            if (empty($data->billing)) {
                if (!$this->request->isAjax()) {
                    throw new \Exception('Billing section is missing');
                }
                return $this->httpError(400, 'Billing section is missing');
            }

            if (empty($data->billing->firstname)) {
                if (!$this->request->isAjax()) {
                    throw new \Exception('Billing: First name is missing');
                }
                return $this->httpError(400, 'Billing: First name is missing');
            }

            if (empty($data->billing->surname)) {
                if (!$this->request->isAjax()) {
                    throw new \Exception('Billing: Surname is missing');
                }
                return $this->httpError(400, 'Billing: Surname is missing');
            }

            if (empty($data->billing->address)) {
                if (!$this->request->isAjax()) {
                    throw new \Exception('Billing: Address is missing');
                }
                return $this->httpError(400, 'Billing: Address is missing');
            }

            if (empty($data->billing->town)) {
                if (!$this->request->isAjax()) {
                    throw new \Exception('Billing: City is missing');
                }
                return $this->httpError(400, 'Billing: City is missing');
            }

            if (empty($data->billing->region)) {
                if (!$this->request->isAjax()) {
                    throw new \Exception('Billing: Region/State is missing');
                }
                return $this->httpError(400, 'Billing: Region/State is missing');
            }

            if (empty($data->billing->country)) {
                if (!$this->request->isAjax()) {
                    throw new \Exception('Billing: Country is missing');
                }
                return $this->httpError(400, 'Billing: Country is missing');
            } else {
                if (empty(eCommerce::get_all_countries()[$data->billing->country])) {
                    if (!$this->request->isAjax()) {
                        throw new \Exception('Billing: Country "' . $data->billing->country . '" is not allowed!');
                    }
                    return $this->httpError(400, 'Billing: Country "' . $data->billing->country . '" is not allowed!');
                }
            }

            if (empty($data->billing->postcode)) {
                if (!$this->request->isAjax()) {
                    throw new \Exception('Billing: Postcode/ZIP is missing');
                }
                return $this->httpError(400, 'Billing: Postcode/ZIP is missing');
            }
        }
    }
}
