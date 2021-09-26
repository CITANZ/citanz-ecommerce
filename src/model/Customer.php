<?php

namespace Cita\eCommerce\Model;

use SilverStripe\Dev\Debug;
use SilverStripe\Forms\TextareaField;
use SilverStripe\Forms\TextField;
use Cita\eCommerce\Model\Order;
use Cita\eCommerce\Model\Address;
use SilverStripe\Security\Group;

use Ramsey\Uuid\Uuid;
use SilverStripe\Control\Email\Email;
use SilverStripe\Core\Environment;
use SilverStripe\ORM\DataObject;
use SilverStripe\SiteConfig\SiteConfig;
use SilverStripe\Control\Director;
use Psr\Log\LoggerInterface;
use Cita\eCommerce\Model\SubscriptionOrder;

class Customer extends DataObject implements \JsonSerializable
{
    private static $dependencies = [
        'Logger' => '%$' . LoggerInterface::class,
    ];

    protected $logger;

    private static $table_name = 'Cita_eCommerce_Customer';
    private static $db = [
        'GUID' => 'Varchar(40)',
        'FirstName' => 'Varchar(255)',
        'LastName' => 'Varchar(255)',
        'Email' => 'Varchar(255)',
        'Phone' => 'Varchar(32)',
        'Password' => 'Varchar(255)',
        'LastLoggedIn' => 'Datetime',
        'Verified' => 'Boolean',
        'AccountInitialised' => 'Boolean',
        'Expiry' => 'Date',
        'NeverExpire' => 'Boolean',
    ];

    /**
     * Defines summary fields commonly used in table columns
     * as a quick overview of the data for this dataobject.
     *
     * @var array
     */
    private static $summary_fields = [
        'FirstName' => 'First name',
        'LastName' => 'Last name',
        'Email' => 'Email',
        'Created.Nice' => 'Date Signed up',
        'LastLoggedIn.Nice' => 'Last logged in',
    ];

    /**
     * Database indexes.
     *
     * @var array
     */
    private static $indexes = [
        'GUID' => 'unique',
        'Email' => 'unique',
    ];

    private static $has_many = [
        'VerificationCodes' => MemberVerificationCode::class,
        'Orders'    =>  Order::class,
        'Addresses' =>  Address::class,
    ];

    private static $belongs_many_many = [
        'Groups' => CustomerGroup::class,
    ];

    /**
     * this owns those
    */
    private static $owns = [
        'VerificationCodes',
    ];

    public function setLogger(LoggerInterface $logger)
    {
        $this->logger = $logger;
        return $this;
    }

    public function canRenew()
    {
        if ($this->NeverExpire) {
            return false;
        }

        if (empty($this->Expiry)) {
            return false;
        }

        $days = $this->config()->AllowMembershipRenewalBeforeExpiry;

        if (empty($days)) {
            return true;
        }

        return time() >= strtotime($this->Expiry . "-{$days} days");
    }

    /**
     * CMS Fields.
     *
     * @return FieldList
     */
    public function getCMSFields()
    {
        $fields = parent::getCMSFields();

        $fields->removeByName([
            'Password',
            'MyContactCardID',
        ]);

        $lastLoggedIn = $fields->fieldByName('Root.Main.LastLoggedIn');
        $lastLoggedIn->setReadonly(true);

        $guid = $fields->fieldByName('Root.Main.GUID');
        $guid->setReadonly(true);

        return $fields;
    }

    public function extendExpiry($days)
    {
        $this->logger->info($days);
        $expiry = empty($this->Expiry) || (is_int($this->Expiry) ? $this->Expiry : strtotime($this->Expiry)) < time() ? date('Y-m-d', time()) : $this->Expiry;

        $expiry = $expiry . " +{$days} days";
        $this->logger->info($expiry);
        $this->Expiry = strtotime($expiry);
        $this->logger->info(date('Y-m-d', $this->Expiry));
        $this->write();

        return $this->Expiry;
    }

