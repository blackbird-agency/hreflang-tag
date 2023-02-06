<?php

namespace Blackbird\HrefLang\Api;

use Magento\Store\Api\Data\StoreInterface;

interface HrefLangProvidersInterface {

    /**
     * @return ProviderInterface[]
     */
    public function getSortedProviders(): array;


}
