<?php

namespace Cita\eCommerce\API;

use SilverStripe\Control\Controller;
use Cita\eCommerce\Model\Product;
use SilverStripe\Versioned\Versioned;
use Cita\eCommerce\Model\Discount;

class CMSDiscountAPI extends Controller
{
    use APITrait;

    private static $allowed_actions = [
        'search_product',
        'add_product',
        'remove_product'
    ];

    protected function handleAction($request, $action)
    {
        $header = $this->getResponse();

        if (!$request->isAjax()) {
            return $this->httpError(400, 'AJAX access only');
        }

        if (in_array($action, static::$allowed_actions)) {
            return $this->json($this->$action($request));
        }

        return $this->httpError(404, 'not allowed');
    }

    public function search_product(&$request)
    {
        if (!$request->isPost()) {
            return $this->httpError(400, 'Wrong method');
        }

        $discount_id = $request->postVar('discount_id');

        if (empty($discount_id)) {
            return $this->httpError(400, 'missing parameters');
        }

        $discount = Discount::get_by_id($discount_id);

        if (empty($discount)) {
            return $this->httpError(404, 'discount or product not found');
        }

        if ($term = $request->postVar('term')) {
            $filter = ['Title:PartialMatch' => $term];
            if ($discount->Products()->exists()) {
                $filter['ID:not'] = $discount->Products()->column('ID');
            }
            $raw = Versioned::get_by_stage(Product::class, 'Stage')->filter($filter)->limit(5);

            $products = [];

            foreach ($raw as $product) {
                $products[] = [
                    'id' => $product->ID,
                    'title' => $product->Title,
                    'product_class' => $product->ClassName
                ];
            }

            return $products;
        }

        return [];
    }

    public function add_product(&$request)
    {
        if (!$request->isPost()) {
            return $this->httpError(400, 'Wrong method');
        }

        $discount_id = $request->postVar('discount_id');
        $product_id = $request->postVar('product_id');

        if (empty($discount_id) || empty($product_id)) {
            return $this->httpError(400, 'missing parameters');
        }

        $discount = Discount::get_by_id($discount_id);
        $product = Versioned::get_by_stage(Product::class, 'Stage')->byID($product_id);

        if (empty($discount) || empty($product)) {
            return $this->httpError(404, 'discount or product not found');
        }

        $discount->Products()->add($product);

        return [
            'id' => $product->ID,
            'title' => $product->Title
        ];
    }

    public function remove_product(&$request)
    {
        if (!$request->isPost()) {
            return $this->httpError(400, 'Wrong method');
        }

        $discount_id = $request->postVar('discount_id');
        $product_id = $request->postVar('product_id');

        if (empty($discount_id) || empty($product_id)) {
            return $this->httpError(400, 'missing parameters');
        }

        $discount = Discount::get_by_id($discount_id);
        $product = Versioned::get_by_stage(Product::class, 'Stage')->byID($product_id);

        if (empty($discount) || empty($product)) {
            return $this->httpError(404, 'discount or product not found');
        }

        $discount->Products()->remove($product);

        return true;
    }
}
