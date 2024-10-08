<?php

declare(strict_types=1);

namespace Axytos\KaufAufRechnung\Shopware\Installer;

use Axytos\KaufAufRechnung\Shopware\DataAbstractionLayer\PaymentMethodEntityRepository;
use Shopware\Core\Checkout\Payment\PaymentMethodCollection;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Symfony\Component\DependencyInjection\ContainerInterface;

class PluginInstallerFactory
{
    public static function createInstaller(string $pluginClassName, ContainerInterface $container): PluginInstaller
    {
        $paymentMethodRepository = self::createPaymentMethodRepository($container);
        $pluginIdProvider = self::createPluginIdProvider($pluginClassName, $container);

        return new PluginInstaller($paymentMethodRepository, $pluginIdProvider);
    }

    private static function createPaymentMethodRepository(ContainerInterface $container): PaymentMethodEntityRepository
    {
        /** @var EntityRepository<PaymentMethodCollection> */
        $paymentMethodEntityRepository = $container->get('payment_method.repository');

        return new PaymentMethodEntityRepository($paymentMethodEntityRepository);
    }

    private static function createPluginIdProvider(string $pluginClassName, ContainerInterface $container): PluginIdProviderInterface
    {
        return new DefaultPluginIdProvider($container, $pluginClassName);
    }
}
