<?php

class Aoe_LazyCatalogImages_Model_HttpTransferAdapter extends Varien_File_Transfer_Adapter_Http
{
    /**
     * @param array $options
     *
     * @throws Exception
     */
    public function send(array $options = array())
    {
        $filepath = $options['filepath'];
        if (!is_file($filepath) || !is_readable($filepath)) {
            throw new Exception("File '{$filepath}' does not exists.");
        }

        $response = new Zend_Controller_Response_Http();
        $response->setHeader('Content-Length', filesize($filepath));
        $response->setHeader('Content-Type', $this->_detectMimeType(array('name' => $filepath)));
        if (is_array($options) && array_key_exists('headers', $options) && is_array($options['headers'])) {
            foreach ($options['headers'] as $header => $value) {
                $response->setHeader($header, $value);
            }
        }

        $response->sendHeaders();
        readfile($filepath);
    }
}
