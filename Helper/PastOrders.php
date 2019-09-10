<?php

namespace Trustpilot\Reviews\Helper;

use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Sales\Model\Order;

class PastOrders extends AbstractHelper
{
    protected $_helper;
    protected $_trustpilotHttpClient;
    protected $_orderData;
    protected $_orders;
    protected $_trustpilotLog;

    public function __construct(
        Data $helper,
        TrustpilotHttpClient $trustpilotHttpClient,
        OrderData $orderData,
        Order $orders,
        TrustpilotLog $trustpilotLog)
    {
        $this->_helper = $helper;
        $this->_trustpilotHttpClient = $trustpilotHttpClient;
        $this->_orderData = $orderData;
        $this->_orders = $orders;
        $this->_trustpilotLog = $trustpilotLog;
    }

    public function sync($period_in_days, $scope, $storeId)
    {
        $this->_helper->setConfig('sync_in_progress', 'true', $scope, $storeId);
        $this->_helper->setConfig("show_past_orders_initial", 'false', $scope, $storeId);
        try {
            $key = $this->_helper->getKey($scope, $storeId);
            $collect_product_data = \Trustpilot\Reviews\Model\Config::WITHOUT_PRODUCT_DATA;
            if (!is_null($key)) {
                $this->_helper->setConfig('past_orders', 0, $scope, $storeId);
                $pageId = 1;
                $sales_collection = $this->getSalesCollection($period_in_days, $scope, $storeId);
                $post_batch = $this->getInvitationsForPeriod($sales_collection, $collect_product_data, $pageId);
                while ($post_batch) {
                    set_time_limit(30);
                    $batch = null;
                    if (!is_null($post_batch)) {
                        $batch['invitations'] = $post_batch;
                        $batch['type'] = $collect_product_data;
                        $response = $this->_trustpilotHttpClient->postBatchInvitations($key, $storeId, $batch);
                        $code = $this->handleTrustpilotResponse($response, $batch, $scope, $storeId);
                        if ($code == 202) {
                            $collect_product_data = \Trustpilot\Reviews\Model\Config::WITH_PRODUCT_DATA;
                            $batch['invitations'] = $this->getInvitationsForPeriod($sales_collection, $collect_product_data, $pageId);
                            $batch['type'] = $collect_product_data;
                            $response = $this->_trustpilotHttpClient->postBatchInvitations($key, $storeId, $batch);
                            $code = $this->handleTrustpilotResponse($response, $batch, $scope, $storeId);
                        }
                        if ($code < 200 || $code > 202) {
                            $this->_helper->setConfig('show_past_orders_initial', 'true', $scope, $storeId);
                            $this->_helper->setConfig('sync_in_progress', 'false', $scope, $storeId);
                            $this->_helper->setConfig('past_orders', 0, $scope, $storeId);
                            $this->_helper->setConfig('failed_orders', '{}', $scope, $storeId);
                            return;
                        }
                    }
                    $pageId = $pageId + 1;
                    $post_batch = $this->getInvitationsForPeriod($sales_collection, $collect_product_data, $pageId);
                }
            }
        } catch (\Throwable $e) {
            $description = 'Unable to sync past orders';
            $this->_trustpilotLog->error($e, $description);
        } catch (\Exception $e) {
            $description = 'Unable to sync past orders';
            $this->_trustpilotLog->error($e, $description);
        }
        $this->_helper->setConfig('sync_in_progress', 'false', $scope, $storeId);
    }

    public function resync($scope, $storeId)
    {
        $this->_helper->setConfig('sync_in_progress', 'true', $scope, $storeId);
        try {
            $key = $this->_helper->getKey($scope, $storeId);
            $failed_orders_object = json_decode($this->_helper->getConfig('failed_orders', $storeId, $scope));
            $collect_product_data = \Trustpilot\Reviews\Model\Config::WITHOUT_PRODUCT_DATA;
            if (!is_null($key)) {
                $failed_orders_array = array();
                foreach ($failed_orders_object as $id => $value) {
                    array_push($failed_orders_array, $id);
                }

                $chunked_failed_orders = array_chunk($failed_orders_array, 10, true);
                foreach ($chunked_failed_orders as $failed_orders_chunk) {
                    set_time_limit(30);
                    $post_batch = $this->trustpilotGetOrdersByIds($collect_product_data, $failed_orders_chunk);
                    $batch = null;
                    $batch['invitations'] = $post_batch;
                    $batch['type'] = $collect_product_data;
                    $response = $this->_trustpilotHttpClient->postBatchInvitations($key, $storeId, $batch);
                    $code = $this->handleTrustpilotResponse($response, $batch, $scope, $storeId);

                    if ($code == 202) {
                        $collect_product_data = \Trustpilot\Reviews\Model\Config::WITH_PRODUCT_DATA;
                        $batch['invitations'] = $this->trustpilotGetOrdersByIds($collect_product_data, $failed_orders_chunk);
                        $batch['type'] = $collect_product_data;
                        $response = $this->_trustpilotHttpClient->postBatchInvitations($key, $storeId, $batch);
                        $code = $this->handleTrustpilotResponse($response, $batch, $scope, $storeId);
                    }
                    if ($code < 200 || $code > 202) {
                        $this->_helper->setConfig('sync_in_progress', 'false', $scope, $storeId);
                        return;
                    }
                }
            }
        } catch (\Throwable $e) {
            $description = 'Unable to resync past orders';
            $this->_trustpilotLog->error($e, $description);
        } catch (\Exception $e) {
            $description = 'Unable to resync past orders';
            $this->_trustpilotLog->error($e, $description);
        }
        $this->_helper->setConfig('sync_in_progress', 'false', $scope, $storeId);
    }

