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

namespace Webkul\Marketplace\Block\Order;

/*
 * Webkul Marketplace Order History Block
 */
use Magento\Sales\Model\Order;
use Magento\Customer\Model\Customer;
use Webkul\Marketplace\Model\ResourceModel\Saleslist\CollectionFactory;

class History extends \Magento\Framework\View\Element\Template
{
    /**
     * @var \Magento\Customer\Model\Customer
     */
    public $customer;

    /**
     * @var \Magento\Sales\Model\Order
     */
    public $order;

    /**
     * @var ObjectManagerInterface
     */
    public $_objectManager;

    /**
     * @var Session
     */
    public $_customerSession;

    /**
     * @var CollectionFactory
     */
    public $_orderCollectionFactory;

    /** @var \Magento\Catalog\Model\Product */
    public $salesOrderLists;

    /** @var \Magento\Sales\Model\OrderRepository */
    public $orderRepository;

    /** @var \Magento\Catalog\Model\ProductRepository */
    public $productRepository;

    /**
     * @param \Magento\Framework\View\Element\Template\Context  $context
     * @param \Magento\Framework\ObjectManagerInterface         $objectManager
     * @param Order                                             $order
     * @param Customer                                          $customer
     * @param \Magento\Customer\Model\Session                   $customerSession
     * @param CollectionFactory                                 $orderCollectionFactory
     * @param \Magento\Sales\Model\OrderRepository              $orderRepository
     * @param \Magento\Catalog\Model\ProductRepository          $productRepository
     * @param array                                             $data
     */
    public function __construct(
        \Magento\Framework\View\Element\Template\Context $context,
        \Magento\Framework\ObjectManagerInterface $objectManager,
        Order $order,
        Customer $customer,
        \Magento\Customer\Model\Session $customerSession,
        CollectionFactory $orderCollectionFactory,
        \Magento\Sales\Model\OrderRepository $orderRepository,
        \Magento\Catalog\Model\ProductRepository $productRepository,
        array $data = []
    ) {
        $this->_orderCollectionFactory = $orderCollectionFactory;
        $this->Customer = $customer;
        $this->Order = $order;
        $this->_objectManager = $objectManager;
        $this->_customerSession = $customerSession;
        $this->orderRepository = $orderRepository;
        $this->productRepository = $productRepository;
        parent::__construct($context, $data);
    }

    /**
     */
    public function _construct()
    {
        parent::_construct();
        $this->pageConfig->getTitle()->set(__('My Orders'));
    }

    public function getCustomerId()
    {
        return $this->_customerSession->getCustomerId();
    }

    /**
     * @return bool|\Webkul\Marketplace\Model\ResourceModel\Saleslist\Collection
     */
    public function getAllSalesOrder()
    {
        if (!($customerId = $this->getCustomerId())) {
            return false;
        }
        if (!$this->salesOrderLists) {
            $paramData = $this->getRequest()->getParams();
            $filterOrderid = '';
            $filterOrderstatus = '';
            $filterDataTo = '';
            $filterDataFrom = '';
            $from = null;
            $to = null;

            if (isset($paramData['s'])) {
                $filterOrderid = $paramData['s'] != '' ? $paramData['s'] : '';
            }
            if (isset($paramData['orderstatus'])) {
                $filterOrderstatus = $paramData['orderstatus'] != '' ? $paramData['orderstatus'] : '';
            }
            if (isset($paramData['from_date'])) {
                $filterDataFrom = $paramData['from_date'] != '' ? $paramData['from_date'] : '';
            }
            if (isset($paramData['to_date'])) {
                $filterDataTo = $paramData['to_date'] != '' ? $paramData['to_date'] : '';
            }

            $orderids = $this->getOrderIdsArray($customerId, $filterOrderstatus);

            $ids = $this->getEntityIdsArray($orderids);

            $collection = $this->_orderCollectionFactory->create()->addFieldToSelect(
                '*'
            )
            ->addFieldToFilter(
                'entity_id',
                ['in' => $ids]
            );

            if ($filterDataTo) {
                $todate = date_create($filterDataTo);
                $to = date_format($todate, 'Y-m-d 23:59:59');
            }
            if ($filterDataFrom) {
                $fromdate = date_create($filterDataFrom);
                $from = date_format($fromdate, 'Y-m-d H:i:s');
            }

            if ($filterOrderid) {
                $collection->addFieldToFilter(
                    'magerealorder_id',
                    ['eq' => $filterOrderid]
                );
            }

            $collection->addFieldToFilter(
                'created_at',
                ['datetime' => true, 'from' => $from, 'to' => $to]
            );

            $collection->setOrder(
                'created_at',
                'desc'
            );
            $this->salesOrderLists = $collection;
        }

        return $this->salesOrderLists;
    }

