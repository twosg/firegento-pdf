<?php
/**
 * This file is part of a FireGento e.V. module.
 *
 * This FireGento e.V. module is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License version 3 as
 * published by the Free Software Foundation.
 *
 * This script is distributed in the hope that it will be useful, but WITHOUT
 * ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS
 * FOR A PARTICULAR PURPOSE. See the GNU General Public License for more details.
 *
 * PHP version 5
 *
 * @category  FireGento
 * @package   FireGento_Pdf
 * @author    FireGento Team <team@firegento.com>
 * @copyright 2014 FireGento Team (http://www.firegento.com)
 * @license   http://opensource.org/licenses/gpl-3.0 GNU General Public License, version 3 (GPLv3)
 */
/**
 * Invoice model rewrite.
 *
 * The invoice model serves as a proxy to the actual PDF engine as set via
 * backend configuration.
 *
 * @category  FireGento
 * @package   FireGento_Pdf
 * @author    FireGento Team <team@firegento.com>
 */
class FireGento_Pdf_Model_Order
{

    /**
     * The actual PDF engine responsible for rendering the file.
     *
     * @var Mage_Sales_Model_Order_Pdf_Abstract
     */
    private $_engine;

    /**
     * get pdf rendering engine
     *
     * @return Mage_Sales_Model_Order_Pdf_Abstract|Mage_Sales_Model_Order_Pdf_Invoice
     */
    protected function getEngine()
    {
        if (!$this->_engine)
        {
            $modelClass = Mage::getStoreConfig('sales_pdf/order/engine');
            $engine = Mage::getModel($modelClass);
            //die(get_class($engine));
            if (!$engine) {
                // Fallback to Magento standard invoice layout.
                $engine = new Mage_Sales_Model_Order_Pdf_Invoice();
            }
            $this->_engine = $engine;
        }

        return $this->_engine;
    }

    /**
     * get pdf for invoices
     *
     * @param  array|Varien_Data_Collection $invoices invoices to render pdfs for
     *
     * @return mixed
     */
    public function getPdf($orders = array())
    {
        return $this->getEngine()->getPdf($orders);
    }

}
