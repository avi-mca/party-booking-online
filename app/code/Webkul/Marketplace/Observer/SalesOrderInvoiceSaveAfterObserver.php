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
namespace Webkul\Marketplace\Observer;

use Magento\Framework\Event\ObserverInterface;
use Webkul\Marketplace\Model\ResourceModel\Seller\CollectionFactory;

/**
 * Webkul Marketplace SalesOrderInvoiceSaveAfterObserver Observer.
 */
class SalesOrderInvoiceSaveAfterObserver implements ObserverInterface
{
    /**
     * @var eventManager
     */
    protected $_eventManager;

    /**
     * @var ObjectManagerInterface
     */
    protected $_objectManager;

    /**
     * @var CollectionFactory
     */
    protected $_collectionFactory;

    /**
     * @var \Magento\Framework\Stdlib\DateTime\DateTime
     */
    protected $_date;

    /**
     * @param \Magento\Framework\ObjectManagerInterface   $objectManager
     * @param \Magento\Framework\Stdlib\DateTime\DateTime $date
     * @param CollectionFactory                           $collectionFactory
     */
    public function __construct(
        \Magento\Framework\Event\Manager $eventManager,
        \Magento\Framework\ObjectManagerInterface $objectManager,
        \Magento\Framework\Stdlib\DateTime\DateTime $date,
        CollectionFactory $collectionFactory
    ) 
    {
        $this->_eventManager = $eventManager;
        $this->_objectManager = $objectManager;
        $this->_collectionFactory = $collectionFactory;
        $this->_date = $date;
    }

    /**
     * Sales Order Invoice Save After event handler.
     *
     * @param \Magento\Framework\Event\Observer $observer
     */
    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        $sellerItemsArray = [];
        $invoiceSellerIds = [];

        $order = $observer->getOrder();
        $lastOrderId = $order->getId();
        $helper = $this->_objectManager->get(
            'Webkul\Marketplace\Helper\Data'
        );
        $event = $observer->getInvoice();

        foreach ($event->getAllItems() as $value) {
            $invoiceproduct = $value->getData();
            $proSellerId = 0;
            $productSeller = $this->_objectManager->create(
                'Webkul\Marketplace\Model\Product'
            )
            ->getCollection()
            ->addFieldToFilter(
                'mageproduct_id',
                $invoiceproduct['product_id']
            );
            foreach ($productSeller as $sellervalue) {
                if ($sellervalue->getSellerId()) {
                    $invoiceSellerIds[$sellervalue->getSellerId()] = $sellervalue->getSellerId();
                    $proSellerId = $sellervalue->getSellerId();
                }
            }
            if ($proSellerId) {
                $sellerItemsArray[$proSellerId][] = $invoiceproduct;
            }
        }

        /*send placed order mail notification to seller*/

        $salesOrder = $this->_objectManager->create(
            'Webkul\Marketplace\Model\ResourceModel\Seller\Collection'
        )->getTable('sales_order');
        $salesOrderItem = $this->_objectManager->create(
            'Webkul\Marketplace\Model\ResourceModel\Seller\Collection'
        )->getTable('sales_order_item');

        $paymentCode = '';
        if ($order->getPayment()) {
            $paymentCode = $order->getPayment()->getMethod();
        }
        if ($paymentCode == 'mpcashondelivery') {
            $saleslistColl = $this->_objectManager->create(
                'Webkul\Marketplace\Model\Saleslist'
            )
            ->getCollection()
            ->addFieldToFilter(
                'seller_id', 
                ['in' => $invoiceSellerIds]
            )
            ->addFieldToFilter(
                'order_id',
                $lastOrderId
            );
            foreach ($saleslistColl as $saleslist) {
                $saleslist->setCollectCodStatus(1);
                $saleslist->save();
            }
        }
        $shippingInfo = '';
        $shippingDes = '';

        $billingId = $order->getBillingAddress()->getId();

