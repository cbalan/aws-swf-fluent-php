<?php

require_once 'vendor/autoload.php';

/**
 * Class SimpleDomain
 */
class SimpleDomain extends Aws\Swf\Fluent\Domain {

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
         * SWF client configuration can be done also outside of this method, prior to
         * startWorkflowExecution, pollForDecisionTask or pollForActivityTask calls.
         */
        $aws = Aws\Common\Aws::factory(__DIR__ . '/aws-config.json');
        $this->setSwfClient($aws->get('swf'));

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

        $this->addWorkflow('secondWorkflow')
            ->to('activity://stepBeforeChildWorkflow')
            ->to('childWorkflow://threeStepsZen')
            ->to('activity://stepAfterChildWorkflow');
    }

    /**
     * activity://stepOne executed by the activity workers
     *
     * @param $context
     * @return mixed
     */
    public function stepOne($context) {
        $input = $context->getInput();

        $context->recordActivityTaskHeartbeat();
        print($this->getWorkerIdentity().'#step1:');
        var_dump($input);

        return $input * 10;
    }

    /**
     * decision://evaluateStepOneResult executed by the decision workers
     * @param $context
     * @param $decisionHint
     */
    public function evaluateStepOneResult($context, $decisionHint) {
        $lastEvent = $decisionHint->getLastEvent();
        if ($lastEvent['eventType'] == Aws\Swf\Enum\EventType::ACTIVITY_TASK_FAILED) {
            $decisionHint->setItem($this->getActivity('stepFour'));
            $decisionHint->setDecisionType(Aws\Swf\Enum\DecisionType::SCHEDULE_ACTIVITY_TASK);
        }
    }

    /**
     * activity://stepTwo executed by the activity workers
     * @param $context
     * @return mixed
     */
    public function stepTwo($context) {
        $input = $context->getInput();

        print($this->getWorkerIdentity().'#step2:');
        var_dump($input);

        return $input * 20;
    }

    /**
     * activity://stepThree executed by the activity workers
     * @param $context
     * @return mixed
     */
    public function stepThree($context) {
        $input = $context->getInput();

        print($this->getWorkerIdentity().'#step3:');
        var_dump($input);

        return $input * 30;
    }

    /**
     * activity://stepFour executed by the activity workers
     * @param $context
     * @return mixed
     */
    public function stepFour($context) {
        $input = $context->getInput();

        print($this->getWorkerIdentity().'#step4:');
        var_dump($input);

        return $input * 30;
    }

    /**
     * @param $context
     */
    public function stepBeforeChildWorkflow($context) {
        $input = $context->getInput();

        print($this->getWorkerIdentity().__METHOD__);
        var_dump($input);
        return $input;
    }

    /**
     * @param $context
     */
    public function stepAfterChildWorkflow($context) {
        $input = $context->getInput();

        print($this->getWorkerIdentity().__METHOD__);
        var_dump($input);
        return $input;
    }
}
