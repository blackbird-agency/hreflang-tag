<?php

namespace Blackbird\HrefLang\Api;

use Magento\Store\Api\Data\StoreInterface;

interface ProviderInterface {

    public function getAlternativeUrlForStore(StoreInterface $store): ?string;

}
