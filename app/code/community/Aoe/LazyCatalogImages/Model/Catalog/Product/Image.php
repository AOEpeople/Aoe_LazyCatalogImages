<?php

class Aoe_LazyCatalogImages_Model_Catalog_Product_Image extends Mage_Catalog_Model_Product_Image
{
    /**
     * @return bool
     */
    public function getIsBaseFilePlaceholder()
    {
        return (bool)$this->_isBaseFilePlaceholder;
    }

    /**
     * @param string $file
     *
     * @return $this
     */
    public function setNewFile($file)
    {
        $this->_newFile = $file;
        return $this;
    }
}
