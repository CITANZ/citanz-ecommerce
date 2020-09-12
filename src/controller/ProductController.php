<?php

namespace Cita\eCommerce\Controller;
use SilverStripe\Forms\Form;
use SilverStripe\Forms\HiddenField;
use SilverStripe\Forms\TextField;
use SilverStripe\Forms\FieldList;
use SilverStripe\Forms\FormAction;
use SilverStripe\Forms\RequiredFields;
use Cita\eCommerce\eCommerce;
use Cita\eCommerce\Model\Order;
use Cita\eCommerce\Model\Variant;
use PageController;

/**
 * Description
 *
 * @package silverstripe
 * @subpackage mysite
 */
class ProductController extends PageController
{
    /**
     * Defines methods that can be called directly
     * @var array
     */
    private static $allowed_actions = [
        'ProductForm'   => true,
        'VariantForm'   =>  true
    ];

    public function ProductForm()
    {
        $fields     =   FieldList::create(
            TextField::create('Quantity', 'Qty')->setValue(1),
            HiddenField::create('ID', 'ID', $this->ID),
            HiddenField::create('Class', 'Class', $this->ClassName)
        );

        $actions    =   FieldList::create(FormAction::create('AddToCart')->setTitle('Add to Cart'));
        $required   =   RequiredFields::create(['Quantity', 'ID', 'Class']);
        $form       =   Form::create($this, 'ProductForm', $fields, $actions, $required);

        return $form;
    }

    public function VariantForm($variant_id = null)
    {
        $fields     =   FieldList::create(
            TextField::create('Quantity', 'Qty')->setValue(1),
            HiddenField::create('ID', 'ID', $variant_id),
            HiddenField::create('Class', 'Class', Variant::class)
        );

        $actions    =   FieldList::create(FormAction::create('AddToCart')->setTitle('Add to Cart'));
        $required   =   RequiredFields::create(['Quantity', 'ID', 'Class']);
        $form       =   Form::create($this, 'VariantForm', $fields, $actions, $required);
        return $form;
    }

    public function AddToCart($data, Form $form)
    {
        $cart   =   eCommerce::get_cart();

        if (!$cart) {
            $cart   =   Order::create();
        }

        $cart->AddToCart($data['ID'], $data['Quantity']);
        $form->sessionMessage('Variant has been added to cart', 'good');
        return $this->redirectBack();
    }
}
