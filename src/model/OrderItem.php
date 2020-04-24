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
        'Quantity'      =>  'Decimal',
        'Subtotal'      =>  'Currency', // this is the product price x qty
        'Subweight'     =>  'Decimal',
        'isRefunded'    =>  'Boolean'
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
        'ShowTitle' =>  'Title',
        'UnitPrice' =>  'Unit Price',
        'Quantity'  =>  'Quantity',
        'Subtotal'  =>  'Subtotal'
    ];

    public function getSKU()
    {
        if ($this->Variant()->exists()) {
            return $this->Variant()->SKU;
        }

        return 'DELETED-ITEM-SKU';
    }

    public function ShowTitle()
    {
        if ($this->Variant()->exists()) {
            $product = '';

            if ($this->Variant()->Product()->exists()) {
                $product = $this->Variant()->Product()->Title . ' - ';
            }

            return $product . $this->Variant()->Title;
        }

        return 'DELETED ITEM';
    }

    public function UnitPrice()
    {
        return  $this->Variant()->exists() ? '$' . money_format('%i',  $this->Variant()->Price) : '-';
    }

    public function getData()
    {
        return [
            'id'            =>  $this->ID,
            'product'       =>  $this->get_product_details(),
            'quantity'      =>  $this->Quantity,
            'subtotal'      =>  $this->Subtotal,
            'subweight'     =>  $this->Subweight,
            'discountable'  =>  !$this->NoDiscount,
            'taxable'       =>  !$this->isExempt
        ];
    }

    private function get_product_details()
    {
        return $this->Variant()->exists() ? $this->Variant()->getData() : null;
    }

    /**
     * Event handler called before writing to the database.
     */
    public function onBeforeWrite()
    {
        parent::onBeforeWrite();

        if ($this->Variant()->exists()) {
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
