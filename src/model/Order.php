<?php

namespace Cita\eCommerce\Model;

use SilverStripe\Dev\Debug;
use Cita\eCommerce\eCommerce;
use SilverStripe\SiteConfig\SiteConfig;
use SilverStripe\ORM\DataObject;
use SilverStripe\Forms\TextField;
use SilverStripe\Forms\TextareaField;
use SilverStripe\Security\Member;
use Cita\eCommerce\Model\OrderItem;
use Cita\eCommerce\Model\Customer;
use Cita\eCommerce\Model\Variant;
use SilverStripe\Core\Config\Config;
use Cita\eCommerce\API\DPS;
use Cita\eCommerce\API\Poli;
use Cita\eCommerce\API\Paystation;
use Cita\eCommerce\Model\Freight;
use SilverStripe\Control\Cookie;
use Konekt\PdfInvoice\InvoicePrinter;
use SilverStripe\Control\Email\Email;
use SilverStripe\Control\Director;
use Leochenftw\Grid;
use Dynamic\CountryDropdownField\Fields\CountryDropdownField;
use SilverStripe\Omnipay\Model\Payment;
use Cita\eCommerce\Model\Bundle;
use Leochenftw\Debugger;

/**
 * Description
 *
 * @package silverstripe
 * @subpackage mysite
 */
class Order extends DataObject
{
    /**
     * Defines the database table name
     * @var string
     */
    private static $table_name = 'Cita_eCommerce_Order';
    /**
     * Database fields
     * @var array
     */
    private static $db = [
        'MerchantReference'         =>  'Varchar(64)',
        'CustomerReference'         =>  'Varchar(8)',
        'Status'                    =>  'Enum("Pending,Invoice Pending,Debit Pending,Payment Received,Shipped,Cancelled,Refunded,CardCreated,Completed,Free Order")',
        'AnonymousCustomer'         =>  'Varchar(128)',
        'TotalAmount'               =>  'Currency',
        'DiscountableTaxable'       =>  'Currency',
        'DiscountableNonTaxable'    =>  'Currency',
        'NonDiscountableTaxable'    =>  'Currency',
        'NonDiscountableNonTaxable' =>  'Currency',
        'TaxIncludedTotal'          =>  'Currency',
        'TotalWeight'               =>  'Decimal',
        'PayableTotal'              =>  'Currency',
        'Email'                     =>  'Varchar(256)',
        'ShippingFirstname'         =>  'Varchar(128)',
        'ShippingSurname'           =>  'Varchar(128)',
        'ShippingAddress'           =>  'Text',
        'ShippingOrganisation'      =>  'Varchar(128)',
        'ShippingApartment'         =>  'Varchar(64)',
        'ShippingSuburb'            =>  'Varchar(128)',
        'ShippingTown'              =>  'Varchar(128)',
        'ShippingRegion'            =>  'Varchar(128)',
        'ShippingCountry'           =>  'Varchar(128)',
        'ShippingPostcode'          =>  'Varchar(128)',
        'ShippingPhone'             =>  'Varchar(128)',
        'SameBilling'               =>  'Boolean',
        'BillingFirstname'          =>  'Varchar(128)',
        'BillingSurname'            =>  'Varchar(128)',
        'BillingAddress'            =>  'Text',
        'BillingOrganisation'       =>  'Varchar(128)',
        'BillingApartment'          =>  'Varchar(64)',
        'BillingSuburb'             =>  'Varchar(128)',
        'BillingTown'               =>  'Varchar(128)',
        'BillingRegion'             =>  'Varchar(128)',
        'BillingCountry'            =>  'Varchar(128)',
        'BillingPostcode'           =>  'Varchar(128)',
        'BillingPhone'              =>  'Varchar(128)',
        'Comment'                   =>  'Text',
        'TrackingNumber'            =>  'Varchar(128)',
        'ShippingCost'              =>  'Currency'
    ];

