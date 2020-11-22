<?php

namespace Cita\eCommerce\Model;

/**
 * Description
 *
 * @package silverstripe
 * @subpackage mysite
 */
class Subscription extends Product
{
    private static $can_be_root = true;
    private static $table_name = 'Subscription';

    private static $defaults = [
        'ShowInMenus' => false,
    ];

    private static $has_many = [
        'Variants' => SubscriptionVariant::class,
    ];

    public function getData()
    {
        return array_merge(parent::getData(), [
            'subscriptions' => $this->Variants()->Data,
        ]);
    }
}
