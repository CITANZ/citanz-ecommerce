<?php

namespace Cita\eCommerce\Model;

use SilverStripe\Forms\TextField;
use SilverStripe\ORM\DataObject;
use Leochenftw\Util;
use Leochenftw\Extension\SortOrderExtension;
use Cita\eCommerce\Extension\ProductVariantCommonFields;
use Cita\eCommerce\Extension\ProductOrderItemCommonFields;

/**
 * Description
 *
 * @package silverstripe
 * @subpackage mysite
 */
class Variant extends DataObject
{
    /**
     * Defines the database table name
     * @var string
     */
    private static $table_name = 'Cita_eCommerce_Variant';
    /**
     * Database fields
     * @var array
     */
    private static $db = [
        'Title'     =>  'Varchar(128)',
        'Content'   =>  'HTMLText'
    ];
    /**
     * Defines extension names and parameters to be applied
     * to this object upon construction.
     * @var array
     */
    private static $extensions = [
        ProductOrderItemCommonFields::class,
        ProductVariantCommonFields::class,
        SortOrderExtension::class
    ];

    /**
     * Defines summary fields commonly used in table columns
     * as a quick overview of the data for this dataobject
     * @var array
     */
    private static $summary_fields = [
        'Product.Title' =>  'Product',
        'Title'         =>  'Title',
        'Price'         =>  'Price'
    ];

    /**
     * Has_one relationship
     * @var array
     */
    private static $has_one = [
        'Product' =>  Product::class
    ];

    /**
     * CMS Fields
     * @return FieldList
     */
    public function getCMSFields()
    {
        $fields = parent::getCMSFields();

        $fields->removeByName([
            'isDigital'
        ]);

        return $fields;
    }

    public function getData()
    {
        return array_merge($this->getBaseData(), [
            'link'          =>  $this->Product()->exists() ? $this->Product()->Link() : null,
            'title'         =>  $this->getProductTitle(),
            'variant_title' =>  $this->Title,
            'content'       =>  Util::preprocess_content(empty($this->Content) ? $this->Product()->Content : $this->Content)
        ]);
    }

    public function getProductTitle()
    {
        if ($this->Product()->exists()) {
            return trim($this->Product()->Title);
        }

        $result = $this->extend('getProductTitle');
        if (!empty($result)) {
            return $result[0];
        }

        return null;
    }

    /**
     * Event handler called after writing to the database.
     */
    public function onAfterWrite()
    {
        parent::onAfterWrite();
        if ($this->Product()->exists()) {
            $this->Product()->onAfterWrite();
        }
    }
}
