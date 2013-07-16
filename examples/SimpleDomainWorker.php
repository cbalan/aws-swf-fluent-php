<?php
require_once __DIR__ . '/SimpleDomain.php';
$domain = new SimpleDomain();
$domain->pollForActivityTask();