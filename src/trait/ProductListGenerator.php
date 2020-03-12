<?php
namespace Cita\eCommerce\Traits;

use SilverStripe\Forms\TextField;
use SilverStripe\Control\Controller;

trait ProductListGenerator
{
    public function add_pagesize_field(&$fields)
    {
        $fields->addFieldToTab(
            'Root.Main',
            TextField::create(
                'PageSize',
                'Number of items per page'
            ),
            'Content'
        );
    }
}
