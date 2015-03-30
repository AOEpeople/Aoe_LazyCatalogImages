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
Mage::init($mageRunCode, $mageRunType);

try {

    $pathInfo = Mage::app()->getRequest()->getPathInfo();
    $res = preg_match('/'.Aoe_LazyCatalogImages_Helper_Catalog_Image::TOKEN_PREFIX.'\/([A-Za-z0-9-_~\/]*)\.(png|jpe?g|gif)$/', $pathInfo, $matches);

    if (!$res) {
        // no match or error
        exit;
    }

    $token = str_replace('/', '', $matches[1]);

    /** @var Aoe_LazyCatalogImages_Helper_Catalog_Image $imageHelper */
    $imageHelper = Mage::helper('Aoe_LazyCatalogImages/Catalog_Image');
    if ($imageHelper->initFromToken($token)) {
        $cacheAge = $imageHelper->getMaxCacheAge();
        try {

            // let Magento generate the image
            $outputFile = $imageHelper->getOutputFile();

            // copy/hardlink it to LCI path (pathinfo is safe at this point)
            $dirname = Mage::getBaseDir() . pathinfo($pathInfo,  PATHINFO_DIRNAME);
            if (!is_dir($dirname)) {
                mkdir($dirname, 0775, true);
            }
            if (is_dir($dirname)) {
                $targetFile = Mage::getBaseDir() . $pathInfo;
                link($outputFile, $targetFile);
                if (is_file($targetFile)) {
                    $outputFile = $targetFile;
                }
            }

            /** @var Aoe_LazyCatalogImages_Model_HttpTransferAdapter $adapter */
            $adapter = Mage::getModel('Aoe_LazyCatalogImages/HttpTransferAdapter');
            $adapter->send(
                array(
                    'filepath' => $outputFile,
                    'headers'  => array(
                        'Cache-Control' => 'public, max-age=' . $cacheAge
                    )
                )
            );
            exit;
        } catch (Exception $e) {
            Mage::logException($e);
            Mage::app()->getResponse()
                ->setRedirect(Mage::getDesign()->getSkinUrl($imageHelper->getPlaceholder()))
                ->setHeader('Cache-Control', 'public, max-age=' . intval($cacheAge / 10))
                ->sendResponse();
            exit;
        }
    } else {
        Mage::app()->getResponse()
            ->setHttpResponseCode(404)
            ->sendResponse();
        exit;
    }
} catch (Exception $e) {
    Mage::logException($e);
}