    private static $indexes = [
        'MerchantReference' =>  true,
        'CustomerReference' =>  true
    ];

    private static $cascade_deletes = [
        'Items',
        'Payments'
    ];

    /**
     * Defines summary fields commonly used in table columns
     * as a quick overview of the data for this dataobject
     * @var array
     */
    private static $summary_fields = [
        'ID'        =>  'ID',
        'ItemCount' =>  'Item(s)',
        'Status'    =>  'Status'
    ];

    /**
     * Defines a default list of filters for the search context
     * @var array
     */
    private static $searchable_fields = [
        'MerchantReference',
        'CustomerReference',
        'Status'
    ];

    /**
     * Has_one relationship
     * @var array
     */
    private static $has_one = [
        'Discount'  =>  Discount::class,
        'Customer'  =>  Customer::class,
        'Freight'   =>  Freight::class
    ];

    /**
     * Default sort ordering
     * @var array
     */
    private static $default_sort = ['ID' => 'DESC'];

    public function populateDefaults()
    {
        $this->SameBilling  =   true;
        $member             =   Member::currentUser();
        $cookie             =   Cookie::get('eCommerceCookie');

        if (empty($cookie)) {
            $cookie =   session_id();
            if (empty($cookie)) {
                session_start();
                $cookie =   session_id();
            }
            Cookie::set('eCommerceCookie', $cookie, $expiry = 30);
        }

        if (!empty($member) && $member->ClassName == Customer::class) {
            $this->CustomerID           =   $member->ID;
        } else {
            $this->AnonymousCustomer    =   $cookie;
        }

        $this->MerchantReference    =   sha1(md5(round(microtime(true) * 1000) . '-' . session_id()));
        $this->CustomerReference    =   strtoupper(substr($this->MerchantReference, 0, 8));

        if ($member && ($group = $member->Groups()->first())) {
            if ($group->Discount()->exists()) {
                $this->DiscountID   =   $group->DiscountID;
            }
        }
    }

    /**
     * Has_many relationship
     * @var array
     */
    private static $has_many = [
        'Items'     =>  OrderItem::class,
        'Payments'  =>  Payment::class,
        'Messages'  =>  OrderMessage::class
    ];

    private static $many_many = [
        'Variants' => Variant::class
    ];

    private static $many_many_extraFields = [
        'Variants' => [
            'Quantity' => 'Int'
        ]
    ];

    public function onAfterWrite()
    {
        parent::onAfterWrite();
        $inCartVariants = [];
        foreach ($this->Items() as $item) {
            if ($item->Variant()->exists()) {
                $variant = $item->Variant();
                $inCartVariants[] = $variant->ID;
                $this->Variants()->add($variant, ['Quantity' => $item->Quantity]);
            }
        }

        $to_remove = array_diff($this->Variants()->column('ID'), $inCartVariants);

        foreach ($to_remove as $id) {
            $this->Variants()->removeByID($id);
        }
    }

    public function getSuccessPayment()
    {
        if ($this->exists() && $this->Payments()->exists()) {
            return $this->Payments()->filter(['Status' => 'Captured'])->first();
        }

        return null;
    }

