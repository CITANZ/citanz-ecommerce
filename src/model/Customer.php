<?php

namespace Cita\eCommerce\Model;
use SilverStripe\Security\Member;
use SilverStripe\Forms\TextareaField;
use SilverStripe\Forms\TextField;
use Cita\eCommerce\Model\Order;
use Cita\eCommerce\Model\Address;
use SilverStripe\Security\Group;

class Customer extends Member
{
    /**
     * Defines the database table name
     * @var string
     */
    private static $table_name = 'Cita_eCommerce_Customer';

    /**
     * Has_many relationship
     * @var array
     */
    private static $has_many = [
        'Orders'    =>  Order::class,
        'Addresses' =>  Address::class
    ];

    /**
     * CMS Fields
     * @return FieldList
     */
    public function getCMSFields()
    {
        $fields     =   parent::getCMSFields();

        $fields->removeByName([
            'Locale',
            'FailedLoginCount',
            'DirectGroups',
            'Permissions'
        ]);

        return $fields;
    }

    // call this upon sign in
    public function grantDiscountToCart()
    {
        if ($group = $this->Groups()->first()) {
            if ($group->Discount()->exists()) {
                if ($recent_cart = $this->Orders()->first()) {
                    $recent_cart->DiscountID    =   $group->DiscountID;
                    $recent_cart->write();
                }
            }
        }
    }

    /**
     * Event handler called after writing to the database.
     */
    public function onAfterWrite()
    {
        parent::onAfterWrite();
        $this->addToGroupByCode('customers', 'Customers');
    }

    public function requireDefaultRecords()
    {
        if (empty(Group::get()->filter(['Code' => 'customers'])->first())) {
            $group          =   Group::create();
            $group->Code    =   'customers';
            $group->Title   =   'Customers';
            $group->write();
        }
    }
}
