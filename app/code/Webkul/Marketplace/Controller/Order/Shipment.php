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
 * Webkul Marketplace Order Shipment Controller.
 */
class Shipment extends \Webkul\Marketplace\Controller\Order
{
    /**
     * Prepare shipment.
     *
     * @param \Magento\Sales\Model\Order\Invoice $invoice
     *
     * @return \Magento\Sales\Model\Order\Shipment|false
     */
    protected function _prepareShipment($order, $items, $trackingData)
    {
        $shipment = $this->_shipmentFactory->create(
            $order,
            $items,
            $trackingData
        );

        if (!$shipment->getTotalQty()) {
            return false;
        }

        return $shipment->register();
    }
    /**
     * @return \Magento\Framework\View\Result\Page
     */
    public function execute()
    {
        $helper = $this->_objectManager->create('Webkul\Marketplace\Helper\Data');
        $isPartner = $helper->isSeller();
        if ($isPartner == 1) {
            if ($order = $this->_initOrder()) {
                try {
                    $sellerId = $this->_customerSession->getCustomerId();
                    $orderId = $order->getId();
                    $marketplaceOrder = $this->_objectManager->create(
                        'Webkul\Marketplace\Helper\Orders'
                    )
                    ->getOrderinfo($orderId);
                    $trackingid = '';
                    $carrier = '';
                    $trackingData = array();
                    $paramData = $this->getRequest()->getParams();
                    if (!empty($paramData['tracking_id'])) {
                        $trackingid = $paramData['tracking_id'];
                        $trackingData[1]['number'] = $trackingid;
                        $trackingData[1]['carrier_code'] = 'custom';
                    }
                    if (!empty($paramData['carrier'])) {
                        $carrier = $paramData['carrier'];
                        $trackingData[1]['title'] = $carrier;
                    }

                    if (!empty($paramData['api_shipment'])) {
                        $this->_eventManager->dispatch(
                            'generate_api_shipment',
                            [
                                'api_shipment' => $paramData['api_shipment'], 
                                'order_id' => $orderId
                            ]
                        );
                        $shipmentData = $this->_customerSession->getData('shipment_data');
                        $apiName = $shipmentData['api_name'];
                        $trackingid = $shipmentData['tracking_number'];
                        $trackingData[1]['number'] = $trackingid;
                        $trackingData[1]['carrier_code'] = 'custom';
                        $this->_customerSession->unsetData('shipment_data');
                    }

                    if (!empty($paramData['api_shipment']) && 
                        $paramData['api_shipment'] && 
                        $trackingid == ''
                    ) {
                        return $this->resultRedirectFactory->create()->setPath(
                            '*/*/view',
                            [
                                'id' => $order->getEntityId(),
                                '_secure' => $this->getRequest()->isSecure(),
                            ]
                        );
                    } else {
                        if ($order->canUnhold()) {
                            $this->messageManager->addError(
                                __('Can not create shipment as order is in HOLD state')
                            );
                        } else {
                            $items = array();
                            $shippingAmount = 0;

                            $trackingsdata = $this->_objectManager->create(
                                'Webkul\Marketplace\Model\Orders'
                            )
                            ->getCollection()
                            ->addFieldToFilter(
                                'order_id',
                                $orderId
                            )
                            ->addFieldToFilter(
                                'seller_id',
                                $sellerId
                            );
                            foreach ($trackingsdata as $tracking) {
                                $shippingAmount = $tracking->getShippingCharges();
                            }

                            $collection = $this->_objectManager->create(
                                'Webkul\Marketplace\Model\Saleslist'
                            )
                            ->getCollection()
                            ->addFieldToFilter(
                                'order_id',
                                $orderId
                            )
                            ->addFieldToFilter(
                                'seller_id',
                                $sellerId
                            );
                            foreach ($collection as $saleproduct) {
                                array_push($items, $saleproduct['order_item_id']);
                            }

                            $itemsarray = $this->_getShippingItemQtys($order, $items);

                            if (count($itemsarray) > 0) {
                                $shipment = false;
                                $shipmentId = 0;
                                if (!empty($paramData['shipment_id'])) {
                                    $shipmentId = $paramData['shipment_id'];
                                }
                                if ($shipmentId) {
                                    $shipment = $this->_objectManager->create(
                                        'Magento\Sales\Model\Order\Shipment'
                                    )->load($shipmentId);
                                } elseif ($orderId) {
                                    if ($order->getForcedDoShipmentWithInvoice()) {
                                        $this->messageManager
                                        ->addError(
                                            __('Cannot do shipment for the order separately from invoice.')
                                        );
                                    }
                                    if (!$order->canShip()) {
                                        $this->messageManager->addError(
                                            __('Cannot do shipment for the order.')
                                        );
                                    }

                                    $shipment = $this->_prepareShipment(
                                        $order, $itemsarray['data'], $trackingData
                                    );
                                }
                                if ($shipment) {
                                    $comment = '';
                                    $shipment->getOrder()->setCustomerNoteNotify(
                                        !empty($data['send_email'])
                                    );
                                    $isNeedCreateLabel =
                                    !empty($data['create_shipping_label']) && $data['create_shipping_label'];
                                    $shipment->getOrder()->setIsInProcess(true);

                                    $transactionSave = $this->_objectManager->create(
                                        'Magento\Framework\DB\Transaction'
                                    )->addObject(
                                        $shipment
                                    )->addObject(
                                        $shipment->getOrder()
                                    );
                                    $transactionSave->save();

                                    $shipmentId = $shipment->getId();

                                    $courrier = 'custom';
                                    $sellerCollection = $this->_objectManager->create(
                                        'Webkul\Marketplace\Model\Orders'
                                    )
                                    ->getCollection()
                                    ->addFieldToFilter(
                                        'order_id',
                                        ['eq' => $orderId]
                                    )
                                    ->addFieldToFilter(
                                        'seller_id',
                                        ['eq' => $sellerId]
                                    );
                                    foreach ($sellerCollection as $row) {
                                        if ($shipment->getId() != '') {
                                            $row->setShipmentId($shipment->getId());
                                            $row->setTrackingNumber($trackingid);
                                            $row->setCarrierName($carrier);
                                            $row->save();
                                        }
                                    }

                                    $this->_shipmentSender->send($shipment);

                                    $shipmentCreatedMessage = __('The shipment has been created.');
                                    $labelCreatedMessage = __('The shipping label has been created.');
                                    $this->messageManager->addSuccess(
                                        $isNeedCreateLabel ? $shipmentCreatedMessage.' '.$labelCreatedMessage
                                        : $shipmentCreatedMessage
                                    );
                                }
                            }
                        }
                    }
                } catch (\Magento\Framework\Exception\LocalizedException $e) {
                    $this->messageManager->addError($e->getMessage());
                } catch (\Exception $e) {
                    $this->messageManager->addError(
                        __('We can\'t save the shipment right now.')
                    );
                    $this->messageManager->addError($e->getMessage());
                }

                return $this->resultRedirectFactory->create()->setPath(
                    '*/*/view',
                    [
                        'id' => $order->getEntityId(),
                        '_secure' => $this->getRequest()->isSecure(),
                    ]
                );
            } else {
                return $this->resultRedirectFactory->create()->setPath(
                    '*/*/history', 
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
