<?php

namespace Cita\eCommerce\Interfaces;

interface ShippingInterface
{
    public function Calculate(&$order);
}
