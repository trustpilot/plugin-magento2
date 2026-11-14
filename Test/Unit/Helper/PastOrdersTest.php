<?php

namespace Trustpilot\Reviews\Test\Unit\Helper;

use Magento\Sales\Model\Order;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Trustpilot\Reviews\Helper\Data;
use Trustpilot\Reviews\Helper\OrderData;
use Trustpilot\Reviews\Helper\PastOrders;
use Trustpilot\Reviews\Helper\TrustpilotHttpClient;
use Trustpilot\Reviews\Helper\TrustpilotLog;

class PastOrdersTest extends TestCase
{
    /** @var Data|MockObject */
    private $helper;

    /** @var PastOrders */
    private $pastOrders;

    protected function setUp(): void
    {
        $this->helper = $this->createMock(Data::class);
        $this->pastOrders = new PastOrders(
            $this->helper,
            $this->createMock(TrustpilotHttpClient::class),
            $this->createMock(OrderData::class),
            $this->createMock(Order::class),
            $this->createMock(TrustpilotLog::class)
        );
    }

    private function stubConfig(array $values)
    {
        $this->helper->method('getConfig')->willReturnCallback(
            function ($config) use ($values) {
                return $values[$config] ?? null;
            }
        );
    }

    public static function corruptFailedOrdersProvider()
    {
        return [
            'truncated json' => ['{"B00125008":"QXV0b21hdGlj","B00125009":"QXV0'],
            'not an object' => ['null'],
        ];
    }

    /**
     * @dataProvider corruptFailedOrdersProvider
     */
    #[DataProvider('corruptFailedOrdersProvider')]
    public function testGetPastOrdersInfoToleratesCorruptFailedOrders($failedOrders)
    {
        $this->stubConfig([
            'sync_in_progress' => 'false',
            'show_past_orders_initial' => 'false',
            'past_orders' => '5',
            'failed_orders' => $failedOrders,
        ]);

        $info = $this->pastOrders->getPastOrdersInfo('stores', 1);

        $this->assertSame(5, $info['pastOrders']['synced']);
        $this->assertSame(0, $info['pastOrders']['unsynced']);
        $this->assertSame([], $info['pastOrders']['failed']);
    }

    public function testGetPastOrdersInfoListsFailedOrders()
    {
        $this->stubConfig([
            'sync_in_progress' => 'false',
            'show_past_orders_initial' => 'false',
            'past_orders' => '5',
            'failed_orders' => '{"B00125008":"QXV0b21hdGlj"}',
        ]);

        $info = $this->pastOrders->getPastOrdersInfo('stores', 1);

        $this->assertSame(1, $info['pastOrders']['unsynced']);
        $this->assertSame(
            [['referenceId' => 'B00125008', 'error' => 'QXV0b21hdGlj']],
            $info['pastOrders']['failed']
        );
    }

    /**
     * @dataProvider corruptFailedOrdersProvider
     */
    #[DataProvider('corruptFailedOrdersProvider')]
    public function testHandleTrustpilotResponseRecordsNewFailureOnCorruptFailedOrders($failedOrders)
    {
        $this->stubConfig([
            'past_orders' => '0',
            'failed_orders' => $failedOrders,
        ]);
        $saved = [];
        $this->helper->method('setConfig')->willReturnCallback(
            function ($config, $value) use (&$saved) {
                $saved[$config] = $value;
            }
        );

        $error = base64_encode('invalid email');
        $response = [
            'code' => 201,
            'data' => [(object)['referenceId' => 'B00125009', 'error' => 'invalid email']],
        ];
        $postBatch = ['invitations' => [['referenceId' => 'B00125009']]];

        $method = new \ReflectionMethod(PastOrders::class, 'handleTrustpilotResponse');
        $method->invoke($this->pastOrders, $response, $postBatch, 'stores', 1);

        $this->assertSame(['B00125009' => $error], json_decode($saved['failed_orders'], true));
    }
}
