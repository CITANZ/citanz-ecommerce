<?php

namespace Cita\eCommerce\Model;
use Page;

/**
 * Description
 *
 * @package silverstripe
 * @subpackage mysite
 */
class BundleList extends Page
{
    /**
     * Defines the database table name
     * @var string
     */
    private static $table_name = 'Cita_eCommerce_BundleList';

    /**
     * Defines the allowed child page types
     * @var array
     */
    private static $allowed_children = [
        Bundle::class
    ];
}
