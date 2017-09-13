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
namespace Webkul\Marketplace\Controller\Product\Attribute;

/**
 * Marketplace Product GetAttributes controller.
 */
class GetAttributes extends \Magento\Framework\App\Action\Action
{
    /**
     * @var \Magento\ConfigurableProduct\Model\AttributesList
     */
    protected $_configurableAttributesList;

    /**
     * @param \Magento\Framework\App\Action\Context             $context
     * @param \Magento\ConfigurableProduct\Model\AttributesList $configurableAttributesList
     */
    public function __construct(
        \Magento\Framework\App\Action\Context $context,
        \Magento\ConfigurableProduct\Model\AttributesList $configurableAttributesList
    ) 
    {
        $this->_configurableAttributesList = $configurableAttributesList;
        parent::__construct($context);
    }

    /**
     * Get Eav Attributes action.
     */
    public function execute()
    {
        $helper = $this->_objectManager->create(
            'Webkul\Marketplace\Helper\Data'
        );
        $isPartner = $helper->isSeller();
        if ($isPartner == 1) {
            $attributesArray = $this->_configurableAttributesList
            ->getAttributes($this->getRequest()->getParam('attributes'));
            $this->getResponse()->representJson(
                $this->_objectManager->get(
                    'Magento\Framework\Json\Helper\Data'
                )->jsonEncode($attributesArray)
            );
        } else {
            return $this->resultRedirectFactory->create()->setPath(
                'marketplace/account/becomeseller',
                ['_secure' => $this->getRequest()->isSecure()]
            );
        }
    }
}
