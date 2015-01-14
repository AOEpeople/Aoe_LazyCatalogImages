<?php

class Aoe_LazyCatalogImages_Helper_Catalog_Image extends Mage_Catalog_Helper_Image
{
    const TOKEN_PREFIX = 'LCI';

    /** @var int */
    protected $_maxCacheAge = 3600;
    /** @var bool */
    protected $_keepAspectRatio = true;
    /** @var bool */
    protected $_keepFrame = true;
    /** @var bool */
    protected $_keepTransparency = true;

    /**
     * @return int
     */
    public function getMaxCacheAge()
    {
        return $this->_maxCacheAge;
    }

    /**
     * @param int $maxAge
     *
     * @return $this
     */
    public function setMaxCacheAge($maxAge)
    {
        $this->_maxCacheAge = intval($maxAge);
        return $this;
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
        $this->_getModel()->setDestinationSubdir(self::TOKEN_PREFIX);

        if (isset($params['f'])) {
            $this->setImageFile($params['f']);
        }

        if (isset($params['fr'])) {
            $this->setAngle($params['fr']);
        }
        if (isset($params['fw']) || isset($params['fh'])) {
            $this->resize($params['fw'], $params['fh']);
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
        $model = $this->_getModel();

        if ($this->getImageFile()) {
            $model->setBaseFile($this->getImageFile());
        } else {
            Mage::throwException('Invalid image helper setup');
        }

        if ($model->isCached()) {
            return $model->getNewFile();
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

            return $model->saveFile()->getNewFile();
        }
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
        try {
            $params = array();

            if ($this->getImageFile()) {
                $params['f'] = $this->getImageFile();
            } else {
                $params['f'] = $this->getProduct()->getData($this->_getModel()->getDestinationSubdir());
            }

            $params['fr'] = $this->getAngle();
            $params['fw'] = $this->_getModel()->getWidth();
            $params['fh'] = $this->_getModel()->getHeight();
            $params['fq'] = $this->_getModel()->getQuality();
            $params['fa'] = $this->_keepAspectRatio;
            $params['ft'] = $this->_keepTransparency;
            $params['ff'] = $this->_keepFrame;

            if ($this->getWatermark()) {
                $params['wf'] = $this->getWatermark();
                $params['wo'] = $this->getWatermarkImageOpacity();
                $params['wp'] = $this->getWatermarkPosition();
                $params['ws'] = $this->getWatermarkSize();
            }

            // Encode the parameters into a tamper-proof, URL-safe token
            $token = $this->generateToken($params);

            /** @var Mage_Catalog_Model_Product_Media_Config $mediaConfig */
            $mediaConfig = Mage::getSingleton('catalog/product_media_config');
            $url = $mediaConfig->getMediaUrl(self::TOKEN_PREFIX . '/' . $token);
        } catch (Exception $e) {
            Mage::logException($e);
            $url = Mage::getDesign()->getSkinUrl($this->getPlaceholder());
        }

        return $url;
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
        $token = hash_hmac('sha256', $params, $key) . ':' . $params;

        // Base64 encode the token and transcribe the non URL-safe characters
        $token = strtr(base64_encode($token), '+/=', '-_~');

        return $token;
    }

    public function decodeToken($token)
    {
        // Decode the token and un-transcribe the non URL-safe characters
        $token = base64_decode(strtr($token, '-_~', '+/='));
        if (!$token) {
            return false;
        }

        // Parse token
        $token = explode(':', $token, 2);
        if (count($token) !== 2) {
            return false;
        }
        $hash = $token[0];
        $params = $token[1];

        // Validate hash
        $key = (string)Mage::getConfig()->getNode('global/crypt/key');
        $expectedHash = hash_hmac('sha256', $params, $key);
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
        $this->_maxCacheAge = 3600;
        return parent::_reset();
    }
}
