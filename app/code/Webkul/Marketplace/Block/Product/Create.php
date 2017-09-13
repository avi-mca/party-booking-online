<?php
/**
 * Webkul Software.
 *
 * @category  Webkul
 * @package   Webkul_Marketplace
 * @author    Webkul
 * @copyright Copyright (c) 2010-2016 Webkul Software Private Limited (https://webkul.com)
 * @license   https://store.webkul.com/license.html
 */
namespace Webkul\Marketplace\Block\Product;
/**
 * Webkul Marketplace Product Create Block
 */
use Magento\Catalog\Model\Product;

use Magento\Catalog\Model\Category;

class Create extends \Magento\Framework\View\Element\Template
{
    /**
     * @var \Magento\Catalog\Model\Product
     */
    protected $_product;

    /**
     * @var \Magento\Catalog\Model\Category
     */
    protected $_category;

    /**
     * @var \Magento\Cms\Model\Wysiwyg\Config
     */
    protected $_wysiwygConfig;

    /**
    * @param \Magento\Catalog\Block\Product\Context $context
    * @param \Magento\Cms\Model\Wysiwyg\Config $wysiwygConfig
    * @param Product $product
    * @param Category $category
    * @param array $data
    */
    public function __construct(
        \Magento\Catalog\Block\Product\Context $context,
        \Magento\Cms\Model\Wysiwyg\Config $wysiwygConfig,
        Product $product,
        Category $category,
        array $data = []
    ) 
    {
        $this->_wysiwygConfig = $wysiwygConfig;
        $this->_product = $product;
        $this->_category = $category;
        parent::__construct($context, $data);
    }

    public function getWysiwygConfig()
    {
        $config = $this->_wysiwygConfig->getConfig();
        $config = json_encode($config->getData());
    }

    public function getProduct($id)
    {
        return $this->_product->load($id);
    }

    public function getCategory()
    {
        return $this->_category;
    }

    public function _prepareLayout()
    {
        return parent::_prepareLayout();
    }
}
