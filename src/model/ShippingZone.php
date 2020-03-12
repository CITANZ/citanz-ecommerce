<?php

namespace Cita\eCommerce\Model;
use SilverStripe\Core\Config\Config;
use SilverStripe\ORM\DataObject;
use SilverStripe\Forms\ListboxField;
use UncleCheese\DisplayLogic\Forms\Wrapper;

/**
 * Description
 *
 * @package silverstripe
 * @subpackage mysite
 */
class ShippingZone extends DataObject
{
    private static $table_name = 'Cita_eCommerce_ShippingZone';

    /**
     * Database fields
     * @var array
     */
    private static $db = [
        'Title'                 =>  'Varchar(128)',
        'Countries'             =>  'Text',
        'MeasurementUnit'       =>  'Enum("KG,Unit")',
        'SingleItemPrice'       =>  'Currency',
        'BasePrice'             =>  'Currency',
        'AfterX'                =>  'Decimal',
        'Increment'             =>  'Currency',
        'ContainerPrice'        =>  'Currency',
        'ContainerCapacity'     =>  'Decimal',
        'MaxPrice'              =>  'Currency'
    ];

    /**
     * Defines summary fields commonly used in table columns
     * as a quick overview of the data for this dataobject
     * @var array
     */
    private static $summary_fields = [
        'Title'             =>  'Title',
        'ShowCountries'     =>  'Countries',
        'getSummaryPrice'   =>  'Cost'
    ];

    /**
     * Defines a default list of filters for the search context
     * @var array
     */
    private static $searchable_fields = [
        'Title'
    ];

    /**
     * Has_one relationship
     * @var array
     */
    private static $has_one = [
        'Freight'   =>  Freight::class
    ];

    private function simple_under_x()
    {
        return ($this->MeasurementUnit == 'KG' ? $this->AfterX : strtolower(round($this->AfterX))) . ' ' . strtolower($this->MeasurementUnit) . ($this->ContainerCapacity > 1 ? 's' : '');
    }

    public function getSummaryPrice()
    {
        return '$' . $this->BasePrice . ' under ' . $this->simple_under_x() . ', and + $' . $this->Increment . ' for every additional ' . strtolower($this->MeasurementUnit) . '. Container cost is $' . $this->ContainerPrice . ' for every ' . ((int) ($this->ContainerCapacity)) . ' ' . strtolower($this->MeasurementUnit) . ($this->ContainerCapacity > 1 ? 's' : '') . (!empty($this->MaxPrice) ? ('. Max cost: $' . $this->MaxPrice) : '') . '.';
    }

    /**
     * CMS Fields
     * @return FieldList
     */
    public function getCMSFields()
    {
        $fields =   parent::getCMSFields();

        $fields->addFieldToTab(
            'Root.Main',
            ListboxField::create(
                'Countries',
                'Countries',
                static::get_allowed_countries()
            ),
            'MeasurementUnit'
        );

        $fields->removeByName([
            'FreightID'
        ]);

        Wrapper::create(
            $field_singleitem_price =   $fields->fieldByName('Root.Main.SingleItemPrice')
                                        ->setDescription('This price will be the <strong>ULTIMATE</strong> shipping cost, when there is only <strong>1</strong> item in the shipment')
                                        ->displayIf('MeasurementUnit')->isEqualTo('Unit')->end()
        );

        $base_price =   $fields->fieldByName('Root.Main.BasePrice');
        $base_price->setDescription('The price at or under <strong>X</strong> ' . ($this->exists() ? $this->MeasurementUnit : 'KG') . '(s)');

        $after_x    =   $fields->fieldByName('Root.Main.AfterX');
        $after_x->setTitle('After X ' . ($this->exists() ? $this->MeasurementUnit : 'KG') . '(s)')
            ->setDescription('This is when the freight cost starts to increase.');

        $increment  =   $fields->fieldByName('Root.Main.Increment');
        $increment->setDescription('After X ' . ($this->exists() ? $this->MeasurementUnit : 'KG') . '(s), the freight cost is going to increase by this value.');

        $container  =   $fields->fieldByName('Root.Main.ContainerPrice');
        $container->setDescription('Some freight providers don\'t give you free containers, hence you need to include the cost here. But, you can leave it blank or 0.00');

        $capacity   =   $fields->fieldByName('Root.Main.ContainerCapacity');
        $capacity->setDescription('The capacity of the container. e.g. a container can contain 10 items/KG. When there are 9 items, it will cost the customer 1 container price cost; When there are 11 items, the customer needs to pay for 2 containers. <strong style="text-decoration: underline;">Leave it blank or 0.00 if your container is free</strong>');

        return $fields;
    }

