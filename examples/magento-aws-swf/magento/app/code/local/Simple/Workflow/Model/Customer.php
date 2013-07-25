<?php

/**
 * Class Simple_Workflow_Model_Customer
 */
class Simple_Workflow_Model_Customer extends Mage_Customer_Model_Customer {

    /**
     * @param string $type
     * @param string $backUrl
     * @param string $storeId
     * @return $this
     */
    public function sendNewAccountEmail($type = 'registered', $backUrl = '', $storeId = '0') {
        if (Mage::getStoreConfig('simple_workflow/customer_send_new_account_email/enabled')) {
            if (!in_array($type, array('registered', 'confirmed'))) {
                $serializedEmailData = $this->serializeEmailData($type, $backUrl, $storeId);

                // start sendNewAccountEmailWorkflow workflow execution
                Mage::getSingleton('simple_workflow/domain')
                    ->startWorkflowExecution('sendNewAccountEmailWorkflow', $serializedEmailData, true);
            }
        }
        else {
            return parent::sendNewAccountEmail($type, $backUrl, $storeId);
        }
    }

    /**
     * @param string $type
     * @param string $backUrl
     * @param string $storeId
     * @return mixed
     */
    public function realSendNewAccountEmail($type = 'registered', $backUrl = '', $storeId = '0') {
        return parent::sendNewAccountEmail($type, $backUrl, $storeId);
    }


    /**
     * @param string $type
     * @param string $backUrl
     * @param string $storeId
     * @return string
     */
    public function serializeEmailData($type = 'registered', $backUrl = '', $storeId = '0') {
        $emailData = array(
            'customer_id' => $this->getId(),
            'type' => $type,
            'back_url' => $backUrl,
            'store_id' => $storeId
        );
        $serializedEmailData = serialize($emailData);
        return $serializedEmailData;
    }

    /**
     * @param $serializedEmailData
     * @return array
     * @throws
     */
    public function unserializeEmailData($serializedEmailData) {
        $emailData = null;
        $emailDataCandidate = unserialize($serializedEmailData);

        if (is_array($emailDataCandidate)) {
            $emailData = $emailDataCandidate;
        }
        else {
            throw Exception('Unable to unserialize email data');
        }

        return $emailData;
    }

    /**
     * @param $serializedEmailData
     * @return mixed
     */
    public function sendNewAccountEmailFromData($serializedEmailData) {
        $emailData = $this->unserializeEmailData($serializedEmailData);
        $this->load($emailData['customer_id']);
        return $this->realSendNewAccountEmail($emailData['type'], $emailData['back_url'], $emailData['store_id']);
    }
}