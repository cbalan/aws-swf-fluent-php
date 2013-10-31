# aws-swf-fluent-php

Glue code around aws-sdk-php to allow fluent workflows definition.
Feedback is welcome.

## Features
 * Fluent workflow definition
 * Transparent domain / workflow type / activity type basic registration
 * Basic support for activities, decision tasks and child workflows.

## To be implemented / outstanding
 * Timer support
 * Signal support
 * Activity/workflow timeouts support

 * ContinueAsNew workflow support
 * Workflow/activity cancelation support
 * Improved domain/workflow/activity registration

## Getting Started
 1. Sign up for AWS - http://docs.aws.amazon.com/aws-sdk-php-2/guide/latest/awssignup.html
 1. Install **aws-swf/fluent** - Using [Composer][] is the recommended way to install this library.
    **aws-swf/fluent** is available via [Packagist][] under the [`aws-swf/fluent`][install-packagist] package.
 1. Add your workflows definitions in the same manner as QuickSimpleDomain listed below
 1. Create long running scripts for decision and activity workers
 1. Add startWorkflowExecution calls in your php application

## Quick Example

### Three steps workflow with decision task

```php
class QuickSimpleDomain extends Aws\Swf\Fluent\Domain {

    /**
     * Simple workflow domain configuration.
     *
     * Mandatory swf objects to be defined here:
     *  - swf domain name using setDomainName method
     *  - swf workflows using addWorkflow method
     *  - actions using workflow's 'to'/registerTask method
     */
    protected function configure() {
        // set swf client
        $domain->setSwfClient(Aws\Swf\SwfClient::factory(array(
            'key' => 'AWS key',
            'secret' => 'AWS secret key',
            'region' => 'us-east-1'
        )));

        // set domain name
        $this->setDomainName('threeStepsZenDomain');

        /**
         * threeStepsZen workflow :
         *  - on start, execute stepOne
         *  - if stepOne failed, execute stepFour. See evaluateStepOneResult method
         *  - if stepOne succeeded, execute stepTwo
         *  - if stepTwo succeeded, execute stepThree
         *
         * On any unhandled exception, workflow execution will terminate
         * with FAIL_WORKFLOW_EXECUTION decision.
         * Decision tasks can catch/handle previous activity fail/success.
         */
        $this->addWorkflow('threeStepsZen')
            ->to('activity://stepOne')
            ->to('decision://evaluateStepOneResult')
            ->to('activity://stepTwo')
            ->to('activity://stepThree')
            ->registerTask('activity://stepFour', array('comment' => 'Optional step 4'));

        $this->addWorkflow('secondWorkflow')
            ->to('activity://stepBeforeChildWorkflow')
            ->to('childWorkflow://threeStepsZen')
            ->to('activity://stepAfterChildWorkflow');

    }

    public function stepOne($context)   { /* do something on activity workers.*/ }

    public function evaluateStepOneResult($context, $decisionHint) {
        $lastEvent = $decisionHint->getLastEvent();
        if ($lastEvent['eventType'] == Aws\Swf\Enum\EventType::ACTIVITY_TASK_FAILED) {
            $decisionHint->setItem($this->getActivity('stepFour'));
            $decisionHint->setDecisionType(Aws\Swf\Enum\DecisionType::SCHEDULE_ACTIVITY_TASK);
        }
    }

    public function stepTwo($context)   { /* do something on activity workers.*/ }

    public function stepThree($context) { /* do something on activity workers.*/ }

    public function stepFour($context)  { /* do something on activity workers.*/ }

    public function stepBeforeChildWorkflow($context)  { /* do something on activity workers.*/ }

    public function stepAfterChildWorkflow($context)  { /* do something on activity workers.*/ }
}
```

decision-worker.php
```php
$domain = new QuickSimpleDomain();
$domain->pollForDecisionTask();
```

activity-worker.php
```php
$domain = new QuickSimpleDomain();
$domain->pollForActivityTask();
```

Start a workflow execution
```php
$domain = new QuickSimpleDomain();
$domain->startWorkflowExecution('threeStepsZen', 5);
```

### More examples
See examples folder for more details

[composer]: http://getcomposer.org
[packagist]: http://packagist.org

[install-packagist]: https://packagist.org/packages/aws-swf/fluent

[![githalytics.com alpha](https://cruel-carlota.pagodabox.com/ba9a4f82caacdb7b02ab4e56eea6b97f "githalytics.com")](http://githalytics.com/cbalan/aws-swf-fluent-php)