    /**
     * CMS Fields
     * @return FieldList
     */
    public function getCMSFields()
    {
        $fields =   parent::getCMSFields();

        if ($this->exists()) {
            $fields->removeByName([
                'Items'
            ]);

            $fields->addFieldToTab(
                'Root.OrderItems',
                Grid::make('Items', 'Order Items', $this->Items(), false, 'GridFieldConfig_RecordViewer')
            );
        }

        $fields->addFieldsToTab(
            'Root.Shipping',
            [
                TextField::create('ShippingFirstname', 'First Name'),
                TextField::create('ShippingSurname', 'Surname'),
                TextField::create('ShippingOrganisation', 'Organisation'),
                TextField::create('ShippingApartment', 'Apartment'),
                TextareaField::create('ShippingAddress', 'Address'),
                TextField::create('ShippingSuburb', 'Suburb'),
                TextField::create('ShippingTown', 'Town'),
                TextField::create('ShippingRegion', 'Region'),
                CountryDropdownField::create('ShippingCountry', 'Country')->setEmptyString('- select one -'),
                TextField::create('ShippingPostcode', 'Postcode'),
                TextField::create('ShippingPhone', 'Phone'),
            ]
        );

        $fields->addFieldsToTab(
            'Root.Billing',
            [
                TextField::create('BillingFirstname', 'First Name'),
                TextField::create('BillingSurname', 'Surname'),
                TextField::create('BillingOrganisation', 'Organisation'),
                TextField::create('BillingApartment', 'Apartment'),
                TextareaField::create('BillingAddress', 'Address'),
                TextField::create('BillingSuburb', 'Suburb'),
                TextField::create('BillingTown', 'Town'),
                TextField::create('BillingRegion', 'Region'),
                CountryDropdownField::create('BillingCountry', 'Country')->setEmptyString('- select one -'),
                TextField::create('BillingPostcode', 'Postcode'),
                TextField::create('BillingPhone', 'Phone'),
            ]
        );

        $fields->addFieldsToTab(
            'Root.Freight & Tracking',
            [
                $fields->fieldByName('Root.Main.FreightID')->setTitle('Freight Provider'),
                $tracking_field =   TextField::create('TrackingNumber', 'Tracking Number')
            ]
        );

        if (empty($this->TrackingNumber) || empty($this->FreightID)) {
            $tracking_field->setDescription('To send tracking number to the customer, please choose a freight provide, fill the tracking number, and then Apply Changes. <br />You will see the button after page refresh');
        }

        $frozen =   $fields->fieldByName('Root.Main.Status')->performReadonlyTransformation();
        $fields->replaceField('Status', $frozen);

        return $fields;
    }

    protected function prep_pdf()
    {
        $siteconfig =   SiteConfig::current_site_config();
        $payment    =   $this->getSuccessPayment();

        $invoice = new InvoicePrinter();
        if ($siteconfig->StoreLogo()->exists()) {
            $invoice->setLogo($siteconfig->StoreLogo()->getAbsoluteURL());   //logo image path
        }

        $invoice->setColor("#000000");      // pdf color scheme
        $invoice->setType("Sale Invoice");    // Invoice Type
        $invoice->setReference("Invoice#" . $this->ID);   // Reference
        $invoice->setDate(date('d/m/Y',time()));   //Billing Date
        $invoice->setTime(date('h:i:s A',time()));   //Billing Time

        $billing_from   =   [];
        if (!empty($siteconfig->TradingName)) {
            $billing_from[] =   $siteconfig->TradingName;
        }

        if (!empty($siteconfig->GST)) {
            $billing_from[] =   'GST Number: ' . $siteconfig->GST;
        }

        if (!empty($siteconfig->StoreLocation)) {
            $chunks         =   explode("\n", $siteconfig->StoreLocation);
            $billing_from   =   array_merge($billing_from, $chunks);
        }

        if (!empty($siteconfig->ContactEmail)) {
            $billing_from[] =   $siteconfig->ContactEmail . (
                !empty($siteconfig->ContactNumber) ?
                (', ' . $siteconfig->ContactNumber) : ''
            );
        }

        $billing_to         =   [];
        $billing_to[]       =   trim((
            !empty($this->BillingFirstname) ?
            $this->BillingFirstname : '') . ' ' . (!empty($this->BillingSurname) ? $this->BillingSurname : ''));

        if (!empty($this->BillingOrganisation)) {
            $billing_to[]   =   $this->BillingOrganisation;
        }

        if (!empty($this->BillingApartment)) {
            $billing_to[]   =   $this->BillingApartment;
        }

        if (!empty($this->BillingAddress)) {
            $billing_to[]   =   $this->BillingAddress;
        }

        if (!empty($this->BillingSuburb)) {
            $billing_to[]   =   $this->BillingSuburb;
        }

        if (!empty($this->BillingTown)) {
            $billing_to[]   =   $this->BillingTown . (!empty($this->BillingRegion) ? (', ' . $this->BillingRegion) : '');
        }

        if (!empty($this->BillingCountry)) {
            $billing_to[]   =   $this->BillingCountry . (!empty($this->BillingPostcode) ? (', ' . $this->BillingPostcode) : '');
        }

        $billing_to[]   =   $this->Email . (!empty($this->BillingPhone) ? (', ' . $this->BillingPhone) : '');

        $size           =   count($billing_from) > count($billing_to) ? count($billing_from) : count($billing_to);

        $billing_from   =   array_pad($billing_from, $size, '');
        $billing_to     =   array_pad($billing_to, $size, '');

        $invoice->setFrom($billing_from);
        $invoice->setTo($billing_to);

        $this->extend('createInvoiceRows', $invoice);

        if ($payment) {
            $invoice->addBadge("Payment Received");
        } else {
            $invoice->addBadge("Payment Outstanding");
            $invoice->addTitle("Cheque payment");
        }

        $this->extend('createInvoiceParagraph', $invoice);

        $invoice->setFooternote(SiteConfig::current_site_config()->Title);

        return $invoice;
    }

