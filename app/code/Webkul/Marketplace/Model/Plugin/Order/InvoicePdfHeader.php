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
 * Marketplace Order PDF InvoicePdfHeader Plugin
 */
class InvoicePdfHeader
{
    /**
     * Insert title and number for concrete document type
     *
     * @param  \Zend_Pdf_Page $page
     * @param  string $text
     * @return void
     */
    public function beforeInsertDocumentNumber(\Webkul\Marketplace\Model\Order\Pdf\Invoice $pdfInvoice, $page, $text)
    {
        $invoiceArr = explode(__('Invoice # '), $text);
        $invoiceIncrementedId = $invoiceArr[1];
        $invoice = $pdfInvoice->getObjectManager()->create('Magento\Sales\Model\Order\Invoice')
        ->loadByIncrementId($invoiceIncrementedId);
        $paymentData = $invoice->getOrder()->getPayment()->getData();
        $paymentInfo = $paymentData['additional_information']['method_title'];
        /* Payment */
        $yPayments = $pdfInvoice->y + 65;
        if (!$invoice->getOrder()->getIsVirtual()) {
            $paymentLeft = 35;
        } else {
            $yPayments = $yPayments +15;
            $paymentLeft = 285;
        }
        foreach ($pdfInvoice->getString()->split($paymentInfo, 45, true, true) as $_value) {
            $page->drawText(strip_tags(trim($_value)), $paymentLeft, $yPayments, 'UTF-8');
        }
    }
}
