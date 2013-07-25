<?php

require_once Mage::getBaseDir('lib') . '/composer-vendor/autoload.php';

/**
 * Class SimpleDomain
 */
class Simple_Workflow_Model_Domain extends Aws\Swf\Fluent\Domain {

    /**
     * Simple workflow domain configuration.
     */
    protected function configure() {
        $aws = Aws\Swf\SwfClient::factory(array(
            'key' => Mage::getStoreConfig('simple_workflow/aws/key'),
            'secret' => Mage::getStoreConfig('simple_workflow/aws/secret_key'),
            'region' => Mage::getStoreConfig('simple_workflow/aws/region')
        ));
        $this->setSwfClient($aws);

        // set aws simple workflow domain
        $this->setDomainName(Mage::getStoreConfig('simple_workflow/general/domain'));

        // sendNewAccountEmailWorkflow
        $this->addWorkflow('sendNewAccountEmailWorkflow')
            ->to('activity://sendNewAccountEmail');

        // sendNewOrderEmailWorkflow
        $this->addWorkflow('sendNewOrderEmailWorkflow')
            ->to('activity://sendNewOrderEmail');
    }

    /**
     * activity://sendNewAccountEmail executed by the activity workers
     *
     * @param $context
     * @return mixed
     */
    public function sendNewAccountEmail($context) {
        $serializedEmailData = $context->getInput();
        Mage::getModel('customer/customer')->sendNewAccountEmailFromData($serializedEmailData);
    }

    /**
     * activity://sendNewOrderEmail executed by the activity workers
     *
     * @param $context
     * @return mixed
     */
    public function sendNewOrderEmail($context) {
        $serializedOrderData = $context->getInput();
        Mage::getModel('sales/order')->sendNewOrderEmailFromData($serializedOrderData);
    }
}