    public function download_invoice()
    {
        $invoice    =   $this->prep_pdf();
        $siteconfig =   SiteConfig::current_site_config();

        return  $invoice->render($siteconfig->TradingName . ' Invoice #' . $this->ID . '.pdf', 'D');
    }

    public function send_invoice()
    {
        $siteconfig =   SiteConfig::current_site_config();
        $invoice    =   $this->prep_pdf();
        $str        =   $invoice->render($siteconfig->TradingName . ' Invoice #' . $this->ID . '.pdf','S');
        $from       =   Config::inst()->get(Email::class, 'noreply_email');
        $to         =   $this->Email;

        if (!empty($to)) {
            $customer_sent_flag =   ['sent' => false];
            $this->extend('SendCustomerEmail', $from, $to, $str, $customer_sent_flag);

            if (!$customer_sent_flag['sent']) {
                $subject    =   $siteconfig->Title . ': order invoice #' . $this->ID;
                $email      =   Email::create($from, $to, $subject);
                $email->setBody('Hi, <br /><br />Please find your order invoice in the attachment.<br /><br />Kind regards<br />' . $siteconfig->Title . ' team');

                $email->addAttachmentFromData($str, $siteconfig->TradingName . ' Invoice #' . $this->ID . '.pdf');
                $email->send();
            }
        }

        $admin_sent_flag    =   ['sent' => false];
        $to_admin           =   !empty($siteconfig->OrderEmail) ? explode(',', $siteconfig->OrderEmail) : $siteconfig->ContactEmail;

        if (!empty($to_admin)) {
            $this->extend('SendAdminEmail', $from, $to_admin, $str, $admin_sent_flag);
            if (!$admin_sent_flag['sent']) {
                $admin_email    =   Email::create($from, $to_admin, $siteconfig->TradingName . ': New order received (#' . $this->ID . ')');

                if (!empty($siteconfig->InvoiceBccEmail)) {
                    $admin_email->setBCC($siteconfig->InvoiceBccEmail);
                }

                $admin_email->setBody('Hi, <br /><br />There is a new order. Please <a target="_blank" href="' . Director::absoluteBaseURL() .  'admin/orders/Cita-eCommerce-Model-Order/EditForm/field/Cita-eCommerce-Model-Order/item/' . $this->ID . '/edit' . '">click here</a> to view the details. <br /><br />' . $siteconfig->TradingName);

                $admin_email->send();
            }
        }
    }

    public function ItemCount()
    {
        $items  =   $this->Items();
        $n      =   0;
        foreach ($items as $item) {
            $n  +=  $item->Quantity;
        }
        return $n;
    }

