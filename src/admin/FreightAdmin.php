<?php

namespace Cita\eCommerce\Admin;
use SilverStripe\Admin\ModelAdmin;
use Cita\eCommerce\Model\Freight;

/**
 * Description
 *
 * @package silverstripe
 * @subpackage mysite
 */
class FreightAdmin extends ModelAdmin
{
    /**
     * Managed data objects for CMS
     * @var array
     */
    private static $managed_models = [
        Freight::class
    ];

    /**
     * URL Path for CMS
     * @var string
     */
    private static $url_segment = 'freight';

    /**
     * Menu title for Left and Main CMS
     * @var string
     */
    private static $menu_title = 'Freight Options';

    private static $menu_icon = 'cita/ecommerce: client/img/freight.png';
}