        $billaddress = $this->_objectManager->create(
            'Magento\Sales\Model\Order\Address'
        )->load($billingId);
        $billinginfo = $billaddress['firstname'].'<br/>'.
        $billaddress['street'].'<br/>'.
        $billaddress['city'].' '.
        $billaddress['region'].' '.
        $billaddress['postcode'].'<br/>'. 
        $this->_objectManager->create(
            'Magento\Directory\Model\Country'
        )->load($billaddress['country_id'])->getName().'<br/>T:'.
        $billaddress['telephone'];

        $payment = $order->getPayment()->getMethodInstance()->getTitle();

        if ($order->getShippingAddress()) {
            $shippingId = $order->getShippingAddress()->getId();
            $address = $this->_objectManager->create(
                'Magento\Sales\Model\Order\Address'
            )->load($shippingId);
            $shippingInfo = $address['firstname'].'<br/>'.
            $address['street'].'<br/>'.
            $address['city'].' '.
            $address['region'].' '.
            $address['postcode'].'<br/>'.
            $this->_objectManager->create(
                'Magento\Directory\Model\Country'
            )->load($address['country_id'])->getName().'<br/>T:'.
            $address['telephone'];
            $shippingDes = $order->getShippingDescription();
        }

        $adminStoreEmail = $helper->getAdminEmailId();
        $adminEmail = $adminStoreEmail ? $adminStoreEmail : $helper->getDefaultTransEmailId();
        $adminUsername = 'Admin';

