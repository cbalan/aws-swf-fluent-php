<?php

require_once __DIR__ . '/abstract.php';

/**
 * Class Mage_Shell_SimpleDecision
 */
class Mage_Shell_SimpleDecision extends Mage_Shell_Abstract {
    /**
     *
     */
    public function run() {
        $domain = Mage::getModel('simple_workflow/domain');
        $deciderIdentity = $this->getArg('identity');
        if ($deciderIdentity) {
            $domain->setDeciderIdentity($deciderIdentity);
        }
        $domain->pollForDecisionTask();
    }
}

$shell = new Mage_Shell_SimpleDecision();
$shell->run();