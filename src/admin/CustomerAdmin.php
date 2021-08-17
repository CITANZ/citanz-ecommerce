<?php

namespace Cita\eCommerce\Admin;
use SilverStripe\Admin\ModelAdmin;
use Cita\eCommerce\Model\Customer;
use Cita\eCommerce\Model\CustomerGroup;

/**
 * Description
 *
 * @package silverstripe
 * @subpackage mysite
 */
class CustomerAdmin extends ModelAdmin
{
    /**
     * Managed data objects for CMS
     * @var array
     */
    private static $managed_models = [
        Customer::class,
        CustomerGroup::class,
    ];

    /**
     * URL Path for CMS
     * @var string
     */
    private static $url_segment = 'customers';

    /**
     * Menu title for Left and Main CMS
     * @var string
     */
    private static $menu_title = 'Customers';

    /**
     * Menu icon for Left and Main CMS
     * @var string
     */
    private static $menu_icon = 'cita/ecommerce: client/img/customer.png';

}
