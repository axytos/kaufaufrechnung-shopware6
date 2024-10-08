<?php

declare(strict_types=1);

namespace Axytos\KaufAufRechnung\Shopware\Client;

use Axytos\ECommerce\Abstractions\UserAgentInfoProviderInterface;
use Axytos\ECommerce\PackageInfo\ComposerPackageInfoProvider;

class UserAgentInfoProvider implements UserAgentInfoProviderInterface
{
    /**
     * @var ComposerPackageInfoProvider
     */
    private $composerPackageInfoProvider;

    public function __construct(ComposerPackageInfoProvider $composerPackageInfoProvider)
    {
        $this->composerPackageInfoProvider = $composerPackageInfoProvider;
    }

    public function getPluginName(): string
    {
        return 'KaufAufRechnung';
    }

    public function getPluginVersion(): string
    {
        /**
         * cannot be null, because this is the package name of THIS plugin.
         *
         * @phpstan-ignore-next-line
         */
        return $this->composerPackageInfoProvider->getVersion('axytos/kaufaufrechnung-shopware6');
    }

    public function getShopSystemName(): string
    {
        return 'Shopware';
    }

    public function getShopSystemVersion(): string
    {
        /**
         * cannot be null, because this is a shopware plugin.
         *
         * @phpstan-ignore-next-line
         */
        return $this->composerPackageInfoProvider->getVersion('shopware/core');
    }
}
