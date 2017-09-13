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
namespace Webkul\Marketplace\Model\Plugin\Order;

/**
 * Marketplace Order PDF CreditmemoPdfHeader Plugin
 */
class CreditmemoPdfHeader
{
    /**
     * Insert title and number for concrete document type
     *
     * @param  \Zend_Pdf_Page $page
     * @param  string $text
     * @return void
     */
    public function beforeInsertDocumentNumber(
        \Webkul\Marketplace\Model\Order\Pdf\Creditmemo $pdfCreditmemo, 
        $page, 
        $text
    ) {
        $creditmemoArr = explode(__('Credit Memo # '), $text);
        
        $creditmemoIncrementedId = $creditmemoArr[1];
        $creditmemoIds = $pdfCreditmemo->getObjectManager()->create(
            'Magento\Sales\Model\Order\Creditmemo'
        )->getCollection()
        ->addAttributeToFilter('increment_id', $creditmemoIncrementedId)
        ->getAllIds();
        if ($creditmemoIds) {
            $creditmemo = $pdfCreditmemo->getObjectManager()->create(
                'Magento\Sales\Model\Order\Creditmemo'
            )->load($creditmemoIds);
            $paymentData = $creditmemo->getOrder()->getPayment()->getData();
            $paymentInfo = $paymentData['additional_information']['method_title'];
            /* Payment */
            $yPayments = $pdfCreditmemo->y + 65;
            if (!$creditmemo->getOrder()->getIsVirtual()) {
                $paymentLeft = 35;
            } else {
                $yPayments = $yPayments +15;
                $paymentLeft = 285;
            }
            foreach ($pdfCreditmemo->getString()->split($paymentInfo, 45, true, true) as $_value) {
                $page->drawText(strip_tags(trim($_value)), $paymentLeft, $yPayments, 'UTF-8');
            }
        }
    }
}
