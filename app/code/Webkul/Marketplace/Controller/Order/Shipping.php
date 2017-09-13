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
namespace Webkul\Marketplace\Controller\Order;

/**
 * Webkul Marketplace Order Print PDF Header Infomation View Controller.
 */
class Shipping extends \Webkul\Marketplace\Controller\Order
{
    /**
     * Webkul\Marketplace\Controller\Order\Shipping.
     *
     * @return \Magento\Framework\View\Result\Page
     */
    public function execute()
    {
        $helper = $this->_objectManager->create(
            'Webkul\Marketplace\Helper\Data'
        );
        $isPartner = $helper->isSeller();
        if ($isPartner == 1) {
            /** @var \Magento\Framework\View\Result\Page $resultPage */
            $resultPage = $this->_resultPageFactory->create();
            $resultPage->getConfig()->getTitle()->set(
                __('Manage Print PDF Header Info')
            );

            return $resultPage;
        } else {
            return $this->resultRedirectFactory->create()->setPath(
                'marketplace/account/becomeseller',
                ['_secure' => $this->getRequest()->isSecure()]
            );
        }
    }
}
