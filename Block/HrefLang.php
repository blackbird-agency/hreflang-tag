<?php

namespace Blackbird\HrefLang\Block;

use Blackbird\HrefLang\Helper\Alternate;
use Magento\Framework\View\Element\AbstractBlock;

class HrefLang extends AbstractBlock
{

    /**
     * Default href lang code
     */
    const DEFAULT_HREF_LANG = 'x-default';

    /**
     * @var Alternate
     */
    protected $alternateHelper;

    /**
     * LinkAlternate constructor.
     *
     * @param Alternate        $alternateHelper
     * @param Template\Context $context
     * @param array            $data
     */
    public function __construct(
        Alternate $alternateHelper,
        Template\Context $context,
        array $data = []
    ) {
        $this->alternateHelper = $alternateHelper;
        parent::__construct($context, $data);
    }

    /**
     * @return array
     */
    protected function getHrefLangWithCode()
    {
        $res       = [];
        $alternate = $this->alternateHelper->getAlternateLinks();
        if ($alternate && isset($alternate['storeCodeToUrl'])) {
            foreach ($alternate['storeCodeToUrl'] as $keyLocate => $url) {
                $altKey = strtolower(substr($keyLocate, 0, 2)); //iso2

                if ($keyLocate === $this->alternateHelper->getXDefault()) {
                    $res[self::DEFAULT_HREF_LANG] = $url;
                }

                $res[$altKey] = $url;
            }
        }

        return $res;
    }

    /**
     * @return string
     */
    public function toHtml()
    {
        $res = '';

        foreach ($this->getHrefLangWithCode() as $code => $link) {
            $res .= '<link rel="alternate" hreflang="' . $code . '" href="' . $link . '" />';
            $res .= "\n";
        }

        return $res;
    }

}
