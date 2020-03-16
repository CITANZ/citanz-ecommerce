<?php

namespace Cita\eCommerce\Model;
use SilverStripe\ORM\DataObject;
use SilverStripe\Assets\Image;
use Leochenftw\Extension\SlugifyExtension;

/**
 * Description
 *
 * @package silverstripe
 * @subpackage mysite
 */
class Brand extends DataObject
{
    /**
     * Defines the database table name
     * @var string
     */
    private static $table_name = 'Cita_eCommerce_Brand';
    /**
     * Database fields
     * @var array
     */
    private static $db = [
        'Title'     =>  'Varchar(128)',
        'Content'   =>  'HTMLText'
    ];

    /**
     * Has_one relationship
     * @var array
     */
    private static $has_one = [
        'Logo'  =>  Image::class
    ];

    /**
     * Relationship version ownership
     * @var array
     */
    private static $owns = [
        'Logo'
    ];

    private static $cascade_deletes = ['Logo'];

    /**
     * Defines extension names and parameters to be applied
     * to this object upon construction.
     * @var array
     */
    private static $extensions = [
        SlugifyExtension::class
    ];

    /**
     * Default sort ordering
     * @var array
     */
    private static $default_sort = ['Title' => 'ASC'];

    /**
     * Has_many relationship
     * @var array
     */
    private static $has_many = [
        'Products'  =>  Product::class
    ];

    /**
     * Defines summary fields commonly used in table columns
     * as a quick overview of the data for this dataobject
     * @var array
     */
    private static $summary_fields = [
        'getLogoThumb'      =>  'Logo',
        'Title'             =>  'Title',
        'Products.Count'    =>  'Products'
    ];

    public function getLogoThumb()
    {
        if ($this->Logo()->exists()) {
            return $this->Logo()->ScaleWidth(128);
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

        return $fields;
    }

    public function getData()
    {
        return [
            'title' =>  $this->Title,
            'slug'  =>  $this->Slug,
            'url'   =>  $this->Link(),
            'logo'  =>  $this->Logo()->getData('ScaleWidth', 64)
        ];
    }

    private function Link()
    {
        if ($catalog = Catalog::get()->first()) {
            return $catalog->Link() . '?brand=' . $this->Slug;
        }

        return '?brand=' . $this->Slug;
    }
}
