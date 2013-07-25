<?php

require_once __DIR__ . '/abstract.php';

/**
 * Class Mage_Shell_SimpleWorker
 */
class Mage_Shell_SimpleWorker extends Mage_Shell_Abstract {
    /**
     *
     */
    public function run() {
        $domain = Mage::getModel('simple_workflow/domain');
        $workerIdentity = $this->getArg('identity');
        if ($workerIdentity) {
            $domain->setWorkerIdentity($workerIdentity);
        }
        $domain->pollForDecisionTask();
    }
}

$shell = new Mage_Shell_SimpleWorker();
$shell->run();