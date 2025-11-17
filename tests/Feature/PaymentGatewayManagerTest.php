<?php

namespace Tests\Feature;

use App\Services\Payment\Gateway1Client;
use App\Services\Payment\Gateway2Client;
use App\Services\Payment\PaymentGatewayManager;
use InvalidArgumentException;
use Tests\TestCase;

class PaymentGatewayManagerTest extends TestCase
{
    public function test_manager_returns_gateway1()
    {
        $manager = new PaymentGatewayManager(
            new Gateway1Client(),
            new Gateway2Client()
        );

        $gateway = $manager->driver('gateway1');

        $this->assertInstanceOf(Gateway1Client::class, $gateway);
    }

    public function test_manager_returns_gateway2()
    {
        $manager = new PaymentGatewayManager(
            new Gateway1Client(),
            new Gateway2Client()
        );

        $gateway = $manager->driver('gateway2');

        $this->assertInstanceOf(Gateway2Client::class, $gateway);
    }

    public function test_manager_throws_error_for_invalid_gateway()
    {
        $this->expectException(InvalidArgumentException::class);

        $manager = new PaymentGatewayManager(
            new Gateway1Client(),
            new Gateway2Client()
        );

        $manager->driver('whatever');
    }
}
