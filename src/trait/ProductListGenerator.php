<?php
namespace Cita\eCommerce\Traits;

use SilverStripe\Forms\TextField;
use SilverStripe\Control\Controller;
use SilverStripe\Core\Injector\Injector;
use Leochenftw\Util\CacheHandler;
use SilverStripe\Control\HTTPRequest;
use SilverStripe\Versioned\Versioned;
use Cita\eCommerce\Model\Product;
use Cita\eCommerce\Model\Category;
use Leochenftw\Util;
use SilverStripe\ORM\DB;
use SilverStripe\ORM\ArrayList;
use SilverStripe\View\ArrayData;
use SilverStripe\ORM\PaginatedList;
use Cita\eCommerce\Model\Variant;

trait ProductListGenerator
{
    use TopSellerGenerator;

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

    public function getProductList()
    {
        $request    =   Injector::inst()->get(HTTPRequest::class);
        $category   =   !empty($request->getVar('category')) ? $request->getVar('category') : null;
        $cslug      =   $category;
        $brand      =   !empty($request->getVar('brand')) ? $request->getVar('brand') : null;
        $price_from =   !is_null($request->getVar('price_from')) ? ( (int) $request->getVar('price_from') ) : null;
        $price_to   =   !is_null($request->getVar('price_to')) ? ( (int) $request->getVar('price_to') ) : null;
        $sort_by    =   !empty($request->getVar('sort')) ? $request->getVar('sort') : 'Title';
        $order_by   =   !empty($request->getVar('order')) ? $request->getVar('order') : 'ASC';
        $page       =   !empty($request->getVar('page')) ? $request->getVar('page') : 0;
        $sort_by    =   $sort_by == 'Price' ? 'SortingPrice' : $sort_by;

        $result     =   $this->get_products($category, $brand);

        if (empty($price_ranges)) {
            $price_ranges   =   $this->get_price_ranges($result);
            CacheHandler::save('page.' . $this->key_cutter($this->ID, $category, $brand) . '.price_ranges', $price_ranges, 'PageData');
        }

        $data['price_ranges']   =   $price_ranges;

        if (!is_null($price_from) && !is_null($price_to)) {
            $result =   $result->filter([
                'SortingPrice:GreaterThanOrEqual'   =>  $price_from,
                'SortingPrice:LessThanOrEqual'      =>  $price_to
            ]);
        }

        $result     =   $result->sort([$sort_by => $order_by]);

        return PaginatedList::create($result, $request)->setPageLength($this->PageSize);
    }

    public function getData()
    {
        $key        =   $this->ID;
        $request    =   Injector::inst()->get(HTTPRequest::class);
        $category   =   !empty($request->getVar('category')) ? $request->getVar('category') : null;
        $cslug      =   $category;
        $brand      =   !empty($request->getVar('brand')) ? $request->getVar('brand') : null;
        $price_from =   !is_null($request->getVar('price_from')) ? ( (int) $request->getVar('price_from') ) : null;
        $price_to   =   !is_null($request->getVar('price_to')) ? ( (int) $request->getVar('price_to') ) : null;
        $sort_by    =   !empty($request->getVar('sort')) ? $request->getVar('sort') : 'Title';
        $order_by   =   !empty($request->getVar('order')) ? $request->getVar('order') : 'ASC';
        $page       =   !empty($request->getVar('page')) ? $request->getVar('page') : 0;
        $sort_by    =   $sort_by == 'Price' ? 'SortingPrice' : $sort_by;
        $key        =   $this->key_cutter($key, $category, $brand, $price_from, $price_to, $sort_by, $order_by, $page);
        $data       =   CacheHandler::read('page.' . $key, 'PageData');

        if (empty($data)) {
            $data       =   parent::getData();
            $result     =   $this->get_products($category, $brand);
            if (empty($result)) {
                $data['result'] =   [
                    'list'  =>  [],
                    'pages' =>  0,
                    'total' =>  0
                ];
            } else {
                $price_ranges   =   CacheHandler::read('page.' . $this->key_cutter($this->ID, $category, $brand) . '.price_ranges', 'PageData');

                if (empty($price_ranges)) {
                    $price_ranges   =   $this->get_price_ranges($result);
                    CacheHandler::save('page.' . $this->key_cutter($this->ID, $category, $brand) . '.price_ranges', $price_ranges, 'PageData');
                }

                $data['price_ranges']   =   $price_ranges;

                if (!is_null($price_from) && !is_null($price_to)) {
                    $result =   $result->filter([
                        'SortingPrice:GreaterThanOrEqual'   =>  $price_from,
                        'SortingPrice:LessThanOrEqual'  =>  $price_to
                    ]);
                }

                $result     =   $result->sort([$sort_by => $order_by]);
                $total      =   $result->count();
                $pages      =   ceil($total / $this->PageSize);
                $result     =   $result->limit($this->PageSize, $page * $this->PageSize);

                $data['result'] =   [
                    'list'  =>  $result->getTileData(),
                    'pages' =>  $pages,
                    'total' =>  $total
                ];
            }

            if (!empty($category)) {
                if ($category = $this->get_category($category)) {
                    $data['title']      =   $category->Title;
                    $data['content']    =   Util::preprocess_content($category->Content);
                    $data['ancestors']  =   $category->get_ancestors($this);
                }
            } elseif (!empty($brand)) {
                if ($brand = $this->get_brand($brand)) {
                    $data['title']      =   $brand->Title;
                    $data['content']    =   Util::preprocess_content($brand->Content);
                }
            }

            CacheHandler::save('page.' . $key, $data, 'PageData');
        }

        if ($new_offer = $this->get_new_offer()) {
            $data['new_offer']  =   $new_offer;
        }

        if ($top_sellers = $this->get_top_sellers()) {
            $data['top_sellers']    =   $top_sellers;
        }

        $data['related_categories'] =   $this->get_related_categories($cslug);

        return $data;
    }

