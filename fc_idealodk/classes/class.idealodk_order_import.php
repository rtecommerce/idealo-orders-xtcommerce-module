<?php
/**
 * idealo Direktkauf XT-Commerce Plugin
 * Copyright 2015 idealo internet GmbH
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 * http://www.apache.org/licenses/LICENSE-2.0
 * 
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 * 
 * PHP version 5
 * 
 * @category Plugin
 * @package  idealo Direktkauf XT-Commerce Plugin
 * @link     http://www.idealo.de/
 * @author   FATCHIP GmbH <supportfatchip.de>
 * @license  http://www.apache.org/licenses/LICENSE-2.0 Apache License, Version 2.0
 */

class idealodk_order_import
{
    public function __construct()
    {
        require_once dirname(__FILE__) . '/../lib/idealodksdk/Client.php';
    }
    
    public function run()
    {
        idealodk_logger::log('IDEALO ORDER IMPORT: NOTICE: Starting.');
        $oIdealo = new idealo\Direktkauf\REST\Client();
        $oIdealo->setToken(FC_IDEALODK_APIKEY);
        $blLiveMode = (FC_IDEALODK_MODE == 'live') ? true : false;
        $oIdealo->setIsLiveMode($blLiveMode);
        
        $aOrders = $oIdealo->getOrders();
        if (empty($aOrders)){
            idealodk_logger::log('IDEALO ORDER IMPORT: NOTICE: No Orders to import.');
        }
        foreach ($aOrders as $aOrder) {
            $this->saveOrder($aOrder);
        }
        idealodk_logger::log('IDEALO ORDER IMPORT: NOTICE: Finished.');
    }
    
    protected function saveOrder($aOrder)
    {
        $iCustomerId = $this->addCustomer($aOrder);
        $iAdressbookShippingId = $this->addCustomerAdress($aOrder, $iCustomerId, 'shipping');
        $iAdressbookBillingId = $this->addCustomerAdress($aOrder, $iCustomerId, 'payment');
        $iOrderId = $this->addOrder($aOrder, $iCustomerId, $iAdressbookShippingId, $iAdressbookBillingId);
        idealodk_logger::log('IDEALO ORDER IMPORT: NOTICE: XTC Order ID ' . $iOrderId);
        $this->addOrderTotal($aOrder, $iOrderId);
        $this->addOrderStatusHistory($iOrderId);
        if ( $this->addOrderProducts($aOrder, $iOrderId) == false){
            $this->resetImport($iOrderId);
            idealodk_logger::log('IDEALO ORDER IMPORT: ERROR: Order Nr.' . $aOrder['order_number'] . ' could not be imported!');
        } else {
            $this->sendOrderNr($aOrder['order_number'], $iOrderId);
            $this->addOrderStats($aOrder, $iOrderId);
            $this->addFcIdealoStatus($iOrderId);
        }
    }
    
    protected function addCustomerAdress($aOrder, $iCustomerId, $sAdressClass = 'default')
    {
        $oCustomer = new customer();
        
        $node = 'shipping_address';
        if ($sAdressClass == 'payment' && is_array($aOrder['billing_address'])){
            $node = 'billing_address';
        }
        
        $aData = [];
        $aData['customers_id'] = $iCustomerId;
        $aData['customers_firstname'] = $aOrder[$node]['given_name'];
        $aData['customers_lastname'] = $aOrder[$node]['family_name'];
        $aData['customers_street_address'] = $aOrder[$node]['address1'];
        $aData['customers_suburb'] = $aOrder[$node]['address2'];
        $aData['customers_postcode'] = $aOrder[$node]['zip'];
        $aData['customers_city'] = $aOrder[$node]['city'];
        $aData['customers_country_code'] = $aOrder[$node]['country'];
        $aData['customers_phone'] = $aOrder['customer']['phone'];
        $aData['address_class'] = $sAdressClass;
        $aData['date_added'] = date("Y-m-d H:i:s");
        if($aOrder[$node]['salutation'] != 'NONE') {
            $aData['customers_gender'] = ($aOrder[$node]['salutation'] == "MR") ? "m" : "f";
        }
        
        $oCustomer->_writeAddressData($aData);
        
        return $oCustomer->address_book_id;
    }
    
