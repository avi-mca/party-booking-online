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

namespace Webkul\Marketplace\Controller\Product;

use Magento\Framework\App\Action\Action;
use Magento\Customer\Model\Session;
use Magento\Framework\App\Action\Context;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Registry;
use Magento\Catalog\Model\ResourceModel\Product\CollectionFactory;
use Webkul\Marketplace\Model\ResourceModel\Product\CollectionFactory as SellerProduct;

/**
 * Webkul Marketplace Product MassDelete controller.
 */
class MassDelete extends Action
{
    /**
     * @var \Magento\Customer\Model\Session
     */
    protected $_customerSession;

    /**
     * Core registry.
     *
     * @var \Magento\Framework\Registry
     */
    protected $_coreRegistry = null;

    /**
     * @var CollectionFactory
     */
    protected $_productCollectionFactory;

    /**
     * @var SellerProduct
     */
    protected $_sellerProductCollectionFactory;

    /**
     * @param Context               $context
     * @param Session               $customerSession
     * @param Registry              $coreRegistry
     * @param CollectionFactory     $productCollectionFactory
     * @param SellerProduct         $sellerProductCollectionFactory
     */
    public function __construct(
        Context $context,
        Session $customerSession,
        Registry $coreRegistry,
        CollectionFactory $productCollectionFactory,
        SellerProduct $sellerProductCollectionFactory
    ) 
    {
        $this->_customerSession = $customerSession;
        $this->_coreRegistry = $coreRegistry;
        $this->_productCollectionFactory = $productCollectionFactory;
        $this->_sellerProductCollectionFactory = $sellerProductCollectionFactory;
        parent::__construct(
            $context
        );
    }

    /**
     * Check customer authentication.
     *
     * @param RequestInterface $request
     *
     * @return \Magento\Framework\App\ResponseInterface
     */
    public function dispatch(RequestInterface $request)
    {
        $loginUrl = $this->_objectManager->get('Magento\Customer\Model\Url')->getLoginUrl();

        if (!$this->_customerSession->authenticate($loginUrl)) {
            $this->_actionFlag->set('', self::FLAG_NO_DISPATCH, true);
        }

        return parent::dispatch($request);
    }

    /**
     * Retrieve customer session object.
     *
     * @return \Magento\Customer\Model\Session
     */
    protected function _getSession()
    {
        return $this->_customerSession;
    }

    /**
     * Mass delete seller products action.
     *
     * @return \Magento\Framework\View\Result\Page
     */
    public function execute()
    {
        $helper = $this->_objectManager->create('Webkul\Marketplace\Helper\Data');
        $isPartner = $helper->isSeller();
        if ($isPartner == 1) {
            try {
                $wholedata = $this->getRequest()->getParams();

                $ids = $this->getRequest()->getParam('product_mass_delete');

                $this->_eventManager->dispatch(
                    'mp_delete_product',
                    [$wholedata]
                );

                $sellerId = $this->_getSession()->getCustomerId();
                $this->_coreRegistry->register('isSecureArea', 1);
                $deletedIdsArr = [];
                $sellerProducts = $this->_sellerProductCollectionFactory
                ->create()
                ->addFieldToFilter(
                    'mageproduct_id', 
                    ['in' => $ids]
                )->addFieldToFilter(
                    'seller_id', $sellerId
                );
                foreach ($sellerProducts as $sellerProduct) {
                    array_push($deletedIdsArr, $sellerProduct['mageproduct_id']);
                    $sellerProduct->delete();
                }

                $mageProducts = $this->_productCollectionFactory
                ->create()
                ->addFieldToFilter(
                    'entity_id', 
                    ['in' => $deletedIdsArr]
                );
                foreach ($mageProducts as $mageProduct) {
                    $mageProduct->delete();
                }            
                $unauthIds = array_diff($ids, $deletedIdsArr);
                $this->_coreRegistry->unregister('isSecureArea');
                if (count($unauthIds)) {
                    $this->messageManager->addError(
                        __(
                            'You are not authorize to delete products with id(s) %1 .', 
                            implode(',', $unauthIds)
                        )
                    );
                } else {
                    $this->messageManager->addSuccess(
                        __('Products are successfully deleted from your account.')
                    );
                }

                return $this->resultRedirectFactory->create()->setPath(
                    '*/*/productlist',
                    ['_secure' => $this->getRequest()->isSecure()]
                );
            } catch (\Exception $e) {
                $this->messageManager->addError($e->getMessage());

                return $this->resultRedirectFactory->create()->setPath(
                    '*/*/productlist',
                    ['_secure' => $this->getRequest()->isSecure()]
                );
            }
        } else {
            return $this->resultRedirectFactory->create()->setPath(
                'marketplace/account/becomeseller',
                ['_secure' => $this->getRequest()->isSecure()]
            );
        }
    }
}
