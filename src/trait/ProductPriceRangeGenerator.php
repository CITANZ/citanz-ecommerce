<?php
namespace Cita\eCommerce\Traits;

trait ProductPriceRangeGenerator
{
    public function get_price_ranges($products)
    {
        $lowest     =   $products->sort(['SortingPrice' => 'ASC'])->first();
        $highest    =   $products->sort(['SortingPrice' => 'DESC'])->first();

        if (!$lowest || !$highest) {
            return [];
        }

        $lowest     =   $lowest->SortingPrice;
        $highest    =   $highest->SortingPrice;

        $lowest     =   floor($lowest);
        $highest    =   ceil($highest);

        $increment  =   ceil(($highest - $lowest) / 10);

        $ranges     =   [];
        $from       =   $lowest;
        for ($i = 0; $i < 10; $i++) {
            $to         =   $from + $increment;
            if ($range = $this->get_range($products, $from, $to)) {
                $ranges[]   =   $range;
            }
            $from       =   $to;
        }

        return $ranges;
    }

    private function get_range($products, $from, $to)
    {
        $filtered   =   $products->filter([
                            'SortingPrice:GreaterThanOrEqual' => $from,
                            'SortingPrice:LessThanOrEqual' => $to
                        ]);
        if ($filtered->count() == 0) {
            return null;
        }

        $range  =   [
            'from'  =>  floor($filtered->sort(['SortingPrice' => 'ASC'])->first()->SortingPrice),
            'to'    =>  ceil($filtered->sort(['SortingPrice' => 'DESC'])->first()->SortingPrice)
        ];

        $range['from']  =   $range['from'] == $range['to'] ? $from : $range['from'];

        return $range;
    }
}