    public function ShippableItemCount()
    {
        $items  =   $this->Items()->filter(['isDigital' => false]);
        $n      =   0;
        foreach ($items as $item) {
            $n  +=  $item->Quantity;
        }
        return $n;
    }

    public function UpdateAmountWeight()
    {
        $amount         =   0;
        $weight         =   0;
        $distax         =   0;
        $nondistax      =   0;
        $disnontax      =   0;
        $nondisnontax   =   0;
        $taxincluded    =   0;

        foreach ($this->Items() as $item) {
            $amount     +=  $item->Subtotal;
            $weight     +=  $item->Subweight;

            if ($item->NoDiscount) {
                if ($item->isExempt || $item->GSTIncluded) {
                    $nondisnontax   +=  $item->Subtotal;
                } else {
                    $nondistax      +=  $item->Subtotal;
                }
            } else {
                if ($item->isExempt || $item->GSTIncluded) {
                    $disnontax      +=  $item->Subtotal;
                } else {
                    $distax         +=  $item->Subtotal;
                }
            }

            if ($item->GSTIncluded) {
                $taxincluded += $item->Subtotal;
            }
        }

        $this->TotalAmount                  =   $amount;
        $this->TotalWeight                  =   $weight;
        $this->DiscountableTaxable          =   $distax;
        $this->DiscountableNonTaxable       =   $disnontax;
        $this->NonDiscountableTaxable       =   $nondistax;
        $this->NonDiscountableNonTaxable    =   $nondisnontax;
        $this->TaxIncludedTotal             =   $taxincluded;

        $this->PayableTotal                 =   $this->CalculatePayableTotal();

        $this->extend('updateOrderFields', $this);
        $this->write();
    }

    public function completePayment($status)
    {
        if ($this->Status == 'Payment Received' || $this->Status == 'Shipped' || $this->Status == 'Cancelled' || $this->Status == 'Refunded' || $this->Status == 'Completed') return false;
        if ($status != 'CardCreated' && $status != 'Captured' && $status != 'Invoice Pending' && $status != 'Debit Pending' && $status != 'Free Order') return false;

        if ($status == 'Success' || $status == 'Captured') {
            $this->Status   =   'Payment Received';
        } else {
            $this->Status   =   $status;
        }

        $this->write();

        foreach ($this->Items() as $item) {
            $item->FreezePrice();
        }

        if ($this->Status == 'Payment Received') {
            if ($this->Discount()->exists()) {
                $discount   =   $this->Discount();

                if (!$discount->InfiniteUse) {
                    $discount->LifePoint--;
                }

                $discount->write();
            }

            $this->extend('doPaymentSuccessAction', $this);
            $this->send_invoice();
        }
    }

    /**
     * Event handler called before writing to the database.
     */
    public function onBeforeWrite()
    {
        parent::onBeforeWrite();

        if (empty($this->CustomerReference)) {
            $this->CustomerReference    =   strtoupper(substr($this->MerchantReference, 0, 8));
        }

        if (!$this->Customer()->exists() && Member::currentUser()) {
            if (Member::currentUser()->ClassName == Customer::class) {
                $this->CustomerID           =   Member::currentUser()->ID;
            }
        }

        if ($this->Status == 'Pending') {
            if ($this->SameBilling) {
                $this->BillingFirstname    =   $this->ShippingFirstname;
                $this->BillingSurname      =   $this->ShippingSurname;
                $this->BillingOrganisation =   $this->ShippingOrganisation;
                $this->BillingApartment    =   $this->ShippingApartment;
                $this->BillingAddress      =   $this->ShippingAddress;
                $this->BillingSuburb       =   $this->ShippingSuburb;
                $this->BillingTown         =   $this->ShippingTown;
                $this->BillingRegion       =   $this->ShippingRegion;
                $this->BillingCountry      =   $this->ShippingCountry;
                $this->BillingPostcode     =   $this->ShippingPostcode;
                $this->BillingPhone        =   $this->ShippingPhone;
            }
        }
    }

