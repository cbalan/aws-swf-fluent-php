# Magento aws-swf simple practical example

## Running three steps workflow example
 1. Install [Composer][] unless is already installed: `curl -sS https://getcomposer.org/installer | php`
 1. Install dependencies. Under current folder execute: `php composer.phar install`
 1. Copy magento folder on top of your magento installation
 1. Clear magento cache
 1. Start decider worker in a new terminal: `php magento/shell/simpleDecider.php`
 1. Start activity worker script in a new terminal: `php magento/shell/simpleWorker.php`
 1. Register in magento as a new customer and place a new order.
    Feel free to repeat this step a couple of times
 1. Go to AWS console a look at simple-domain workflow executions

[composer]: http://getcomposer.org