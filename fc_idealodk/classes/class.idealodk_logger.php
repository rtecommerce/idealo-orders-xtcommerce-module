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

/**
 * Loggin class
 */
class idealodk_logger
{
    /**
     * Writes message to a logfile.
     * If config-option 'debug-output' is on message is additinally echoed.
     * 
     * @param type $sMessage
     * 
     * @return void
     */
    static public function log($sMessage)
    {
        if (FC_IDEALODK_DEBUG_OUTPUT == "true"){
            echo $sMessage . "\n";
        }
        
        $sTime = "[" . date("d-M-Y H:i:s ") . date_default_timezone_get() . "] ";
        $handle = fopen(_SRV_WEB_LOG . '/fc_idealodk.log', "a+");
        fwrite($handle, $sTime . $sMessage . "\n");
        fclose($handle);
    }
}

