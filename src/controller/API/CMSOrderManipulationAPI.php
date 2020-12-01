<?php

namespace Cita\eCommerce\API;

use SilverStripe\Core\Convert;
use SilverStripe\Security\Member;
use SilverStripe\Control\Controller;
use Cita\eCommerce\Model\Order;
use Cita\eCommerce\Model\Variant;
use SilverStripe\Versioned\Versioned;
use Cita\eCommerce\Model\Discount;

class CMSOrderManipulationAPI extends Controller
{
    use APITrait;

    private $order = null;

    private static $allowed_actions = [
        'update_shipping',
        'update_billing',
        'update_item',
        'update_email',
    ];

    protected function handleAction($request, $action)
    {
        $member = Member::currentUser();

        if (!$member || !$member->inGroup('administrators')) {
            return $this->httpError(403, 'You do not have permission!');
        }

        $header = $this->getResponse();

        if (!$request->isAjax()) {
            return $this->httpError(400, 'AJAX access only');
        }

        $id = Convert::raw2sql($request->param('id'));

        if (empty($id)) {
            return $this->httpError(400, 'order id missing');
        }

        $this->order = Order::get()->byID($id);

        if (empty($this->order)) {
            return $this->httpError(404, 'No such order');
        }

        if (in_array($action, static::$allowed_actions)) {
            return $this->json($this->$action($request));
        }

        return $this->httpError(404, 'not allowed');
    }

    public function update_email(&$request)
    {
        if (!$request->isPost()) {
            return $this->httpError(400, 'Wrong method');
        }

        $email = Convert::raw2sql($request->postVar('email'));

        if (empty($email)) {
            return $this->httpError(400, 'Missing email');
        }

        $this->order->Email = $email;
        $this->order->write();

        return $this->order->VueUIData;
    }

    public function update_item(&$request)
    {
        if (!$request->isPost()) {
            return $this->httpError(400, 'Wrong method');
        }

        $vid = Convert::raw2sql($request->postVar('vid'));
        if (empty($vid)) {
            return $this->httpError(400, 'Missing variant id');
        }

        $status = Convert::raw2sql($request->postVar('delivered'));
        $qty = Convert::raw2sql($request->postVar('qty'));

        $data = [];

        if (!empty($status)) {
            $data = array_merge($data, ['Delivered' => $status]);
        }

        if (!empty($qty)) {
            $qty = (int) $qty;
            $data = array_merge($data, ['Quantity' => $qty]);
        } elseif ($qty == 0) {
            return $this->httpError(400, 'You cannot set this to 0');
        }

        $this->order->Variants()->add($vid, $data);
    }

    public function update_shipping(&$request)
    {
        if (!$request->isPost()) {
            return $this->httpError(400, 'Wrong method');
        }

        $this->order->ShippingFirstname = Convert::raw2sql($request->postVar('firstname'));
        $this->order->ShippingSurname = Convert::raw2sql($request->postVar('surname'));
        $this->order->ShippingAddress = Convert::raw2sql($request->postVar('address'));
        $this->order->ShippingOrganisation = Convert::raw2sql($request->postVar('org'));
        $this->order->ShippingApartment = Convert::raw2sql($request->postVar('apartment'));
        $this->order->ShippingSuburb = Convert::raw2sql($request->postVar('suburb'));
        $this->order->ShippingTown = Convert::raw2sql($request->postVar('town'));
        $this->order->ShippingRegion = Convert::raw2sql($request->postVar('region'));
        $this->order->ShippingCountry = Convert::raw2sql($request->postVar('country_code'));
        $this->order->ShippingPostcode = Convert::raw2sql($request->postVar('postcode'));
        $this->order->ShippingPhone = Convert::raw2sql($request->postVar('phone'));

        $this->order->write();

        return $this->order->VueUIData;
    }

    public function update_billing(&$request)
    {
        if (!$request->isPost()) {
            return $this->httpError(400, 'Wrong method');
        }

        $same_addr = Convert::raw2sql($request->postVar('same_addr'));

        if ($same_addr) {
            $this->order->SameBilling = true;
            $this->order->BillingFirstname = $this->order->ShippingFirstname;
            $this->order->BillingSurname = $this->order->ShippingSurname;
            $this->order->BillingAddress = $this->order->ShippingAddress;
            $this->order->BillingOrganisation = $this->order->ShippingOrganisation;
            $this->order->BillingApartment = $this->order->ShippingApartment;
            $this->order->BillingSuburb = $this->order->ShippingSuburb;
            $this->order->BillingTown = $this->order->ShippingTown;
            $this->order->BillingRegion = $this->order->ShippingRegion;
            $this->order->BillingCountry = $this->order->ShippingCountry;
            $this->order->BillingPostcode = $this->order->ShippingPostcode;
            $this->order->BillingPhone = $this->order->ShippingPhone;
        } else {
            $this->order->SameBilling = false;
            $this->order->BillingFirstname = Convert::raw2sql($request->postVar('firstname'));
            $this->order->BillingSurname = Convert::raw2sql($request->postVar('surname'));
            $this->order->BillingAddress = Convert::raw2sql($request->postVar('address'));
            $this->order->BillingOrganisation = Convert::raw2sql($request->postVar('org'));
            $this->order->BillingApartment = Convert::raw2sql($request->postVar('apartment'));
            $this->order->BillingSuburb = Convert::raw2sql($request->postVar('suburb'));
            $this->order->BillingTown = Convert::raw2sql($request->postVar('town'));
            $this->order->BillingRegion = Convert::raw2sql($request->postVar('region'));
            $this->order->BillingCountry = Convert::raw2sql($request->postVar('country_code'));
            $this->order->BillingPostcode = Convert::raw2sql($request->postVar('postcode'));
            $this->order->BillingPhone = Convert::raw2sql($request->postVar('phone'));
        }

        $this->order->write();

        return $this->order->VueUIData;
    }
}
