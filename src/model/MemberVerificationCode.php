<?php

namespace Cita\eCommerce\Model;

use SilverStripe\ORM\DataObject;
use Ramsey\Uuid\Uuid;

class MemberVerificationCode extends DataObject
{
    private static $table_name = 'Cita_eCommerce_MemberVerificationCode';

    private static $db = [
        'Code' => 'Varchar(40)',
        'Invalid' => 'Boolean',
        'Type' => 'Enum("activation,recovery")'
    ];

    private static $has_one = [
        'Customer' => Customer::class,
    ];

    private static $indexes = [
        'Code' => [
            'type' => 'unique',
        ],
    ];

    private static $summary_fields = [
        'ID' => 'ID',
        'Code' => 'Code',
        'Invalid.Nice' => 'Invalid',
        'Created.Nice' => 'Created',
    ];

    public static function createOnePassCode(Customer $customer, $type = 'activation')
    {
        $found = $customer->VerificationCodes()->filter([
            'Type' => $type,
            'Invalid' => false,
        ])->sort('ID DESC')->first();

        if ($found) {
            return $found;
        }

        if ($type == 'activation') {
            $bytes = random_bytes(32);
            $code = strtolower(substr(bin2hex($bytes), 0, 6));
        } else {
            $uuid = Uuid::uuid4();
            $code = $uuid->toString();
        }

        $verificationCode = new self();
        $verificationCode->Code = $code;
        $verificationCode->Type = $type;
        $verificationCode->Invalid = false;
        $verificationCode->CustomerID = $customer->ID;
        $verificationCode->write();

        return $verificationCode;
    }
}
