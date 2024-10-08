<?php

declare(strict_types=1);

namespace Axytos\KaufAufRechnung\Shopware\Installer;

use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\Plugin\Util\PluginIdProvider as ShopwarePluginIdProvider;
use Symfony\Component\DependencyInjection\ContainerInterface;

class DefaultPluginIdProvider implements PluginIdProviderInterface
{
    /**
     * @var ContainerInterface
     */
    private $container;
    /**
     * @var string
     */
    private $pluginClassName;

    public function __construct(
        ContainerInterface $container,
        string $pluginClassName
    ) {
        $this->container = $container;
        $this->pluginClassName = $pluginClassName;
    }

    public function getPluginId(Context $context): string
    {
        /** @var ShopwarePluginIdProvider */
        $pluginIdProvider = $this->container->get(ShopwarePluginIdProvider::class);

        return $pluginIdProvider->getPluginIdByBaseClass($this->pluginClassName, $context);
    }
}
