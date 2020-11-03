<?php

namespace Cita\eCommerce\Task;
use SilverStripe\Dev\BuildTask;
use Cita\eCommerce\Model\Customer;
use Cita\eCommerce\Model\Order;
/**
 * Description
 *
 * @package silverstripe
 * @subpackage mysite
 */
class CleanupCarts extends BuildTask
{
    /**
     * @var bool $enabled If set to FALSE, keep it from showing in the list
     * and from being executable through URL or CLI.
     */
    protected $enabled = true;

    /**
     * @var string $title Shown in the overview on the TaskRunner
     * HTML or CLI interface. Should be short and concise, no HTML allowed.
     */
    protected $title = 'Delete expired carts';

    /**
     * @var string $description Describe the implications the task has,
     * and the changes it makes. Accepts HTML formatting.
     */
    protected $description = 'Delete expired carts';

    /**
     * This method called via the TaskRunner
     *
     * @param SS_HTTPRequest $request
     */
    public function run($request)
    {
        $carts  =   Order::get()->filter(['Status' => 'Pending', 'Created:LessThanOrEqual' => strtotime('-30 days')]);
        $count  =   $carts->count();
        foreach ($carts as $cart) {
            if ($cart->ClassName != Order::class) {
                $subclass   =   $cart->ClassName;
                $cart       =   call_user_func($subclass .'::get')->byID($cart->ID);
            }
            $cart->delete();
        }

        print $count . ' redundant cart' . ($count > 1 ? 's have ' : ' has ') . 'been recycled!' . PHP_EOL;
    }
}
