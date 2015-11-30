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
 * Default item model rewrite.
 *
 * @category  FireGento
 * @package   FireGento_Pdf
 * @author    FireGento Team <team@firegento.com>
 */
class FireGento_Pdf_Model_Items_Default extends Mage_Sales_Model_Order_Pdf_Items_Invoice_Default
{
    /**
     * Return item Sku
     *
     * @param  $item
     * @return mixed
     */
    public function getSku($item)
    {
        if($item->getSku())
        {
            return $item->getSku();

        } elseif($item->getOrderItem()->getProductOptionByCode('simple_sku')) {

            return $item->getOrderItem()->getProductOptionByCode('simple_sku');
        }
    }

    /**
     * Retrieve item options
     *
     * @return array
     */
    public function getItemOptions()
    {
        $result = array();

        if(is_object($this->getItem()->getOrderItem()))
        {
            $options = $this->getItem()->getOrderItem()->getProductOptions();
        } else {
            $options = $this->getItem()->getProductOptions();
        }

        if (is_array($options))
        {
            if (isset($options['options'])) {
                $result = array_merge($result, $options['options']);
            }
            if (isset($options['additional_options'])) {
                $result = array_merge($result, $options['additional_options']);
            }
            if (isset($options['attributes_info'])) {
                $result = array_merge($result, $options['attributes_info']);
            }
        }
        return $result;
    }

    /**
     * Draw item line.
     *
     * @param  int $position position of the product
     *
     * @return void
     */
    public function draw($position = 1)
    {
        $pdf = $this->getPdf();
        $page = $this->getPage();
        $order = $this->getOrder();
        $item = $this->getItem();
        if(!is_object($item->getOrderItem())) {
            $item->setOrderItem($item);
            $item->setQty($item->getQtyOrdered());
        }
        $lines = array();
        $fontSize = 9;

        // draw Position Number
        $lines[0] = array(
            array(
                'text'      => $position,
                'feed'      => $pdf->margin['left'] + 10,
                'align'     => 'right',
                'font_size' => $fontSize
            )
        );

        // draw SKU
        $lines[0][] = array(
            'text' => Mage::helper('core/string')->str_split($this->getSku($item), 19),
            'feed' => $pdf->margin['left'] + 25,
            'font_size' => $fontSize
        );

        // draw Product name
        $lines[0][] = array(
            'text' => Mage::helper('core/string')->str_split($item->getName(), 40, true, true),
            'feed' => $pdf->margin['left'] + 130,
            'font_size' => $fontSize
        );

        $options = $this->getItemOptions();
        if ($options) {
            foreach ($options as $option) {
                $optionTxt = $option['label'] . ': ';
                // append option value
                if (isset($option['value'])) {
                    $optionTxt .= isset($option['print_value']) ? $option['print_value'] : strip_tags($option['value']);
                }
                $optionArray = $pdf->_prepareText($optionTxt, $page, $pdf->getFontRegular(), $fontSize, 215);
                $lines[][] = array(
                    'text' => $optionArray,
                    'feed' => $pdf->margin['left'] + 135
                );
            }
        }

        $columns = array();
        // prepare qty
        $columns['qty'] = array(
            'text'      => $item->getQty() * 1,
            'align'     => 'right',
            'font_size' => $fontSize,
            '_width' => 30
        );

        // prepare price
        $columns['price'] = array(
            'text'      => $order->formatPriceTxt($item->getPrice()),
            'align'     => 'right',
            'font_size' => $fontSize,
            '_width'    => 60
        );

        // prepare price_incl_tax
        $columns['price_incl_tax'] = array(
            'text'      => $order->formatPriceTxt($item->getPriceInclTax()),
            'align'     => 'right',
            'font_size' => $fontSize,
            '_width'    => 60
        );

        // prepare tax
        $columns['tax'] = array(
            'text'      => $order->formatPriceTxt($item->getTaxAmount() + $item->getHiddenTaxAmount()),
            'align'     => 'right',
            'font_size' => $fontSize,
            '_width'    => 50
        );

        // prepare tax_rate
        $columns['tax_rate'] = array(
            'text'      => round($item->getOrderItem()->getTaxPercent(), 2) . '%',
            'align'     => 'right',
            'font_size' => $fontSize,
            '_width'    => 50
        );

        // prepare subtotal
        $columns['subtotal'] = array(
            'text'      => $order->formatPriceTxt($item->getRowTotal()),
            'align'     => 'right',
            'font_size' => $fontSize,
            '_width'    => 50
        );

        // prepare subtotal_incl_tax
        $columns['subtotal_incl_tax'] = array(
            'text'      => $order->formatPriceTxt($item->getRowTotalInclTax()),
            'align'     => 'right',
            'font_size' => $fontSize,
            '_width'    => 70
        );

        // draw columns in specified order
        $columnsOrder = explode(',', Mage::getStoreConfig('sales_pdf/invoice/item_price_column_order'));
        // draw starting from right
        $columnsOrder = array_reverse($columnsOrder);
        $columnOffset = 0;
        foreach ($columnsOrder as $columnName) {
            $columnName = trim($columnName);
            if (array_key_exists($columnName, $columns)) {
                $column = $columns[$columnName];
                $column['feed'] = $pdf->margin['right'] - $columnOffset;
                $columnOffset += $column['_width'];
                unset($column['_width']);
                $lines[0][] = $column;
            }
        }

        if (Mage::getStoreConfig('sales_pdf/invoice/show_item_discount') && 0 < $item->getDiscountAmount()) {
            // print discount
            $text = Mage::helper('firegento_pdf')->__(
                'You get a discount of %s.',
                $order->formatPriceTxt($item->getDiscountAmount())
            );
            $lines[][] = array(
                'text'  => $text,
                'align' => 'right',
                'feed'  => $pdf->margin['right'] - $columnOffset
            );
        }

        $lineBlock = array(
            'lines'  => $lines,
            'height' => 15
        );

        $page = $pdf->drawLineBlocks($page, array($lineBlock), array('table_header' => true));
        $this->setPage($page);
    }
}
