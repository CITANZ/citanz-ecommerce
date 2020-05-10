<?php

namespace Cita\eCommerce\Model;
use SilverStripe\ORM\DataObject;
use SilverStripe\Forms\HTMLEditor\HtmlEditorField;
use SilverStripe\Forms\TextField;
use SilverStripe\Forms\FieldList;
use SilverStripe\Assets\Image;
use Leochenftw\Extension\SlugifyExtension;
use Leochenftw\Util;
use Leochenftw\Grid;
use SilverStripe\CMS\Forms\SiteTreeURLSegmentField;

/**
 * Description
 *
 * @package silverstripe
 * @subpackage mysite
 */
class Category extends DataObject
{
    /**
     * Defines the database table name
     * @var string
     */
    private static $table_name = 'Cita_eCommerce_Category';
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
        'Image'     =>  Image::class,
        'Parent'    =>  Category::class
    ];

    /**
     * Relationship version ownership
     * @var array
     */
    private static $owns = [
        'Image'
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
     * Has_many relationship
     * @var array
     */
    private static $has_many = [
        'Children'  =>  Category::class
    ];

    /**
     * Many_many relationship
     * @var array
     */
    private static $many_many = [
        'Products'  =>  Product::class
    ];

    /**
     * Defines summary fields commonly used in table columns
     * as a quick overview of the data for this dataobject
     * @var array
     */
    private static $summary_fields = [
        'Title'                 =>  'Title',
        'getSubcategoryTitles'  =>  'Children',
        'Products.Count'        =>  'Products'
    ];

    public function getSubcategoryTitles()
    {
        $titles =   $this->Children()->column('Title');

        return implode(', ', $titles);
    }


    /**
     * CMS Fields
     * @return FieldList
     */
    public function getCMSFields()
    {
        $catalog = Catalog::get()->first();
        $link = trim($catalog ? $catalog->Link() : '', '/');
        $fields = FieldList::create(
            TextField::create(
                'Title',
                'Title'
            ),
            $slug = SiteTreeURLSegmentField::create('Slug', 'Slug', $this->owner->Slug)->setURLPrefix("/$link?="),
            HtmlEditorField::create(
                'Content',
                'Content'
            )
        );

        // \Leochenftw\Debugger::inspect($slug->getAttributes());

        if ($this->exists()) {
            $fields->push(
                Grid::make('Children', 'Children', $this->Children(), false)
            );
        }

        return $fields;
    }

    public function get_ancestors($upper = null)
    {
        $ancestors  =   [];
        $item       =   $this;

        while ($item->Parent()->exists()) {
            $ancestors[]    =   [
                'title' =>  $item->Parent()->Title,
                'link'  =>  $upper->Link() . '?category=' . $item->Parent()->Slug
            ];
            $item   =   $item->Parent();
        }


        $ancestors[]    =   [
            'title' =>  $upper->Title,
            'link'  =>  $upper->Link()
        ];

        return array_reverse($ancestors);
    }

    public function handle_product(&$product)
    {
        $this->Products()->add($product);
        if ($this->Parent()->exists()) {
            $this->Parent()->handle_product($product);
        }
    }

    public function get_total_product_count()
    {
        $n  =   $this->Products()->count();
        foreach ($this->Children() as $child) {
            $n  +=  $child->get_total_product_count();
        }

        return $n;
    }

    public function getData($include_sub = true)
    {
        if ($include_sub) {
            $list   =   $this->Children()->getData();
            $this->ExcludeEmptyCategory($list);
        }

        $data   =   [
            'title'     =>  $this->Title,
            'slug'      =>  $this->Slug,
            'active'    =>  false,
            'url'       =>  null,
            'sub'       =>  $include_sub ? $list : [],
            'count'     =>  $this->get_total_product_count()
        ];

        return $data;
    }

    private function ExcludeEmptyCategory(&$array)
    {
        $list    =   [];
        foreach ($array as $item) {
            if ($item['count'] != 0) {
                $list[]  =   $item;
            }
        }

        $array   =   $list;
    }
}
