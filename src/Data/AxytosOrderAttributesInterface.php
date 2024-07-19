<?php

declare(strict_types=1);

namespace Axytos\KaufAufRechnung\Shopware\Data;

interface AxytosOrderAttributesInterface
{
    const ID = 'id';
    const SHOPWARE_ORDER_ENTITY_ID = 'shopware_order_entity_id';
    const SHOPWARE_ORDER_ENTITY_VERSION_ID = 'shopware_order_entity_version_id';
    const SHOPWARE_ORDER_NUMBER = 'shopware_order_number';
    const ORDER_PRE_CHECK_RESULT = 'order_pre_check_result';
    const SHIPPING_REPORTED = 'shipping_reported';
    const REPORTED_TRACKING_CODE = 'reported_tracking_code';
    const ORDER_BASKET_HASH = 'order_basket_hash';
    const ORDER_STATE = 'order_state';
    const ORDER_STATE_DATA = 'order_state_data';

    /**
     * @return int
     */
    public function getId();

    /**
     * @param string $id
     * @return void
     */
    public function setId(string $id);

    /**
     * @return string|null
     */
    public function getShopwareOrderEntityId();

    /**
     * @param string|null  $shopwareOrderEntityId
     * @return void
     */
    public function setShopwareOrderEntityId($shopwareOrderEntityId);

    /**
     * @return string|null
     */
    public function getShopwareOrderNumber();

    /**
     * @param string|null $magentoOrderIncrementId
     * @return void
     */
    public function setShopwareOrderNumber($magentoOrderIncrementId);

    /**
     * @return array<mixed>
     */
    public function getOrderPreCheckResult();

    /**
     * @param array<mixed> $orderPreCheckResult
     * @return void
     */
    public function setOrderPreCheckResult($orderPreCheckResult);

    /**
     * @return bool
     */
    public function getShippingReported();

    /**
     * @param bool $shippingReported
     * @return void
     */
    public function setShippingReported($shippingReported);

    /**
     * @return string|null
     */
    public function getReportedTrackingCode();

    /**
     * @param string|null $reportedTrackingCode
     * @return void
     */
    public function setReportedTrackingCode($reportedTrackingCode);

    /**
     * @return string
     */
    public function getOrderBasketHash();

    /**
     * @param string $orderBasketHash
     * @return void
     */
    public function setOrderBasketHash($orderBasketHash);

    /**
     * @return string
     */
    public function getOrderState();

    /**
     * @param string $orderState
     * @return void
     */
    public function setOrderState($orderState);

    /**
     * @return string
     */
    public function getOrderStateData();

    /**
     * @param string $orderStateData
     * @return void
     */
    public function setOrderStateData($orderStateData);
}
