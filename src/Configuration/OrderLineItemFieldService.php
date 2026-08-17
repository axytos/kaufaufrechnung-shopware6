<?php

declare(strict_types=1);

namespace Axytos\KaufAufRechnung\Shopware\Configuration;

use Doctrine\DBAL\Connection;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Shopware\Core\System\CustomField\Aggregate\CustomFieldSet\CustomFieldSetCollection;
use Shopware\Core\System\CustomField\Aggregate\CustomFieldSet\CustomFieldSetEntity;
use Shopware\Core\System\CustomField\CustomFieldEntity;

class OrderLineItemFieldService
{
    /** @var Connection */
    private $connection;

    /** @var EntityRepository<CustomFieldSetCollection> */
    private $customFieldSetRepo;

    /**
     * @param EntityRepository<CustomFieldSetCollection> $customFieldSetRepo
     */
    public function __construct(Connection $connection, EntityRepository $customFieldSetRepo)
    {
        $this->connection = $connection;
        $this->customFieldSetRepo = $customFieldSetRepo;
    }

    /**
     * @return array<int, array{label: string, value: string}>
     */
    public function getSelectableFields(Context $context): array
    {
        $customFields = $this->getCustomFields($context);
        $extraColumns = $this->getExtraColumns();

        $all = array_merge($customFields, $extraColumns);
        sort($all);

        return array_map(static fn (string $field): array => ['label' => $field, 'value' => $field], $all);
    }

    /**
     * @return list<string>
     */
    private function getCustomFields(Context $context): array
    {
        $criteria = (new Criteria())
            ->addFilter(new EqualsFilter('relations.entityName', 'order_line_item'))
            ->addAssociation('relations')
            ->addAssociation('customFields')
        ;

        $sets = $this->customFieldSetRepo->search($criteria, $context);

        $fields = [];

        /** @var CustomFieldSetEntity $set */
        foreach ($sets as $set) {
            /** @var CustomFieldEntity $field */
            foreach ($set->getCustomFields() ?? [] as $field) {
                $name = $field->getName();
                if (null !== $name) {
                    $fields[] = $name;
                }
            }
        }

        /** @var list<string> $fields */
        return array_values(array_unique($fields));
    }

    /**
     * @return list<string>
     */
    private function getExtraColumns(): array
    {
        $columns = $this->connection->createSchemaManager()->listTableColumns('order_line_item');

        $default = [
            'id', 'version_id', 'order_id', 'order_version_id', 'parent_id', 'parent_version_id', 'identifier',
            'referenced_id', 'product_id', 'product_version_id', 'promotion_id', 'description', 'cover_id', 'quantity',
            'unit_price', 'total_price', 'label', 'payload', 'good', 'removable',
            'stackable', 'position', 'price', 'price_definition', 'created_at',
            'updated_at', 'custom_fields', 'states', 'type',
        ];

        /** @var list<string> $default */
        return array_values(array_diff(array_keys($columns), $default));
    }
}
