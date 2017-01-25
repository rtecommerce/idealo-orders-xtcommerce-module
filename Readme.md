# idealo Direktkauf XT-Commerce Plugin
The idealo Direktkauf XT-Commerce Plugin allows you to import your idealo-orders into your shop from the idealo-API.

See description and installation video on youtube: https://youtu.be/8KSOgp2GmWg (in german language)

## Version
1.0.5 
January 25 2017

## Requirements
* Installed and running XT-Commerce shop > 4.2 < 5.0
* An active idealo account and products listing feed.
* An active idealo Direktkauf account and an API-Auth-Token.

## Installation
1. Copy the the folder `fc_idealodk` into your shops plugin-dir.
2. Log into your shops admin an activate the new plugin.
3. Configure module options as your idealo auth token etc.
4. Set up a cronjob to `plugins\fc_idealodk\cron\fcidealodk_orders_batch.php` (i.e. every 15 minutes)
5. Set up a cronjob to `plugins\fc_idealodk\cron\fcidealodk_status_batch.php` (i.e. every 15 minutes)

## License
Apache License 2.0, see LICENSE

## Author
FATCHIP GmbH

http://www.fatchip.de
