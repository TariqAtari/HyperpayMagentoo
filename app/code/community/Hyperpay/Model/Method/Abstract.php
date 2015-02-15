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

/**
 * Abstract payment model
 *
 */
 
$ExternalLibPath=Mage::getModuleDir('', 'Hyperpay') . DS . 'core' . DS .'copyandpay.php';
require_once ($ExternalLibPath);

abstract class Hyperpay_Model_Method_Abstract extends Mage_Payment_Model_Method_Abstract
{
    
    /**
     * Is method a gateaway
     *
     * @var boolean
     */
    protected $_isGateway = true;

    /**
     * Can this method use for checkout
     *
     * @var boolean
     */
    protected $_canUseCheckout = true;

    /**
     * Can this method use for multishipping
     *
     * @var boolean
     */
    protected $_canUseForMultishipping = false;
    
    /**
     * Is a initalize needed
     *
     * @var boolean
     */
    protected $_isInitializeNeeded = true;

    /**
     *
     * @var string
     */
    protected $_accountBrand = '';

    /**
     *
     * @var type
     */
    protected $_methodCode = '';

    /**
     * Payment Title
     *
     * @var type
     */
    protected $_methodTitle = '';

    /**
     * @var string
     */
    protected $_paymentCode = 'DB';

    /**
     *
     * @var string
     */
    protected $_subtype = '';

    /**
     * Magento method code
     *
     * @var string
     */
    protected $_code = 'hyperpay_abstract';

    /**
     *
     * @var string
     */
    protected $_collectData = '';

    /**
     * Redirect or iFrame
     * @var type 
     */
    protected $_implementation = 'iframe';
    
    protected $_canCapture = true;
	
	
    /**
     * Retrieve the server mode
     *
     * @return string
     */	
    public function getServerMode()
    {
        $server_mode = Mage::getStoreConfig('payment/' . $this->getCode() . '/server_mode', $this->getOrder()->getStoreId());			
        return $server_mode;
    }
	
    /**
     * Retrieve the credentials
     *
     * @return array
     */
    public function getCredentials()
    {
        $credentials = array(
            'sender'      => Mage::getStoreConfig('payment/' . $this->getCode() . '/sender', $this->getOrder()->getStoreId()),
            'channel_id'  => Mage::getStoreConfig('payment/' . $this->getCode() . '/channel_id', $this->getOrder()->getStoreId()),
            'login'       => Mage::getStoreConfig('payment/' . $this->getCode() . '/login', $this->getOrder()->getStoreId()),
            'password'    => Mage::getStoreConfig('payment/' . $this->getCode() . '/password', $this->getOrder()->getStoreId())
        );
        return $credentials;
    }

    /**
     * Return Quote or Order Object depending what the Payment is
     *
     * @return Mage_Sales_Model_Order
     */
    public function getOrder()
    {
        $paymentInfo = $this->getInfoInstance();

        if ($paymentInfo instanceof Mage_Sales_Model_Order_Payment) {
            return $paymentInfo->getOrder();
        }

        return $paymentInfo->getQuote();
    }

    /**
     * Retrieve the order place URL
     *
     * @return string
     */
    public function getOrderPlaceRedirectUrl()
    {
        $name = Mage::helper('hyperpay')->getNameData($this->getOrder());
        $address = Mage::helper('hyperpay')->getAddressData($this->getOrder());
        $contact = Mage::helper('hyperpay')->getContactData($this->getOrder());
        $basket = Mage::helper('hyperpay')->getBasketData($this->getOrder());

        $credentials = $this->getCredentials();
        $server = $this->getServerMode();

        Mage::getSingleton('customer/session')->setServerMode($server);

        $lang='';
        $jsUrl = getJsUrl($server,$lang);
        Mage::getSingleton('customer/session')->setJsUrl($jsUrl);  

        $dataCust['first_name'] = $name['first_name'];
        $dataCust['last_name'] = $name['last_name'];
        $dataCust['street'] = $address['street'];
        $dataCust['zip'] = $address['zip'];
        $dataCust['city'] = $address['city'];
        $dataCust['country_code'] = $address['country_code'];
        $dataCust['email'] = $contact['email'];
        //$dataCust['amount'] = $basket['amount'];
        //$dataCust['currency'] = Mage::app()->getStore()->getCurrentCurrencyCode();
        $dataCust['amount'] = $basket['baseAmount'];
        $dataCust['currency'] = $basket['baseCurrency'];        

        $dataTransaction = $credentials;
        $dataTransaction['tx_mode'] = $this->getTransactionMode();
        $dataTransaction['payment_type'] = $this->getHyperpayTransactionCode();
        $dataTransaction['orderId'] = Mage::getModel("sales/order")->getCollection()->getLastItem()->getIncrementId();

        $postData = getPostParameter($dataCust,$dataTransaction);

        $url = getTokenUrl($server);
        
        $token = getToken($postData,$url);
        
        Mage::getSingleton('customer/session')->setIframeToken($token);         
        Mage::getSingleton('customer/session')->setIframeName($name['first_name'].' '.$name['last_name']);          
        Mage::getSingleton('customer/session')->setIframeBrand($this->_accountBrand);           
        Mage::getSingleton('customer/session')->setIframeFrontendResponse(Mage::getUrl('hyperpay/response/handleCpResponse/',array('_secure'=>true)));           

        if ($token != '') {
            $this->_paymentform();
        } else {
            Mage::throwException(Mage::helper('hyperpay')->__('Error before redirect'));
        }

		return Mage::getSingleton('customer/session')->getRedirectUrl();
    }
	
