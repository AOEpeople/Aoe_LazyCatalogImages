<?php

class Aoe_LazyCatalogImages_Model_Core_File_Storage_Database extends Mage_Core_Model_File_Storage_Database
{
    /**
     * {@inheritdoc}
     *
     * NOTE: This hijacks the database storage to attempt a decode on the filename to lazy load an image.
     * If an exception is thrown during the filename decode process then it logs and bails to allow the database
     * to handle the request as usual.
     * If a variable is not expected during the decode process then it bails to allow the database to handle
     * the request as usual.
     * If an exception is thrown after a successful decode (during the image creation) then it logs and a
     * redirect to the placeholder image is issued
     * If everything works as expected then an image is sent and the program is exited.
     * The early 'exit' allows the hijack to work as expected.
     *
     * @return Mage_Core_Model_File_Storage_Database
     */
    public function loadByFilename($filePath)
    {
        try {
            $filename = basename($filePath);
            $path = dirname($filePath);
            $prefix = Aoe_LazyCatalogImages_Helper_Catalog_Image::TOKEN_PREFIX;
            if (substr($path, -(strlen($prefix) + 1)) === ('/' . $prefix)) {
                /** @var Aoe_LazyCatalogImages_Helper_Catalog_Image $imageHelper */
                $imageHelper = Mage::helper('Aoe_LazyCatalogImages/Catalog_Image');
                if ($imageHelper->initFromToken($filename)) {
                    $cacheAge = $imageHelper->getMaxCacheAge();
                    try {
                        /** @var Aoe_LazyCatalogImages_Model_HttpTransferAdapter $adapter */
                        $adapter = Mage::getModel('Aoe_LazyCatalogImages/HttpTransferAdapter');
                        $adapter->send(
                            array(
                                'filepath' => $imageHelper->getOutputFile(),
                                'headers'  => array(
                                    'Cache-Control' => 'public, max-age=' . $cacheAge
                                )
                            )
                        );
                        exit;
                    } catch (Exception $e) {
                        Mage::logException($e);
                        $url = Mage::getDesign()->getSkinUrl($imageHelper->getPlaceholder());
                        header("HTTP/1.0 302 Moved Temporarily");
                        header('Cache-Control: public, max-age=' . intval($cacheAge / 10));
                        header('Location: ' . $url);
                        exit;
                    }
                }
            }
        } catch (Exception $e) {
            Mage::logException($e);
        }

        return parent::loadByFilename($filePath);
    }
}
