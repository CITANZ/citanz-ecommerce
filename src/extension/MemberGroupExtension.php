<?php

namespace Cita\eCommerce\Extension;
use Cita\eCommerce\Model\Discount;
use SilverStripe\Forms\DropdownField;
use SilverStripe\Forms\FieldList;
use SilverStripe\ORM\DataExtension;

class MemberGroupExtension extends DataExtension
{
    /**
     * Has_one relationship
     * @var array
     */
    private static $has_one = [
        'Discount'  =>  Discount::class
    ];

    /**
     * Update Fields
     * @return FieldList
     */
    public function updateCMSFields(FieldList $fields)
    {
        $owner = $this->owner;
        $fields->addFieldToTab(
            'Root.Members',
            DropdownField::create(
                'DiscountID',
                'Discount',
                Discount::get()->filter(['Type' => 'Member Type'])->map()
            )->setEmptyString('- select one -'),
            'Members'
        );
        return $fields;
    }
}