    private function CalculateContainerCost($value)
    {
        if ($this->ContainerCapacity == 0) {
            return 0;
        }

        if ($this->ContainerCapacity > $value) {
            return $this->ContainerPrice;
        }

        return $this->getNumContainers($value) * $this->ContainerPrice;
    }

    private function getNumContainers($value)
    {
        return ceil($value / $this->ContainerCapacity);
    }

    public function CalculateOrderCost(&$order)
    {
        if ($this->MeasurementUnit == 'KG') {
            $weight =   $order->TotalWeight;
            if ($weight <= $this->AfterX) {
                $sum    =   $this->BasePrice + $this->CalculateContainerCost($weight);
                if (!empty($this->MaxPrice) && $sum > $this->MaxPrice) {
                    $sum    =   $this->MaxPrice;
                }
                return array_merge($this->getData(), [
                    'cost'  =>  $sum,
                    'note'  =>  $this->getShipmentDescription($order)
                ]);
            }

            $sum    =   $this->BasePrice + ($weight - $this->AfterX) * $this->Increment + $this->CalculateContainerCost($weight);
            if (!empty($this->MaxPrice) && $sum > $this->MaxPrice) {
                $sum    =   $this->MaxPrice;
            }

            return array_merge($this->getData(), [
                'cost'  =>  $sum,
                'note'  =>  $this->getShipmentDescription($order)
            ]);
        }

        $unit   =   $order->ShippableItemCount();

        if ($unit == 1) {
            return array_merge($this->getData(), ['cost' => $this->SingleItemPrice]);
        }

        $diff   =   $unit - $this->AfterX;

        if ($diff <= 0) {
            $sum    =   $this->BasePrice + $this->CalculateContainerCost($unit);
        } else {
            $sum    =   $this->BasePrice + $diff * $this->Increment + $this->CalculateContainerCost($unit);
        }

        if (!empty($this->MaxPrice) && $sum > $this->MaxPrice) {
            $sum    =   $this->MaxPrice;
        }

        return array_merge($this->getData(), [
            'cost'  =>  $sum,
            'note'  =>  $this->getShipmentDescription($order)
        ]);
    }

    private function getShipmentDescription(&$order)
    {
        if ($this->MeasurementUnit == 'KG') {
            return 'Your shipment weights ' . $order->TotalWeight . 'Kg(s), and uses ' . $this->getNumContainers($order->TotalWeight) . ' container(s).';
        }

        return 'Your shipment contains ' . $order->ShippableItemCount() . ' item(s), and uses ' . $this->getNumContainers($order->ShippableItemCount()) . ' container(s).';
    }

    public static function get_allowed_countries()
    {
        $zones      =   Config::inst()->get(Freight::class, 'allowed_countries');
        $countries  =   [];
        foreach ($zones as $key => $zone) {
            foreach ($zone as $code => $name) {
                $countries[$code]   =   $name;
            }
        }

        return $countries;
    }

    public function ShowCountries()
    {
        $list   =   json_decode($this->Countries);
        $str    =   '';
        foreach ($list as $code) {
            $str    .=  static::get_allowed_countries()[$code] . ', ';
        }

        return rtrim($str, ', ');
    }

    public function getData()
    {
        return [
            'title'         =>  ($this->Freight()->exists() ? ($this->Freight()->Title . ' - ') : '') . $this->Title,
            'countries'     =>  $this->ShowCountries(),
            'description'   =>  $this->getSummaryPrice()
        ];
    }
}
