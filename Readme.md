# idealo Direktkauf XT-Commerce Plugin
The idealo Direktkauf XT-Commerce Plugin allows you to import your idealo-orders into your shop from the idealo-API.

## Version
1.0.0_5985  
September 20 2016

## Requirements
* Installed and running XT-Commerce shop > 4.2
* An active idealo account and products listing feed.
* An active idealo Direktkauf account and an API-Auth-Token.

## Installation
1. Copy the the folder "fc_idealodk" into your shops plugin-dir.
2. Log into your shops admin an activate the new plugin.
3. Configure module options as your idealo auth token etc.
4. Set up a cronjob to plugins\fc_idealodk\cron\fcidealodk_batch.php (i.e. every 15 minutes)

## License
Copyright 2016 idealo internet GmbH

Licensed under the Apache License, Version 2.0 (the "License");
you may not use this file except in compliance with the License.
You may obtain a copy of the License at

    http://www.apache.org/licenses/LICENSE-2.0

Unless required by applicable law or agreed to in writing, software
distributed under the License is distributed on an "AS IS" BASIS,
WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
See the License for the specific language governing permissions and
limitations under the License.

## Author
FATCHIP GmbH
www.fatchip.de
<a href="mailto:support@fatchip.de">support@fatchip.de</a>
