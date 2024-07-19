<?php

declare(strict_types=1);

namespace Axytos\KaufAufRechnung\Shopware\Data;

use Shopware\Core\Framework\DataAbstractionLayer\Entity;
use Shopware\Core\Framework\DataAbstractionLayer\EntityIdTrait;

/**
 * reference: https://developer.shopware.com/docs/guides/plugins/plugins/framework/data-handling/add-custom-complex-data.html#entity-class
 *
 * WARNING
 * The properties of the entity class have to be at least protected, otherwise the data abstraction layer won't be able to set the values.
 *
 * @package Axytos\KaufAufRechnung\Shopware\Data
 */
class AxytosOrderAttributesEntity extends Entity implements AxytosOrderAttributesInterface
{
    use EntityIdTrait;

    /**
     * @var string|null
     */
    protected $shopwareOrderEntityId = null;

    /**
     * @var string|null
     */
    protected $shopwareOrderNumber = null;

    /**
     * @var array<mixed>
     */
    protected $orderPreCheckResult = [];

    /**
     * @var bool
     */
    protected $shippingReported = false;

    /**
     * @var string|null
     */
    protected $reportedTrackingCode = '';

    /**
     * @var string
     */
    protected $orderBasketHash = '';

    /**
     * @var string
     */
    protected $orderState = '';

    /**
     * @var string
     */
    protected $orderStateData = '';

    /**
     * @return string|null
     */
    public function getShopwareOrderEntityId()
    {
        return $this->shopwareOrderEntityId;
    }

    /**
     * @param string|null $shopwareOrderEntityId
     * @return void
     */
    public function setShopwareOrderEntityId($shopwareOrderEntityId)
    {
        $this->shopwareOrderEntityId = $shopwareOrderEntityId;
    }

    /**
     * @return string|null
     */
    public function getShopwareOrderNumber()
    {
        return $this->shopwareOrderNumber;
    }

    /**
     * @param string|null $shopwareOrderNumber
     * @return void
     */
    public function setShopwareOrderNumber($shopwareOrderNumber)
    {
        $this->shopwareOrderNumber = $shopwareOrderNumber;
    }

    /**
     * @return array<mixed>
     */
    public function getOrderPreCheckResult()
    {
        return $this->orderPreCheckResult;
    }

    /**
     * @param array<mixed> $orderPreCheckResult
     * @return void
     */
    public function setOrderPreCheckResult($orderPreCheckResult)
    {
        $this->orderPreCheckResult = $orderPreCheckResult;
    }

    /**
     * @return bool
     */
    public function getShippingReported()
    {
        return $this->shippingReported;
    }

    /**
     * @param bool $shippingReported
     * @return void
     */
    public function setShippingReported($shippingReported)
    {
        $this->shippingReported = $shippingReported;
    }

    /**
     * @return string|null
     */
    public function getReportedTrackingCode()
    {
        return $this->reportedTrackingCode;
    }

    /**
     * @param string|null $reportedTrackingCode
     * @return void
     */
    public function setReportedTrackingCode($reportedTrackingCode)
    {
        $this->reportedTrackingCode = $reportedTrackingCode;
    }

    /**
     * @return string
     */
    public function getOrderBasketHash()
    {
        return $this->orderBasketHash;
    }

    /**
     * @param string $orderBasketHash
     * @return void
     */
    public function setOrderBasketHash($orderBasketHash)
    {
        $this->orderBasketHash = $orderBasketHash;
    }

    /**
     * @return string
     */
    public function getOrderState()
    {
        return $this->orderState;
    }

    /**
     * @param string $orderState
     * @return void
     */
    public function setOrderState($orderState)
    {
        $this->orderState = $orderState;
    }

    /**
     * @return string
     */
    public function getOrderStateData()
    {
        return $this->orderStateData;
    }

    /**
     * @param string $orderStateData
     * @return void
     */
    public function setOrderStateData($orderStateData)
    {
        $this->orderStateData = $orderStateData;
    }
}