    protected function addCustomer($aOrder)
    {
        $oCustomer = new customer();
        
        $aData = [];
        $aData['customers_status'] = 2;
        $aData['customers_email_address'] = $aOrder['customer']['email'];
        $aData['date_added'] = date("Y-m-d H:i:s");
        $aData['shop_id'] = 1;
        $aData['customers_default_currency'] = 'EUR';
        $aData['customers_default_language'] = 'de';
        
        $oCustomer->_writeCustomerData($aData);
        
        return $oCustomer->data_customer_id;
    }
    
    protected function addFcIdealoStatus($iOrderId)
    {
        global $db;
        
        $aData = [];
        $aData['orders_id'] = $iOrderId;
        $aData['date_imported'] = date("Y-m-d H:i:s");
        
        $db->AutoExecute('xt_fcidealo_status',$aData,'INSERT');
    }
    
    protected function addOrder($aOrder, $iCustomerId, $iAdressbookShippingId, $iAdressbookBillingId)
    {
        $oOrder = new order();
        
        $aData = [];
        $aData['customers_id'] = $iCustomerId;
        $aData['customers_status'] = 2;
        $aData['customers_email_address'] = $aOrder['customer']['email'];
        $aData['currency_code'] = 'EUR';
        $aData['currency_value'] = '1.0000';
        $aData['language_code'] = 'de';
        $aData['last_modified'] = date("Y-m-d H:i:s");
        $aData['date_purchased'] = date("Y-m-d H:i:s");
        $aData['orders_status'] = 16;
        $aData['allow_tax'] = 1;
        $aData['shop_id'] = 1;
        $aData['source_id'] = $this->getSourceId();
        $aData['orders_source_external_id'] = $aOrder['order_number'];
        
        $aData['delivery_phone'] = $aOrder['customer']['phone'];
        $aData['delivery_firstname'] = $aOrder['shipping_address']['given_name'];
        $aData['delivery_lastname'] = $aOrder['shipping_address']['family_name'];
        $aData['delivery_street_address'] = $aOrder['shipping_address']['address1'];
        $aData['delivery_suburb'] = $aOrder['shipping_address']['address2'];
        $aData['delivery_city'] = $aOrder['shipping_address']['city'];
        $aData['delivery_postcode'] = $aOrder['shipping_address']['zip'];
        $aData['delivery_zone'] = 31;
        $aData['delivery_country'] = 'Deutschland';
        $aData['delivery_country_code'] = $aOrder['shipping_address']['country'];
        $aData['delivery_address_book_id'] = $iAdressbookShippingId;
        if($aOrder['shipping_address']['salutation'] != 'NONE') {
            $aData['delivery_gender'] = ($aOrder['shipping_address']['salutation'] == "MR") ? "m" : "f";
        }
        
        $aData['billing_phone'] = $aOrder['customer']['phone'];
        $aData['billing_firstname'] = ($aOrder['billing_address']['given_name'] != "") ? $aOrder['billing_address']['given_name'] : $aOrder['shipping_address']['given_name'];
        $aData['billing_lastname'] = ($aOrder['billing_address']['family_name'] != "") ? $aOrder['billing_address']['family_name'] : $aOrder['shipping_address']['family_name'];
        $aData['billing_street_address'] = ($aOrder['billing_address']['address1'] != "") ? $aOrder['billing_address']['address1'] : $aOrder['shipping_address']['address1'];
        $aData['billing_suburb'] = ($aOrder['billing_address']['address2'] != "") ? $aOrder['billing_address']['address2'] : $aOrder['shipping_address']['address2'];
        $aData['billing_city'] = ($aOrder['billing_address']['city'] != "") ? $aOrder['billing_address']['city'] : $aOrder['shipping_address']['city'];
        $aData['billing_postcode'] = ($aOrder['billing_address']['zip'] != "") ? $aOrder['billing_address']['zip'] : $aOrder['shipping_address']['zip'];
        $aData['billing_zone'] = 31;
        $aData['billing_country'] = 'Deutschland';
        $aData['billing_country_code'] = ($aOrder['billing_address']['country'] != "") ? $aOrder['billing_address']['country'] : $aOrder['shipping_address']['country'];
        $aData['billing_address_book_id'] = ($iAdressbookBillingId != "") ? $iAdressbookBillingId : $iAdressbookShippingId;
        if(isset($aOrder['billing_address']['salutation']) && $aOrder['billing_address']['salutation'] != 'NONE') {
            $aData['billing_gender'] = ($aOrder['billing_address']['salutation'] == "MR") ? "m" : "f";
        } else if($aOrder['shipping_address']['salutation'] != 'NONE') {
            $aData['billing_gender'] = ($aOrder['shipping_address']['salutation'] == "MR") ? "m" : "f";
        }
        
        if ($aOrder['payment']['payment_method'] == 'PAYPAL') {
            $sPayCode = FC_IDEALODK_PAYMENTCODE_PAYPAL;
        } else if ($aOrder['payment']['payment_method'] == 'SOFORT') {
            $sPayCode = FC_IDEALODK_PAYMENTCODE_SOFORT;
        } else if ($aOrder['payment']['payment_method'] == 'CREDITCARD') {
            $sPayCode = FC_IDEALODK_PAYMENTCODE_CC;
        }        
        $aData['payment_code'] = $sPayCode;
        $aData['authorization_id'] = $aOrder['payment']['transaction_id'];
        
        if ($aOrder['fulfillment']['type'] == 'POSTAL') {
            $aData['shipping_code'] = FC_IDEALODK_SHIPPINGCODE;
        } else if ($aOrder['fulfillment']['type'] == 'FORWARDING') {
            $aData['shipping_code'] = FC_IDEALODK_FORWARDINGCODE;
        }
        
        // Info field used for various values
        $aOrdersData = array();
        $aOrdersData['FCIDEALODK_ORDERNR'] = $aOrder['order_number'];
        $blFirst = true;
        $sFulOptions = "";
        foreach ($aOrder['fulfillment']['fulfillment_options'] as $aFulOption) {
            if ($blFirst == true){
                $blFirst = false;
            } else {
                $sFulOptions .= ", ";
            }
            $sFulOptions .= $aFulOption['name'] . "(" . $aFulOption['price'] . " " . $aOrder['currency'] . ")";
        }
        $aOrdersData['FCIDEALODK_FULFILMENT_OPTIONS'] = $sFulOptions;
        $aOrdersData['FCIDEALODK_TRANSACTIONID'] = $aOrder['payment']['transaction_id'];
        $aOrdersData['FCIDEALODK_DELTIME'] = utf8_decode( $aOrder['line_items'][0]['delivery_time'] );
        $aData['orders_data'] = serialize($aOrdersData);
        
        $oOrder->_saveCustomerData($aData);
        
        return $oOrder->data_orders_id;
    }
    
