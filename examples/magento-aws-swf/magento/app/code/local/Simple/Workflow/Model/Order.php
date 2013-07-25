<?php

/**
 * Class Simple_Workflow_Model_Order
 */
class Simple_Workflow_Model_Order extends Mage_Sales_Model_Order {
    /**
     *
     */
    public function sendNewOrderEmail() {
        if (Mage::getStoreConfig('simple_workflow/order_send_new_order_email/enabled')) {
            $serializedOrderData = $this->getId();

            // start sendNewOrderEmailWorkflow workflow execution
            Mage::getSingleton('simple_workflow/domain')
                ->startWorkflowExecution('sendNewOrderEmailWorkflow', $serializedOrderData, true);
        }
        else {
            parent::sendNewOrderEmail();
        }
    }

    /**
     * @return mixed
     */
    public function  realSendNewOrderEmail() {
        return parent::sendNewOrderEmail();
    }

    /**
     * @param $serializedOrderData
     */
    public function sendNewOrderEmailFromData($serializedOrderData) {
        $orderId = $serializedOrderData;
        $this->load($orderId);
        $this->realSendNewOrderEmail();
    }
}