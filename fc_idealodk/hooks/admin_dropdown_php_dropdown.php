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

defined('_VALID_CALL') or die('Direct Access is not allowed.');

if ($request['get'] === 'fc_idealodk_mode')
{
	$result = array(
		array('id' => 'test', 'name' => 'Test', 'desc' => 'Test-Mode'),
		array('id' => 'live', 'name' => 'Live', 'desc' => 'Production-Mode')
	);
}

if ($request['get'] === 'fc_idealodk_storno_reason')
{
	$result = array(
		array('id' => 'CUSTOMER_REVOKE', 'name' => 'Widerruf', 'desc' => 'Widerruf durch Kunden'),
		array('id' => 'MERCHANT_DECLINE', 'name' => 'Storno', 'desc' => 'Storno durch Händler'),
		array('id' => 'RETOUR', 'name' => 'Retoure', 'desc' => 'Retoure')
	);
}

if ($request['get'] === 'fc_idealodk_debug_output')
{
	$result = array(
		array('id' => 'true', 'name' => 'true', 'desc' => 'An'),
		array('id' => 'false', 'name' => 'false', 'desc' => 'Aus')
	);
}