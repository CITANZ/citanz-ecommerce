<?php

namespace Cita\eCommerce\Extension;


use SilverStripe\Forms\HTMLEditor\HtmlEditorField;
use SilverStripe\AssetAdmin\Forms\UploadField;
use SilverStripe\Assets\Image;
use SilverStripe\Forms\TextareaField;
use SilverStripe\Forms\TextField;
use SilverStripe\Forms\FieldList;
use SilverStripe\ORM\DataExtension;
use Cita\eCommerce\Model\Variant;
use Cita\eCommerce\Model\Product;
use Leochenftw\Grid;

class ProductVariantCommonFields extends DataExtension
{
    /**
     * Database fields
     * @var array
     */
    private static $db = [
        'ShortDesc' => 'HTMLText',
        'SortingPrice' => 'Currency'
    ];
    /**
     * Has_one relationship
     * @var array
     */
    private static $has_one = [
        'Image' =>  Image::class
    ];
/**
     * Relationship version ownership
     * @var array
     */
    private static $owns = [
        'Image'
    ];

    /**
     * Update Fields
     * @return FieldList
     */
    public function updateCMSFields(FieldList $fields)
    {
        $owner = $this->owner;

        $fields->removeByName([
            'SortingPrice'
        ]);

        $fields->addFieldsToTab(
            'Root.Main',
            [
                HtmlEditorField::create(
                    'ShortDesc',
                    'Short Description'
                ),
                HtmlEditorField::create('Content'),
            ],
            'Content'
        );

        $fields->addFieldsToTab(
            'Root.ProductDetails',
            [
                UploadField::create(
                    'Image',
                    'Product Image'
                )
            ]
        );

        return $fields;
    }
}
