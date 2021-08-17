<?php

namespace Cita\eCommerce\Model;

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

class Customer extends DataObject implements \JsonSerializable
{
    private static $table_name = 'Cita_eCommerce_Customer';
    private static $db = [
        'GUID' => 'Varchar(40)',
        'FirstName' => 'Varchar(255)',
        'LastName' => 'Varchar(255)',
        'Email' => 'Varchar(255)',
        'Password' => 'Varchar(255)',
        'LastLoggedIn' => 'Datetime',
        'Verified' => 'Boolean',
        'AccountInitialised' => 'Boolean',
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
        'Orders'    =>  Order::class,
        'Addresses' =>  Address::class
    ];

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

    public function jsonSerialize()
    {
        return [
            'id' => $this->GUID,
            'email' => $this->Email,
            'first_name' => $this->FirstName,
            'last_name' => $this->LastName,
            'verified' => (bool) $this->Verified,
            'inited' => (bool) $this->AccountInitialised,
            'last_logged_in' => date(\DateTime::ATOM, strtotime($this->LastLoggedIn)),
        ];
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

    public function SendVerificationEmail()
    {
        $sitename = SiteConfig::current_site_config()->Title;
        $email = Email::create(null, $this->Email, "[{$sitename}] Your account activation code");

        $email->setHTMLTemplate('App\\Web\\Email\\UserVerificationEmail');

        $verificationCode = MemberVerificationCode::createOnePassCode($this);

        $email->setData([
            'ID' => $this->ID,
            'User' => $this,
            'VerificationCode' => $verificationCode->Code,
            'Link' => rtrim(Environment::getEnv('FRONTEND_BASE_URL'), '/') . '/activate?code=' . $verificationCode->Code,
        ]);

        $email->send();
    }

    public function RequestPasswordReset()
    {
        $sitename = SiteConfig::current_site_config()->Title;
        $frontend_base_url = rtrim(Environment::getEnv('FRONTEND_BASE_URL'), '/');
        if (empty($frontend_base_url)) {
            user_error('Please define FRONTEND_BASE_URL constant in your .env file');
        }

        $email = Email::create(null, $this->Email, "[{$sitename}] Password recovery link");

        $email->setHTMLTemplate('App\\Web\\Email\\UserPasswordRecovery');

        $verificationCode = MemberVerificationCode::createOnePassCode($this, 'lostpass');
        $recoverylink = "{$frontend_base_url}/me/password-recovery?recovery_token={$verificationCode->Code}";

        $email->setData([
            'ID' => $this->ID,
            'User' => $this,
            'RecoveryLink' => $recoverylink,
        ]);

        $email->send();
    }

    public static function hashPassword($password)
    {
        return password_hash($password, PASSWORD_DEFAULT);
    }
}
