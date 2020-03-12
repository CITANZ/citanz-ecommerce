<?php

namespace Cita\eCommerce\Model;
use SilverStripe\ORM\DataObject;
use SilverStripe\Assets\File;
use SilverStripe\Forms\DropdownField;
use Leochenftw\Extension\SortOrderExtension;

/**
 * Description
 *
 * @package silverstripe
 * @subpackage mysite
 */
class PaymentMethod extends DataObject
{
    /**
     * Defines the database table name
     * @var string
     */
    private static $table_name = 'Cita_eCommerce_PaymentMethod';

    /**
     * Database fields
     * @var array
     */
    private static $db = [
        'Title'         =>  'Varchar(128)',
        'Gateway'       =>  'Varchar(256)'
    ];

    /**
     * Defines summary fields commonly used in table columns
     * as a quick overview of the data for this dataobject
     * @var array
     */
    private static $summary_fields = [
        'Title', 'Gateway'
    ];

    /**
     * Defines extension names and parameters to be applied
     * to this object upon construction.
     * @var array
     */
    private static $extensions = [
        SortOrderExtension::class
    ];

    /**
     * Has_one relationship
     * @var array
     */
    private static $has_one = [
        'Logo'      =>  File::class
    ];

    /**
     * Relationship version ownership
     * @var array
     */
    private static $owns = [
        'Logo'
    ];

    /**
     * CMS Fields
     * @return FieldList
     */
    public function getCMSFields()
    {
        $fields = parent::getCMSFields();
        $fields->removeByName([
            'Gateway'
        ]);

        $fields->addFieldToTab(
            'Root.Main',
            DropdownField::create(
                'Gateway',
                'Payment Gateway',
                $this->config()->payment_methods
            )->setEmptyString('- select one -')
        );

        return $fields;
    }

    public function validate()
    {
        $result = parent::validate();

        if (!$this->Gateway) {
            $result->addError('Please bind this method to a payment gateway');
        }

        if ($existing = $this->ClassName::get()->filter(['Gateway' => $this->Gateway])->first()) {
            if ($existing->ID != $this->ID) {
                $result->addError('Gateway: ' . $this->config()->payment_methods[$this->Gateway] . ' has already been bound');
            }
        }

        return $result;
    }

    /**
     * Event handler called before writing to the database.
     */
    public function onBeforeWrite()
    {
        parent::onBeforeWrite();
        if (empty($this->Title)) {
            $this->Title    =   $this->config()->payment_methods[$this->Gateway];
        }
    }

    public function getData()
    {
        return [
            'title'     =>  $this->Title,
            'gateway'   =>  $this->Gateway
        ];
    }
}
