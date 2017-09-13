<?php
	exec("php bin/magento indexer:reindex");
    /*require_once("app/Mage.php");
    Mage::app('default');
    Mage::app()->setCurrentStore(Mage_Core_Model_App::ADMIN_STORE_ID);
    try{
        $indexerByShell = Mage::getBaseDir('shell/indexer.php');
        if(file_exists($indexerByShell))  
        {  
            $indexListByCode = array(
                           "catalog_product_attribute",
                           "catalog_product_price",
                           "catalog_product_flat",
                           "catalog_category_flat",
                           "catalog_category_product",
                           "catalog_url",
                           "catalogsearch_fulltext",
                           "cataloginventory_stock"
                    );
            //reindex using magento command line  
            foreach($indexListByCode as $indexer)  
            {  
                echo "reindex $indexer \n ";  
                exec("php $indexerByShell --reindex $indexer");  
            }
        }
    }catch(Exception $e){
        echo $e;
    }*/
?>