    protected function addOrderProducts($aOrder, $iOrderId)
    {
        $oOrder = new order();
        foreach ($aOrder['line_items'] as $aProduct) {
            $iNetPrice = ($aProduct['item_price'] / 119) * 100;
            
            $sModel = $aProduct['sku'];
            $oProduct = $this->getProduct($sModel);
            if (!$oProduct){
                return false;
            }
            $aData['orders_id'] = $iOrderId;
            $aData['products_id'] = $oProduct->data['products_id'];
            $aData['products_model'] = $oProduct->data['products_model'];
            $aData['products_name'] = utf8_decode( $aProduct['title'] );
            $aData['products_shipping_time'] = utf8_decode( $aProduct['delivery_time'] );
            $aData['products_price'] = $iNetPrice;
            $aData['products_tax'] = '19.0000';
            $aData['products_tax_class'] = '1';
            $aData['products_quantity'] = $aProduct['quantity'];
            $aData['allow_tax'] = '1';
            if ($aProduct['delivery_time']){
                $aData['products_shipping_time'] = $aProduct['delivery_time'];
            }
            
            $oOrder->_saveProductData($aData, 'insert', true);
        }
        return true;
    }
    
    protected function addOrderStatusHistory($iOrderId)
    {
        global $db;
        
        $aData['orders_id'] = $iOrderId;
        $aData['orders_status_id'] = 16;
        $aData['date_added'] = date("Y-m-d H:i:s");
        $aData['customer_notified'] = 0;
        $aData['comments'] = 'Order von Idealo Direktkauf API importiert';
        $aData['change_trigger'] = 'plugin fcIdealoDk';
        $aData['callback_id'] = 0;
        $aData['customer_show_comment'] = 0;
        $aData['callback_messge'] = '';
        
        $db->AutoExecute(TABLE_ORDERS_STATUS_HISTORY,$aData,'INSERT');
    }
    
