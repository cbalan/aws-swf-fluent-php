# Three steps workflow with decision task

## Running three steps workflow example
 1. Update aws-config.json with your AWS credentials
 2. Start decider worker in a new terminal: `php SimpleDomainDecider.php`
 3. Start activity worker script in a new terminal: `php SimpleDomainWorker.php`
 3. Start a second activity worker script in a new terminal: `php SimpleDomainWorker.php`
 4. Start workflow execution in a new terminal: `php SimpleDomainStart.php`
 5. Go to AWS console a look at threeStepsZenDomain workflow executions