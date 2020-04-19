<?php
namespace Cita\eCommerce\Traits;
use SilverStripe\Forms\Form;
use SilverStripe\Forms\OptionsetField;
use SilverStripe\Forms\TextareaField;
use SilverStripe\Forms\CheckboxField;
use SilverStripe\Forms\HiddenField;
use SilverStripe\Forms\TextField;
use SilverStripe\Forms\FieldList;
use SilverStripe\Forms\FormAction;
use SilverStripe\Forms\LiteralField;
use SilverStripe\Forms\RequiredFields;
use SilverStripe\View\ArrayData;
use Cita\eCommerce\eCommerce;
use Cita\eCommerce\Model\Freight;
use Cita\eCommerce\API\DirectDebit;
use Cita\eCommerce\API\Invoice;
use Cita\eCommerce\API\Paystation;
use Cita\eCommerce\API\POLi;
use Cita\eCommerce\API\DPS;
use Cita\eCommerce\API\Stripe;


trait CartTemplateActions
{
    public function add() { return $this->do_add(); }
    public function update() { return $this->do_update(); }
    public function delete() { return $this->do_delete(); }
    public function estimate_freight() { return $this->do_estimate_freight(); }
    public function coupon_validate() { return $this->do_coupon_validate(); }

    /* ---------------------------------------------------------------------- */

    public function CartUpdateForm()
    {
        $actions    =   FieldList::create(
            FormAction::create('CartUpdateItem')->setTitle('Update'),
            FormAction::create('CartCheckout')->setTitle('Checkout')
        );
        $form       =   Form::create($this, 'CartUpdateForm', null, $actions);

        return $form;
    }

    public function CartCheckout($data, Form $form)
    {
        if (empty($data['ItemID']) || empty($data['Quantity'])) {
            $form->sessionMessage('Your cart is empty!', 'bad');
            return $this->redirectBack();
        }

        $cart   =   eCommerce::get_cart();
        if (!$cart) {
            $form->sessionMessage('Your cart is empty!', 'bad');
            return $this->redirectBack();
        }

        $this->HandleCartUpdate($data, $cart);

        return $this->redirect($this->Link() . 'checkout');
    }

    public function CartUpdateItem($data, Form $form)
    {
        if (empty($data['ItemID']) || empty($data['Quantity'])) {
            $form->sessionMessage('Your cart is empty!', 'bad');
            return $this->redirectBack();
        }

        $cart   =   eCommerce::get_cart();
        if (!$cart) {
            $form->sessionMessage('Your cart is empty!', 'bad');
            return $this->redirectBack();
        }

        $this->HandleCartUpdate($data, $cart);

        $form->sessionMessage('Cart updated', 'good');
        return $this->redirectBack();
    }

    public function HandleCartUpdate(&$data, &$cart)
    {
        $i  =   0;
        foreach ($data['ItemID'] as $ItemID) {
            if ($item = $cart->Items()->byID($ItemID)) {
                $qty            =   $data['Quantity'][$i];
                if (empty($qty)) {
                    $item->delete();
                } else {
                    $item->Quantity =   $qty;
                    $item->write();
                }
            }
            $i++;
        }

        $cart->UpdateAmountWeight();
    }

