<?php

namespace Cita\eCommerce\Model;

class SubscriptionVariant extends Variant implements \JsonSerializable
{
    private static $table_name = 'Cita_eCommerce_SubscriptionVariant';

    private static $db = [
        'CreateMember' => 'Boolean',
        'Duration' => 'Decimal',
    ];

    private static $has_one = [
        'Product' => Subscription::class,
    ];

    private static $defaults = [
        'isDigital' => true,
    ];

    private static $belongs_many_many = [
        'Orders' => SubscriptionOrder::class,
    ];

    public function getCMSFields()
    {
        $fields = parent::getCMSFields();

        $fields->fieldByName('Root.Main.Duration')
            ->setDescription('duration in days');

        $fields
            ->fieldByName('Root.Main.VariantTitle')
            ->setTitle('Title')
            ->setDescription('Leave empty if you wish to use the same name as the subscription title');

        $fields->removeByName([
            'isDigital',
            'SubscriptionID',
        ]);

        return $fields;
    }

    public function jsonSerialize()
    {
        return $this->Data;
    }

    public function getData()
    {
        return array_merge(
            parent::getData(),
            [
                'duration' => $this->Duration . ' day' . ($this->Duration > 1 ? 's' : ''),
            ]
        );
    }
}
