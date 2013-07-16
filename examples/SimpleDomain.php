<?php

require_once __DIR__ . '/../Aws/Swf/Domain.php';
require_once __DIR__ . '/../tmp/aws-sdk/aws-autoloader.php';

use Aws\Swf\Enum;

class SimpleDomain extends Aws_Swf_Domain {

    protected function configure() {
        $this->setDomainName('threeStepsZenDomain');
        $this->setSwfClient(Aws\Swf\SwfClient::factory(array(
            'key' => 'AWS key',
            'secret' => 'AWS secret key',
            'region' => 'us-east-1'
        )));

        $this->addWorkflow('threeStepsZen')
            ->to('activity://stepOne')
            ->to('decision://evaluateStepOneResult')
            ->to('activity://stepTwo')
            ->to('activity://stepThree')
            ->registerTask('activity://stepFour', array('comment' => 'Optional step 4'));
    }

    public function evaluateStepOneResult($context, $decisionHint) {
        $lastEvent = $decisionHint->getLastEvent();
        if ($lastEvent['eventType'] == Enum\EventType::ACTIVITY_TASK_FAILED) {
            $decisionHint->setItem($this->getActivity('stepFour'));
            $decisionHint->setDecisionType(Enum\DecisionType::SCHEDULE_ACTIVITY_TASK);
        }
    }

    public function stepOne($context) {
        $input = $context->getInput();

        $context->recordActivityTaskHeartbeat();
        print('step1:');
        var_dump($input);

        return $input * 10;
    }

    public function stepTwo($context) {
        $input = $context->getInput();

        print('step2:');
        var_dump($input);

        return $input * 20;
    }

    public function stepThree($context) {
        $input = $context->getInput();

        print('step3:');
        var_dump($input);

        return $input * 30;
    }

    public function stepFour($context) {
        $input = $context->getInput();

        print('step4:');
        var_dump($input);

        return $input * 30;
    }
}
