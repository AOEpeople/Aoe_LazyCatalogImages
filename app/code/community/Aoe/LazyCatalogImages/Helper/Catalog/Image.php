<?php

class Aoe_LazyCatalogImages_Helper_Catalog_Image extends Mage_Catalog_Helper_Image
{
    const TOKEN_PREFIX = 'LCI';
    const TOKEN_LENGTH = 32;  // raw sha256 hmac
    const BASE64_REMAP_SEARCH = '+/=';
    const BASE64_REMAP_REPLACE = '-_~';
    const REGEX_ENCODE_SEARCH = '/(.{2})(.{2})(.*)/';
    const REGEX_ENCODE_REPLACE = '\1/\2/\3';
    const REGEX_DECODE_SEARCH = '|([a-zA-Z0-9-_~]{2})/([a-zA-Z0-9-_~]{2})/([a-zA-Z0-9-_~]+)|';
    const REGEX_DECODE_REPLACE = '\1\2\3';
    const DEFAULT_EXTENSION = 'jpg';

    /** @var bool */
    protected $_keepAspectRatio = true;
    /** @var bool */
    protected $_keepFrame = true;
    /** @var bool */
    protected $_keepTransparency = true;
    /** @var string */
    protected $_outputFile = null;

    /**
     * @param Mage_Core_Model_Store|int|null $store
     *
     * @return bool
     */
    public function isLciLogEnabled($store = null)
    {
        return Mage::getStoreConfigFlag('catalog/product_image/lci_log_enabled', $store);
    }

    /**
     * @param Mage_Core_Model_Store|int|null $store
     *
     * @return int
     */
    public function getMaxCacheAge($store = null)
    {
        return max(intval(Mage::getStoreConfig('catalog/product_image/lci_max_age', $store)), 0);
    }

    /**
     * {@inheritdoc}
     *
     * NOTE: This is just to capture the flag locally
     */
    public function keepAspectRatio($flag)
    {
        $this->_keepAspectRatio = (bool)$flag;
        return parent::keepAspectRatio($flag);
    }

    /**
     * {@inheritdoc}
     *
     * NOTE: This is just to capture the flag locally
     */
    public function keepFrame($flag, $position = array('center', 'middle'))
    {
        $this->_keepFrame = (bool)$flag;
        return parent::keepFrame($flag, $position);
    }

    /**
     * {@inheritdoc}
     *
     * NOTE: This is just to capture the flag locally
     */
    public function keepTransparency($flag, $alphaOpacity = null)
    {
        $this->_keepTransparency = (bool)$flag;
        return parent::keepTransparency($flag, $alphaOpacity);
    }

    public function initFromPathInfo($pathInfo)
    {
        return $this->initFromToken($this->getTokenFromPathInfo($pathInfo));
    }

    /**
     * Initialize the helper from a (possible) token
     *
     * NOTE: If the token cannot be decoded or verified this will return false
     *
     * @param $token
     *
     * @return bool
     */
    public function initFromToken($token)
    {
        $params = $this->decodeToken($token);
        if (!$params) {
            return false;
        }

        $this->_reset();
        $this->_setModel(Mage::getModel('catalog/product_image'));
        $this->_getModel()->setDestinationSubdir(isset($params['ds']) ? $params['ds'] : self::TOKEN_PREFIX);

        if (isset($params['f'])) {
            $this->setImageFile($params['f']);
        }

        if (isset($params['fr'])) {
            $this->setAngle($params['fr']);
        }
        if (isset($params['fw']) || isset($params['fh'])) {
            $width = (isset($params['fw']) ? $params['fw'] : null);
            $height = (isset($params['fh']) ? $params['fh'] : null);
            $this->resize($width, $height);
        }
        if (isset($params['fq'])) {
            $this->setQuality($params['fq']);
        }

        $this->keepAspectRatio(isset($params['fa']) && $params['fa']);
        $this->keepTransparency(isset($params['ft']) && $params['ft']);
        $this->keepFrame(isset($params['ff']) && $params['ff']);

        if (isset($params['wf'])) {
            $this->setWatermark($params['wf']);
        }
        if (isset($params['wo'])) {
            $this->setWatermarkImageOpacity($params['wo']);
        }
        if (isset($params['wp'])) {
            $this->setWatermarkPosition($params['wp']);
        }
        if (isset($params['ws'])) {
            $this->setWatermarkSize($params['ws']);
        }

        if (Mage::getStoreConfigFlag('catalog/product_image/lci_cache')) {
            // let Magento generate the image
            $outputFile = $this->getOutputFile();

            // Extract a file extension from original filename
            $extension = ($this->getImageFile() ? strtolower(pathinfo($this->getImageFile(), PATHINFO_EXTENSION)) : self::DEFAULT_EXTENSION);

            // Generate a full path to the LCI version of the cached file
            $cacheFile = $this->getPathFromToken($token, $extension);

            // If the LCI version of the file doesn't exist then create/link it to the Magento version
            if ($outputFile != $cacheFile && !is_dir($cacheFile) && !is_file($cacheFile)) {
                // Get the directory for the file
                $directory = pathinfo($cacheFile, PATHINFO_DIRNAME);

                // Ensure the directory exists
                if (!is_dir($directory)) {
                    mkdir($directory, 0775, true);
                }

                // Link cacheFile to outputFile
                if (is_dir($directory)) {
                    link($outputFile, $cacheFile);
                    if (is_file($cacheFile)) {
                        $this->_outputFile = $cacheFile;
                    }
                }
            }
        }

        return true;
    }

