<?php

/**
 * @author Amasty Team
 * @copyright Copyright (c) 2017 Amasty (https://www.amasty.com)
 * @package Amasty_Stockstatus
 */
class Amasty_Stockstatus_Helper_Data extends Mage_Core_Helper_Abstract
{
    protected $_statusId = null;
    protected $_uploadDir = '/amstockstatus/icons/';

    const ONE_DAY = 86400;//1 Day = 24*60*60 = 86400;

    public function show($product)
    {
        return Mage::app()->getLayout()->createBlock('amstockstatus/status')->setProduct($product)->toHtml();
    }


    public function getStockStatusFromHistory($orderId, $productId)
    {
        if (!Mage::getStoreConfig('amstockstatus/general/display_order')) {
            return '';
        }
        $model = Mage::getModel('amstockstatus/history')->loadByOrderAndProduct($orderId, $productId);
        if ($model && $model->getStatus()) {
            return ' (' . $model->getStatus() . ')';
        }

        return '';
    }

    public function showStockStatus($product, $addAvail = false, $isProductList = false)
    {
        if ($product->isAvailable()) {
            $result = $this->__('In stock');
        } else {
            $result = $this->__('Out of stock');
        }
        if ($isProductList) {
            $result = '';
        }

        if (!Mage::getStoreConfig('amstockstatus/general/displayforoutonly') || !$product->isSaleable()) {
            if ($status = $this->getCustomStockStatusText($product)) {
                if (Mage::getStoreConfig('amstockstatus/general/icononly')) {
                    if ($product->getData('hide_default_stock_status'))
                        $result = $this->getStatusIconImage($product);
                    else
                        $result .= $this->getStatusIconImage($product);
                } else {
                    if ($product->getData('hide_default_stock_status'))
                        $result = $this->getStatusIconImage($product) . $status;
                    else
                        $result .= ' ' . $this->getStatusIconImage($product) . $status;
                }
               
                $result =
                    '<span class="amstockstatus 123 amsts_' . $this->getCustomStockStatusId() . '">' . $result . '</span>';
            }
        }
        if ($addAvail) {
            $result = $this->__('Availability:') . $result;
        }
        if ($isProductList) {
            $result = '<p class="availability" style="padding-bottom: 6px;">' . $result . '</p>';
        }

        return $result;
    }

    public function processViewStockStatus($product, $html)
    {
        if (!Mage::getStoreConfig('amstockstatus/general/displayforoutonly') || !$product->isSaleable()) {
            if ($status = Mage::helper('amstockstatus')->getCustomStockStatusText($product)) {
                //            v
                $status = Mage::getStoreConfig('amstockstatus/general/icononly') ? ' ' : $status;
                $status = $this->getStatusIconImage($product)
                    .'<span class="amstockstatus 321 amsts_' . $this->getCustomStockStatusId() . '">' . $status . '</span>';

                $tag = '<p class="availability';
                if ((false !== strpos($html, $tag)) && (false === strpos($html, $status))) {
                    $pattern = "@($tag)(.*?<span class=\"value\")(.*?)</span>@si";
                    $count = 0;

                    $replace = '$1$2$3 ' . $status . '</span>';
                    if (Mage::getStoreConfig('amstockstatus/general/icononly')
                        || $product->getData('hide_default_stock_status')
                    ) {
                        $replace = '$1$2>' . $status . '</span>';
                    }
                    $html = preg_replace($pattern, $replace, $html, 2, $count);
                    if ($count == 0) {//where status doesn't have value class
                        $pattern = "@($tag)(.*?<span)(.*?)</span>@si";
                        $html = preg_replace($pattern, $replace, $html, 2, $count);
                    }
                }
                if (false === strpos($html, $status)) {
                    // regexp
                    $inStock = Mage::helper('amstockstatus')->__('In stock') . '.?';
                    $outStock = Mage::helper('amstockstatus')->__('Out of stock') . '.?';

                    if (Mage::getStoreConfig('amstockstatus/general/icononly')
                        || $product->getData('hide_default_stock_status')
                    ) {
                        $html = preg_replace(
                            "@($inStock|$outStock)[\s]*<@",
                            '' . $status . '<',
                            $html
                        );
                    } else {
                        $html = preg_replace(
                            "@($inStock|$outStock)[\s]*<@",
                            '$1 ' . $status . '<',
                            $html
                        );
                    }
                }
            }
        }
        return $html;
    }

