<?php

namespace Cita\eCommerce\Model;
use Page;
use SilverStripe\Forms\CurrencyField;
use SilverStripe\Forms\CheckboxField;
use SilverStripe\Forms\TextField;
use SilverStripe\Forms\FieldList;
use Cita\eCommerce\Extension\ProductVariantCommonFields;
use Cita\eCommerce\Extension\ProductOrderItemCommonFields;
use Leochenftw\Grid;
use UncleCheese\DisplayLogic\Forms\Wrapper;
use Cita\eCommerce\Controller\ProductController;

/**
 * Description
 *
 * @package silverstripe
 * @subpackage mysite
 */
class Product extends Page
{
    public function getControllerName()
    {
        return ProductController::class;
    }
    /**
     * Defines whether a page can be in the root of the site tree
     * @var boolean
     */
    private static $can_be_root = false;
    /**
     * Defines the database table name
     * @var string
     */
    private static $table_name = 'Cita_eCommerce_Product';
    private static $description = 'You are encouraged to extend this class, rather than using it directly...';
    /**
     * Defines whether a page is displayed within the site tree
     * @var boolean
     */
    private static $show_in_sitetree = false;

    /**
     * Database fields
     * @var array
     */
    private static $db = [
        'hasVariants'   =>  'Boolean'
    ];

    /**
     * Defines extension names and parameters to be applied
     * to this object upon construction.
     * @var array
     */
    private static $extensions = [
        ProductOrderItemCommonFields::class,
        ProductVariantCommonFields::class
    ];

    /**
     * Has_many relationship
     * @var array
     */
    private static $has_many = [
        'OrderItems'    =>  OrderItem::class,
        'Variants'      =>  Variant::class
    ];

    /**
     * CMS Fields
     * @return FieldList
     */
    public function getCMSFields()
    {
        $fields =   parent::getCMSFields();

        $fields->addFieldsToTab(
            'Root.Variants',
            [
                CheckboxField::create(
                    'hasVariants',
                    'Product has variants'
                )
            ]
        );

        if ($this->exists()) {
            $fields->addFieldToTab(
                'Root.Variants',
                Wrapper::create(
                    Grid::make('Variants', 'Variants', $this->Variants(), true, 'GridFieldConfig_RelationEditor')
                )->displayIf('hasVariants')->isChecked()->end()
            );
        }

        $this->extend('updateCMSFields', $fields);
        return $fields;
    }

    public function getData()
    {
        $data   =   parent::getData();
        $data   =   array_merge($data, [
            'variants'  =>  $this->hasVariants ?
                            $this->Variants()->getData() :
                            [ $this->getMiniData() ]
        ]);

        return $data;
    }

    public function getMiniData()
    {
        return array_merge($this->getBaseData(), [
            'link'  =>  $this->Link(),
            'title' =>  $this->Title
        ]);
    }

    public function getTileData()
    {
        return array_merge($this->getMiniData(), []);
    }
}
