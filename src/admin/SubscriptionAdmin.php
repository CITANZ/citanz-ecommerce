<?php

namespace Cita\eCommerce\Admin;

use SilverStripe\Dev\Debug;
use SilverStripe\Admin\ModelAdmin;
use Cita\eCommerce\Model\Subscription;
use SilverStripe\Security\Member;

/**
 * Description
 *
 * @package silverstripe
 * @subpackage mysite
 */
class SubscriptionAdmin extends ModelAdmin
{
    private static $managed_models = [
        Subscription::class
    ];

    /**
     * URL Path for CMS
     * @var string
     */
    private static $url_segment = 'subscriptions';

    /**
     * Menu title for Left and Main CMS
     * @var string
     */
    private static $menu_title = 'Subscriptions';

    private static $menu_icon_class = 'font-icon-back-in-time';
}
