<?php

namespace Blackbird\HrefLang\Model;

use Blackbird\HrefLang\Api\HrefLangProvidersInterface;
use Blackbird\HrefLang\Api\ProviderInterface;

class HrefLangProviders implements HrefLangProvidersInterface
{
    protected array $providers = [];

    public function __construct(
        array $providers
    ) {
        $this->providers = $providers;
    }

    public function getSortedProviders(): array
    {
        $sortedProviders = [];
        foreach ($this->providers as $provider) {
            if (
                isset($provider['class'])
                && is_object($provider['class'])
                && $provider['class'] instanceof ProviderInterface
            ) {
                $providerClass                                                          = $provider['class'];

                $sortOrder = (int) $provider['sortOrder'];
                while(isset($sortedProviders[$sortOrder]))
                {
                    $sortOrder++;
                }

                $sortedProviders[$sortOrder] = $providerClass;
            }
        }

        ksort($sortedProviders);

        return $sortedProviders;
    }

}