    public function getDiscounted()
    {
        $dt     =   $this->DiscountableTaxable;
        $dnt    =   $this->DiscountableNonTaxable;
        return $this->Discount()->calc_discount($dt + $dnt);
    }

    public function getGST()
    {
        $dt = $this->DiscountableTaxable;
        $ndt = $this->NonDiscountableTaxable;
        $gst_rate = SiteConfig::current_site_config()->GSTRate;

        $discounted_taxable = $this->Discount()->exists() ? $this->Discount()->calc_discount($dt) : 0;

        $gst_base = $dt - $discounted_taxable + $ndt;
        $gst = $gst_base * $gst_rate;

        return number_format($gst, 2);
    }

    public function getIncludedGST()
    {
        $gst_rate = SiteConfig::current_site_config()->GSTRate;
        $includedGST = $this->TaxIncludedTotal * $gst_rate / (1 + $gst_rate);

        return number_format($includedGST, 2);
    }

    public function send_tracking()
    {
        if ($this->Status == 'Payment Received') {
            $this->Status = 'Shipped';
            $this->write();
        }

        $siteconfig =   SiteConfig::current_site_config();
        $from       =   Config::inst()->get(Email::class, 'noreply_email');
        $to         =   $this->Email;

        if (!empty($to)) {
            $customer_sent_flag =   ['sent' => false];
            $this->extend('SendTracking', $from, $to, $customer_sent_flag);

            if (!$customer_sent_flag['sent']) {
                $subject = $siteconfig->Title . ': order #' . $this->ID . ' has been dispatched';
                $email = Email::create($from, $to, $subject);
                $email->setBody('Hi, <br /><br />The trakcing number for your order #' . $this->ID . ' is: ' . $this->TrackingNumber . '.<br /><br />Kind regards<br />' . $siteconfig->Title . ' team');
                $email->send();
            }
        }
    }

    public function refund()
    {
        if ($this->Status == 'Payment Received' ||
            $this->Status == 'Shipped' ||
            $this->Status == 'Completed'
        ) {
            $this->Status  =   'Refunded';
            $this->write();
        }

        $this->extend('doRefund');
    }

    public function cheque_cleared()
    {
        if ($this->exists() && $this->Payments()->exists()) {
            $payment            =   $this->Payments()->filter(['Status' => 'Invoice Pending'])->first();
            $payment->Status    =   'Success';
            $payment->write();
        }

        $this->completePayment('Success');
    }

    public function debit_cleared()
    {
        if ($this->exists() && $this->Payments()->exists()) {
            $payment            =   $this->Payments()->filter(['Status' => 'Debit Pending'])->first();
            $payment->Status    =   'Success';
            $payment->write();
        }

        $this->completePayment('Success');
    }

    public function is_freeshipping()
    {
        if ($this->Items()->count() == 0) {
            return true;
        }

        $n  =   0;
        foreach ($this->Items() as $item) {
            if ($item->isDigital) {
                $n++;
            }
        }

        return $n == $this->Items()->count();
    }

    public function add_to_cart($id, $qty)
    {
        $existing_item = $this->Items()->filter(['VariantID' => $id])->first();

        if (!empty($existing_item)) {
            $existing_item->Quantity    +=  $qty;
            $existing_item->write();
        } else {
            $item = OrderItem::create();
            $item->VariantID = $id;
            $item->Quantity = $qty;
            $item->OrderID = $this->ID;
            $item->write();
        }

        $this->UpdateAmountWeight();

        return $this->getData();
    }

