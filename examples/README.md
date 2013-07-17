# Three steps workflow with decision task

## Running three steps workflow example
 1. Install **composer.phar** unless is already installed: `curl -sS https://getcomposer.org/installer | php`
 1. Install dependencies. Under current folder execute: `php composer.phar install`
 1. Update aws-config.json with your AWS credentials
 1. Start decider worker in a new terminal: `php SimpleDomainDecider.php`
 1. Start activity worker script in a new terminal: `php SimpleDomainWorker.php worker1`
 1. Start a second activity worker script in a new terminal: `php SimpleDomainWorker.php worker2`
 1. Start a third activity worker script in a new terminal: `php SimpleDomainWorker.php worker3`
 1. Start workflow execution in a new terminal: `php SimpleDomainStart.php`.
    Feel free to repeat this step a couple of times
 1. Go to AWS console a look at threeStepsZenDomain workflow executions