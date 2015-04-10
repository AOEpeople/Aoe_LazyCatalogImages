<?php

class Aoe_LazyCatalogImages_Model_Observer
{
    public function cleanCache(Varien_Event_Observer $observer)
    {
        /** @var Mage_Catalog_Model_Product_Media_Config $mediaConfig */
        $mediaConfig = Mage::getSingleton('catalog/product_media_config');
        $baseCacheDir = realpath($mediaConfig->getMediaPath(Aoe_LazyCatalogImages_Helper_Catalog_Image::TOKEN_PREFIX));

        $io = new Varien_Io_File();
        $io->cd($baseCacheDir);
        foreach ($io->ls(Varien_Io_File::GREP_DIRS) as $info) {
            $dir = $info['id'];
            if (strpos($dir, $baseCacheDir) === 0) {
                $io->rmdir($dir, true);
            }
        }
    }
}
