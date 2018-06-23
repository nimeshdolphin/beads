<?php
/***************************************************************************
 Extension Name	: New Products
 Extension URL	: http://www.magebees.com/magento-new-products-extension.html
 Copyright		: Copyright (c) 2015 MageBees, http://www.magebees.com
 Support Email	: support@magebees.com 
 ***************************************************************************/
class CapacityWebSolutions_Newproducts_Block_Adminhtml_DefaultTemplate
    extends Mage_Adminhtml_Block_System_Config_Form_Field
{
    protected function _getElementHtml(Varien_Data_Form_Element_Abstract $element)
    {
		return '<div style="background:#efefef;border:1px solid #d8d8d8;padding:10px;margin-bottom:10px; "><span>{{block type="newproducts/newproducts" name="newproducts"}}</span></div>';
	}
	
}