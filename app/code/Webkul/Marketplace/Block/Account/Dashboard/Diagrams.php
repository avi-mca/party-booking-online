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
namespace Webkul\Marketplace\Block\Account\Dashboard;

class Diagrams extends \Magento\Framework\View\Element\Template
{
    /**
     * Google Api URL
     */
    const GOOGLE_API_URL = 'http://chart.apis.google.com/chart';

    /**
     * Seller statistics graph width
     *
     * @var string
     */
    protected $_width = '800';

    /**
     * Seller statistics graph height
     *
     * @var string
     */
    protected $_height = '375';

    /**
     * @var ObjectManagerInterface
     */
    protected $_objectManager;

    /**
     * @var Session
     */
    protected $_customerSession;

    /**
    * @param Context $context
    * @param array $data
    * @param \Magento\Framework\ObjectManagerInterface $objectManager
    * @param \Magento\Customer\Model\Session $customerSession
    */
    public function __construct(
        \Magento\Framework\ObjectManagerInterface $objectManager,
        \Magento\Customer\Model\Session $customerSession,
        \Magento\Framework\View\Element\Template\Context $context,
        array $data = []
    ) 
    {
        $this->_objectManager = $objectManager;
        $this->_customerSession = $customerSession;
        parent::__construct($context, $data);
    }

    /**
     * @return void
     */
    protected function _construct()
    {
        parent::_construct();
    }

    /**
     * @return $this
     */
    protected function _prepareLayout()
    {
        return parent::_prepareLayout();
    }

    public function getMonthlysale()
    {
        $sellerId = $this->_customerSession->getCustomerId();
        $data=array();  
        $curryear = date('Y');
        for ($i=0;$i<=12;$i++) {
            $date1=$curryear."-".$i."-01 00:00:00";
            $date2=$curryear."-".$i."-31 23:59:59";
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
                ['neq' => 0]
            )
            ->addFieldToFilter(
                'paid_status',
                ['neq' => 2]
            );
            $month = $collection
            ->addFieldToFilter(
                'created_at', 
                ['datetime' => true,'from' =>  $date1,'to' =>  $date2]
            );
            $sum=array();
            $temp=0;
            foreach ($collection as $record) {
                $temp = $temp+$record->getActualSellerAmount();
            }
            $price = $temp;
            $data[$i]=$price;
        }
        return $data;
    }

    /**
     * Get seller statistics graph image url
     *
     * @return string
     */
    public function getSellerStatisticsGraphUrl()
    {   
        $params = [
            'cht' => 'lc',
            'chm' => 'N,000000,0,-1,11',
            'chf' => 'bg,s,ffffff',
            'chxt' => 'x,y',
            'chds' =>'a'
        ];
        $getMonthlysale = $this->getMonthlysale();

        $totalSale = max($getMonthlysale);

        if ($totalSale) {
            $a = $totalSale/10;
            $axisYArr = array();
            for ($i=1; $i<=10 ; $i++) { 
                array_push($axisYArr, $a*$i);
            }
            $axisY = implode('|', $axisYArr);
        } else {
            $axisY = '10|20|30|40|50|60|70|80|90|100';
        }

        $params['chxl'] = '0:||'.
        __('January').'|'.
        __('February').'|'.
        __('March').'|'.
        __('April').'|'.
        __('May').'|'.
        __('June').'|'.
        __('July').'|'.
        __('August').'|'.
        __('September').'|'.
        __('October').'|'.
        __('November').'|'.
        __('December');
             
        $minvalue = 0;
        $maxvalue = $totalSale;

        $params['chd'] = 't:'.implode(',', $getMonthlysale);

        $valueBuffer = [];

        // seller statistics graph size
        $params['chs'] = $this->_width . 'x' . $this->_height;

        // return the encoded graph image url
        $_sellerDashboardHelperData = $this->_objectManager->get(
            'Webkul\Marketplace\Helper\Dashboard\Data'
        );
        $getParamData = urlencode(base64_encode(json_encode($params)));
        $getEncryptedHashData = 
        $_sellerDashboardHelperData->getChartEncryptedHashData($getParamData);
        $params = [
            'param_data' => $getParamData, 
            'encrypted_data' => $getEncryptedHashData
        ];
        return $this->getUrl(
            '*/*/dashboard_tunnel', 
            ['_query' => $params, '_secure' => $this->getRequest()->isSecure()]
        );
    }
}