    public function getStatusIconImage($product)
    {
        $iconHtml = '';
        $altText = '';
        if ($iconUrl = $this->getStatusIconUrl($this->getCustomStockStatusId())) {
            $altText = Mage::getStoreConfig('amstockstatus/general/alt_text');
            if (false !== strpos($altText, '{qty}')) {
                $quantity = $this->_getProductQty($product);
                $altText = str_replace('{qty}', $quantity, $altText);
            }
            $bubble = Mage::getBaseUrl('js') . 'amasty/amstockstatusxnotif/bubble.gif';
            $bubbleFiller = Mage::getBaseUrl('js') . 'amasty/amstockstatusxnotif/bubble_filler.gif';
            if ($altText) {
                $iconHtml .= <<<INLINECSS
                <style type="text/css">
                /*---------- bubble tooltip -----------*/
                span.tt{
                    position:relative;
                    z-index:950;
                    color:#3CA3FF;
                	font-weight:bold;
                    text-decoration:none;
                }
                span.tt span{ display: none; }
                /*background:; ie hack, something must be changed in a for ie to execute it*/
                span.tt:hover{ z-index:25; color: #aaaaff; background:;}
                span.tt:hover span.amtooltip{
                    display:block;
                    position:absolute;
                    top:0px; left:0;
                	padding: 15px 0 0 0;
                	width:200px;
                	color: #3f3f3f;
                	font-size: 12px;
                    text-align: center;
                	filter: alpha(opacity:95);
                	KHTMLOpacity: 0.95;
                	MozOpacity: 0.95;
                	opacity: 0.95;
                }
                span.tt:hover span.top{
                	display: block;
                	padding: 30px 8px 0;
                    background: url($bubble) no-repeat top;
                }
                span.tt:hover span.middle{ /* different middle bg for stretch */
                	display: block;
                	padding: 0 8px;
                	background: url($bubbleFiller) repeat bottom;
                }
                span.tt:hover span.bottom{
                	display: block;
                	padding:3px 8px 10px;
                	color: #548912;
                    background: url($bubble) no-repeat bottom;
                }
                </style>
INLINECSS;
            }
            if ($altText) {
                $altText = Mage::helper('core')->escapeHtml($altText);
                $altText =
                    '<span class="amtooltip">
                        <span class="top"></span>
                        <span class="middle">
                            <strong>' . $altText . '</strong>
                        </span>
                        <span class="bottom"></span>
                    </span>';
            }
            $iconHtml .=
                ' <span class="tt">
                    <img src="' . $iconUrl . '" class="amstockstatus_icon" alt="" title="">' . $altText
                . '</span> ';
        }
        return $iconHtml;
    }
 public function getCustomStockStatusText(Mage_Catalog_Model_Product $product, $qty = 0)
    {
        if (!$product) {
            return '';
        }

        if ('true' == (string)Mage::getConfig()->getNode('modules/Amasty_Preorder/active')
            && Mage::helper('ampreorder')->getIsProductPreorder($product)
        ) {
            return Mage::helper('ampreorder')->getProductPreorderNote($product);
        }
        if (!$this->checkRightsForGroup()) {
            return '';
        }

        $status = '';
        $rangeStatus = Mage::getModel('amstockstatus/range');
        $quantity = null;
        $this->_statusId = null;

        if (($product->getData('custom_stock_status_qty_based')
            || $product->getData('custom_stock_status_quantity_based'))
        ) {
            $quantity = $this->_getProductQty($product);
            //load status from our model
            if (Mage::getStoreConfig('amstockstatus/general/use_range_rules')
                && $product->getData('custom_stock_status_qty_rule')
            ) {
                $rangeStatus->loadByQtyAndRule($quantity + $qty, $product->getData('custom_stock_status_qty_rule'));
            } else {
                $rangeStatus->loadByQty($quantity + $qty);
            }

        }

        if ($rangeStatus->hasData('status_id')) {
            $this->_statusId = $rangeStatus->getData('status_id');
            // getting status for range
            $attribute = Mage::getModel('eav/config')->getAttribute('catalog_product', 'custom_stock_status');
            foreach ($attribute->getSource()->getAllOptions(true, false) as $option) {
                if ($rangeStatus->getData('status_id') == $option['value']) {
                    $status = $option['label'];
                    break;
                }
            }
        } elseif (!Mage::getStoreConfig('amstockstatus/general/userangesonly') || $product->isConfigurable()) {
            $status = $product->getAttributeText('custom_stock_status');
            $this->_statusId = $product->getData('custom_stock_status');
        }

        $status = $this->replaceVariables($status, $product, $quantity, $qty);

        return $status;
    }


    public function getCustomStockStatusId()
    {
        return $this->_statusId;
    }

    protected function replaceVariables($status, $product, $quantity, $qty)
    {
        if (false !== strpos($status, '{qty}')) {
            if (!$quantity) {
                $quantity = $this->_getProductQty($product);
            }

            $status = str_replace('{qty}', (int)($quantity + $qty), $status);
        }


        if (false !== strpos($status, '{eta}')) {
         $id = $product->getData('entity_id');
                $eta = Mage::getModel('catalog/product')->load($id);
$eta = Mage::getSingleton('core/date')->date("d-M-Y",$eta->getEta() );
            $status = str_replace('{eta}',$eta, $status);
        }
        $status = $this->_replaceCustomDates($status, self::ONE_DAY, "tomorrow", 1);
        $status = $this->_replaceCustomDates($status, 2 * self::ONE_DAY, "day-after-tomorrow", 1);
        $status = $this->_replaceCustomDates($status, -self::ONE_DAY, "yesterday", 1);

        $status = $this->searchAttributes($status, $product);

        return $status;
    }

    /**
     * search for attribute entries
     * @param $status
     * @param $product
     * @return mixed
     */
    protected function searchAttributes($status, $product)
    {
        preg_match_all('@\{(.+?)\}@', $status, $matches);
        if (isset($matches[1]) && !empty($matches[1])) {
            foreach ($matches[1] as $match) {
                if ($value = $product->getData($match)) {
                    if (preg_match("/([0-9]{4})-([0-9]{2})-([0-9]{2})/", $value)) {
                        $value = Mage::helper('core')->formatDate(
                            $value,
                            Mage_Core_Model_Locale::FORMAT_TYPE_MEDIUM,
                            false
                        );
                    }
                    $status = str_replace('{' . $match . '}', $value, $status);
                } else {
                    $status = str_replace('{' . $match . '}', "", $status);
                }
            }
        }

        return $status;
    }

    protected function _replaceCustomDates($status, $time, $name, $excludeSunday)
    {
        $pattern = '@\{' . $name . '\}@';
        preg_match_all($pattern, $status, $matches);
        if (isset($matches[0]) && !empty($matches[0])) {
            foreach ($matches[0] as $match) {
                if ($excludeSunday && Mage::getSingleton('core/date')->date('w', time() + $time) == 0) {
                    $time += self::ONE_DAY;
                }
                $value = Mage::getSingleton('core/date')->date(
                    "d-m-Y",
                    time() + $time
                );

                $format = Mage::app()->getLocale()->getDateFormat(Mage_Core_Model_Locale::FORMAT_TYPE_MEDIUM);
                $value = $this->getCoreLocaleModel()->date(strtotime($value), null, null, false)
                    ->toString($format);

                $status = str_replace($match, $value, $status);
            }
        }

        return $status;
    }

    /**
     * @return Mage_Core_Model_Locale
     */
    protected function getCoreLocaleModel()
    {
        return Mage::getSingleton('core/locale');
    }

    public function getBackorderQnt()
    {
        return 0;
    }

    public function getStatusIconUrl($optionId)
    {
        $model = Mage::getModel('amstockstatus/type')->load($optionId);
        $type = $model->getExtenshion();
        $filePath = $this->getUploadDir() . $optionId . $type;
        if (file_exists(Mage::getBaseDir('media') . $filePath)) {
            return Mage::getBaseUrl('media') . $filePath;
        }
        return '';
    }

    public function getStockAlert($product)
    {
        if (!$product->getId() || !Mage::getStoreConfig('amstockstatus/general/stockalert')) {
            return '';
        }

        $tempCurrentProduct = Mage::registry('current_product');
        Mage::unregister('product');
        Mage::unregister('current_product');
        Mage::register('current_product', $product);
        Mage::register('product', $product);

        $alertBlock = Mage::app()->getLayout()->createBlock(
            'productalert/product_view',
            'productalert.stock.' . $product->getId()
        );

        if ($alertBlock) {
            $alertBlock->setTemplate('productalert/product/view.phtml');
            $alertBlock->prepareStockAlertData();
            $alertBlock->setHtmlClass('alert-stock link-stock-alert');
            $alertBlock->setSignupLabel($this->__('Sign up to get notified when this configuration is back in stock'));
            $html = $alertBlock->toHtml();

            Mage::unregister('product');
            Mage::unregister('current_product');
            Mage::register('current_product', $tempCurrentProduct);
            Mage::register('product', $tempCurrentProduct);

            return $html;
        }

        Mage::unregister('product');
        Mage::unregister('current_product');
        Mage::register('current_product', $tempCurrentProduct);
        Mage::register('product', $tempCurrentProduct);

        return '';
    }

    public function getStatusBySku($sku, $qty = 1)
    {
        if (Mage::getStoreConfig('amstockstatus/general/displayinemail')) {
            $product = Mage::getModel('catalog/product')->loadByAttribute('sku', $sku);
            if (!$product)
                return false;

            if (!Mage::getStoreConfig('amstockstatus/general/displayforoutonly') || !$product->isSaleable()) {
                $status = null;
                if ($status = $this->getCustomStockStatusText($product, $qty)) {
                    return ' (' . $status . ')';
                }
            }
        }
        return "";
    }

    public function getStockStatusByIdInCart($productId, $qty = 1)
    {
        $status = '';
        $product = Mage::getModel('catalog/product')->load($productId);
        if (!(Mage::getStoreConfig('amstockstatus/general/displayforoutonly')
                && $product->isSaleable())
            || ($product->isInStock()
                && $qty <= $this->getBackorderQnt())
            && Mage::getStoreConfig('amstockstatus/general/displayincart')
        ) {
            $status = $this->getCustomStockStatusText($product);
        }

        return $status;
    }

    /**
     *
     * Check if MageWorx Admin Order Editor (MageWorx_OrdersEdit) enabled
     *
     * @return bool
     */
    public function isOrderEditEnabled()
    {
        if (!Mage::helper('core')->isModuleEnabled('MageWorx_OrdersEdit')) {
            return false;
        }
        if (!is_object(Mage::helper('mageworx_ordersedit'))) {
            return false;
        }

        return Mage::helper('mageworx_ordersedit')->isEnabled();

    }

    protected function _getProductQty($product)
    {
        if ($product->isConfigurable()) {
            //get total qty for configurable product as summ from simple
            $conf = Mage::getModel('catalog/product_type_configurable')->setProduct($product);
            $collection = $conf->getUsedProductCollection()->addAttributeToSelect('*')->addFilterByRequiredOptions();

            $quantity = 0;
            foreach ($collection as $simple) {
                $simpleQty = (int)(Mage::getModel('cataloginventory/stock_item')->loadByProduct($simple)->getQty());
                if ($simpleQty > 0) {
                    $quantity += $simpleQty;
                }
            }
        } else {
            $stockItem = Mage::getModel('cataloginventory/stock_item')->loadByProduct($product);
            $quantity = $stockItem->getData('qty');
        }

        return (int)$quantity;
    }


    public function getUploadDir()
    {
        return $this->_uploadDir;
    }

    protected function checkRightsForGroup()
    {
        $setting = Mage::getStoreConfig('amstockstatus/general/allowgroup');
        $setting = explode(',', $setting);
        if (in_array(Amasty_Stockstatus_Model_Source_Group::ALL_GROUPS, $setting)) {
            return true;
        }
        if (in_array(Mage::getSingleton('customer/session')->getCustomerGroupId(), $setting)) {
            return true;
        }

        return false;
    }
}