    private function get_related_categories($category = null)
    {
        $key    =   'page.' . $this->ID . '.page-categories' . '.' . (!empty($category) ? $category : 'all-categories');
        $data   =   CacheHandler::read($key, 'PageData');

        if (empty($data)) {

            if ($category) {
                if ($citem = $this->get_category($category)) {
                    $list   =   $citem->Children()->getData();
                }
            } else {
                if ($this->hasMethod('Products')) {
                    $children_ids   =   $this->Products()->column('ID');
                } else {
                    $children_ids   =   $this->AllChildren()->column('ID');
                }

                $cids       =   [];
                foreach ($children_ids as $id) {
                    $cids   =   array_merge($cids, DB::query('SELECT "Cita_eCommerce_CategoryID" FROM "Cita_eCommerce_Category_Products" WHERE "Cita_eCommerce_ProductID" = ' . $id)->column('Cita_eCommerce_CategoryID'));
                }

                if (!empty($cids)) {
                    $list   =   Category::get()->filter(['ID' => array_unique($cids), 'ParentID' => 0])->getData();
                }
            }

            if (!empty($list)) {
                Util::exclude_empty_category($list);
            }

            $data   =   [
                'list'  =>  !empty($list) ? $list : [],
                'upper' =>  !empty($citem) && $citem->Parent()->exists() ?
                            ['title' => $citem->Parent()->Title, 'slug' => $citem->Parent()->Slug] :
                            ['title' => $this->Title, 'url' => $this->Link()]
            ];

            CacheHandler::save($key, $data, 'PageData');
        }

        return $data;
    }

    private function key_cutter($key, $category, $brand, $price_from = null, $price_to = null, $sort_by = null, $order_by = null, $page = null)
    {
        if (empty($sort_by) && empty($order_by) && empty($page)) {
            return strtolower($key . '.' . (!empty($category) ? $category : 'all-cateogry') . '.' . (!empty($brand) ? $brand : 'all-brand'));
        }
        return strtolower(
            $key . '.' .
            (!empty($category) ? $category : 'all-cateogry') . '.' .
            (!empty($brand) ? $brand : 'all-brand') . '.' .
            (!empty($price_from) ? $price_from : 'no-bottom') . '.' .
            (!empty($price_to) ? $price_to : 'no-top') . '.' .
            $sort_by . '.' .
            $order_by . '.' .
            $page);
    }

    private function get_products($category = null, $brand = null)
    {
        if (!empty($category) && !empty($brand)) {
            if (($category = $this->get_category($category)) && ($brand = $this->get_brand($brand))) {
                $cids   =   $category->Products()->column('ID');
                $bids   =   $brand->Products()->column('ID');
                $ids    =   array_intersect($cids, $bids);
                return Versioned::get_by_stage(Product::class, 'Live')->filter(['ID' => $ids]);
            }

            return null;
        }

        if (!empty($category) && empty($brand)) {
            if ($category = $this->get_category($category)) {
                return $category->Products();
            }

            return Controller::curr()->httpError(404, 'There is no such Category!');
        }

        if (empty($category) && !empty($brand)) {
            if ($brand = $this->get_brand($brand)) {
                return $brand->Products();
            }

            return Controller::curr()->httpError(404, 'There is no such Brand!');
        }

        if ($this->hasMethod('Products')) {
            $children_ids   =   $this->Products()->column('ID');
        } else {
            $children_ids   =   $this->AllChildren()->column('ID');
        }

        if (empty($children_ids)) {
            return Versioned::get_by_stage(Product::class, 'Live')->filter(['ID' => -1]);
        }

        $variants = Variant::get()->where("ProductID IN (" . implode(',', $children_ids) . ") AND ((OutOfStock = 1 AND StockCount > 0) OR (InfiniteStock = 1)) ");

        $eligibles = $variants->column('ProductID');

        if (empty($eligibles)) {
            $eligibles = -1;
        }

        return Versioned::get_by_stage(Product::class, 'Live')->filter(['ID' => $eligibles]);
    }

    private function get_category($slug)
    {
        return Category::get()->filter(['Slug' => $slug])->first();
    }

    private function get_brand($slug)
    {
        return Brand::get()->filter(['Slug' => $slug])->first();
    }
}
