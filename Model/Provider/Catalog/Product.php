<?php

namespace Blackbird\HrefLang\Model\Provider\Catalog;

use Blackbird\HrefLang\Api\ProviderInterface;
use Blackbird\HrefLang\Model\Provider\AbstractProvider;
use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Catalog\Model\ResourceModel\Url;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\View\LayoutInterface;
use Magento\Store\Api\Data\StoreInterface;
use Magento\Store\Model\App\Emulation;
use Magento\UrlRewrite\Model\ResourceModel\UrlRewriteCollectionFactory;

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
     * @var UrlRewriteCollectionFactory
     */
    protected $urlRewriteCollectionFactory;

    public function __construct(
        Emulation $emulation,
        ProductRepositoryInterface $productRepository,
        ScopeConfigInterface $scopeConfig,
        LayoutInterface $layout,
        RequestInterface $request,
        UrlRewriteCollectionFactory $urlRewriteCollectionFactory
    )
    {
        $this->emulation                   = $emulation;
        $this->productRepository           = $productRepository;
        $this->urlRewriteCollectionFactory = $urlRewriteCollectionFactory;

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
    protected function getCurrentProduct(): ?ProductInterface
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
    protected function getProductUrl(\Magento\Catalog\Api\Data\ProductInterface $_product, $store): ?string
    {
        $url = '';

        //check if product is visible in corresponding store
        try {
            $urlRewriteCollection = $this->urlRewriteCollectionFactory->create();
            $urlRewriteCollection->addStoreFilter($store);
            $urlRewriteCollection->addFieldToFilter(
                'entity_id',
                $_product->getId());
            $urlRewriteCollection->addFieldToFilter(
                'entity_type',
                'product');
            $urlRewriteCollection->addFieldToFilter(
                'redirect_type',
                ['neq' => 301]);

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

        } catch (NoSuchEntityException $e) {
            //silence is golden
        }

        return $url;
    }
}
