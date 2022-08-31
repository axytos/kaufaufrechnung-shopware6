<?php

declare(strict_types=1);

namespace Axytos\KaufAufRechnung\Shopware\Installer;

use Shopware\Core\Framework\Context;

interface PluginIdProviderInterface
{
    public function getPluginId(Context $context): string;
}
