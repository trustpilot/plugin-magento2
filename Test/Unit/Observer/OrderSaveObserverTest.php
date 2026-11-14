<?php

namespace Trustpilot\Reviews\Test\Unit\Observer;

use Magento\Config\Model\ResourceModel\Config;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Trustpilot\Reviews\Helper\Data;
use Trustpilot\Reviews\Helper\OrderData;
use Trustpilot\Reviews\Helper\TrustpilotHttpClient;
use Trustpilot\Reviews\Helper\TrustpilotLog;
use Trustpilot\Reviews\Observer\OrderSaveObserver;

class OrderSaveObserverTest extends TestCase
{
    /** @var Data|MockObject */
    private $helper;

    /** @var Config|MockObject */
    private $config;

    /** @var TrustpilotLog|MockObject */
    private $log;

    /** @var OrderSaveObserver */
    private $observer;

    protected function setUp(): void
    {
        $this->helper = $this->createMock(Data::class);
        $this->config = $this->createMock(Config::class);
        $this->log = $this->createMock(TrustpilotLog::class);
        $this->observer = new OrderSaveObserver(
            $this->createMock(TrustpilotHttpClient::class),
            $this->createMock(OrderData::class),
            $this->helper,
            $this->config,
            $this->log
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
    public function testHandleSingleResponseRecordsFailureOnCorruptFailedOrders($failedOrders)
    {
        $this->helper->method('getConfig')->willReturnCallback(
            function ($config) use ($failedOrders) {
                return $config === 'failed_orders' ? $failedOrders : '0';
            }
        );
        $this->log->expects($this->never())->method('error');
        $this->config->expects($this->once())
            ->method('saveConfig')
            ->with(
                'trustpilot/trustpilot_general_group/failed_orders',
                json_encode(['B00125009' => base64_encode('Automatic invitation sending failed')]),
                'stores',
                1
            );

        $this->observer->handleSingleResponse(['code' => 500], ['referenceId' => 'B00125009'], 1);
    }

    public function testHandleSingleResponseKeepsExistingFailures()
    {
        $this->helper->method('getConfig')->willReturnCallback(
            function ($config) {
                return $config === 'failed_orders' ? '{"B00125008":"QXV0b21hdGlj"}' : '0';
            }
        );
        $this->config->expects($this->once())
            ->method('saveConfig')
            ->with(
                'trustpilot/trustpilot_general_group/failed_orders',
                json_encode([
                    'B00125008' => 'QXV0b21hdGlj',
                    'B00125009' => base64_encode('Automatic invitation sending failed'),
                ]),
                'stores',
                1
            );

        $this->observer->handleSingleResponse(['code' => 500], ['referenceId' => 'B00125009'], 1);
    }
}