    protected function getTransactionMode()
    {
		$server = $this->getServerMode();
		
		if ($server == "LIVE")
		{
			return 'LIVE';
		}
		else
		{
			switch ($this->_code) {
				case 'hyperpay_directdebit':
				case 'hyperpay_eps':				
				case 'hyperpay_giropay':					
					return 'INTEGRATOR_TEST';
					break;
                case 'hyperpay_creditcard':                    
				case 'hyperpay_ideal':
				case 'hyperpay_paypal':
				case 'hyperpay_sofortuberweisung':
				default:
					return 'CONNECTOR_TEST';
					break;
					
			}
		}
    }	
/*
    public function authorize(Varien_Object $payment, $amount)
    {		 
		try{
			
			$name = Mage::helper('hyperpay')->getNameData($this->getOrder());
			$address = Mage::helper('hyperpay')->getAddressData($this->getOrder());
			$contact = Mage::helper('hyperpay')->getContactData($this->getOrder());
			$basket = Mage::helper('hyperpay')->getBasketData($this->getOrder());

			$credentials = $this->getCredentials();
			$server = $this->getServerMode();

			Mage::getSingleton('customer/session')->setServerMode($server);

            $langs = Mage::helper('hyperpay')->getLocaleIsoCode(); 
            switch ($langs) {
                case 'de':
                case 'fr':
                case 'nl':
                case 'it':
                  $lang = $langs;
                  break;

                default:
                  $lang='en';
            }
            $jsUrl = getJsUrl($server,$lang);
			Mage::getSingleton('customer/session')->setJsUrl($jsUrl);			

			$dataCust['first_name'] = $name['first_name'];
			$dataCust['last_name'] = $name['last_name'];
			$dataCust['street'] = $address['street'];
			$dataCust['zip'] = $address['zip'];
			$dataCust['city'] = $address['city'];
			$dataCust['country_code'] = $address['country_code'];
			$dataCust['email'] = $contact['email'];
			$dataCust['amount'] = $basket['amount'];
			$dataCust['currency'] = Mage::app()->getStore()->getCurrentCurrencyCode();

			$dataTransaction = $credentials;
			$dataTransaction['tx_mode'] = $this->getTransactionMode();
			$dataTransaction['payment_type'] = $this->getHyperpayTransactionCode();

			$postData = getPostParameter($dataCust,$dataTransaction);

			$url = getTokenUrl($server);
			
			$token = getToken($postData,$url);
			
			Mage::getSingleton('customer/session')->setIframeToken($token);			
			Mage::getSingleton('customer/session')->setIframeName($name['first_name'].' '.$name['last_name']);			
			Mage::getSingleton('customer/session')->setIframeBrand($this->_accountBrand);			
			Mage::getSingleton('customer/session')->setIframeFrontendResponse(Mage::getUrl('hyperpay/response/handleCpResponse/',array('_secure'=>true)));			

			if ($token != '') {
                $payment->setAdditionalInformation('hyperpay_transaction_code', $this->getHyperpayTransactionCode());
                $this->_paymentform();
            } else {
                Mage::throwException(Mage::helper('hyperpay')->__('Error before redirect'));
            }
        } catch(Exception $e) {
            Mage::getSingleton('checkout/session')->setGotoSection('payment');
            Mage::throwException($e->getMessage());
        }
        
        return $this;
    }
*/	
    public function capture(Varien_Object $payment, $amount)
    {
		
		if ($payment->getAdditionalInformation('hyperpay_transaction_code') == 'PA') {
		
			$refId = $payment->getAdditionalInformation('IDENTIFICATION_REFERENCEID');
			$currency = $payment->getAdditionalInformation('CURRENCY');

			$dataTransaction =  $this->getCredentials();
			$dataTransaction['tx_mode'] = $this->getTransactionMode();

			$postData = getPostCapture($refId, $amount, $currency, $dataTransaction);

			$server = $this->getServerMode();

			$url = getExecuteUrl($server);

			$response = executePayment($postData, $url);
			$result = buildResponseArray($response);

			$payment->setAdditionalInformation('CAPTURE', $result['PROCESSING.RESULT']);
			
			if ($result['PROCESSING.RESULT'] == 'ACK') {
			
				$payment->setStatus('APPROVED')
						->setTransactionId($payment->getAdditionalInformation('IDENTIFICATION_REFERENCEID'))
						->setIsTransactionClosed(1)->save();
			} else {
				Mage::throwException(Mage::helper('hyperpay')->__('An error occurred while processing'));
			}
        
		}
		else {
			$payment->setStatus('APPROVED')
					->setTransactionId($payment->getAdditionalInformation('IDENTIFICATION_REFERENCEID'))
					->setIsTransactionClosed(1)->save();			
		}
        return $this;
    }
    
