<?php

namespace Cita\eCommerce\Model;
use Page;
use SilverStripe\Forms\CheckboxField;
use Leochenftw\Grid;
use Cita\eCommerce\Traits\ProductPriceRangeGenerator;
use Cita\eCommerce\Traits\ProductListGenerator;
use Cita\eCommerce\Forms\GridField\ExistingProductAdder;
use SilverStripe\Forms\GridField\GridFieldAddExistingAutocompleter;

/**
 * Description
 *
 * @package silverstripe
 * @subpackage mysite
 */
class ProductCollection extends Page
{
    use ProductListGenerator;
    /**
     * Defines the database table name
     * @var string
     */
    private static $table_name = 'Cita_eCommerce_ProductCollection';
    private static $description = 'A product collection page helps you group products';

    /**
     * Database fields
     * @var array
     */
    private static $db = [
        'PageSize'      =>  'Int',
        'isBestSeller'  =>  'Boolean',
        'isSpecial'     =>  'Boolean'
    ];

    /**
     * Add default values to database
     * @var array
     */
    private static $defaults = [
        'PageSize'  =>  12
    ];

    /**
     * Many_many relationship
     * @var array
     */
    private static $many_many = [
        'Products'  =>  Product::class
    ];

    /**
     * CMS Fields
     * @return FieldList
     */
    public function getCMSFields()
    {
        $fields = parent::getCMSFields();

        $fields->addFieldToTab(
            'Root.Main',
            CheckboxField::create(
                'isSpecial',
                'This collection shows products on special'
            )->setDescription('If this box is checked, products that has valid special price will get imported automatically.'),
            'URLSegment'
        );

        $fields->addFieldToTab(
            'Root.Main',
            CheckboxField::create(
                'isBestSeller',
                'This collection shows the most sold products'
            ),
            'URLSegment'
        );

        $fields->addFieldToTab(
            'Root.Products',
            $grid = Grid::make(
                'Products',
                'Products',
                $this->Products(),
                false,
                $this->isSpecial ? 'GridFieldConfig_RecordViewer' : 'GridFieldConfig_RelationEditor',
                true
            )
        );

        $config = $grid->getConfig();
        $config->removeComponentsByType(GridFieldAddExistingAutocompleter::class);
        $comps = $config->getComponents();
        $config->addComponent(new ExistingProductAdder());

        $this->add_pagesize_field($fields);

        return $fields;
    }
}