    /**
     * This method will render the image according to the current helper configuration
     * and return a file path to the location of the result
     *
     * NOTE: This method call should be considered expensive as the result of the call
     * is subject to garbage collection at any point. This means that every call to this
     * method should be treated as if the dynamic image creation happens each time.
     *
     * @return string
     * @throws Exception
     */
    public function getOutputFile()
    {
        if ($this->_outputFile) {
            return $this->_outputFile;
        }

        $model = $this->_getModel();

        // Set the base file.
        // We do not check if the image file is set here as an empty value will result in a placeholder already.
        $model->setBaseFile($this->getImageFile());

        // Try to detect if we failed to a placeholder image and trigger a redirect.
        $noSelection = ($this->getImageFile() === 'no_selection' || $this->getImageFile() === '' || $this->getImageFile() === null);
        if (!$noSelection && $this->isBaseFilePlaceholder($model)) {
            $this->setImageFile('no_selection');
            $newUrl = $this->__toString();
            throw new Aoe_LazyCatalogImages_RedirectException($newUrl, 'Invalid file specified');
        }

        if ($model->isCached()) {
            $this->_outputFile = $model->getNewFile();
        } else {
            if ($this->_scheduleRotate) {
                $model->rotate($this->getAngle());
            }

            if ($this->_scheduleResize) {
                $model->resize();
            }

            if ($this->getWatermark()) {
                $model->setWatermark($this->getWatermark());
            }

            $this->_outputFile = $model->saveFile()->getNewFile();
        }

        return $this->_outputFile;
    }

    /**
     * Return a URL that will trigger a lazy image creation on request
     *
     * NOTE: This URL is meant to be fronted by a caching proxy or CDN
     *
     * @return string
     */
    public function __toString()
    {
        if (!Mage::getStoreConfigFlag('catalog/product_image/lci_active')) {
            return parent::__toString();
        }

        Varien_Profiler::start('LCI: Aoe_LazyCatalogImages_Helper_Catalog_Image->__toString');
        try {
            $params = array();

            $params['ds'] = $this->_getModel()->getDestinationSubdir();

            if ($this->getImageFile()) {
                $params['f'] = $this->getImageFile();
            } elseif ($this->getProduct()) {
                $params['f'] = $this->getProduct()->getData($this->_getModel()->getDestinationSubdir());
            }

            $params['fr'] = $this->getAngle();
            $params['fw'] = $this->_getModel()->getWidth();
            $params['fh'] = $this->_getModel()->getHeight();
            $params['fq'] = $this->_getModel()->getQuality();
            // use 1 and 0 instead of true and false to lower the filename length
            $params['fa'] = $this->_keepAspectRatio ? 1 : 0;
            $params['ft'] = $this->_keepTransparency ? 1 : 0;
            $params['ff'] = $this->_keepFrame ? 1 : 0;

            if ($this->getWatermark()) {
                $params['wf'] = $this->getWatermark();
                $params['wo'] = $this->getWatermarkImageOpacity();
                $params['wp'] = $this->getWatermarkPosition();
                $params['ws'] = $this->getWatermarkSize();
            }

            // Encode the parameters into a tamper-proof, URL-safe token
            $token = $this->generateToken($params);

            // Set a default file extension for caching reasons
            $extension = self::DEFAULT_EXTENSION;

            // Extract a file extension if possible
            if (isset($params['f']) && $params['f'] !== 'no_selection') {
                $extension = strtolower(pathinfo($params['f'], PATHINFO_EXTENSION));
            }

            // Generate image URL
            $url = $this->getUrlFromToken($token, $extension);
        } catch (Exception $e) {
            // Log the exception so we can debug the problem later
            Mage::logException($e);

            // Generate placeholder image URL
            $url = Mage::getDesign()->getSkinUrl($this->getPlaceholder());
        }
        Varien_Profiler::stop('LCI: Aoe_LazyCatalogImages_Helper_Catalog_Image->__toString');
        return $url;
    }

