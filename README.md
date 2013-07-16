# aws-swf-fluent-php

Glue code around aws-sdk-php to allow fluent workflows definition


## Quick Example

### Three steps workflow with decision task

```php
class QuickSimpleDomain extends Aws_Swf_Domain {

    /**
     * Simple workflow domain configuration.
     *
     * Mandatory swf objects to be defined here:
     *  - swf domain name using setDomainName method
     *  - swf workflows using addWorkflow method
     *  - actions using workflow's 'to'/registerTask method
     */
    protected function configure() {
        $this->setDomainName('threeStepsZenDomain');

        /**
         * threeStepsZen workflow :
         *  - on start, execute stepOne
         *  - if stepOne failed, execute stepFour. See evaluateStepOneResult method
         *  - if stepOne succeeded, execute stepTwo
         *  - if stepTwo succeeded, execute stepThree
         *
         * On any unhandled exception, workflow execution will terminate with FAIL_WORKFLOW_EXECUTION decision.
         * Decision tasks can catch/handle previous activity fail/success.
         */
        $this->addWorkflow('threeStepsZen')
            ->to('activity://stepOne')
            ->to('decision://evaluateStepOneResult')
            ->to('activity://stepTwo')
            ->to('activity://stepThree')
            ->registerTask('activity://stepFour', array('comment' => 'Optional step 4'));
    }

    public function stepOne($context)   { /* do something on activity workers.*/ }

    public function evaluateStepOneResult($context) {
        $lastEvent = $decisionHint->getLastEvent();
        if ($lastEvent['eventType'] == Enum\EventType::ACTIVITY_TASK_FAILED) {
            $decisionHint->setItem($this->getActivity('stepFour'));
            $decisionHint->setDecisionType(Enum\DecisionType::SCHEDULE_ACTIVITY_TASK);
        }
    }

    public function stepTwo($context)   { /* do something on activity workers.*/ }

    public function stepThree($context) { /* do something on activity workers.*/ }

    public function stepFour($context)  { /* do something on activity workers.*/ }
}

// $domain = new QuickSimpleDomain();
// set aws swf client
// $domain->setSwfClient(Aws\Swf\SwfClient::factory(array(
//    'key' => 'AWS key',
//    'secret' => 'AWS secret key',
//    'region' => 'us-east-1'
//)));

// start decision worker
// $domain->pollForDecisionTask();

// start activity worker
// $domain->pollForActivityTask();

// start a workflow execution
// $domain->startWorkflowExecution('threeStepsZen', 5, true);
```

### More examples
* See examples folder for more details