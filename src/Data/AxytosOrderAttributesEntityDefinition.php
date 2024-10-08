<?php

declare(strict_types=1);

namespace Axytos\KaufAufRechnung\Shopware\Data;

use Shopware\Core\Checkout\Order\OrderDefinition;
use Shopware\Core\Framework\DataAbstractionLayer\EntityDefinition;
use Shopware\Core\Framework\DataAbstractionLayer\Field\BoolField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\FkField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\Flag\PrimaryKey;
use Shopware\Core\Framework\DataAbstractionLayer\Field\Flag\Required;
use Shopware\Core\Framework\DataAbstractionLayer\Field\IdField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\JsonField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\LongTextField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\OneToOneAssociationField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\ReferenceVersionField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\StringField;
use Shopware\Core\Framework\DataAbstractionLayer\FieldCollection;

/**
 * reference: https://developer.shopware.com/docs/guides/plugins/plugins/framework/data-handling/add-custom-complex-data.html#entitydefinition-class.
 */
class AxytosOrderAttributesEntityDefinition extends EntityDefinition
{
    public const ENTITY_NAME = 'axytos_kaufaufrechnung_order_attributes';

    public function getEntityName(): string
    {
        return self::ENTITY_NAME;
    }

    public function getEntityClass(): string
    {
        return AxytosOrderAttributesEntity::class;
    }

    public function getCollectionClass(): string
    {
        return AxytosOrderAttributesEntityCollection::class;
    }

    protected function defineFields(): FieldCollection
    {
        return new FieldCollection([
            (new IdField(AxytosOrderAttributesInterface::ID, 'id'))->addFlags(new Required(), new PrimaryKey()),
            new FkField(AxytosOrderAttributesInterface::SHOPWARE_ORDER_ENTITY_ID, 'shopwareOrderEntityId', OrderDefinition::class),
            new ReferenceVersionField(OrderDefinition::class, AxytosOrderAttributesInterface::SHOPWARE_ORDER_ENTITY_VERSION_ID),
            new StringField(AxytosOrderAttributesInterface::SHOPWARE_ORDER_NUMBER, 'shopwareOrderNumber'),
            new JsonField(AxytosOrderAttributesInterface::ORDER_PRE_CHECK_RESULT, 'orderPreCheckResult'),
            new BoolField(AxytosOrderAttributesInterface::SHIPPING_REPORTED, 'shippingReported'),
            new LongTextField(AxytosOrderAttributesInterface::REPORTED_TRACKING_CODE, 'reportedTrackingCode'),
            new LongTextField(AxytosOrderAttributesInterface::ORDER_BASKET_HASH, 'orderBasketHash'),
            new StringField(AxytosOrderAttributesInterface::ORDER_STATE, 'orderState'),
            new LongTextField(AxytosOrderAttributesInterface::ORDER_STATE_DATA, 'orderStateData'),
            new OneToOneAssociationField(
                'order',
                AxytosOrderAttributesInterface::SHOPWARE_ORDER_ENTITY_ID,
                'id',
                OrderDefinition::class,
                false
            ),
        ]);
    }
}
