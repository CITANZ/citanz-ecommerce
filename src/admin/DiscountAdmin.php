<?php

namespace Cita\eCommerce\Admin;
use SilverStripe\Admin\ModelAdmin;
use Cita\eCommerce\Model\Discount;
use Colymba\BulkManager\BulkManager;
/**
 * Description
 *
 * @package silverstripe
 * @subpackage mysite
 */
class DiscountAdmin extends ModelAdmin
{
    /**
     * Managed data objects for CMS
     * @var array
     */
    private static $managed_models = [
        Discount::class
    ];

    /**
     * URL Path for CMS
     * @var string
     */
    private static $url_segment = 'discounts';

    /**
     * Menu title for Left and Main CMS
     * @var string
     */
    private static $menu_title = 'Discounts';

    private static $menu_icon = 'cita/ecommerce: client/img/percentage.png';

    /**
     * @param Int $id
     * @param FieldList $fields
     * @return Form
     */
    public function getEditForm($id = null, $fields = null)
    {
        $form           =   parent::getEditForm($id, $fields);
        $gridFieldName  =   $this->sanitiseClassName($this->modelClass);
        $gridField      =   $form->Fields()->fieldByName($gridFieldName);

        $gridField->getConfig()->addComponent(new BulkManager());

        return $form;
    }

}
