<?php

namespace Cita\eCommerce\API;

use SilverStripe\Control\HTTPResponse;

trait APITrait
{
    public function json($data, $status = 200)
    {
        $response = (new HTTPResponse())
            ->setStatusCode($status)
            ->setBody(json_encode($data));

            return $response;
    }
}