    public function CheckoutForm()
    {
        $cart       =   eCommerce::get_cart();
        $fields     =   FieldList::create(
            TextField::create('Email', 'Email', $cart ? $cart->Email : null),
            LiteralField::create('ShippingHeader', '<h2 class="title is-4">Shipping</h2>'),
            TextField::create('ShippingFirstname', 'Fist Name', $cart ? $cart->ShippingFirstname : null),
            TextField::create('ShippingSurname', 'Surname', $cart ? $cart->ShippingSurname : null),
            TextField::create('ShippingPhone', 'Phone', $cart ? $cart->ShippingPhone : null),
            TextField::create('ShippingOrganisation', 'Organisation', $cart ? $cart->ShippingOrganisation : null),
            TextField::create('ShippingApartment', 'Apartment, Suite, Flat, etc.', $cart ? $cart->ShippingApartment : null),
            TextField::create('ShippingAddress', 'Address', $cart ? $cart->ShippingAddress : null),
            TextField::create('ShippingSuburb', 'Suburb', $cart ? $cart->ShippingSuburb : null),
            TextField::create('ShippingTown', 'City', $cart ? $cart->ShippingTown : null),
            TextField::create('ShippingRegion', 'Region / State / Province', $cart ? $cart->ShippingRegion : null),
            TextField::create('ShippingCountry', 'Country', $cart ? $cart->ShippingCountry : null),
            TextField::create('ShippingPostcode', 'Postcode / ZIP', $cart ? $cart->ShippingPostcode : null),
            LiteralField::create('BillingHeader', '<h2 class="title is-4">Billing</h2>'),
            CheckboxField::create('SameBilling', 'Billing address is the same as shipping address', $cart ? $cart->SameBilling : null),
            TextField::create('BillingFirstname', 'Fist Name', $cart ? $cart->BillingFirstname : null),
            TextField::create('BillingSurname', 'Surname', $cart ? $cart->BillingSurname : null),
            TextField::create('BillingPhone', 'Phone', $cart ? $cart->BillingPhone : null),
            TextField::create('BillingOrganisation', 'Organisation', $cart ? $cart->BillingOrganisation : null),
            TextField::create('BillingApartment', 'Apartment, Suite, Flat, etc.', $cart ? $cart->BillingApartment : null),
            TextField::create('BillingAddress', 'Address', $cart ? $cart->BillingAddress : null),
            TextField::create('BillingSuburb', 'Suburb', $cart ? $cart->BillingSuburb : null),
            TextField::create('BillingTown', 'City', $cart ? $cart->BillingTown : null),
            TextField::create('BillingRegion', 'Region / State / Province', $cart ? $cart->BillingRegion : null),
            TextField::create('BillingCountry', 'Country', $cart ? $cart->BillingCountry : null),
            TextField::create('BillingPostcode', 'Postcode / ZIP', $cart ? $cart->BillingPostcode : null),
            TextField::create('PromoCode', 'Promo Code', $cart ? $cart->PromoCode : null),
            OptionsetField::create(
                'FreightID',
                'Freight',
                Freight::get()->map(),
                $cart ? $cart->FreightID : (Freight::get()->count() == 1 ? Freight::get()->first()->ID : null)
            ),
            TextareaField::create('Comment', 'Comment', $cart ? $cart->Comment : null),
            LiteralField::create('TotalToPay', '<p>Amount to pay: $' . number_format($cart->PayableTotal, 2) . '</p>')
        );

        $actions    =   FieldList::create(FormAction::create('CalculateFreight')->setTitle('Calculate Shipping')->addExtraClass('button is-warning'));

        if ($cart->Freight()->exists()) {

            $gateways = eCommerce::get_available_payment_methods();

            foreach ($gateways as $name => $title) {
                $actions->push(
                    FormAction::create('Pay' . $name)->setTitle('Pay with ' . $title)
                    ->addExtraClass('button is-info')
                );
            }
        }

        $required   =   RequiredFields::create([
            'Email',
            'ShippingFirstname',
            'ShippingSurname',
            'ShippingPhone',
            'ShippingAddress',
            'ShippingSuburb',
            'ShippingTown',
            'ShippingRegion',
            'ShippingCountry',
            'ShippingPostcode',
        ]);
        $form       =   Form::create($this, 'CheckoutForm', $fields, $actions, $required);

        return $form;
    }

    public function DeleteCartItem($data, Form $form)
    {
        if (($cart = eCommerce::get_cart()) && ($id = $data['action_DeleteCartItem'])) {
            if ($item = $cart->Items()->byID($id)) {
                $item->delete();
                $cart->UpdateAmountWeight();
                $form->sessionMessage('Item removed', 'good');
                return $this->redirectBack();
            }
        }

        $form->sessionMessage('Your cart is empty!', 'bad');
        return $this->redirectBack();
    }

    public function CalculateFreight($data, Form $form)
    {
        if ($cart = eCommerce::get_cart()) {
            $this->SaveCheckoutData($data, $cart);
            $form->sessionMessage('Shipping Calulated. You may now proceed to payment', 'good');
            return $this->redirectBack();
        }

        $form->sessionMessage('Your cart is empty!', 'bad');
        return $this->redirectBack();
    }

    private function SaveCheckoutData(&$data, &$cart)
    {
        $cart->update($data);
        $cart->write();
    }

    public function PayDPS($data, Form $form)
    {
        if ($cart = eCommerce::get_cart()) {
            $this->SaveCheckoutData($data, $cart);
            if ($cart->PayableTotal == 0) {
                $form->sessionMessage('There is nothing to pay!', 'good');
                return $this->redirectBack();
            }

            $url    =   $this->getPaymentURL(DPS::class, $cart);
            return $this->redirect($url['url']);
        }

        $form->sessionMessage('DPS payment: ????', 'good');
        return $this->redirectBack();
    }

    public function PayPaystation($data, Form $form)
    {
        if ($cart = eCommerce::get_cart()) {
            $this->SaveCheckoutData($data, $cart);
            if ($cart->PayableTotal == 0) {
                $form->sessionMessage('There is nothing to pay!', 'good');
                return $this->redirectBack();
            }

            $url    =   $this->getPaymentURL(Paystation::class, $cart);
            return $this->redirect($url['url']);
        }

        $form->sessionMessage('Paystation payment: ????', 'good');
        return $this->redirectBack();
    }

    public function PayPOLi($data, Form $form)
    {
        if ($cart = eCommerce::get_cart()) {
            $this->SaveCheckoutData($data, $cart);
            if ($cart->PayableTotal == 0) {
                $form->sessionMessage('There is nothing to pay!', 'good');
                return $this->redirectBack();
            }

            $url    =   $this->getPaymentURL(POLi::class, $cart);
            return $this->redirect($url['url']);
        }

        $form->sessionMessage('POLi payment: ????', 'good');
        return $this->redirectBack();
    }

    public function PayStripe($data, Form $form)
    {
        if ($cart = eCommerce::get_cart()) {
            $this->SaveCheckoutData($data, $cart);
        }

        $form->sessionMessage('Please implement your JS code to handle Stripe payment', 'good');
        return $this->redirectBack();
    }

    public function TranslateCountry($code)
    {
        return eCommerce::translate_country($code);
    }
}
