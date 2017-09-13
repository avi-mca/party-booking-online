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
 * Marketplace Seller Profile controller.
 */
class Profile extends Action
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
     * Marketplace Seller's Profile Page.
     * 
     * @return \Magento\Framework\View\Result\Page
     */
    public function execute()
    {
        $shopUrl = $this->_objectManager->create(
            'Webkul\Marketplace\Helper\Data'
        )->getProfileUrl();
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
                    __('Marketplace Seller Profile')
                );

                return $resultPage;
            }
        }
        return $this->resultRedirectFactory->create()->setPath(
            'marketplace', ['_secure' => $this->getRequest()->isSecure()]
        );
    }
}