        $sellerOrder = $this->_objectManager->create(
            'Webkul\Marketplace\Model\Orders'
        )
        ->getCollection()
        ->addFieldToFilter(
            'seller_id', 
            ['in' => $invoiceSellerIds]
        )
        ->addFieldToFilter(
            'order_id', 
            $lastOrderId
        );
        foreach ($sellerOrder as $info) {
            if ($info['seller_id'] != 0) {
                $userdata = $this->_objectManager->create(
                    'Magento\Customer\Model\Customer'
                )->load($info['seller_id']);
                $username = $userdata['firstname'];
                $useremail = $userdata['email'];

                $senderInfo = [];
                $receiverInfo = [];

                $receiverInfo = [
                    'name' => $username,
                    'email' => $useremail,
                ];
                $senderInfo = [
                    'name' => $adminUsername,
                    'email' => $adminEmail,
                ];
                $totalprice = '';
                $totalTaxAmount = 0;
                $codCharges = 0;
                $shippingCharges = 0;
                $orderinfo = '';

                $saleslistIds = [];
                $collection1 = $this->_objectManager->create(
                    'Webkul\Marketplace\Model\Saleslist'
                )->getCollection();
                $collection1->addFieldToFilter('order_id', $lastOrderId);
                $collection1->addFieldToFilter('seller_id', $info['seller_id']);
                $collection1->addFieldToFilter('parent_item_id', ['null' => 'true']);
                $collection1->addFieldToFilter('magerealorder_id', ['neq' => 0]);
                foreach ($collection1 as $value) {
                    array_push($saleslistIds, $value['entity_id']);
                }

                $fetchsale = $this->_objectManager->create(
                    'Webkul\Marketplace\Model\Saleslist'
                )
                ->getCollection()
                ->addFieldToFilter(
                    'entity_id', 
                    ['in' => $saleslistIds]
                );
                $fetchsale->getSelect()->join(
                    $salesOrder.' as so', 
                    'main_table.order_id = so.entity_id', 
                    array('status' => 'status')
                );

                $fetchsale->getSelect()
                ->join(
                    $salesOrderItem.' as soi', 
                    'main_table.order_item_id = soi.item_id AND 
                    main_table.order_id = soi.order_id', 
                    array(
                        'item_id' => 'item_id', 
                        'qty_canceled' => 'qty_canceled', 
                        'qty_invoiced' => 'qty_invoiced', 
                        'qty_ordered' => 'qty_ordered', 
                        'qty_refunded' => 'qty_refunded', 
                        'qty_shipped' => 'qty_shipped', 
                        'product_options' => 'product_options', 
                        'mage_parent_item_id' => 'parent_item_id'
                    )
                );
                foreach ($fetchsale as $res) {
                    $product = $this->_objectManager->create(
                        'Magento\Catalog\Model\Product'
                    )->load($res['mageproduct_id']);

                    /* product name */
                    $productName = $res->getMageproName();
                    $result = [];
                    if ($options = unserialize($res->getProductOptions())) {
                        if (isset($options['options'])) {
                            $result = array_merge($result, $options['options']);
                        }
                        if (isset($options['additional_options'])) {
                            $result = array_merge($result, $options['additional_options']);
                        }
                        if (isset($options['attributes_info'])) {
                            $result = array_merge($result, $options['attributes_info']);
                        }
                    }
                    if ($_options = $result) {
                        $proOptionData = '<dl class="item-options">';
                        foreach ($_options as $_option) {
                            $proOptionData .= '<dt>'.$_option['label'].'</dt>';

                            $proOptionData .= '<dd>'.$_option['value'];
                            $proOptionData .= '</dd>';
                        }
                        $proOptionData .= '</dl>';
                        $productName = $productName.'<br/>'.$proOptionData;
                    } else {
                        $productName = $productName.'<br/>';
                    }
                    /* end */

                    $sku = $product->getSku();
                    $orderinfo = $orderinfo."<tbody><tr>
                        <td class='item-info'>".$productName."</td>
                        <td class='item-info'>".$sku."</td>
                        <td class='item-qty'>".($res['magequantity'] * 1)."</td>
                        <td class='item-price'>".
                            $order->formatPrice($res['magepro_price'] * $res['magequantity']).
                        '</td>
                    </tr></tbody>';
                    $totalTaxAmount = $totalTaxAmount + $res['total_tax'];
                    $totalprice = $totalprice + ($res['magepro_price'] * $res['magequantity']);
                }
                $shippingCharges = $info->getShippingCharges();
                $totalCod = 0;

                if ($paymentCode == 'mpcashondelivery') {
                    $totalCod = $info->getCodCharges();
                    $codRow = "<tr class='subtotal'>
                                <th colspan='3'>".__('Cash On Delivery Charges')."</th>
                                <td colspan='3'><span>".
                                $order->formatPrice($totalCod).
                                '</span></td>
                                </tr>';
                } else {
                    $codRow = '';
                }

                $orderinfo = $orderinfo."<tfoot class='order-totals'>
                                    <tr class='subtotal'>
                                        <th colspan='3'>".__('Shipping & Handling Charges')."</th>
                                        <td colspan='3'><span>".
                                            $order->formatPrice($shippingCharges).
                                        "</span></td>
                                    </tr>
                                    <tr class='subtotal'>
                                        <th colspan='3'>".__('Tax Amount')."</th>
                                        <td colspan='3'><span>".
                                            $order->formatPrice($totalTaxAmount).
                                        '</span></td>
                                    </tr>'.$codRow."
                                    <tr class='subtotal'>
                                        <th colspan='3'>".__('Grandtotal')."</th>
                                        <td colspan='3'><span>".
                                        $order->formatPrice(
                                            $totalprice + 
                                            $totalTaxAmount + 
                                            $shippingCharges + 
                                            $totalCod
                                        ).
                                        '</span></td>
                                    </tr></tfoot>';

                $emailTemplateVariables = [];
                if ($shippingInfo != '') {
                    $isNotVirtual = 1;
                } else {
                    $isNotVirtual = 0;
                }
                $emailTempVariables['myvar1'] = $order->getRealOrderId();
                $emailTempVariables['myvar2'] = $order['created_at'];
                $emailTempVariables['myvar4'] = $billinginfo;
                $emailTempVariables['myvar5'] = $payment;
                $emailTempVariables['myvar6'] = $shippingInfo;
                $emailTempVariables['isNotVirtual'] = $isNotVirtual;
                $emailTempVariables['myvar9'] = $shippingDes;
                $emailTempVariables['myvar8'] = $orderinfo;
                $emailTempVariables['myvar3'] = $username;
                $this->_objectManager->get(
                    'Webkul\Marketplace\Helper\Email'
                )->sendInvoicedOrderEmail(
                    $emailTempVariables,
                    $senderInfo,
                    $receiverInfo
                );
            }
        }
        /*
        * Marketplace Order product sold Observer
        */
        $this->_eventManager->dispatch(
            'mp_product_sold',
            ['itemwithseller' => $sellerItemsArray]
        );
    }
}
