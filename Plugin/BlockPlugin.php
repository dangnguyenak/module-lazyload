<?php
/**
 * Copyright © 2017 Ihor Vansach (ihor@magefan.com). All rights reserved.
 * See LICENSE.txt for license details (http://opensource.org/licenses/osl-3.0.php).
 *
 * Glory to Ukraine! Glory to the heroes!
 */

namespace Magefan\LazyLoad\Plugin;

/**
 * Plugin for sitemap generation
 */
class BlockPlugin
{
    const LAZY_TAG = '<!-- MAGEFAN_LAZY_LOAD -->';

    /**
     * Request
     * @var \Magento\Framework\App\RequestInterface
     */
    protected $request;

    /**
     * Core store config
     *
     * @var \Magento\Framework\App\Config\ScopeConfigInterface
     */
    protected $scopeConfig;

    /**
     * @var array
     */
    protected $blocks;

    /**
     * Lazy store config
     *
     * @var \Magefan\LazyLoad\Model\Config
     */
    protected $config;

    /**
     * BlockPlugin constructor
     * @param \Magento\Framework\App\RequestInterface $request
     * @param \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig
     * @param null|\Magefan\LazyLoad\Model\Config $config
     */
    public function __construct(
        \Magento\Framework\App\RequestInterface $request,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        $config = null
    ) {
        $this->request = $request;
        $this->scopeConfig = $scopeConfig;

        $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
        $this->config = $config ?: $objectManager->get(
            \Magefan\LazyLoad\Model\Config::class
        );
    }


    /**
     * @param \Magento\Framework\View\Element\AbstractBlock $block
     * @param string $html
     * @return string
     */
    public function afterToHtml(\Magento\Framework\View\Element\AbstractBlock $block, $html)
    {
        if (!$this->isEnabled($block, $html)) {
            return $html;
        }

        $html = preg_replace('#<img\s+([^>]*)(?:src="([^"]*)")([^>]*)\/?>#isU', '<img src="' .
            $block->getViewFileUrl('Magefan_LazyLoad::images/pixel.jpg') . '" ' .
            'data-original="$2" $1 $3/>', $html);

        $html = str_replace(self::LAZY_TAG, '', $html);

        return $html;
    }

    /**
     * Check if lazy load is available for block
     * @param \Magento\Framework\View\Element\AbstractBlock $block
     * @param string $html
     * @return boolean
     */
    protected function isEnabled($block, $html)
    {
        if (PHP_SAPI === 'cli' || $this->request->isXmlHttpRequest()) {
            return false;
        }

        if (!$this->config->getEnabled()) {
            return false;
        }

        $blockName = $block->getBlockId() ?: $block->getNameInLayout();
        $blockTemplate = $block->getTemplate();
        $blocks = $this->config->getBlocks();

        if (!in_array($blockName, $blocks)
            && !in_array(get_class($block), $blocks)
            && !in_array($blockTemplate, $blocks)
            && (false === strpos($html, self::LAZY_TAG))
        ) {
            return false;
        }

        return true;
    }

}