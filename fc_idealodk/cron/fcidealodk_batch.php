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

include dirname(__FILE__) . '/../../../xtCore/main.php';
include dirname(__FILE__) . '/../classes/class.idealodk_logger.php';
include dirname(__FILE__) . '/../classes/class.idealodk_order_import.php';
include dirname(__FILE__) . '/../classes/class.idealodk_orderstatus_export.php';

$oImport = new idealodk_order_import();
$oImport->run();

$oExport = new idealodk_orderstatus_export();
$oExport->run();