    public function CheckOrderRoutine()
    {
        // scan the order items, just in case the product has been deleted
        $count = $this->Items()->count();
        $iterator = $this->Items()->getIterator();
        $removed = [];

        foreach ($iterator as $item) {
            $goods = $item->Bundle()->exists() ? $item->Bundle() : $item->Variant();

            if (!$goods->exists() || $goods->isSoldout) {
                $removed[] = "<strong>$item->Title</strong>";
                $item->delete();
            } else {
                $item->write();
            }
        }

        if (!empty($removed)) {
            $removed = implode(', ', $removed);
            $this->Log("<p>The following item(s) has removed due to out-of-stock: $removed</p>");
        }

        $this->UpdateAmountWeight();

        // check bundle
        $bundle = Bundle::MatchBundle($this);

        // bundle and discount item count type cannot be used together!
        if ($bundle) {
            return;
        }

        if ($discount = Discount::get()->filter(['Type' => 'Item Count'])->first()) {
            if ($discount->CheckOrder($this)) {
                $this->DiscountID = $discount->ID;
            } else {
                $this->DiscountID = 0;
            }

            $this->UpdateAmountWeight();
        }
    }

    public function Log($message, $admin = false)
    {
        OrderMessage::create([
            'Message' => $message,
            'AdminUse' => $admin,
            'OrderID' => $this->ID
        ])->write();
    }

    public function getData()
    {
        if ($this->Status == 'Pending') {
            $this->CheckOrderRoutine();
        }

        $amount = $this->TotalAmount;
        $gst = $this->GST;
        $data   =   [
            'id'    =>  $this->ID,
            'ref'   =>  $this->CustomerReference,
            'count'         =>  $this->ItemCount(),
            'messages'      =>  $this->Messages()->filter(['Displayed' => false, 'AdminUse' => false])->getData(),
            'items'         =>  $this->Items()->sort(['Created' => 'DESC'])->getData(),
            'amount'        =>  $amount,
            'amounts'       =>  [
                'discountable_taxable' => $this->DiscountableTaxable,
                'discountable_nontaxable' => $this->DiscountableNonTaxable,
                'nondiscountable_taxable' => $this->NonDiscountableTaxable,
                'nondiscountable_nontaxable' => $this->NonDiscountableNonTaxable,
                'gst_included_amount' => $this->TaxIncludedTotal
            ],
            'gst'           =>  $gst,
            'gst_included'  =>  $this->IncludedGST,
            'grand_total'   =>  $gst + $amount,
            'weight'        =>  $this->TotalWeight,
            'comment'       =>  $this->Comment,
            'discount'      =>  $this->Discount()->getData(),
            'shipping_cost' =>  $this->ShippingCost
        ];

        if ($this->Discount()->exists()) {
            $dt     =   $this->DiscountableTaxable;
            $dnt    =   $this->DiscountableNonTaxable;
            $data['discount']['amount'] =   $this->getDiscounted();
        }

        $this->extend('getData', $data);

        return $data;
    }

    public function digest(&$data, $cal_freight = true)
    {
        $this->Email                    =   $data->email;
        $this->FreightID                =   $data->freight;
        $this->ShippingFirstname        =   $data->shipping->firstname;
        $this->ShippingSurname          =   $data->shipping->surname;
        $this->ShippingAddress          =   $data->shipping->address;
        $this->ShippingOrganisation     =   $data->shipping->org;
        $this->ShippingApartment        =   $data->shipping->apartment;
        $this->ShippingSuburb           =   $data->shipping->suburb;
        $this->ShippingTown             =   $data->shipping->town;
        $this->ShippingRegion           =   $data->shipping->region;
        $this->ShippingCountry          =   $data->shipping->country;
        $this->ShippingPostcode         =   $data->shipping->postcode;
        $this->ShippingPhone            =   $data->shipping->phone;
        $this->SameBilling              =   $data->same_addr;

        if (!$data->same_addr) {
            $this->BillingFirstname     =   $data->billing->firstname;
            $this->BillingSurname       =   $data->billing->surname;
            $this->BillingAddress       =   $data->billing->address;
            $this->BillingOrganisation  =   $data->billing->org;
            $this->BillingApartment     =   $data->billing->apartment;
            $this->BillingSuburb        =   $data->billing->suburb;
            $this->BillingTown          =   $data->billing->town;
            $this->BillingRegion        =   $data->billing->region;
            $this->BillingCountry       =   $data->billing->country;
            $this->BillingPostcode      =   $data->billing->postcode;
            $this->BillingPhone         =   $data->billing->phone;
        }

        if (!empty($data->discount)) {
            if ($discount = Discount::check_valid($data->discount->code)) {
                $this->DiscountID       =   $discount->ID;
            } else {
                $this->DiscountID       =   0;
            }
        } else {
            $this->DiscountID           =   0;
        }

        $this->Comment                  =   $data->comment;

        if ($cal_freight) {
            if ($freight = $this->get_freight_data()) {
                if (is_array($freight)) {
                    $this->ShippingCost         =   $freight['cost'];
                }
            } else {
                $this->ShippingCost         =   0;
            }
        }

        $this->UpdateAmountWeight();
    }

