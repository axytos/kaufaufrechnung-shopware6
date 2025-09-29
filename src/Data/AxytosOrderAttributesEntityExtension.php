<?php

declare(strict_types=1);

namespace Axytos\KaufAufRechnung\Shopware\Data;

use Shopware\Core\Checkout\Order\OrderDefinition;
use Shopware\Core\Framework\DataAbstractionLayer\EntityExtension;
use Shopware\Core\Framework\DataAbstractionLayer\Field\OneToOneAssociationField;
use Shopware\Core\Framework\DataAbstractionLayer\FieldCollection;

/**
 * reference: https://developer.shopware.com/docs/guides/plugins/plugins/framework/data-handling/add-complex-data-to-existing-entities.html#creating-the-extension.
 */
class AxytosOrderAttributesEntityExtension extends EntityExtension
{
    public function getEntityName(): string
    {
        return OrderDefinition::ENTITY_NAME;
    }

    public function getDefinitionClass(): string
    {
        return OrderDefinition::class;
    }

    public function extendFields(FieldCollection $collection): void
    {
        $collection->add(
            new OneToOneAssociationField(
                'axytosKaufAufRechnungOrderAttributes',
                'id',
                AxytosOrderAttributesInterface::SHOPWARE_ORDER_ENTITY_ID,
                AxytosOrderAttributesEntityDefinition::class,
                true
            )
        );
    }
}
