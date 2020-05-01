<?php

namespace Cita\eCommerce\Model;
use Page;
use SilverStripe\Forms\CurrencyField;
use SilverStripe\Forms\TextField;
use Leochenftw\Grid;
use Cita\eCommerce\Extension\ProductOrderItemCommonFields;

/**
 * Description
 *
 * @package silverstripe
 * @subpackage mysite
 */
class Bundle extends Page
{
    /**
     * Defines whether a page can be in the root of the site tree
     * @var boolean
     */
    private static $can_be_root = false;
    /**
     * Defines the database table name
     * @var string
     */
    private static $table_name = 'Cita_eCommerce_Bundle';

    /**
     * Database fields
     * @var array
     */
    private static $db = [
        'SKU' => 'Varchar(64)',
        'BundledPrice' => 'Currency'
    ];

    /**
     * Defines extension names and parameters to be applied
     * to this object upon construction.
     * @var array
     */
    private static $extensions = [
        ProductOrderItemCommonFields::class
    ];


    /**
     * Many_many relationship
     * @var array
     */
    private static $many_many = [
        'Variants' => Variant::class
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
            CurrencyField::create(
                'BundledPrice',
                'Bundled Price'
            ),
            'Content'
        );

        $fields->addFieldToTab(
            'Root.BundledItems',
            Grid::make('Variants', 'Variants', $this->Variants(), false, 'GridFieldConfig_RelationEditor', true)
        );
        return $fields;
    }

    public function getUnitWeight()
    {
        if ($this->isDigital) {
            return 0;
        }

        $weight = 0;
        foreach ($this->Variants() as $variant) {
            $weight += $variant->UnitWeight;
        }

        return $weight;
    }

    public function getisSoldout()
    {
        $b = false;

        foreach ($this->Variants() as $variant) {
            if ($variant->isSoldout) {
                $b = true;
            }
        }

        return $b;
    }

    public static function MatchBundle(&$order)
    {
        $order_variants = $order->Items()->column('VariantID');

        $bundles = Bundle::get()->sort(['BundledPrice' => 'DESC']);
        foreach ($bundles as $bundle) {
            $my_variants = $bundle->Variants()->column('ID');
            sort($my_variants);

            $result = array_intersect($order_variants, $my_variants);

            sort($result);

            if ($my_variants == $result) {
                return $bundle->InjectToCart($order);
            }
        }

        return null;
    }

    public function InjectToCart(&$order)
    {
        $ids = $this->Variants()->column('ID');
        $items = $order->Items()->filter(['VariantID' => $ids]);

        $new_item = OrderItem::create();
        $new_item->BundleID = $this->ID;
        $new_item->OrderID = $order->ID;
        $new_item->write();
        $covered = [];

        $togo = $items->filter(['Quantity' => 1]);
        foreach ($togo as $item) {
            if ($item->Quantity <= 1) {
                $covered[] = "<strong>$item->Title</strong>";
                $item->delete();
            }
        }

        $tokeep = $items->filter(['Quantity:GreaterThan' => 1]);
        foreach ($tokeep as $item) {
            $covered[] = "<strong>$item->Title</strong>";
            $item->Quantity -= 1;
            $item->write();
        }

        $covered = implode(', ', $covered);

        $order->Log("<p>We found a bundle deal for you! Bundle: <strong>$this->Title</strong> now covers {$covered}</p>");

        $order->UpdateAmountWeight();

        return $this;
    }

    public function getMiniData()
    {
        return [
            'id' => $this->ID,
            'sku' => $this->SKU,
            'price' => $this->BundledPrice,
            'stock' => "Infinite",
            'price_label' => '$' . number_format($this->BundledPrice, 2),
            'image' => null,
            'link' => $this->Link(),
            'title' => $this->Title,
            'variants' => $this->Variants()->getMiniData()
        ];
    }
}