    private function trustpilotGetOrdersByIds($collect_product_data, $order_ids) {
        $invitations = array();
        foreach ($order_ids as $id) {
            $order = $this->_orders->loadByIncrementId($id);
            $invitation =  $this->_orderData->getInvitation($order, 'past-orders', $collect_product_data);
            if (!is_null($invitation)) {
                array_push($invitations, $invitation);
            }
        }

        return $invitations;
    }

    public function getPastOrdersInfo($scope, $storeId)
    {
        $syncInProgress = $this->_helper->getConfig('sync_in_progress', $storeId, $scope);
        $showInitial = $this->_helper->getConfig('show_past_orders_initial', $storeId, $scope);
        if ($syncInProgress === 'false') {
            $synced_orders = (int) $this->_helper->getConfig('past_orders', $storeId, $scope);
            $failed_orders = json_decode($this->_helper->getConfig('failed_orders', $storeId, $scope));

            $failed_orders_result = array();
            foreach ($failed_orders as $key => $value) {
                $item = array(
                    'referenceId' => $key,
                    'error' => $value
                );
                array_push($failed_orders_result, $item);
            }

            return array(
                'pastOrders' => array(
                    'synced' => $synced_orders,
                    'unsynced' => count($failed_orders_result),
                    'failed' => $failed_orders_result,
                    'syncInProgress' => $syncInProgress === 'true',
                    'showInitial' => $showInitial === 'true',
                )
            );
        } else {
            return array(
                'pastOrders' => array(
                    'syncInProgress' => $syncInProgress === 'true',
                    'showInitial' => $showInitial === 'true',
                )
            );
        }
    }

    private function getSalesCollection($period_in_days, $scope, $storeId) {
        $date = new \DateTime();
        $args = array(
            'date_created' => $date->setTimestamp(time() - (86400 * $period_in_days))->format('Y-m-d'),
            'limit' => 20,
            'past_order_statuses' => json_decode($this->_helper->getConfig('master_settings_field', $storeId, $scope))->pastOrderStatuses
        );
        
        $collection = $this->_orders->getCollection()
            ->addAttributeToFilter('state', array('in' => $args['past_order_statuses']))
            ->addAttributeToFilter('created_at', array('gteq' => $args['date_created']))
            ->setPageSize($args['limit']);

        return $collection;
    }

    private function getInvitationsForPeriod($sales_collection, $collect_product_data, $page_id)
    {
        if ($page_id <= $sales_collection->getLastPageNumber()) {
            $sales_collection->setCurPage($page_id)->load();
            $orders = array();
            foreach($sales_collection as $order) {
                array_push($orders, $this->_orderData->getInvitation($order, 'past-orders', $collect_product_data));
            }
            $sales_collection->clear();
            return $orders;
        } else {
            return null;
        }
    }

    private function handleTrustpilotResponse($response, $post_batch, $scope, $storeId)
    {
        $synced_orders = (int) $this->_helper->getConfig('past_orders', $storeId, $scope);
        $failed_orders = json_decode($this->_helper->getConfig('failed_orders', $storeId, $scope));

        $data = array();
        if (isset($response['data']))
        {
            $data = $response['data'];
        }

        // all succeeded
        if ($response['code'] == 201 && count($data) == 0) {
            $this->saveSyncedOrders($synced_orders, $post_batch['invitations'], $scope, $storeId);
            $this->saveFailedOrders($failed_orders, $post_batch['invitations'], $scope, $storeId);
        }
        // all/some failed
        if ($response['code'] == 201 && count($data) > 0) {
            $failed_order_ids = $this->selectColumn($data, 'referenceId');
            $succeeded_orders = array_filter($post_batch['invitations'], function ($invitation) use ($failed_order_ids)  {
                return !(in_array($invitation['referenceId'], $failed_order_ids));
            });

            $this->saveSyncedOrders($synced_orders, $succeeded_orders, $scope, $storeId);
            $this->saveFailedOrders($failed_orders, $succeeded_orders, $scope, $storeId, $data);
        }
        return $response['code'];
    }

    private function selectColumn($array, $column)
    {
        if (version_compare(phpversion(), '7.2.10', '<')) {
            $newarr = array();
            foreach ($array as $row) {
                array_push($newarr, $row->{$column});
            }
            return $newarr;
        } else {
            return array_column($array, $column);
        }
    }

    private function saveSyncedOrders($synced_orders, $new_orders, $scope, $storeId)
    {
        if (count($new_orders) > 0) {
            $synced_orders = (int)($synced_orders + count($new_orders));
            $this->_helper->setConfig('past_orders', $synced_orders, $scope, $storeId);
        }
    }

    private function saveFailedOrders($failed_orders, $succeeded_orders, $scope, $storeId, $new_failed_orders = array())
    {
        $update_needed = false;
        if (count($succeeded_orders) > 0) {
            $update_needed = true;
            foreach ($succeeded_orders as $order) {
                if (isset($failed_orders->{$order['referenceId']})) {
                    unset($failed_orders->{$order['referenceId']});
                }
            }
        }

        if (count($new_failed_orders) > 0) {
            $update_needed = true;
            foreach ($new_failed_orders as $failed_order) {
                $failed_orders->{$failed_order->referenceId} = base64_encode($failed_order->error);
            }
        }

        if ($update_needed) {
            $this->_helper->setConfig('failed_orders', json_encode($failed_orders), $scope, $storeId);
        }
    }
}
