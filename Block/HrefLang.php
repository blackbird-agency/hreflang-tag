<?php

namespace Blackbird\HrefLang\Block;

use Blackbird\HrefLang\Helper\Alternate;
use Magento\Framework\View\Element\Template;

class HrefLang extends \Magento\Framework\View\Element\Template
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
                if (strpos($keyLocate, 'en') !== false) {
                    $altKey = strtolower($keyLocate);
                } else {
                    $altKey = strtolower(substr($keyLocate, 0, 2));
                }

                if ($keyLocate === $this->alternateHelper->getXDefault()) {
                    $res[self::DEFAULT_HREF_LANG] = $url;
                }

                $res[str_replace('_', '-', $altKey)] = $url;
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
            if ($link) {
                $res .= '<link rel="alternate" hreflang="' . $code . '" href="' . $link . '" />';
                $res .= "\n";
            }
        }

        return $res;
    }

}
