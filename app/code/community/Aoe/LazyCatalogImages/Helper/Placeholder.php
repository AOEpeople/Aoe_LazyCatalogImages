<?php

/**
 * Exploiting PHP to gain access to a protected variable
 *
 * @author David Robinson
 * @since 2015-04-27
 */
class Aoe_LazyCatalogImages_Helper_Placeholder extends Mage_Catalog_Model_Product_Image
{
    /**
     * @return bool
     */
    public function getIsBaseFilePlaceholder(Mage_Catalog_Model_Product_Image $model)
    {
        return (bool)$model->_isBaseFilePlaceholder;
    }
}
