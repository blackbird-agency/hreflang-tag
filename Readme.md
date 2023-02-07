# Installation

- Fill out all configurations within Store -> System Configuration -> Blackbird Extensions -> Href Lang
- Be careful, some config are only available by Store View


![system_config_screenshot.png](system_config_screenshot.png)


### Enable Href Lang Block
Enable block to be injected in layout


### Show Only Store From Same Website

Define if all store views are displayed within hreflang tags or only storeview with same website than current crawled website.

### Replace Native Store Switcher Url

Native Store Switcher block will use URL from hreflang system instead of ugly default Magento URL
Better for SEO. Fallback on default system if no URL are available through Hreflang system

### Remove "___store" Parameter from Url	
In case you have ___store in URL key, remove it

### Default locale

Define which locale is the default one.
Based on what you defined in Store view scope for field "Locale code for HrefLang".
Define which store view will have a tag "x-default" attached.

## Additionnal config by Store view

![system_config_screenshot2.png](system_config_screenshot2.png)

### Use this store for Href Lang Block
Can exclude targeted store view from hreflang system

### Locale code for HrefLang Tag
(e.g. "fr", "en-us", "es-us"). By Default will use global locale configuration

# Add your own hreflang system in the providers

You can add additionnal hreflang URL based on different module logic easily.

1. Declare your provider in your di.xml file within a separated Magento Module (e.g. HrefLangContentManager)

```xml
<?xml version="1.0"?>
<config xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xsi:noNamespaceSchemaLocation="urn:magento:framework:ObjectManager/etc/config.xsd">

    <type name="Blackbird\HrefLang\Api\HrefLangProvidersInterface">
        <arguments>
            <argument name="providers" xsi:type="array">
                <item name="contentmanager_content" xsi:type="array">
                    <item name="class" xsi:type="object">Blackbird\HrefLangContentManager\Model\Provider\Content</item>
                    <item name="sortOrder" xsi:type="number">8</item>
                    <item name="enabled" xsi:type="boolean">true</item>
                </item>
            </argument>
        </arguments>
    </type>
    
</config>
```
2. Define your PHP Class and implements ProviderInterface :
Example For Advanced Content Manager Module : 
```php
<?php

namespace Blackbird\HrefLangContentManager\Model\Provider;

use Blackbird\ContentManager\Api\Data\ContentInterface;
use Blackbird\HrefLang\Api\ProviderInterface;
use Blackbird\HrefLang\Model\Provider\AbstractProvider;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Registry;
use Magento\Framework\View\LayoutInterface;
use Magento\Store\Api\Data\StoreInterface;
use Magento\Store\Model\App\Emulation;
use Magento\UrlRewrite\Model\ResourceModel\UrlRewriteCollectionFactory;

class Content extends AbstractProvider implements ProviderInterface
{
    protected Registry $registry;
    protected UrlRewriteCollectionFactory $urlRewriteCollectionFactory;
    protected Emulation $emulation;

    public function __construct(
        UrlRewriteCollectionFactory $urlRewriteCollectionFactory,
        Registry $registry,
        ScopeConfigInterface $scopeConfig,
        LayoutInterface $layout,
        Emulation $emulation,
        RequestInterface $request
    ) {
        parent::__construct(
            $scopeConfig,
            $layout,
            $request);

        $this->registry = $registry;
        $this->urlRewriteCollectionFactory = $urlRewriteCollectionFactory;
        $this->emulation = $emulation;
    }

    public function getAlternativeUrlForStore(StoreInterface $store): ?string
    {
        if ($content = $this->getCurrentContent()) {
            return $this->getContentUrl(
                $content,
                $store);
        }

        return null;
    }

    protected function getContentUrl(ContentInterface $content, $store): string
    {
        $url = '';

        $urlRewriteCollection = $this->urlRewriteCollectionFactory->create();
        $urlRewriteCollection->addStoreFilter($store);
        $urlRewriteCollection->addFieldToFilter(
            'entity_id',
            $content->getId());
        $urlRewriteCollection->addFieldToFilter(
            'entity_type',
            'contenttype_content');
        $urlRewriteCollection->addFieldToSelect(['request_path']);

        $urlRewrite = $urlRewriteCollection->getFirstItem();

        if ($urlRewrite && $urlRewrite->getRequestPath()) {
            if ($this->getRemoveStoreTag()) {
                $this->emulation->startEnvironmentEmulation($store->getId());
                $url = $store->getUrl('/') . $urlRewrite['request_path'];
                $this->emulation->stopEnvironmentEmulation();
            } else {
                $url = $store->getUrl($urlRewrite['request_path']);
            }
        }

        return $url;
    }

    protected function getCurrentContent(): ?ContentInterface
    {
        //Check we are on right router
        if (!in_array(
            'contentmanager_index_content',
            $this->layout->getUpdate()->getHandles())) {
            return null;
        }

        //Check current content exists and return it
        return $this->registry->registry('current_content');
    }

}
```

