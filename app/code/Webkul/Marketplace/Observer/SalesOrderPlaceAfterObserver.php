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
use Magento\Framework\Session\SessionManager;

/**
 * Webkul Marketplace SalesOrderPlaceAfterObserver Observer Model.
 */
class SalesOrderPlaceAfterObserver implements ObserverInterface
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
     * @var Session
     */
    protected $_customerSession;

    /**
     * [$_coreSession description]
     * @var SessionManager
     */
    protected $_coreSession;

    /**
     * @var \Magento\Framework\Stdlib\DateTime\DateTime
     */
    protected $_date;

    /**
     * @param \Magento\Framework\Event\Manager            $eventManager
     * @param \Magento\Framework\ObjectManagerInterface   $objectManager
     * @param \Magento\Customer\Model\Session             $customerSession
     * @param SessionManager                              $coreSession
     * @param \Magento\Framework\Stdlib\DateTime\DateTime $date
     */
    public function __construct(
        \Magento\Framework\Event\Manager $eventManager,
        \Magento\Framework\ObjectManagerInterface $objectManager,
        \Magento\Customer\Model\Session $customerSession,
        SessionManager $coreSession,
        \Magento\Framework\Stdlib\DateTime\DateTime $date
    ) {
        $this->_eventManager = $eventManager;
        $this->_objectManager = $objectManager;
        $this->_customerSession = $customerSession;
        $this->_coreSession = $coreSession;
        $this->_date = $date;
    }

    /**
     * Sales Order Place After event handler.
     *
     * @param \Magento\Framework\Event\Observer $observer
     */
    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        /** @var $orderInstance Order */
        $order = $observer->getOrder();
        $lastOrderId = $observer->getOrder()->getId();
        $helper = $this->_objectManager->get(
            'Webkul\Marketplace\Helper\Data'
        );
        $this->getProductSalesCalculation($order);

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

        $adminStoremail = $helper->getAdminEmailId();
        $adminEmail = $adminStoremail ? $adminStoremail : $helper->getDefaultTransEmailId();
        $adminUsername = 'Admin';

        $customerModel = $this->_objectManager->create(
            'Magento\Customer\Model\Customer'
        );

        $sellerOrder = $this->_objectManager->create(
            'Webkul\Marketplace\Model\Orders'
        )
        ->getCollection()
        ->addFieldToFilter('order_id', $lastOrderId)
        ->addFieldToFilter('seller_id', ['neq' => 0]);
        foreach ($sellerOrder as $info) {
            $userdata = $customerModel->load($info['seller_id']);
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
            )->getCollection()
            ->addFieldToFilter('order_id', $lastOrderId)
            ->addFieldToFilter('seller_id', $info['seller_id'])
            ->addFieldToFilter('parent_item_id', ['null' => 'true'])
            ->addFieldToFilter('magerealorder_id', ['neq' => 0])
            ->addFieldToSelect('entity_id');

            $saleslistIds = $collection1->getData();

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
                ['status' => 'status']
            );

            $fetchsale->getSelect()->join(
                $salesOrderItem.' as soi', 
                'main_table.order_item_id = soi.item_id AND main_table.order_id = soi.order_id', 
                [
                    'item_id' => 'item_id', 
                    'qty_canceled' => 'qty_canceled', 
                    'qty_invoiced' => 'qty_invoiced', 
                    'qty_ordered' => 'qty_ordered', 
                    'qty_refunded' => 'qty_refunded', 
                    'qty_shipped' => 'qty_shipped', 
                    'product_options' => 'product_options', 
                    'mage_parent_item_id' => 'parent_item_id'
                ]
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
                                    $order->formatPrice(
                                        $res['magepro_price'] * $res['magequantity']
                                    ).
                                '</td>
                             </tr></tbody>';
                $totalTaxAmount = $totalTaxAmount + $res['total_tax'];
                $totalprice = $totalprice + ($res['magepro_price'] * $res['magequantity']);

                /*
                * Low Stock Notification mail to seller
                */
                if ($helper->getlowStockNotification()) {
                    $stockItemQty = $product['quantity_and_stock_status']['qty'];
                    if ($stockItemQty <= $helper->getlowStockQty()) {
                        $orderProductInfo = "<tbody><tr>
                                <td class='item-info'>".$productName."</td>
                                <td class='item-info'>".$sku."</td>
                                <td class='item-qty'>".($res['magequantity'] * 1).'</td>
                             </tr></tbody>';

                        $emailTemplateVariables = [];
                        $emailTempVariables['myvar1'] = $orderProductInfo;
                        $emailTempVariables['myvar2'] = $username;

                        $this->_objectManager->get(
                            'Webkul\Marketplace\Helper\Email'
                        )->sendLowStockNotificationMail(
                            $emailTemplateVariables,
                            $senderInfo,
                            $receiverInfo
                        );
                    }
                }
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
                                    $order->formatPrice($shippingCharges)."</span></td>
                                </tr>
                                <tr class='subtotal'>
                                    <th colspan='3'>".__('Tax Amount')."</th>
                                    <td colspan='3'><span>".
                                    $order->formatPrice($totalTaxAmount).'</span></td>
                                </tr>'.$codRow."
                                <tr class='subtotal'>
                                    <th colspan='3'>".__('Grandtotal')."</th>
                                    <td colspan='3'><span>".
                                    $order->formatPrice(
                                        $totalprice + 
                                        $totalTaxAmount + 
                                        $shippingCharges + 
                                        $totalCod
                                    ).'</span></td>
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
            )->sendPlacedOrderEmail(
                $emailTempVariables,
                $senderInfo,
                $receiverInfo
            );
        }
    }

    public function getProductSalesCalculation($order)
    {
        /*
        * Marketplace Order details save before Observer
        */
        $this->_eventManager->dispatch(
            'mp_order_save_before',
            ['order' => $order]
        );

        /*
        * Get Global Commission Rate for Admin
        */

        $helper = $this->_objectManager->get(
            'Webkul\Marketplace\Helper\Data'
        );

        $percent = $helper->getConfigCommissionRate();

        /*
        * Get Current Store Currency Rate
        */
        $currentCurrencyCode = $helper->getCurrentCurrencyCode();
        $baseCurrencyCode = $helper->getBaseCurrencyCode();
        $allowedCurrencies = $helper->getConfigAllowCurrencies();
        $rates = $helper->getCurrencyRates(
            $baseCurrencyCode, array_values($allowedCurrencies)
        );
        if (empty($rates[$currentCurrencyCode])) {
            $rates[$currentCurrencyCode] = 1;
        }

        $lastOrderId = $order->getId();

        /*
        * Marketplace Credit Management module Observer
        */
        $this->_eventManager->dispatch(
            'mp_discount_manager',
            ['order' => $order]
        );
        /*
        * Marketplace Credit discount data
        */
        $discountDetails = [];
        $discountDetails = $this->_coreSession->getData('salelistdata');

        $this->_eventManager->dispatch(
            'mp_advance_commission_rule',
            ['order' => $order]
        );

        $advanceCommissionRule = $this->_customerSession->getData(
            'advancecommissionrule'
        );

        $sellerProArr = [];
        $sellerTaxArr = [];
        $isShippingFlag = [];
        foreach ($order->getAllItems() as $item) {
            $itemData = $item->getData();
            $attrselection = unserialize(serialize($itemData['product_options']));
            $bundleSelectionAttributes = [];
            if (isset($attrselection['bundle_selection_attributes'])) {
                $bundleSelectionAttributes = unserialize(
                    serialize($attrselection['bundle_selection_attributes'])
                );
            } else {
                $bundleSelectionAttributes['option_id'] = 0;
            }
            if (!$bundleSelectionAttributes['option_id']) {
                $temp = $item->getProductOptions();
                $infoBuyRequest = $item->getProductOptionByCode('info_buyRequest');

                $mpassignproductId = 0;
                if (isset($infoBuyRequest['mpassignproduct_id'])) {
                    $mpassignproductId = $infoBuyRequest['mpassignproduct_id'];
                }
                if ($mpassignproductId) {
                    $mpassignModel = $this->_objectManager->create(
                        'Webkul\MpAssignProduct\Model\Items'
                    )->load($mpassignproductId);
                    $sellerId = $mpassignModel->getSellerId();
                } else if (array_key_exists('seller_id', $infoBuyRequest)) {
                    $sellerId= $infoBuyRequest['seller_id'];
                } else {
                    $sellerId='';
                }
                if ($discountDetails[$item->getProductId()]) {
                    $price = $discountDetails[$item->getProductId()]['price'] 
                    / $rates[$currentCurrencyCode];
                } else {
                    $price = $item->getPrice() / $rates[$currentCurrencyCode];
                }
                if ($sellerId == '') {
                    $collectionProduct = $this->_objectManager->create(
                        'Webkul\Marketplace\Model\Product'
                    )
                    ->getCollection()
                    ->addFieldToFilter(
                        'mageproduct_id',
                        $item->getProductId()
                    );
                    foreach ($collectionProduct as $value) {
                        $sellerId = $value->getSellerId();
                    }
                }
                if ($sellerId == '') {
                    $sellerId = 0;
                }

                if (
                    ($item->getProductType()!='virtual') && 
                    ($item->getProductType() != 'downloadable')
                ) {
                    if (!isset($isShippingFlag[$sellerId])) {
                        $isShippingFlag[$sellerId] = 1;
                    } else {
                        $isShippingFlag[$sellerId] = 0;
                    }
                }

                $collection1 = $this->_objectManager->create(
                    'Webkul\Marketplace\Model\Saleperpartner'
                )
                ->getCollection()
                ->addFieldToFilter(
                    'seller_id',
                    $sellerId
                );
                $taxamount = $itemData['tax_amount'];
                $qty = $item->getQtyOrdered();

                $totalamount = $qty * $price;

                if ($collection1->getSize() != 0) {
                    foreach ($collection1 as $rowdatasale) {
                        $commission = ($totalamount * $rowdatasale->getCommissionRate()) / 100;
                    }
                } else {
                    $commission = ($totalamount * $percent) / 100;
                }

                if (!$helper->getUseCommissionRule()) {
                    $wholedata['id'] = $item->getProductId();
                    $this->_eventManager->dispatch(
                        'mp_advance_commission',
                        [$wholedata]
                    );

                    $advancecommission = $this->_customerSession->getData('commission');
                    if ($advancecommission != '') {
                        $percent = $advancecommission;
                        $commType = $helper->getCommissionType();
                        if ($commType == 'fixed') {
                            $commission = $percent;
                        } else {
                            $commission = ($totalamount * $advancecommission) / 100;
                        }
                        if ($commission > $totalamount) {
                            $commission = $totalamount * $helper->getConfigCommissionRate() / 100;
                        }
                    }
                } else {
                    if (count($advanceCommissionRule)) {
                        if ($advanceCommissionRule[$item->getId()]['type'] == 'fixed') {
                            $commission = $advanceCommissionRule[$item->getId()]['amount'];
                        } else {
                            $commission = 
                            ($totalamount * $advanceCommissionRule[$item->getId()]['amount']) / 100;
                        }
                    }
                }

                $actparterprocost = $totalamount - $commission;

                $collectionsave = $this->_objectManager->create(
                    'Webkul\Marketplace\Model\Saleslist'
                );
                $collectionsave->setMageproductId($item->getProductId());
                $collectionsave->setOrderItemId($item->getItemId());
                $collectionsave->setParentItemId($item->getParentItemId());
                $collectionsave->setOrderId($lastOrderId);
                $collectionsave->setMagerealorderId($order->getIncrementId());
                $collectionsave->setMagequantity($qty);
                $collectionsave->setSellerId($sellerId);
                $collectionsave->setCpprostatus(\Webkul\Marketplace\Model\Saleslist::PAID_STATUS_PENDING);
                $collectionsave->setMagebuyerId($this->_customerSession->getCustomerId());
                $collectionsave->setMageproPrice($price);
                $collectionsave->setMageproName($item->getName());
                if ($totalamount != 0) {
                    $collectionsave->setTotalAmount($totalamount);
                    $commissionRate=($commission*100)/$totalamount;
                } else {
                    $collectionsave->setTotalAmount($price);
                    $commissionRate=$percent;
                }
                $collectionsave->setTotalTax($taxamount);
                $collectionsave->setTotalCommission($commission);
                $collectionsave->setActualSellerAmount($actparterprocost);
                $collectionsave->setCommissionRate($commissionRate);
                if (isset($isShippingFlag[$sellerId])) {
                    $collectionsave->setIsShipping($isShippingFlag[$sellerId]);
                }
                $collectionsave->setCreatedAt($this->_date->gmtDate());
                $collectionsave->setUpdatedAt($this->_date->gmtDate());
                $collectionsave->save();
                $qty = '';
                if (!isset($sellerTaxArr[$sellerId])) {
                    $sellerTaxArr[$sellerId] = 0;
                }
                $sellerTaxArr[$sellerId] = $sellerTaxArr[$sellerId]+$taxamount;
                if ($price != 0.0000) {
                    if (!isset($sellerProArr[$sellerId])) {
                        $sellerProArr[$sellerId] = [];
                    }
                    array_push($sellerProArr[$sellerId], $item->getProductId());
                } else {
                    if (!$item->getParentItemId()) {
                        if (!isset($sellerProArr[$sellerId])) {
                            $sellerProArr[$sellerId] = [];
                        }
                        array_push($sellerProArr[$sellerId], $item->getProductId());
                    }
                }
            }
        }

        $taxToSeller = $helper->getConfigTaxManage();
        $shippingAll = $this->_coreSession->getData('shippinginfo');
        $shippingAllCount = count($shippingAll);
        foreach ($sellerProArr as $key => $value) {
            $productIds = implode(',', $value);
            $data = [
                'order_id'      =>  $lastOrderId,
                'product_ids'   =>  $productIds,
                'seller_id'     =>  $key,
                'total_tax'     =>  $sellerTaxArr[$key],
                'tax_to_seller' =>  $taxToSeller
            ];
            if (!$shippingAllCount && $key == 0) {
                $shippingCharges = $order->getShippingAmount();
                $data = [
                    'order_id'         => $lastOrderId,
                    'product_ids'      => $productIds,
                    'seller_id'        => $key,
                    'shipping_charges' => $shippingCharges,
                    'total_tax'        => $sellerTaxArr[$key],
                    'tax_to_seller'    => $taxToSeller
                ];
            }
            $collection = $this->_objectManager->create(
                'Webkul\Marketplace\Model\Orders'
            );
            $collection->setData($data);
            $collection->setCreatedAt($this->_date->gmtDate());
            $collection->setUpdatedAt($this->_date->gmtDate());
            $collection->save();
        }
        /*
        * Marketplace Order details save after Observer
        */
        $this->_eventManager->dispatch(
            'mp_order_save_after',
            ['order' => $order]
        );
    }
}
