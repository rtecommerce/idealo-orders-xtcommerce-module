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

class idealodk_orderstatus_export
{
    public $oIdealo = null;
    
    public function __construct()
    {
        require_once dirname(__FILE__) . '/../lib/idealodksdk/Client.php';
        
        $oIdealo = new idealo\Direktkauf\REST\Client();
        $oIdealo->setToken(FC_IDEALODK_APIKEY);
        $blLiveMode = (FC_IDEALODK_MODE == 'live') ? true : false;
        $oIdealo->setIsLiveMode($blLiveMode);

        $oIdealo->setERPShopSystem('XTC');
        $oIdealo->setERPShopSystemVersion(_SYSTEM_VERSION);
        $oIdealo->setIntegrationPartner('FATCHIP');
        $oIdealo->setInterfaceVersion($this->getPluginVersion());
        idealodk_logger::log('IDEALO ORDER IMPORT: NOTICE: XTC ' . _SYSTEM_VERSION . ' FATCHIP v' . $this->getPluginVersion());

        $this->oIdealo = $oIdealo;
    }
    
    public function run()
    {
        $aOrders = $this->getShippedOrders();
        idealodk_logger::log('IDEALODK-EXPORT: Found ' . count($aOrders) . ' new shipped orders');
        foreach ($aOrders as $aOrder) {
            $this->markOrderAsShipped($aOrder);
        }
        
        $aTrackOrders = $this->getTrackableOrders();
        idealodk_logger::log('IDEALODK-EXPORT: Found ' . count($aTrackOrders) . ' new tracked orders');
        foreach ($aTrackOrders as $aOrder) {
            $this->markOrderAsTracked($aOrder);
        }
        
        $aCanceledOrders = $this->getCanceledOrders();
        idealodk_logger::log('IDEALODK-EXPORT: Found ' . count($aCanceledOrders) . ' new canceled orders');
        foreach ($aCanceledOrders as $aOrder) {
            $this->markOrderAsCanceled($aOrder);
        }
    }
    
    protected function getCanceledOrders()
    {
        global $db;
        $sQ = "SELECT 
                xt_orders.orders_id AS orders_id,
                xt_orders.orders_source_external_id AS idealo_id
               FROM xt_orders
               INNER JOIN xt_fcidealo_status 
                ON xt_orders.orders_id = xt_fcidealo_status.orders_id
               WHERE xt_orders.orders_status = 34
                AND xt_fcidealo_status.date_sent_cancel IS NULL";
        
        $aOrders = $db->GetArray($sQ);
        return $aOrders;
    }
    
    protected function getErrorOutput($oClient)
    {
        $sOutput  = '';
        $sOutput .= 'HTTP-Code: '.$oClient->getHttpStatus().'<br>';
        $sOutput .= 'CURL-Error: '.$oClient->getCurlError().'<br>';
        $sOutput .= 'CURL-Error-Nr: '.$oClient->getCurlErrno().'<br>';
        return $sOutput;
    }

    protected function getPluginVersion()
    {
        $sPluginXml = dirname(__FILE__) . '/../installer/fc_idealodk.xml';
        $oPluginXml = simplexml_load_file($sPluginXml);
        $sVersion = $oPluginXml->version;
        return $sVersion;
    }
    
    protected function getShippedOrders()
    {
        global $db;
        $sQ = "SELECT 
                xt_orders.orders_id AS orders_id,
                xt_orders.orders_source_external_id AS idealo_id
               FROM xt_orders
               INNER JOIN xt_fcidealo_status 
                ON xt_orders.orders_id = xt_fcidealo_status.orders_id
               WHERE xt_orders.orders_status = 33
                AND xt_fcidealo_status.date_sent_shipping IS NULL
                AND xt_fcidealo_status.date_sent_cancel IS NULL";
        
        $aOrders = $db->GetArray($sQ);
        return $aOrders;
    }
    
    protected function getTrackableOrders()
    {
        global $db;
        $sQ = "SELECT 
                xt_orders.orders_id AS orders_id,
                xt_orders.orders_source_external_id AS idealo_id,
                xt_tracking.tracking_code AS tracking_code,
                xt_shipper.shipper_name AS shipper_name
               FROM xt_orders
               INNER JOIN xt_fcidealo_status 
                ON xt_orders.orders_id = xt_fcidealo_status.orders_id
               INNER JOIN xt_tracking
                ON xt_orders.orders_id = xt_tracking.tracking_order_id
               INNER JOIN xt_shipper
                ON xt_tracking.tracking_shipper_id = xt_shipper.id
               WHERE xt_orders.orders_status = 33
                AND xt_fcidealo_status.date_sent_trackcode IS NULL
                AND xt_fcidealo_status.date_sent_cancel IS NULL";
        
        $aOrders = $db->GetArray($sQ);
        return $aOrders;
    }
    
    protected function markOrderAsCanceled($aOrder)
    {
        global $db;
        
        $sResponse = $this->oIdealo->sendRevocationStatus($aOrder['idealo_id'], FC_IDEALODK_STORNO_REASON);
        if($sResponse === false) {
            idealodk_logger::log('IDEALODK-API: Order ' . $aOrder['idealo_id'] . ' cancel-ERROR: ' . print_r($this->getErrorOutput($this->oIdealo), true));
        } else {
            idealodk_logger::log('IDEALODK-API: Order ' . $aOrder['idealo_id'] . ' canceled, HTTP-Code: '.$this->oIdealo->getHttpStatus()." ".$sResponse);
            $sQ = "UPDATE xt_fcidealo_status
                   SET date_sent_cancel = '" . date("Y-m-d H:i:s") . "'
                   WHERE orders_id = '" . $aOrder['orders_id'] . "'" ;
            $db->Execute($sQ);
        }
    }
    
    protected function markOrderAsShipped($aOrder)
    {
        global $db;
        
        $sResponse = $this->oIdealo->sendFulfillmentStatus($aOrder['idealo_id']);
        if($sResponse === false) {
            idealodk_logger::log('IDEALODK-API: Order ' . $aOrder['idealo_id'] . ' ship-ERROR: ' . print_r($this->getErrorOutput($this->oIdealo), true));
        } else {
            idealodk_logger::log('IDEALODK-API: Order ' . $aOrder['idealo_id'] . ' shipped, HTTP-Code: '.$this->oIdealo->getHttpStatus()." ".$sResponse);
            $sQ = "UPDATE xt_fcidealo_status
                   SET date_sent_shipping = '" . date("Y-m-d H:i:s") . "'
                   WHERE orders_id = '" . $aOrder['orders_id'] . "'" ;
            $db->Execute($sQ);
        }
        
    }
    
    protected function markOrderAsTracked($aOrder)
    {
        global $db;
        
        $sResponse = $this->oIdealo->sendFulfillmentStatus(
                $aOrder['idealo_id'],
                $aOrder['tracking_code'],
                $aOrder['shipper_name']
                );
        if($sResponse === false) {
            idealodk_logger::log('IDEALODK-API: Order ' . $aOrder['idealo_id'] . ' Track-ERROR: ' . print_r($this->getErrorOutput($this->oIdealo), true));
        } else {
            idealodk_logger::log('IDEALODK-API: Order ' . $aOrder['idealo_id'] . ' tracked, HTTP-Code: '.$this->oIdealo->getHttpStatus()." ".$sResponse);
            $sQ = "UPDATE xt_fcidealo_status
                   SET date_sent_trackcode = '" . date("Y-m-d H:i:s") . "'
                   WHERE orders_id = '" . $aOrder['orders_id'] . "'" ;
            $db->Execute($sQ);
        }
    }
}

