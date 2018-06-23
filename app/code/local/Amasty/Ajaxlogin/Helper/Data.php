<?php

/**
 * @author Amasty Team
 * @copyright Copyright (c) 2018 Amasty (https://www.amasty.com)
 * @package Amasty_Ajaxlogin
 */
class Amasty_Ajaxlogin_Helper_Data extends Mage_Core_Helper_Abstract
{
    public function jsParam($obj)
    {
        $param = array(
            'send_url' => $obj->getSendUrl(),
            'src_image_progress' => $obj->getSkinUrl('images/amasty/loading.gif'),
            'error' => $this->__(' ↑ This is a required field.'),
            'logged_in' => Mage::getSingleton('customer/session')->isLoggedIn()
        );

        return Zend_Json::encode($param);
    }

    public function getItemId($_product)
    {
        return Mage::getSingleton('checkout/session')->getQuote()->getItemByProduct($_product)->getId();
    }

    public function getCustomerBySocialId($name, $value)
    {
        $customer = Mage::getModel('customer/customer');

        $collection = $customer->getCollection()
            ->addAttributeToFilter($name, $value)
            ->setPageSize(1);

        if ($customer->getSharingConfig()->isWebsiteScope()) {
            $collection->addAttributeToFilter(
                'website_id',
                Mage::app()->getWebsite()->getId()
            );
        }

        if (Mage::getSingleton('customer/session')->isLoggedIn()) {
            $collection->addFieldToFilter(
                'entity_id',
                array('neq' => Mage::getSingleton('customer/session')->getCustomerId())
            );
        }
        if ($collection->getSize()) {
            return $collection->getFirstItem();
        }

        return false;
    }

    public function getCustomerByEmail($email)
    {
        $customer = Mage::getModel('customer/customer');

        $collection = $customer->getCollection()
            ->addFieldToFilter('email', $email)
            ->setPageSize(1);

        if ($customer->getSharingConfig()->isWebsiteScope()) {
            $collection->addAttributeToFilter(
                'website_id',
                Mage::app()->getWebsite()->getId()
            );
        }

        if (Mage::getSingleton('customer/session')->isLoggedIn()) {
            $collection->addFieldToFilter(
                'entity_id',
                array('neq' => Mage::getSingleton('customer/session')->getCustomerId())
            );
        }

        if ($collection->count()) return $collection->getFirstItem();
        return;
    }

    public function connectByCreatingAccount(
        $email,
        $firstName,
        $lastName,
        $Id,
        $token,
        $type)
    {
        $customer = Mage::getModel('customer/customer');
        $tokenName = "amajaxlogin_" . $type . "_token";
        $idName = "amajaxlogin_" . $type . "_id";
        $customer->setEmail($email)
            ->setFirstname($firstName)
            ->setLastname($lastName)
            ->setData($idName, $Id)
            ->setData($tokenName, $token)
            ->setPassword($customer->generatePassword(10))
            ->save();

        $customer->setConfirmation(null);
        $customer->save();

        $customer->sendNewAccountEmail();

        Mage::getSingleton('customer/session')->setCustomerAsLoggedIn($customer);

    }

    public function connectByEmail(
        Mage_Customer_Model_Customer $customer,
        $Id,
        $token,
        $type)
    {
        $tokenName = "amajaxlogin_" . $type . "_token";
        $idName = "amajaxlogin_" . $type . "_id";
        $customer->setData($idName, $Id)
            ->setData($tokenName, $token)
            ->save();

        Mage::getSingleton('customer/session')->setCustomerAsLoggedIn($customer);
    }

    public function loginByCustomer(Mage_Customer_Model_Customer $customer)
    {
        if ($customer->getConfirmation()) {
            $customer->setConfirmation(null);
            $customer->save();
        }

        Mage::getSingleton('customer/session')->setCustomerAsLoggedIn($customer);
        Mage::dispatchEvent('customer_customer_authenticated', array(
            'model'    => $customer,
            'password' => '',
        ));
    }


    public function getContentByCurl($url)
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_HEADER, 0);
        curl_setopt($ch, CURLOPT_POST, 0);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
        $response = curl_exec($ch);
        curl_close($ch);
        return $response;
    }

    public function printError($error)
    {
        Mage::helper('ambase/utils')->_echo($error);
        Mage::helper('ambase/utils')->_exit();
    }
}

