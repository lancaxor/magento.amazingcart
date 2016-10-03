<?php

/**
 * Created by PhpStorm.
 * User: serf
 * Date: 03.10.16
 * Time: 17:16
 */

namespace Amazingcard\JsonApi\Api;

interface PaymentMethodInterface
{
    public function getCheckoutRedirectUrl($orderId, $paymentMethod);

    public function getOrderRedirectUrl($orderId, $paymentMethod);

    public function getName($paymentMethod);

    public function getData($paymentMethod);
}