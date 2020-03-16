<?php
namespace Cita\eCommerce\Traits;
use Cita\eCommerce\Model\ProductCollection;
use Cita\eCommerce\Model\Category;

trait TopSellerGenerator
{
    public function get_top_sellers()
    {
        if ($best = ProductCollection::get()->filter(['isBestSeller' => true])->first()) {
            if ($products = $best->Products()->sort('RAND()')->limit(3)) {
                return [
                    'title' =>  'Top Sellers',
                    'list'  =>  $products->getTileData(),
                    'url'   =>  $best->Link()
                ];
            }
        }

        return null;
    }

    public function get_new_offer()
    {
        if ($special = ProductCollection::get()->filter(['isSpecial' => true])->first()) {
            if ($product = $special->Products()->sort('RAND()')->limit(1)->first()) {
                $discount_rate  =   $product->calc_discount_rate();
                return [
                    'title' =>  $product->Title,
                    'label' =>  ($discount_rate ? ($discount_rate . '%! ') : '') . $product->Title,
                    'link'  =>  $product->Link(),
                    'thumb' =>  $product->Image()->getData('Fill', 255, 320)
                ];
            }
        }

        return null;
    }

    public function get_random_categories()
    {
        return [
            'list'  =>  Category::get()->filter(['ParentID' => 0])->sort('RAND()')->limit(3)->getData()
        ];
    }
}
