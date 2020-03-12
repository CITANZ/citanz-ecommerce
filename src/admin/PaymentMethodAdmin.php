<?php

namespace Cita\eCommerce\Admin;
use SilverStripe\Admin\ModelAdmin;
use Cita\eCommerce\Model\PaymentMethod;
use Symbiote\GridFieldExtensions\GridFieldOrderableRows;

/**
 * Description
 *
 * @package silverstripe
 * @subpackage mysite
 */
class PaymentMethodAdmin extends ModelAdmin
{
    /**
     * Managed data objects for CMS
     * @var array
     */
    private static $managed_models = [
        PaymentMethod::class
    ];

    /**
     * URL Path for CMS
     * @var string
     */
    private static $url_segment = 'payment-methods';

    /**
     * Menu title for Left and Main CMS
     * @var string
     */
    private static $menu_title = 'Payment Methods';

    private static $menu_icon = 'cita/ecommerce: client/img/payment-methods.png';

    public function getEditForm($id = null, $fields = null)
    {
        $form = parent::getEditForm();
        $model = $this->sanitiseClassName($this->modelClass);
        $config = $form->Fields()->fieldByName($model)->getConfig();

        $config->addComponent(
            new GridFieldOrderableRows('Sort')
        );

        return $form;
    }
}
