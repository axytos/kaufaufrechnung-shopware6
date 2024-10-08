<?php

declare(strict_types=1);

namespace Axytos\KaufAufRechnung\Shopware\Data;

use Shopware\Core\Framework\DataAbstractionLayer\EntityCollection;

/**
 * reference: https://developer.shopware.com/docs/guides/plugins/plugins/framework/data-handling/add-custom-complex-data.html#entitycollection.
 *
 * @extends EntityCollection<AxytosOrderAttributesEntity>
 *
 * @method void                             add(AxytosOrderAttributesEntity $entity)
 * @method void                             set(string $key, AxytosOrderAttributesEntity $entity)
 * @method AxytosOrderAttributesEntity[]    getIterator()
 * @method AxytosOrderAttributesEntity[]    getElements()
 * @method AxytosOrderAttributesEntity|null get(string $key)
 * @method AxytosOrderAttributesEntity|null first()
 * @method AxytosOrderAttributesEntity|null last()
 */
class AxytosOrderAttributesEntityCollection extends EntityCollection
{
    protected function getExpectedClass(): string
    {
        return AxytosOrderAttributesEntity::class;
    }
}
