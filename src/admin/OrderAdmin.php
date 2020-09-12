<?php

namespace Cita\eCommerce\Admin;
use SilverStripe\Dev\Debug;
use SilverStripe\Admin\ModelAdmin;
use Cita\eCommerce\Model\Order;
use SilverStripe\Security\Member;
use SilverStripe\Forms\GridField\GridFieldDetailForm;
use SilverStripe\Forms\GridField\GridField;
use SilverStripe\Forms\GridField\GridFieldDataColumns;
use SilverStripe\Forms\GridField\GridFieldExportButton;
use SilverStripe\Forms\GridField\GridFieldPrintButton;

/**
 * Description
 *
 * @package silverstripe
 * @subpackage mysite
 */
class OrderAdmin extends ModelAdmin
{
    /**
     * Managed data objects for CMS
     * @var array
     */
    private static $managed_models = [
        Order::class
    ];

    /**
     * URL Path for CMS
     * @var string
     */
    private static $url_segment = 'orders';

    /**
     * Menu title for Left and Main CMS
     * @var string
     */
    private static $menu_title = 'Orders';

    private static $menu_icon = 'cita/ecommerce: client/img/shopping-cart.png';

    public function getList()
    {
        $list   =   parent::getList();

        if (Member::currentUser() && Member::currentUser()->isDefaultadmin()) {
            return $list->filter(['ClassName' => Order::class]);
        }

        return $list->filter(['ClassName' => Order::class])->exclude(['Status' => 'Pending']);
    }

    public function getEditForm($id = null, $fields = null)
    {
        $form = parent::getEditForm($id, $fields);
        if($this->modelClass == Order::class) {
            $form
            ->Fields()
            ->fieldByName($this->sanitiseClassName($this->modelClass))
            ->getConfig()
            ->getComponentByType(GridFieldDetailForm::class)
            ->setItemRequestClass(OrderGridFieldDetailForm_ItemRequest::class);
        }

        if ($gridField = $form->Fields()->dataFieldByName($this->sanitiseClassName($this->modelClass))) {
            if ($gridField instanceof GridField) {
                $config = $gridField->getConfig();
                $dataColumns = $config->getComponentByType(GridFieldDataColumns::class);
                $dataColumns->setDisplayFields([
                    'CustomerReference' => 'Ref#',
                    'ItemCount' => 'Item(s)',
                    'CartItemList' => 'Details',
                    'PayableTotal' => 'Amount',
                    'Status' => 'Status',
                    'Paidat' => 'Paid at'
                ])->setFieldCasting([
                    'TotalAmount' => 'Currency->Nice',
                    'PayableTotal' => 'Currency->Nice',
                    'CartItemList' => 'HTMLText->RAW',
                    'Paidat' => 'Datetime->Nice'
                ]);
            }
        }

        return $form;
    }

}
