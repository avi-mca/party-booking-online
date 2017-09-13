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
namespace Webkul\Marketplace\Controller\Seller;

use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Framework\View\Result\PageFactory;
/**
 * Webkul Marketplace Seller Feedback controller.
 */
class Feedback extends Action
{
    /**
     * @var PageFactory
     */
    protected $_resultPageFactory;

    /**
     * @param Context     $context
     * @param PageFactory $resultPageFactory
     */
    public function __construct(
        Context $context,
        PageFactory $resultPageFactory
    ) 
    {
        $this->_resultPageFactory = $resultPageFactory;
        parent::__construct($context);
    }

    /**
     * @return \Magento\Framework\View\Result\Page
     */
    public function execute()
    {
        $shopUrl = $this->_objectManager->create(
            'Webkul\Marketplace\Helper\Data'
        )->getFeedbackUrl();
        if (!$shopUrl) {
            $shopUrl = $this->getRequest()->getParam('shop');
        }
        if ($shopUrl) {
            $data = $this->_objectManager->create(
                'Webkul\Marketplace\Model\Seller'
            )
            ->getCollection()
            ->addFieldToFilter(
                'is_seller',
                1
            )->addFieldToFilter(
                'shop_url', 
                $shopUrl
            );
            if ($data->getSize()) {
                $resultPage = $this->_resultPageFactory->create();
                $resultPage->getConfig()->getTitle()->set(
                    __('Marketplace Seller Feedback')
                );

                return $resultPage;
            }
        }
        return $this->resultRedirectFactory->create()->setPath(
            'marketplace', 
            ['_secure' => $this->getRequest()->isSecure()]
        );        
    }
}
