<?php

namespace App\Web\Admin;
use SilverStripe\Admin\ModelAdmin;
use Cita\eCommerce\Model\Category;
use Cita\eCommerce\Model\Brand;

/**
 * Description
 *
 * @package silverstripe
 * @subpackage mysite
 */
class CategoryAdmin extends ModelAdmin
{
    /**
     * Managed data objects for CMS
     * @var array
     */
    private static $managed_models = [
        Brand::class,
        Category::class
    ];

    /**
     * URL Path for CMS
     * @var string
     */
    private static $url_segment = 'brand-category';

    /**
     * Menu title for Left and Main CMS
     * @var string
     */
    private static $menu_title = 'Brand & Category';
    // private static $menu_icon = 'b2solution/playmarket: client/img/application-form.png';

    public function getList()
    {
        $list   =   parent::getList();

        if ($this->modelClass == Category::class) {
            return $list->filter(['ParentID' => 0])->sort(['Title' => 'ASC']);
        }

        return $list;
    }

}
