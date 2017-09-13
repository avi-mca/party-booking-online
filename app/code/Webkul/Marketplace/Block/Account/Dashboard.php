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

namespace Webkul\Marketplace\Block\Account;

use Magento\Sales\Model\Order;
use Magento\Customer\Model\Customer;
use Magento\Framework\View\Element\Template\Context;
use Magento\Framework\ObjectManagerInterface;
use Magento\Customer\Model\Session;
use Webkul\Marketplace\Model\ResourceModel\Saleslist\CollectionFactory;
use Magento\Sales\Model\OrderRepository;

class Dashboard extends \Magento\Framework\View\Element\Template
{
    /**
     * @var Customer
     */
    public $_customer;

    /**
     * @var Order
     */
    public $_order;

    /**
     * @var ObjectManagerInterface
     */
    public $_objectManager;

    /**
     * @var Session
     */
    public $_customerSession;

    /**
     * @param ObjectManagerInterface $objectManager
     * @param Order                  $order
     * @param Customer               $customer
     * @param Session                $customerSession
     * @param CollectionFactory      $orderCollectionFactory
     * @param OrderRepository        $orderRepository
     * @param Context                $context
     * @param array                  $data
     */
    public function __construct(
        ObjectManagerInterface $objectManager,
        Order $order,
        Customer $customer,
        Session $customerSession,
        CollectionFactory $orderCollectionFactory,
        OrderRepository $orderRepository,
        Context $context,
        array $data = []
    ) {
        $this->_customer = $customer;
        $this->_order = $order;
        $this->_objectManager = $objectManager;
        $this->_customerSession = $customerSession;
        $this->_orderCollectionFactory = $orderCollectionFactory;
        $this->orderRepository = $orderRepository;
        parent::__construct($context, $data);
    }

    public function _construct()
    {
        parent::_construct();
        $this->pageConfig->getTitle()->set(__('Seller Dashboard'));
    }

    public function getCustomer()
    {
        return $this->_customer;
    }

    public function getOrder()
    {
        return $this->_order;
    }

    public function getCustomerId()
    {
        return $this->_customerSession->getCustomerId();
    }

    /**
     * @return \Webkul\Marketplace\Model\ResourceModel\Saleslist\Collection
     */
    public function getCollection()
    {
        if (!($customerId = $this->getCustomerId())) {
            return false;
        }

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
        $collection->setPageSize(5);

        return $collection;
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

    public function getDateDetail()
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
            ['neq' => 0]
        )
        ->addFieldToFilter(
            'paid_status',
            ['neq' => 2]
        );
        $collection1 = $this->_objectManager->create(
            'Webkul\Marketplace\Model\Saleslist'
        )->getCollection()
        ->addFieldToFilter(
            'seller_id',
            ['eq' => $sellerId]
        )
        ->addFieldToFilter(
            'order_id',
            ['neq' => 0]
        )
        ->addFieldToFilter(
            'paid_status',
            ['neq' => 2]
        );
        $collection2 = $this->_objectManager->create(
            'Webkul\Marketplace\Model\Saleslist'
        )->getCollection()
        ->addFieldToFilter(
            'seller_id',
            ['eq' => $sellerId]
        )
        ->addFieldToFilter(
            'order_id',
            ['neq' => 0]
        )
        ->addFieldToFilter(
            'paid_status',
            ['neq' => 2]
        );
        $collection3 = $this->_objectManager->create(
            'Webkul\Marketplace\Model\Saleslist'
        )->getCollection()
        ->addFieldToFilter(
            'seller_id',
            ['eq' => $sellerId]
        )
        ->addFieldToFilter(
            'order_id',
            ['neq' => 0]
        )
        ->addFieldToFilter(
            'paid_status',
            ['neq' => 2]
        );

        $firstDayOfWeek = date('Y-m-d', strtotime('Last Monday', time()));

        $lastDayOfWeek = date('Y-m-d', strtotime('Next Sunday', time()));

        $month = $collection1->addFieldToFilter(
            'created_at',
            [
                'datetime' => true,
                'from' => date('Y-m').'-01 00:00:00',
                'to' => date('Y-m').'-31 23:59:59',
            ]
        );

        $week = $collection2->addFieldToFilter(
            'created_at',
            [
                'datetime' => true,
                'from' => $firstDayOfWeek.' 00:00:00',
                'to' => $lastDayOfWeek.' 23:59:59',
            ]
        );

        $day = $collection3->addFieldToFilter(
            'created_at',
            [
                'datetime' => true,
                'from' => date('Y-m-d').' 00:00:00',
                'to' => date('Y-m-d').' 23:59:59',
            ]
        );

        $sale = 0;

        $data1['year'] = $sale;

        $sale1 = 0;
        foreach ($day as $record1) {
            $sale1 = $sale1 + $record1->getActualSellerAmount();
        }
        $data1['day'] = $sale1;

        $sale2 = 0;
        foreach ($month as $record2) {
            $sale2 = $sale2 + $record2->getActualSellerAmount();
        }
        $data1['month'] = $sale2;

        $sale3 = 0;
        foreach ($week as $record3) {
            $sale3 = $sale3 + $record3->getActualSellerAmount();
        }
        $data1['week'] = $sale3;

        $temp = 0;
        foreach ($collection as $record) {
            $temp = $temp + $record->getActualSellerAmount();
        }
        $data1['totalamount'] = $temp;

        return $data1;
    }

    public function getpronamebyorder($orderId)
    {
        $sellerId = $this->getCustomerId();
        $collection = $this->_objectManager->create(
            'Webkul\Marketplace\Model\Saleslist'
        )
                            ->getCollection()
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
                'Magento\Catalog\Model\Product'
            )->load($res['mageproduct_id']);
            $name .= "<p style='float:left;'><a href='".
            $products->getProductUrl().
            "' target='blank'>".
            $res['magepro_name'].
            '</a> X '.
            intval($res['magequantity']).
            '&nbsp;</p>';
        }

        return $name;
    }

    public function getPricebyorder($orderId)
    {
        $sellerId = $this->getCustomerId();
        $collection = $this->_objectManager->create(
            'Webkul\Marketplace\Model\Saleslist'
        )->getCollection();
        $name = '';
        $collection->getSelect()
                ->where('seller_id ='.$sellerId)
                ->columns('SUM(actual_seller_amount) AS qty')
                ->group('order_id');
        foreach ($collection as $coll) {
            if ($coll->getOrderId() == $orderId) {
                return $coll->getQty();
            }
        }
    }

    public function getTotalSaleColl($value = '')
    {
        $sellerId = $this->getCustomerId();

        $collection = $this->_objectManager->create(
            'Webkul\Marketplace\Model\Saleperpartner'
        )->getCollection()
        ->addFieldToFilter(
            'seller_id',
            ['eq' => $sellerId]
        );

        return $collection;
    }

    /**
     * Give the current url of recently viewed page.
     *
     * @return string
     */
    public function getCurrentUrl()
    {
        return $this->_urlBuilder->getCurrentUrl();
    }

    public function getReviewcollection($value = '')
    {
        $sellerId = $this->getCustomerId();

        $collection = $this->_objectManager->create(
            'Webkul\Marketplace\Model\Feedback'
        )
        ->getCollection()
        ->addFieldToFilter(
            'seller_id',
            ['eq' => $sellerId]
        )
        ->addFieldToFilter(
            'status',
            ['eq' => 1]
        )
        ->setOrder(
            'created_at',
            'desc'
        )
        ->setPageSize(5)
        ->setCurPage(1);

        return $collection;
    }
}
