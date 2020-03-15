<?php
namespace Cita\eCommerce\Traits;
use SilverStripe\Forms\Form;
use SilverStripe\Forms\HiddenField;
use SilverStripe\Forms\TextField;
use SilverStripe\Forms\FieldList;
use SilverStripe\Forms\FormAction;
use SilverStripe\View\ArrayData;
use Cita\eCommerce\eCommerce;

trait CartTemplateVariables
{
    public function getCart()
    {
        return eCommerce::get_cart();
    }

    public function getLastProcessedCart()
    {
        return eCommerce::get_last_processed_cart($this->request->param('id'));
    }

    public function getCatalogLink()
    {
        return eCommerce::get_catalog_url();
    }
}
