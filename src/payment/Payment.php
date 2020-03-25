<?php

namespace Cita\eCommerce\Model;
use SilverStripe\ORM\DataObject;
use SilverStripe\Forms\DropdownField;
use Cita\eCommerce\Model\Order;

/**
 * Description
 *
 * @package silverstripe
 * @subpackage mysite
 */
class Payment extends DataObject
{
    /**
     * Defines the database table name
     * @var string
     */
    private static $table_name = 'Cita_eCommerce_Payment';

    private static $indexes = [
        'TransacID'     =>  [
            'type'      =>  'index',
            'columns'   =>  ['TransacID']
        ]
    ];

    /**
     * Database fields
     * @var array
     */
    private static $db = [
        'PaymentMethod'         =>  'Varchar(32)',
        'CardType'              =>  'Varchar(16)',
        'CardNumber'            =>  'Varchar(32)',
        'PayerAccountNumber'    =>  'Varchar(128)', // Poli bank account payment only
        'PayerAccountSortCode'  =>  'Varchar(64)',  // Poli bank account payment only
        'PayerBankName'         =>  'Varchar(128)', // Poli bank account payment only
        'CardHolder'            =>  'Varchar(128)',
        'Expiry'                =>  'Varchar(8)',
        'TransacID'             =>  'Varchar(128)',
        'Status'                =>  "Enum('Pending,Invoice Pending,Debit Pending,Unverified,Success,Failed,Cancelled,CardSavedOnly','Pending')",
        'Amount'                =>  'Currency',
        'Message'               =>  'Text',
        'IP'                    =>  'Varchar(48)',
        'PaypalPayerID'         =>  'Varchar(64)',
        'PaypalApprovalURL'     =>  'Varchar(1024)'
    ];

    /**
     * Defines summary fields commonly used in table columns
     * as a quick overview of the data for this dataobject
     * @var array
     */
    private static $summary_fields = [
        'PaymentMethod'         =>  'Payment Method',
        'Status'                =>  'Status',
        'CardDispaly'           =>  'Card Number',
        'Amount'                =>  'Amount',
        'Created'               =>  'At'
    ];

    /**
     * Defines a default list of filters for the search context
     * @var array
     */
    private static $searchable_fields = [
        'PaymentMethod',
        'Status',
        'Created'
    ];

    /**
     * Default sort ordering
     * @var array
     */
    private static $default_sort = ['ID' => 'DESC'];

    /**
     * Has_one relationship
     * @var array
     */
    private static $has_one = [
        'Order'                 =>  Order::class
    ];

    /**
     * CMS Fields
     * @return FieldList
     */
    public function getCMSFields()
    {
        $fields =   parent::getCMSFields();

        if (empty($this->PaymentMethod)) {
            $fields->removeByName([
                'CardType',
                'CardNumber',
                'PayerAccountNumber',
                'PayerAccountSortCode',
                'PayerBankName',
                'CardHolder',
                'Expiry'
            ]);
        } elseif ($this->PaymentMethod == 'POLi') {
            $fields->removeByName([
                'CardType',
                'CardNumber',
                'Expiry'
            ]);
        } else {
            $fields->removeByName([
                'PayerAccountNumber',
                'PayerAccountSortCode',
                'PayerBankName'
            ]);
        }

        return $fields;
    }

    public function CardDispaly()
    {
        return  $this->PaymentMethod == 'POLi' ?
                (!empty($this->PayerAccountNumber) ? ( $this->PayerAccountNumber . ', ' . $this->PayerBankName) : 'N/A') :
                $this->CardNumber;
    }

    public function getData()
    {
        return [
            'transaction_id'    =>  $this->TransacID,
            'created'           =>  $this->Created,
            'status'            =>  $this->Status,
            'amount'            =>  $this->Amount,
            'payment_method'    =>  $this->PaymentMethod,
            'account_no'        =>  $this->PayerAccountNumber,
            'bank_name'         =>  $this->PayerBankName,
            'sort_code'         =>  $this->PayerAccountSortCode,
            'card_type'         =>  $this->CardType,
            'card_number'       =>  $this->CardNumber,
            'card_holder'       =>  $this->CardHolder,
            'card_expiry'       =>  $this->Expiry,
            'approval_url'      =>  $this->Status == 'Unverified' && !empty($this->PaypalApprovalURL) ? $this->PaypalApprovalURL : null
        ];
    }
}
