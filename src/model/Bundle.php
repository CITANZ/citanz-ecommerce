<?php

namespace Cita\eCommerce\Model;
use SilverStripe\Dev\Debug;
use Page;
use SilverStripe\Forms\CurrencyField;
use SilverStripe\Forms\TextField;
use Leochenftw\Grid;
use Cita\eCommerce\Extension\ProductOrderItemCommonFields;
use SilverStripe\Forms\ReadonlyField;

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
     * Defines Database fields for the Many_many bridging table
     * @var array
     */
    private static $many_many_extraFields = [
        'Variants' => [
            'Count' => 'Int'
        ]
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

        $field_config = [
            'Title' => [
                'title' => 'Title',
                'field' => ReadonlyField::class,
            ],
            'Count' => [
                'title' => 'Count',
                'field' => TextField::class,
            ],
        ];

        $fields->addFieldToTab('Root.BundledItems', Grid::manyExtraSortable('Variants', 'Variants', $this->Variants(), Variant::class, $field_config));

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
            if ($bundle->hasMethod('CustomMatchCheck')) {
                $condition_met = $bundle->CustomMatchCheck($order);
                if ($condition_met) {
                    return $bundle->InjectToCart($order);
                }
            } else {
                $my_variants = $bundle->Variants()->column('ID');
                sort($my_variants);

                $result = array_intersect($order_variants, $my_variants);

                sort($result);

                if (empty($result)) {
                    return null;
                }

                if ($my_variants == $result) {
                    $condition_met = true;
                    foreach ($result as $vid) {
                        $bundle_variant_requirement = $bundle->Variants()->byID($vid)->Count;
                        $order_item_count = $order->Items()->filter(['VariantID' => $vid])->first()->Quantity;
                        if ($order_item_count < $bundle_variant_requirement) {
                            $condition_met = false;
                            break;
                        }
                    }

                    if ($condition_met) {
                        return $bundle->InjectToCart($order);
                    }
                }
            }
        }

        return null;
    }

    public function InjectToCart(&$order, $variants = [])
    {
        $new_item = OrderItem::create();
        $new_item->BundleID = $this->ID;
        $new_item->OrderID = $order->ID;
        $new_item->write();
        $covered = [];

        $given_variants = !empty($variants);
        $variants = !empty($variants) ? $variants : $this->Variants();

        foreach ($variants as $variant) {
            if ($order_item = $order->Items()->filter(['VariantID' => $variant->ID])->first()) {
                $order_item->reduce($variant->Count);
                $new_item->BundledVariants()->add($variant, ['Quantity' => $variant->Count]);
                $covered[] = $variant->Title;
            }
        }

        $covered_string = '';
        $len = count($covered);

        for ($i = 0; $i < $len; $i++) {
            if ($i == $len - 1) {
                $covered_string .= 'and ';
            }

            $covered_string .= "<strong>{$covered[$i]}</strong>";

            if ($i < $len - 1 && $i != $len - 2) {
                $covered_string .= ', ';
            } elseif ($i == $len - 2) {
                $covered_string .= ' ';
            }
        }

        $order->Log("<p>We found a bundle deal for you! Bundle: <strong>$this->Title</strong> now covers {$covered_string}</p>");

        $order->UpdateAmountWeight();

        return $this;
    }

    public function getMiniData()
    {
        $variants = [];

        foreach ($this->Variants() as $variant) {
            $variants[] = [
                'id' => $variant->ID,
                'title' => $variant->Title,
                'price' => $variant->Price,
                'count' => $variant->Count
            ];
        }

        return [
            'id' => $this->ID,
            'sku' => $this->SKU,
            'price' => $this->BundledPrice,
            'stock' => "Infinite",
            'price_label' => '$' . number_format($this->BundledPrice, 2),
            'image' => null,
            'link' => $this->Link(),
            'title' => $this->Title,
            'variants' => $variants
        ];
    }
}
