<?php
/**
 * Magento
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Open Software License (OSL 3.0)
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://opensource.org/licenses/osl-3.0.php
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@magentocommerce.com so we can send you a copy immediately.
 *
 *
 * @package     Hyperpay
 * @copyright   Copyright (c) 2014 HYPERPAY
 * @license     http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */

class Hyperpay_Block_Payment_Form_Sofortuberweisung extends Hyperpay_Block_Payment_Form_Abstract
{
    /**
     * Construct
     */
	 
    protected function _construct()
    {
        $alt = $this->_setTitle();
//		$temp = '<!--img src="'.$this->getSkinUrl('images/hyperpay/logohyperpay.png').'" /-->  <img width="86" height=49" src="'.$this->getSkinUrl('images/hyperpay/sofort.png').'" alt="'.$alt.'" /> '.Mage::helper('hyperpay')->__('Pay by').' '.$alt;
		$temp = $alt;        
		$this->setMethodTitle($temp);
        parent::_construct();		

    }
    
    private function _setTitle()
    {
        $code = array('DE', 'CH', 'AT');
        if (in_array(strtoupper(Mage::getSingleton('checkout/session')->getQuote()->getBillingAddress()->getCountryId()), $code)) {
            return Mage::helper('hyperpay')->__('SOFORT Uberweisung');
        } else {
            return Mage::helper('hyperpay')->__('SOFORT Banking');
        }
    }
}