<?php

namespace Cita\eCommerce\Model;
use SilverStripe\ORM\DataObject;
use SilverStripe\Forms\TextField;
use SilverStripe\Forms\DropdownField;
use SilverStripe\Forms\LiteralField;
use SilverStripe\Security\Group;

/**
 * Description
 *
 * @package silverstripe
 * @subpackage mysite
 */
class Discount extends DataObject
{
    /**
     * Defines the database table name
     * @var string
     */
    private static $table_name = 'Cita_eCommerce_Discount';
    /**
     * Singular name for CMS
     * @var string
     */
    private static $singular_name = 'Discount';
    /**
     * Plural name for CMS
     * @var string
     */
    private static $plural_name = 'Discounts';

    /**
     * Database fields
     * @var array
     */
    private static $db = [
        'Title'         =>  'Varchar(128)',
        'DiscountBy'    =>  'Enum("ByPercentage,ByValue")',
        'DiscountRate'  =>  'Decimal',
        'Type'          =>  'Enum("Member Type,Coupon")',
        'CouponCode'    =>  'Varchar(128)',
        'NumCopies'     =>  'Int',
        'Used'          =>  'Boolean',
        'InfiniteUse'   =>  'Boolean',
        'ValidFrom'     =>  'Datetime',
        'ValidUntil'    =>  'Datetime',
        'LifePoint'     =>  'Int'
    ];

    private static $indexes = [
        'CouponCode' => [
            'type'      =>  'unique',
            'columns'   =>  ['CouponCode'],
        ]
    ];

    /**
     * Defines summary fields commonly used in table columns
     * as a quick overview of the data for this dataobject
     * @var array
     */
    private static $summary_fields = [
        'Title',
        'CouponCode',
        'Used'
    ];

    /**
     * Belongs_to relationship
     * @var array
     */
    private static $belongs_to = [
        'Group' =>  Group::class
    ];


    public function populateDefaults()
    {
        $this->Type         =   'Coupon';
        $this->LifePoint    =   1;
        $this->CouponCode   =   strtoupper(substr(sha1(time()), 0, 8));
    }

    /**
     * CMS Fields
     * @return FieldList
     */
    public function getCMSFields()
    {
        $fields =   parent::getCMSFields();
        $coupon =   $fields->fieldByName('Root.Main.CouponCode');
        $fields->removeByName([
            'CouponCode'
        ]);

        $fields->fieldByName('Root.Main.DiscountRate')->setDescription('If "Discount By" is set to "By Percentage", it will be x% off; if set to "By Value", it will be $x off.');
        $type   =   $fields->fieldByName('Root.Main.Type');
        if ($type) {
            $type->setEmptyString('- select one -');
        }

        if ($this->Type == 'Coupon') {
            $fields->addFieldToTab(
                'Root.Main',
                $coupon
            );
        } elseif ($this->Type == 'Member Type') {
            if ($this->Group()->exists()) {
                $field  =   LiteralField::create('Group', 'This discount has been bound to <a href="/admin/security/EditForm/field/Groups/item/' . $this->Group()->ID . '/edit">' . $this->Group()->Title . '</a> group');
            } else {
                $field  =   LiteralField::create('Group', 'Please go to the <a href="/admin/security/groups">Group panel</a>, and bind the discount to the desired group');
            }

            $fields->addFieldToTab(
                'Root.Main',
                $field
            );
        }

        $fields->addFieldToTab(
            'Root.Main',
            $fields->fieldByName('Root.Main.Used')
        );

        $fields->addFieldToTab(
            'Root.Main',
            TextField::create(
                'NumCopies', 'Create another "n" copies of promotion coupon.'
            )->setDescription('Leave blank or input 0 if you only wish to create one')
        );

        $fields->fieldByName('Root.Main.InfiniteUse')->setTitle('This coupon can be used infinitely');

        if ($lp = $fields->fieldByName('Root.Main.LifePoint')) {
            $lp->setTitle('How many times this discount coupon can be used?');
        }

        return $fields;
    }

    public function calc_discount($amount)
    {
        if ($this->DiscountBy == 'ByPercentage') {
            return $amount * $this->DiscountRate * 0.01;
        }

        return ($amount - $this->DiscountRate >= 0) ? $amount - $this->DiscountRate : 0;
    }

    public function getData()
    {
        if (!$this->exists()) return null;
        $data   =   [
            'title' =>  $this->Title,
            'by'    =>  $this->DiscountBy == 'ByPercentage' ? '%' : '-',
            'rate'  =>  (float) $this->DiscountRate,
            'code'  =>  $this->CouponCode
        ];

        $this->extend('CustomGetData', $data);

        return $data;
    }

    public static function check_valid($promo_code)
    {
        if ($coupon = Discount::get()->filter(['CouponCode' => $promo_code, 'Used' => false])->first()) {

            $valid_from     =   strtotime($coupon->ValidFrom);
            $valid_until    =   strtotime($coupon->ValidUntil);

            if (!empty($valid_from) && $valid_from > time()) {
                return null;
            }

            if (!empty($valid_until) && $valid_until < time()) {
                return null;
            }

            if (!$coupon->InfiniteUse && $coupon->LifePoint <= 0) {
                return null;
            }

            return $coupon;
        }

        return null;
    }

    /**
     * Event handler called before writing to the database.
     */
    public function onBeforeWrite()
    {
        parent::onBeforeWrite();
        if ($this->InfiniteUse && $this->Used) {
            $this->Used =   false;
        }

        if (!$this->InfiniteUse && $this->LifePoint <= 0) {
            $this->Used =   true;
        }
    }

    /**
     * Event handler called after writing to the database.
     */
    public function onAfterWrite()
    {
        parent::onAfterWrite();
        if (!empty($this->NumCopies)) {
            $n  =   $this->NumCopies;
            for ($i = 0; $i < $n; $i++) {
                $coupon                 =   Discount::create();
                $coupon->Title          =   $this->Title;
                $coupon->DiscountBy     =   $this->DiscountBy;
                $coupon->DiscountRate   =   $this->DiscountRate;
                $coupon->CouponCode     =   strtoupper(substr(sha1(rand()), 0, 8));
                $coupon->write();
            }
            $this->NumCopies    =   0;
            $this->write();
        }
    }
}
