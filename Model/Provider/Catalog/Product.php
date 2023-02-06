<?php

namespace Blackbird\HrefLang\Model\Provider\Catalog;

use Blackbird\HrefLang\Api\ProviderInterface;
use Blackbird\HrefLang\Model\Provider\AbstractProvider;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Catalog\Model\ResourceModel\Url;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\View\LayoutInterface;
use Magento\Store\Api\Data\StoreInterface;
use Magento\Store\Model\App\Emulation;

class Product extends AbstractProvider implements  ProviderInterface
{

    /**
     * @var Emulation
     */
    protected $emulation;

    /**
     * @var ProductRepositoryInterface
     */
    protected $productRepository;

    /**
     * @var Url
     */
    protected $catalogUrl;

    public function __construct(
        Emulation $emulation,
        ProductRepositoryInterface $productRepository,
        Url $catalogUrl,
        ScopeConfigInterface $scopeConfig,
        LayoutInterface $layout,
        RequestInterface $request
    )
    {
        $this->emulation                   = $emulation;
        $this->productRepository           = $productRepository;
        $this->catalogUrl                  = $catalogUrl;

        parent::__construct($scopeConfig, $layout, $request);
    }

    public function getAlternativeUrlForStore(StoreInterface $store): ?string
    {
        if ($_product = $this->getCurrentProduct()) {
            return  $this->getProductUrl(
                $_product,
                $store);
        }
        return null;
    }

    /**
     * @return \Magento\Catalog\Api\Data\ProductInterface|null
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    protected function getCurrentProduct()
    {
        $product   = null;
        $productId = $this->request->getParam('id');

        //check if we are on product page
        if ($productId && in_array(
                'catalog_product_view',
                $this->layout->getUpdate()->getHandles())) {
            try {
                $product = $this->productRepository->getById($productId);
            } catch (NoSuchEntityException $e) {
                //silence is golden
            }
        }

        return $product;
    }

    /**
     * @param \Magento\Catalog\Api\Data\ProductInterface $_product
     * @param                                            $store
     *
     * @return string
     */
    protected function getProductUrl(\Magento\Catalog\Api\Data\ProductInterface $_product, $store)
    {
        $productsUrl = $this->catalogUrl->getRewriteByProductStore([$_product->getId() => $store->getId()]);
        $url         = !empty($productsUrl[$_product->getId()]) ? $productsUrl[$_product->getId()] : '';
        if (!empty($url)) {
            if ($this->getRemoveStoreTag()) {
                $this->emulation->startEnvironmentEmulation($store->getId());
                $url = $store->getUrl('/') . $url['url_rewrite'];
                $this->emulation->stopEnvironmentEmulation($store->getId());
            } else {
                $url = $store->getUrl($url['url_rewrite']);
            }
        }

        return $url;
    }
}
