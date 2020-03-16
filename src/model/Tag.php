<?php

namespace Cita\eCommerce\Model;
use SilverStripe\ORM\DataObject;
use SilverStripe\Assets\Image;
use Leochenftw\Extension\SlugifyExtension;
use Leochenftw\Util;

/**
 * Description
 *
 * @package silverstripe
 * @subpackage mysite
 */
class Tag extends DataObject
{
    /**
     * Defines the database table name
     * @var string
     */
    private static $table_name = 'Cita_eCommerce_Tag';
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
        SlugifyExtension::class
    ];

    /**
     * Default sort ordering
     * @var array
     */
    private static $default_sort = ['Title' => 'ASC'];

    /**
     * Belongs_many_many relationship
     * @var array
     */
    private static $belongs_many_many = [
        'Products'  =>  Product::class
    ];

    /**
     * Defines summary fields commonly used in table columns
     * as a quick overview of the data for this dataobject
     * @var array
     */
    private static $summary_fields = [
        'Title'             =>  'Title',
        'Content'           =>  'Content',
        'Products.Count'    =>  'Number of Products'
    ];

    /**
     * CMS Fields
     * @return FieldList
     */
    public function getCMSFields()
    {
        $fields =   parent::getCMSFields();

        return $fields;
    }

    public function getMiniData()
    {
        return [
            'title' =>  $this->Title,
            'slug'  =>  $this->Slug,
            'url'   =>  $this->Link()
        ];
    }

    public function getData()
    {
        return array_merge($this->getMiniData(), [
            'content'   =>  Util::preprocess_content($this->Content)
        ]);
    }

    private function Link()
    {
        if ($catalog = Catalog::get()->first()) {
            return $catalog->Link() . '?tag=' . $this->Slug;
        }

        return '?tag=' . $this->Slug;
    }
}