    /**
     * Returns the filename (with dispersion) for a given token
     *
     * @param string $token
     * @param string $extension
     *
     * @return string
     */
    public function getFilenameFromToken($token, $extension = null)
    {
        // Filename dispersion
        $filename = preg_replace(self::REGEX_ENCODE_SEARCH, self::REGEX_ENCODE_REPLACE, $token);

        // Add LCI prefix
        $filename = self::TOKEN_PREFIX . '/' . $filename;

        // Append file extension
        $extension = trim($extension);
        if ($extension) {
            $filename .= '.' . ltrim($extension, '.');
        }

        return $filename;
    }

    /**
     * Returns the URL for a given token
     *
     * @param string $token
     * @param string $extension
     *
     * @return string
     */
    public function getUrlFromToken($token, $extension = null)
    {
        Varien_Profiler::start('LCI: Aoe_LazyCatalogImages_Helper_Catalog_Image->getUrlFromToken');
        /** @var Mage_Catalog_Model_Product_Media_Config $mediaConfig */
        $mediaConfig = Mage::getSingleton('catalog/product_media_config');
        $fileName    = $this->getFilenameFromToken($token, $extension);
        $secure      = Mage::app()->getStore()->isCurrentlySecure();

        // If the configuration is empty, default to base media url
        if ($mediaBaseUrl = Mage::getStoreConfig($secure ? 'web/secure/base_lci_url' : 'web/unsecure/base_lci_url')) {
            $mediaUrl = rtrim($mediaBaseUrl, '/') . '/' . str_replace(DS, '/', $fileName);
        } else {
            $mediaUrl = $mediaConfig->getMediaUrl($fileName);
        }
        Varien_Profiler::stop('LCI: Aoe_LazyCatalogImages_Helper_Catalog_Image->getUrlFromToken');
        return $mediaUrl;
    }

    /**
     * Returns the filename for a given token
     *
     * @param string $token
     * @param string $extension
     *
     * @return string
     */
    public function getPathFromToken($token, $extension = null)
    {
        /** @var Mage_Catalog_Model_Product_Media_Config $mediaConfig */
        $mediaConfig = Mage::getSingleton('catalog/product_media_config');
        return $mediaConfig->getMediaPath($this->getFilenameFromToken($token, $extension));
    }

    public function getTokenFromPathInfo($pathInfo)
    {
        /** @var Mage_Catalog_Model_Product_Media_Config $mediaConfig */
        $mediaConfig = Mage::getSingleton('catalog/product_media_config');
        $prefix = $mediaConfig->getBaseMediaUrlAddition() . '/' . self::TOKEN_PREFIX . '/';
        $start = strpos($pathInfo, $prefix);
        if ($start === false) {
            return null;
        }
        $token = substr($pathInfo, $start + strlen($prefix));
        $token = preg_replace(self::REGEX_DECODE_SEARCH, self::REGEX_DECODE_REPLACE, $token);
        $token = pathinfo($token, PATHINFO_FILENAME);

        return $token ?: null;
    }

    public function generateToken(array $params)
    {
        // Sort parameters
        ksort($params);

        // Filter the parameters
        $params = array_filter($params);

        // JSON serialize the parameters
        $params = json_encode($params);

        // Generate a token with a hash to prevent tampering
        $key = (string)Mage::getConfig()->getNode('global/crypt/key');
        $token = hash_hmac('sha256', $params, $key, true) . $params;

        // Base64 encode the token and transcribe the non URL-safe characters
        $token = strtr(base64_encode($token), self::BASE64_REMAP_SEARCH, self::BASE64_REMAP_REPLACE);

        return $token;
    }

    public function decodeToken($token)
    {
        // Decode the token and un-transcribe the non URL-safe characters
        $token = base64_decode(strtr($token, self::BASE64_REMAP_REPLACE, self::BASE64_REMAP_SEARCH), true);
        if (!$token) {
            return false;
        }

        // Parse token
        if (strlen($token) <= self::TOKEN_LENGTH) {
            return false;
        }
        $hash = substr($token, 0, self::TOKEN_LENGTH);
        $params = substr($token, self::TOKEN_LENGTH);

        // Validate hash
        $key = (string) Mage::getConfig()->getNode('global/crypt/key');
        $expectedHash = hash_hmac('sha256', $params, $key, true);
        if ($hash !== $expectedHash) {
            return false;
        }

        // Decode the serialized JSON data
        $params = json_decode($params, true);
        if (!is_array($params)) {
            return false;
        }

        return $params;
    }

    /**
     * Reset all previous data
     *
     * @return Mage_Catalog_Helper_Image
     */
    protected function _reset()
    {
        $this->_keepAspectRatio = true;
        $this->_keepFrame = true;
        $this->_keepTransparency = true;
        $this->_outputFile = null;

        return parent::_reset();
    }

    /**
     * @param Mage_Catalog_Model_Product_Image $model product image model
     * @return mixed
     */
    public function isBaseFilePlaceholder(Mage_Catalog_Model_Product_Image $model)
    {
        return Mage::helper('Aoe_LazyCatalogImages/placeholder')->getIsBaseFilePlaceholder($model);
    }
}