    public function isValidMembership()
    {
        if ($this->NeverExpire) {
            return true;
        }

        if (empty($this->Expiry)) {
            return false;
        }

        return time() < strtotime($this->Expiry . '+1 day');
    }

    public function getLastSubscription()
    {
        return $this->Orders()->filter([
            'ClassName' => SubscriptionOrder::class,
            'Status' => ['PaymentReceived', 'Shipped', 'Completed', 'Free Order']
        ])->first();
    }

    public function jsonSerialize()
    {
        $extraData = $this->hasMethod('getExtraCustomerData') ? $this->ExtraCustomerData : [];
        return array_merge(
            $extraData,
            [
                'id' => $this->GUID,
                'email' => $this->Email,
                'first_name' => $this->FirstName,
                'last_name' => $this->LastName,
                'phone' => $this->Phone,
                'verified' => (bool) $this->Verified,
                'inited' => (bool) $this->AccountInitialised,
                'last_logged_in' => date(\DateTime::ATOM, strtotime($this->LastLoggedIn)),
            ]
        );
    }

    public function getMyQRCode()
    {
        $password = $this->Password;
        return $this->GUID . '@' . substr($password, 0, 8) . substr($password, -8);
    }

    public function getTitle()
    {
        $name = trim("{$this->FirstName} {$this->LastName}");

        if (!empty($name)) {
            return $name;
        }

        return $this->Email;
    }

    public function onBeforeWrite()
    {
        parent::onBeforeWrite();

        if (empty($this->GUID)) {
            $uuid = Uuid::uuid4();
            $this->GUID = $uuid->toString();
        }
    }

    public function onAfterWrite()
    {
        parent::onAfterWrite();

        if (!$this->Addresses()->exists()) {
            $this->createAddressHolder();
        }
    }

    public function createAddressHolder()
    {
        $defaultAddress = Address::create()->update([
            'FirstName' => $this->FirstName,
            'Surname' => $this->LastName,
            'Email' => $this->Email,
            'Phone' => $this->Phone,
            'CustomerID' => $this->ID,
        ]);

        $defaultAddress->write();
    }

    public function SendVerificationEmail()
    {
        $sitename = SiteConfig::current_site_config()->Title;
        $email = Email::create(null, $this->Email, "[{$sitename}] Your account activation code");

        $email->setHTMLTemplate('Cita\\eCommerce\\Email\\UserVerificationEmail');

        $verificationCode = MemberVerificationCode::createOnePassCode($this);

        $baseURL = !empty(Environment::getEnv('FRONTEND_BASE_URL')) ? Environment::getEnv('FRONTEND_BASE_URL') : Director::absoluteBaseURL();

        $email->setData([
            'ID' => $this->ID,
            'Customer' => $this,
            'VerificationCode' => $verificationCode->Code,
            'Link' => rtrim($baseURL, '/') . '/member/me?code=' . $verificationCode->Code,
            'Sitename' => $sitename,
        ]);

        $email->send();
    }

    public function RequestPasswordReset()
    {
        $sitename = SiteConfig::current_site_config()->Title;
        $baseURL = !empty(Environment::getEnv('FRONTEND_BASE_URL')) ? Environment::getEnv('FRONTEND_BASE_URL') : Director::absoluteBaseURL();

        $email = Email::create(null, $this->Email, "[{$sitename}] Password recovery link");

        $email->setHTMLTemplate('Cita\\eCommerce\\Email\\UserPasswordRecovery');

        $verificationCode = MemberVerificationCode::createOnePassCode($this, 'recovery');
        $recoverylink = rtrim($baseURL, '/') . "/member/passwordRecovery?recovery_token={$verificationCode->Code}";

        $email->setData([
            'ID' => $this->ID,
            'Customer' => $this,
            'RecoveryLink' => $recoverylink,
        ]);

        $email->send();
    }

    public static function hashPassword($password)
    {
        return password_hash($password, PASSWORD_DEFAULT);
    }
}
