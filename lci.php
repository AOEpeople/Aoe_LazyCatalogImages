<?php
if (version_compare(phpversion(), '5.2.0', '<') === true) {
    exit;
}

error_reporting(E_ALL | E_STRICT);
define('MAGENTO_ROOT', getcwd());

$compilerConfig = MAGENTO_ROOT . '/includes/config.php';
if (file_exists($compilerConfig)) {
    include $compilerConfig;
}

$maintenanceFile = 'maintenance.flag';
if (file_exists($maintenanceFile)) {
    include_once dirname(__FILE__) . '/errors/503.php';
    exit;
}

$mageFilename = MAGENTO_ROOT . '/app/Mage.php';
if (!file_exists($mageFilename)) {
    exit;
}
require_once $mageFilename;

if (isset($_SERVER['MAGE_IS_DEVELOPER_MODE'])) {
    Mage::setIsDeveloperMode(true);
}

umask(0);

$mageRunCode = isset($_SERVER['MAGE_RUN_CODE']) ? $_SERVER['MAGE_RUN_CODE'] : '';
$mageRunType = isset($_SERVER['MAGE_RUN_TYPE']) ? $_SERVER['MAGE_RUN_TYPE'] : 'store';
Mage::init($mageRunCode, $mageRunType, array('cache' => array('disallow_save' => true)));

try {
    /** @var Aoe_LazyCatalogImages_Helper_Catalog_Image $imageHelper */
    $imageHelper = Mage::helper('Aoe_LazyCatalogImages/Catalog_Image');
    $cacheAge = $imageHelper->getMaxCacheAge();
    try {
        if ($imageHelper->initFromPathInfo(Mage::app()->getRequest()->getPathInfo())) {
            /** @var Aoe_LazyCatalogImages_Model_HttpTransferAdapter $adapter */
            $adapter = Mage::getModel('Aoe_LazyCatalogImages/HttpTransferAdapter');
            $adapter->send(
                array(
                    'filepath' => $imageHelper->getOutputFile(),
                    'headers'  => array(
                        'Cache-Control' => 'public, max-age=' . $cacheAge,
                        'Last-Modified' => date('r'),
                        'Expires'       => gmdate("D, d M Y H:i:s", time() + $cacheAge) . " GMT"
                    )
                )
            );
            exit;
        } else {
            Mage::app()->getResponse()
                ->setHttpResponseCode(404)
                ->sendResponse();
        }
    } catch (Aoe_LazyCatalogImages_RedirectException $e) {
        Mage::app()->getResponse()
            ->setRedirect($e->getUrl(), ($e->getCode() == 301 ? 301 : 302))
            ->setHeader('Cache-Control', 'public, max-age=' . intval($cacheAge / 10))
            ->sendResponse();
    } catch (Exception $e) {
        Mage::logException($e);
        Mage::app()->getResponse()
            ->setRedirect(Mage::getDesign()->getSkinUrl($imageHelper->getPlaceholder()))
            ->setHeader('Cache-Control', 'public, max-age=' . intval($cacheAge / 10))
            ->sendResponse();
    }
} catch (Exception $e) {
    Mage::logException($e);
    Mage::app()->getResponse()
        ->setHttpResponseCode(500)
        ->setHeader('Content-Type', 'text/plain')
        ->setBody('Internal Server Error')
        ->sendResponse();
}
