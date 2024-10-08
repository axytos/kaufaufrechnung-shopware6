<?php

namespace Axytos\KaufAufRechnung\Shopware\Adapter\HashCalculation;

interface HashAlgorithmInterface
{
    /**
     * @param string $input
     *
     * @return string
     */
    public function compute($input);
}