    public function get_freight_data()
    {
        if ($this->is_freeshipping()) {
            return [
                'title' => 'Free shipping',
                'cost' => 0
            ];
        } elseif ($this->Freight()->exists()) {
            $freight = $this->Freight();
            try {
                $result = $freight->Calculate($this);
                return $result;
            } catch (\Exception $e) {

            }
        }

        return null;
    }

    public function CalculatePayableTotal()
    {
        $dt         =   $this->DiscountableTaxable;
        $dnt        =   $this->DiscountableNonTaxable;
        $ndt        =   $this->NonDiscountableTaxable;
        $ndnt       =   $this->NonDiscountableNonTaxable;
        $gst_rate   =   SiteConfig::current_site_config()->GSTRate;
        $shipping   =   $this->ShippingCost;

        $discounted_taxable     =   $this->Discount()->exists() ? $this->Discount()->calc_discount($dt) : 0;
        $nondiscounted_taxable  =   $this->Discount()->exists() ? $this->Discount()->calc_discount($dnt) : 0;
        $discounted             =   $this->Discount()->exists() ? $this->Discount()->calc_discount($dt + $dnt) : 0;

        $gst_base   =   $dt - $discounted_taxable + $ndt;
        $gst        =   $gst_base * $gst_rate;

        return $this->TotalAmount - $discounted + $gst + $shipping;
    }

    public function getShippingData($translate_country = true)
    {
        return [
            'firstname' =>  $this->ShippingFirstname,
            'surname'   =>  $this->ShippingSurname,
            'org'       =>  $this->ShippingOrganisation,
            'address'   =>  $this->ShippingAddress,
            'apartment' =>  $this->ShippingApartment,
            'suburb'    =>  $this->ShippingSuburb,
            'town'      =>  $this->ShippingTown,
            'region'    =>  $this->ShippingRegion,
            'country'   =>  $translate_country ?
                            eCommerce::translate_country($this->ShippingCountry) :
                            $this->ShippingCountry,
            'postcode'  =>  $this->ShippingPostcode,
            'phone'     =>  $this->ShippingPhone
        ];
    }

    public function getBillingData($translate_country = true)
    {
        return [
            'firstname' =>  $this->BillingFirstname,
            'surname'   =>  $this->BillingSurname,
            'org'       =>  $this->BillingOrganisation,
            'address'   =>  $this->BillingAddress,
            'apartment' =>  $this->BillingApartment,
            'suburb'    =>  $this->BillingSuburb,
            'town'      =>  $this->BillingTown,
            'region'    =>  $this->BillingRegion,
            'country'   =>  $translate_country ?
                            eCommerce::translate_country($this->BillingCountry) :
                            $this->BillingCountry,
            'postcode'  =>  $this->BillingPostcode,
            'phone'     =>  $this->BillingPhone
        ];
    }
}
