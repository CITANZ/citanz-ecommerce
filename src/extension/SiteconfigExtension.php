<?php

namespace Cita\eCommerce\Extension;

use SilverStripe\Forms\HTMLEditor\HtmlEditorField;
use Dynamic\CountryDropdownField\Fields\CountryDropdownField;
use SilverStripe\Forms\EmailField;
use SilverStripe\Assets\Image;
use SilverStripe\AssetAdmin\Forms\UploadField;
use SilverStripe\Forms\TextareaField;
use SilverStripe\Forms\TextField;
use SilverStripe\Forms\FieldList;
use SilverStripe\ORM\DataExtension;

class SiteconfigExtension extends DataExtension
{
    /**
     * Database fields
     * @var array
     */
    private static $db = [
        'TradingName'       =>  'Varchar(128)',
        'GST'               =>  'Varchar(32)',
        'GSTRate'           =>  'Decimal',
        'StoreLocation'     =>  'Text',
        'StoreCountry'      =>  'Varchar(8)',
        'ContactNumber'     =>  'Varchar(16)',
        'ContactEmail'      =>  'Varchar(256)',
        'InvoiceBccEmail'   =>  'Varchar(256)',
        'OrderEmail'        =>  'Text',
        'ShippingReminder'  =>  'HTMLText'
    ];

    /**
     * Add default values to database
     * @var array
     */
    private static $defaults = [
        'GSTRate'   =>  0.15
    ];

    /**
     * Has_one relationship
     * @var array
     */
    private static $has_one = [
        'StoreLogo' =>  Image::class
    ];

    /**
     * Relationship version ownership
     * @var array
     */
    private static $owns = [
        'StoreLogo'
    ];

    /**
     * Update Fields
     * @return FieldList
     */
    public function updateCMSFields(FieldList $fields)
    {
        $owner = $this->owner;
        $fields->addFieldsToTab(
            'Root.StoreInformation',
            [
                UploadField::create(
                    'StoreLogo',
                    'Store Logo'
                ),
                TextField::create('TradingName'),
                TextField::create('GST', 'GST Number'),
                TextField::create('ContactNumber', 'Store Phone Number'),
                EmailField::create('ContactEmail', 'Store Contact Email')->setDescription('If "Order Recipient Emails" field is empty, order will be sent to this email address'),
                TextField::create('OrderEmail', 'Order Recipient Emails')->setDescription('use "," to separate multiple emails'),
                TextField::create('InvoiceBccEmail', 'Invoice Bcc Email')->setDescription('If you wish to receive a copy when an invoice is sent to the customer...'),
                TextareaField::create('StoreLocation', 'Store Location'),
                CountryDropdownField::create('StoreCountry')->setEmptyString('- select one -'),
                TextField::create('GSTRate', 'GST Rate')->setDescription('e.g. 0.15'),
                HtmlEditorField::create(
                    'ShippingReminder',
                    'ShippingReminder'
                )
            ]
        );
        return $fields;
    }

    public function get_store_data()
    {
        $logo   =   $this->owner->StoreLogo();
        return [
            'store_logo'    =>  $logo->exists() ? $logo->ScaleHeight(80)->getAbsoluteURL() : null,
            'title'         =>  $this->owner->TradingName,
            'gst'           =>  $this->owner->GST,
            'phone'         =>  $this->owner->ContactNumber,
            'email'         =>  $this->owner->ContactEmail,
            'location'      =>  $this->owner->StoreLocation
        ];
    }
}