	public function processInvoice($invoice, $payment)
    {
        $invoice->setTransactionId($payment->getLastTransId());
		//$uniqueid = $payment->getAdditionalInformation('IDENTIFICATION_REFERENCEID');
		//$invoice->addComment($uniqueid,false,false);
        $invoice->save(); 
        $invoice->sendEmail();
        return $this;
    }
	
    /**
     *
     * @return string
     */
    public function getAccountBrand()
    {
        return $this->_accountBrand;
    }

    /**
     *
     * @return string
     */
    public function getPaymentCode()
    {
        return $this->_methodCode . "." . $this->getHyperpayTransactionCode();
    }
    
    public function getHyperpayTransactionCode()
    {
        return $this->_paymentCode;
    }
    
    /**
     *
     * @return string
     */
    public function getMethod()
    {
         return $this->_methodCode;
    }

    /**
     *
     * @return string
     */
    public function getSubtype()
    {
        return $this->_subtype;
    }

    /**
     *
     * @return string
     */
    public function getCollectData()
    {
        return $this->_collectData;
    }

    /**
     * Returns Payment Title
     *
     * @return string
     */
    public function getTitle()
    {
		
		if ($this->_code == "hyperpay_sofortuberweisung")
		{
			$address = Mage::helper('hyperpay')->getAddressData($this->getOrder());
			$code = $address['country_code'];
			if ($code == 'DE' || $code == 'AT' || $code =='CH') {
				return Mage::helper('hyperpay')->__('SOFORT Uberweisung');
			} else {
				return Mage::helper('hyperpay')->__('SOFORT Banking');
			}
		}
		else
		{
			return Mage::helper('hyperpay')->__($this->_methodTitle);
		}
	}


    /**
     * Set the iframe Url
     * 
     * @param array $response
     */
   
    protected function _paymentform()
    {
        Mage::getSingleton('customer/session')->setIframeFlag(true);
        
        if ($this->_code == "hyperpay_creditcard")
        {
            Mage::getSingleton('customer/session')->setRedirectUrl(Mage::app()->getStore(Mage::getDesign()->getStore())->getUrl('hyperpay/response/renderCC/', array('_secure'=>true)));
        }
        else if ($this->_code == "hyperpay_directdebit")
        {
            Mage::getSingleton('customer/session')->setRedirectUrl(Mage::app()->getStore(Mage::getDesign()->getStore())->getUrl('hyperpay/response/renderDD/', array('_secure'=>true)));
        }
        else if ($this->_code == "hyperpay_paypal")
        {
            Mage::getSingleton('customer/session')->setRedirectUrl(Mage::app()->getStore(Mage::getDesign()->getStore())->getUrl('hyperpay/response/redirectPayPal/', array('_secure'=>true)));
        }
        else
        {
            Mage::getSingleton('customer/session')->setRedirectUrl(Mage::app()->getStore(Mage::getDesign()->getStore())->getUrl('hyperpay/response/renderCP/', array('_secure'=>true)));
        }
    }
    
    /**
     * Retrieve implementation method
     * 
     * @return string
     */
    protected function _getImplementation()
    {
        return $this->_implementation;
    }
}

