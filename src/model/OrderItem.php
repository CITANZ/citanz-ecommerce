<?php

namespace Cita\eCommerce\Model;
use SilverStripe\ORM\DataObject;
use SilverStripe\Security\Member;
use Cita\eCommerce\Extension\ProductOrderItemCommonFields;

/**
 * Description
 *
 * @package silverstripe
 * @subpackage mysite
 */
class OrderItem extends DataObject
{
    /**
     * Defines the database table name
     * @var string
     */
    private static $table_name = 'Cita_eCommerce_OrderItem';
    /**
     * Database fields
     * @var array
     */
    private static $db = [
        'Title'         =>  'Text',
        'Quantity'      =>  'Decimal',
        'Subtotal'      =>  'Currency', // this is the product price x qty
        'Subweight'     =>  'Decimal',
        'isRefunded'    =>  'Boolean',
        'UnitPriceUponPayment' => 'Currency',
    ];

    /**
     * Defines extension names and parameters to be applied
     * to this object upon construction.
     * @var array
     */
    private static $extensions = [
        ProductOrderItemCommonFields::class
    ];

    /**
     * Defines a default list of filters for the search context
     * @var array
     */
    private static $searchable_fields = [
        'ID'
    ];

    /**
     * Has_one relationship
     * @var array
     */
    private static $has_one = [
        'Variant'   =>  Variant::class,
        'Bundle'    =>  Bundle::class,
        'Order'     =>  Order::class
    ];

    public function populateDefaults()
    {
        $this->Quantity =   1;
    }

    /**
     * Defines summary fields commonly used in table columns
     * as a quick overview of the data for this dataobject
     * @var array
     */
    private static $summary_fields = [
        'Title' => 'Title',
        'UnitPriceLabel' => 'Unit Price',
        'Quantity' => 'Quantity',
        'Subtotal' => 'Subtotal'
    ];

    public function getSKU()
    {
        if ($this->Bundle()->exists()) {
            return $this->Bundle()->SKU;
        }

        if ($this->Variant()->exists()) {
            return $this->Variant()->SKU;
        }

        return 'DELETED-ITEM-SKU';
    }

    public function getUnitPriceLabel()
    {
        return '$' . number_format($this->UnitPrice, 2);
    }

    public function getUnitPrice()
    {
        if (!empty($this->UnitPriceUponPayment)) {
            return $this->UnitPriceUponPayment;
        }

        if ($this->Bundle()->exists()) {
            return $this->Bundle()->BundledPrice;
        }

        return  $this->Variant()->exists() ? $this->Variant()->Price : 0;
    }

    public function FreezePrice()
    {
        $this->UnitPriceUponPayment = $this->UnitPrice;
        $this->write();
    }

    public function getData()
    {
        $data = [
            'id'            =>  $this->ID,
            'quantity'      =>  $this->Quantity,
            'subtotal'      =>  $this->Subtotal,
            'subweight'     =>  $this->Subweight,
            'discountable'  =>  !$this->NoDiscount,
            'taxable'       =>  !$this->isExempt,
            'product'       =>  $this->get_product_details()
        ];

        if ($this->Order()->exists() && $this->Order()->Status != 'Pending') {
            return array_merge($data, [
                'paid_unit_price' => $this->UnitPrice
            ]);
        }

        return $data;
    }

    private function get_product_details()
    {
        if ($this->Bundle()->exists()) {
            return $this->Bundle()->getMiniData();
        }

        return $this->Variant()->exists() ? $this->Variant()->getData() : null;
    }

    /**
     * Event handler called before writing to the database.
     */
    public function onBeforeWrite()
    {
        parent::onBeforeWrite();

        if (!$this->Order()->exists()) {
            return;
        }

        if ($this->Order()->Status == 'Pending') {
            if ($this->Bundle()->exists()) {
                $this->Quantity = 1;
                $this->Title = $this->Bundle()->Title . "\n";

                foreach ($this->Bundle()->Variants() as $variant) {
                    $this->Title .= "- $variant->Title x 1\n";
                }

                $this->Title = trim($this->Title);

                $this->isDigital = $this->Bundle()->isDigital;
                $this->isExempt = $this->Bundle()->isExempt;
                $this->GSTIncluded = $this->Bundle()->GSTIncluded;
                $this->NoDiscount = $this->Bundle()->NoDiscount;
                $this->Subtotal = $this->Quantity * $this->Bundle()->BundledPrice;
                $this->Subweight = $this->Bundle()->UnitWeight;

            } elseif ($this->Variant()->exists()) {
                $this->Title = $this->Variant()->Title;
                $this->isDigital    =   $this->Variant()->isDigital;
                $this->isExempt     =   $this->Variant()->isExempt;
                $this->GSTIncluded  =   $this->Variant()->GSTIncluded;
                $this->NoDiscount   =   $this->Variant()->NoDiscount;
                $this->Subtotal  =   $this->Quantity * $this->Variant()->SortingPrice;
                if (!$this->Variant()->isDigital) {
                    $this->Subweight    =   $this->Quantity * $this->Variant()->UnitWeight;
                }
            }
        }
    }
}