    protected function addOrderStats($aOrder, $iOrderId)
    {
        global $db;
        
        $aData = [];
        $aData['orders_id'] = $iOrderId;
        $aData['orders_stats_price'] = $aOrder['total_price'];
        $aData['products_count'] = 1;
        
        $db->AutoExecute('xt_orders_stats',$aData,'INSERT');
    }
    
    protected function addOrderTotal($aOrder, $iOrderId)
    {
        $oOrder = new order();
        
        $iNetPrice = ($aOrder['total_shipping'] / 119) * 100;
        
        $aData = [];
        $aData['orders_id'] = $iOrderId;
        $aData['orders_total_key'] = 'shipping';
        $aData['orders_total_key_id'] = 1;
        $aData['orders_total_model'] = 'Standard';
        $aData['orders_total_name'] = 'Standard';
        $aData['orders_total_price'] = $iNetPrice;
        $aData['orders_total_tax'] = '19.0000';
        $aData['orders_total_tax_class'] = '1';
        $aData['orders_total_quantity'] = '1.00';
        $aData['allow_tax'] = '1';
        
        $oOrder->_saveTotalData($aData);
    }
    
    protected function getProduct($sSku)
    {
        global $db;
        $oProduct = false;
        $sQ = "SELECT products_id FROM xt_products WHERE products_model = '" . $sSku . "'";
        $sId = $db->GetOne($sQ);
        if ($sId && $sId != ""){
            $oProduct = new product($sId);
            idealodk_logger::log('IDEALO ORDER IMPORT: NOTICE: Product SKU ' . $sSku . '/ ID '. $sId .' loaded');
        } else {
            idealodk_logger::log('IDEALO ORDER IMPORT: ERROR: Product SKU ' . $sSku . ' not found');
        }
        return $oProduct;
    }
    
    protected function getSourceId()
    {
        global $db;
        
        $sQ = "SELECT source_id FROM xt_orders_source WHERE source_name = 'Idealo Direktkauf'";
        $sId = $db->GetOne($sQ);
        
        if (!$sId){
            $sId = '';
        }
        
        return $sId;
    }
    
    protected function resetImport($iOrderId)
    {
        global $db;
        
        $sQ1 = "DELETE FROM xt_orders WHERE orders_id = '" . $iOrderId . "'";
        $db->Execute($sQ1);
        
        $sQ2 = "DELETE FROM " . TABLE_ORDERS_TOTAL . " WHERE orders_id = '" . $iOrderId . "'";
        $db->Execute($sQ2);
        
        $sQ3 = "DELETE FROM " . TABLE_ORDERS_STATUS_HISTORY . " WHERE orders_id = '" . $iOrderId . "'";
        $db->Execute($sQ3);
    }
    
    protected function sendOrderNr($sIdealoOrderNr, $sShopOrderNr)
    {
        if(!$sIdealoOrderNr) {
            die('Idealo order-number missing!');
        } elseif(!$sShopOrderNr) {
            die('Shop order-number missing!');
        }
        
        $oIdealo = new idealo\Direktkauf\REST\Client();
        $oIdealo->setToken(FC_IDEALODK_APIKEY);
        $blLiveMode = (FC_IDEALODK_MODE == 'live') ? true : false;
        $oIdealo->setIsLiveMode($blLiveMode);
        $sResponse = $oIdealo->sendOrderNr($sIdealoOrderNr, $sShopOrderNr);
        
        if($sResponse === false) {
            die($this->getErrorOutput($oIdealo));
        }
    }
    
    protected function getErrorOutput($oClient)
    {
        $sOutput  = '';
        $sOutput .= 'HTTP-Code: '.$oClient->getHttpStatus(). PHP_EOL;
        $sOutput .= 'CURL-Error: '.$oClient->getCurlError(). PHP_EOL;
        $sOutput .= 'CURL-Error-Nr: '.$oClient->getCurlErrno(). PHP_EOL;
        return $sOutput;
    }
}

