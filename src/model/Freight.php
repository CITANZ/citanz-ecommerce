<?php

namespace Cita\eCommerce\Model;
use SilverStripe\Core\Config\Config;
use SilverStripe\ORM\DataObject;
use SilverStripe\Forms\DropdownField;
use SilverStripe\Assets\Image;
use SilverStripe\ORM\DB;
use Cita\eCommerce\Interfaces\ShippingInterface;

/**
 * Description
 *
 * @package silverstripe
 * @subpackage mysite
 */
class Freight extends DataObject implements ShippingInterface
{
    private static $table_name = 'Cita_eCommerce_Freight';

    /**
     * Database fields
     * @var array
     */
    private static $db = [
        'Title'                 =>  'Varchar(128)',
        'Website'               =>  'Varchar(1024)',
        'TrackingPage'          =>  'Varchar(1024)',
        'Disabled'              =>  'Boolean'
    ];

    public function getData()
    {
        return [
            'id'        =>  $this->ID,
            'title'     =>  $this->Title,
            'url'       =>  $this->Website,
            'logo'      =>  $this->getSummaryLogo(80),
            'rate'      =>  null
        ];
    }

    /**
     * Has_one relationship
     * @var array
     */
    private static $has_one = [
        'Logo'          =>  Image::class
    ];

    /**
     * Has_many relationship
     * @var array
     */
    private static $has_many = [
        'Zones' =>  ShippingZone::class
    ];

    private static $owns = [ 'Logo' ];

    public function validate()
    {
        $result = parent::validate();

        if (!empty($this->Website) && strpos($this->Website, 'http://') !== 0 && strpos($this->Website, 'https://') !== 0) {
            $result->addError('The website URL that you entered is not valid. Please include http:// or https:// at the beginning.');
        }

        if (!empty($this->TrackingPage) && strpos($this->TrackingPage, 'http://') !== 0 && strpos($this->TrackingPage, 'https://') !== 0) {
            $result->addError('The tracking page URL that you entered is not valid. Please include http:// or https:// at the beginning.');
        }

        return $result;
    }

    private static $searchable_fields = [
        'Title'
    ];

    /**
     * Defines summary fields commonly used in table columns
     * as a quick overview of the data for this dataobject
     * @var array
     */
    private static $summary_fields = [
        'getSummaryLogo'    =>  'Logo',
        'Title'             =>  'Freight Provider'
    ];

    public function getSummaryLogo($height = 20)
    {
        if ($this->Logo()->exists()) {
            return $this->Logo()->ScaleHeight($height)->getAbsoluteURL();
        }

        return false;
    }

    /**
     * CMS Fields
     * @return FieldList
     */
    public function getCMSFields()
    {
        $fields =   parent::getCMSFields();
        $logo   =   $fields->fieldByName('Root.Main.Logo');

        $fields->addFieldToTab(
            'Root.Main',
            $logo,
            'Title'
        );

        $this->extend('updateCMSFields', $fields);
        return $fields;
    }

    public function find_zone($country)
    {
        foreach ($this->Zones() as $zone) {
            $list   =   json_decode($zone->Countries);
            if (in_array($country, $list)) {
                return $zone;
            }
        }

        return null;
    }

    // this will populate default freight option: NZ Post
    public function requireDefaultRecords()
    {
        $nzpost =   Freight::get()->filter(['Title' => 'NZ Post'])->first();
        if (empty($nzpost)) {
            $nzpost =   Freight::create(['Title' => 'NZ Post', 'Disabled' => true]);
            $nzpost->write();
            DB::alteration_message('Freight option: NZ Post created', 'created');
        }

        $zones  =   Config::inst()->get(__CLASS__, 'allowed_countries');
        foreach ($zones as $zone => $list) {
            $existing   =   $nzpost->Zones()->filter(['Title' => $zone])->first();
            if (!$existing) {
                $existing   =   ShippingZone::create(['Title'   =>  $zone, 'Countries' => json_encode(array_keys($list)), 'FreightID' => $nzpost->ID]);
                $existing->write();
                DB::alteration_message('NZ Post\'s zone: ' . $zone . ' created', 'created');
            }
        }
    }

    public function Calculate(&$order)
    {
        if (!empty($order->ShippingCountry)) {
            if ($zone = $this->find_zone($order->ShippingCountry)) {
                return $zone->CalculateOrderCost($this);
            }
        }

        return null;
    }
}
