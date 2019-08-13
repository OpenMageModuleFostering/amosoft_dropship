<?php

/**
 * Amosoft
 *
 * @category    Local
 * @package     Amosoft_Dropship
 */

class Amosoft_Dropship_Model_Resource_Rankinglog extends Mage_Core_Model_Mysql4_Abstract
{
    protected function _construct()
    {
        $this->_init("dropship/rankinglog", "id");
    }
}