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
namespace Webkul\Marketplace\Model\Config\Source;

/**
 * Used in creating options for getting product type value
 *
 */
class Producttype
{
    /**
     * @var \Magento\Catalog\Model\ProductTypes\ConfigInterface
     */
    protected $_config;

    /**
     * @var \Magento\Framework\Module\Manager
     */
    protected $manager;
    /**
     * Construct
     *
     * @param \Magento\Catalog\Model\ProductTypes\ConfigInterface $config
     */
    public function __construct(
        \Magento\Framework\Module\Manager $manager,
        \Magento\Catalog\Model\ProductTypes\ConfigInterface $config
    ) {
        $this->manager = $manager;
        $this->_config = $config;
    }
    /**
     * Options getter
     *
     * @return array
     */
    public function toOptionArray()
    {
        $data =  array(array('value'=>'simple', 'label'=>__('Simple')),
                        array('value'=>'downloadable', 'label'=>__('Downloadable')),
                        array('value'=>'virtual', 'label'=>__('Virual')),
                        array('value'=>'configurable', 'label'=>__('Configurable'))
        );
        if($this->manager->isEnabled('Webkul_Mpbundleproduct')){
            array_push($data,array('value'=>'bundle', 'label'=>__('Bundle Product')));
        }
        if($this->manager->isEnabled('Webkul_Mpgroupproduct')){
            array_push($data,array('value'=>'grouped', 'label'=>__('Grouped Product')));
        }
        return $data;
    }
}
