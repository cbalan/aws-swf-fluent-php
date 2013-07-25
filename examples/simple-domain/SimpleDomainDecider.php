<?php
require_once __DIR__ . '/SimpleDomain.php';
$domain = new SimpleDomain();
if(sizeof($_SERVER['argv'])>1) {
    $domain->setDeciderIdentity($_SERVER['argv'][1]);
}
$domain->pollForDecisionTask();