    public function getOrderIdsArray($customerId = '', $filterOrderstatus = '')
    {
        $orderids = [];

        $collectionOrders = $this->_objectManager->create(
            'Webkul\Marketplace\Model\Saleslist'
        )->getCollection()
        ->addFieldToFilter(
            'seller_id',
            ['eq' => $customerId]
        )
        ->addFieldToSelect('order_id')
        ->distinct(true);
        
        foreach ($collectionOrders as $collectionOrder) {
            $tracking = $this->_objectManager->create(
                'Webkul\Marketplace\Helper\Orders'
            )->getOrderinfo($collectionOrder->getOrderId());
                
            if ($tracking) {
                if ($filterOrderstatus) {
                    if ($tracking->getIsCanceled()) {
                        if ($filterOrderstatus == 'canceled') {
                            array_push($orderids, $collectionOrder->getOrderId());
                        }
                    } else {
                        $tracking = $this->orderRepository->get($collectionOrder->getOrderId());
                        if ($tracking->getStatus() == $filterOrderstatus) {
                            array_push($orderids, $collectionOrder->getOrderId());
                        }
                    }
                } else {
                    array_push($orderids, $collectionOrder->getOrderId());
                }
            }
        }
        return $orderids;
    }

    public function getEntityIdsArray($orderids = [])
    {
        $ids = [];
        foreach ($orderids as $orderid) {
            $collectionIds = $this->_objectManager->create(
                'Webkul\Marketplace\Model\Saleslist'
            )->getCollection()
            ->addFieldToFilter(
                'order_id',
                ['eq' => $orderid]
            )
            ->setOrder('entity_id', 'DESC')
            ->setPageSize(1);
            foreach ($collectionIds as $collectionId) {
                $autoid = $collectionId->getId();
                array_push($ids, $autoid);
            }
        }
        return $ids;
    }

    /**
     * @return $this
     */
    public function _prepareLayout()
    {
        parent::_prepareLayout();
        if ($this->getAllSalesOrder()) {
            $pager = $this->getLayout()->createBlock(
                'Magento\Theme\Block\Html\Pager',
                'marketplace.dashboard.pager'
            )
            ->setCollection(
                $this->getAllSalesOrder()
            );
            $this->setChild('pager', $pager);
            $this->getAllSalesOrder()->load();
        }

        return $this;
    }

    /**
     * @return string
     */
    public function getPagerHtml()
    {
        return $this->getChildHtml('pager');
    }

    public function getCurrentUrl()
    {
        return $this->_urlBuilder->getCurrentUrl(); // Give the current url of recently viewed page
    }

    public function getpronamebyorder($orderId)
    {
        $sellerId = $this->getCustomerId();
        $collection = $this->_objectManager->create(
            'Webkul\Marketplace\Model\Saleslist'
        )->getCollection()
        ->addFieldToFilter(
            'seller_id',
            ['eq' => $sellerId]
        )
        ->addFieldToFilter(
            'order_id',
            ['eq' => $orderId]
        );
        $name = '';
        foreach ($collection as $res) {
            $products = $this->_objectManager->create(
                'Webkul\Marketplace\Model\Saleslist'
            )->load($res['mageproduct_id']);
            $name .= "<p style='float:left;'><a href='".
            $products->getProductUrl()."' target='blank'>".
            $res['magepro_name'].'</a> X '.(int)$res['magequantity'].'&nbsp;</p>';
        }

        return $name;
    }

    public function getPricebyorder($orderId)
    {
        $sellerId = $this->getCustomerId();
        $collection = $this->_objectManager->create(
            'Webkul\Marketplace\Model\Saleslist'
        )->getCollection()
        ->addFieldToFilter(
            'seller_id',
            $sellerId
        )->getPricebyorderData();
        $name = '';
        foreach ($collection as $coll) {
            if ($coll->getOrderId() == $orderId) {
                return $coll->getQty();
            }
        }
    }
}
