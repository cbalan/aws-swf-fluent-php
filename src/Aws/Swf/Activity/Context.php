<?php

/**
 * Class Aws_Swf_Activity_Context
 */
class Aws_Swf_Activity_Context {
    /**
     * @var null
     */
    protected $input = null;
    /**
     * @var array
     */
    protected $activityTaskData = array();
    /**
     * @var null
     */
    protected $domain = null;

    /**
     * @return null
     */
    public function getDomain() {
        return $this->domain;
    }

    /**
     * @param null $domain
     */
    public function setDomain($domain) {
        $this->domain = $domain;
    }

    /**
     * @return array
     */
    public function getActivityTaskData() {
        return $this->activityTaskData;
    }

    /**
     * @param array $activityTaskData
     */
    public function setActivityTaskData($activityTaskData) {
        $this->activityTaskData = $activityTaskData;
    }

    /**
     * @return null
     */
    public function getInput() {
        return $this->input;
    }

    /**
     * @param null $input
     */
    public function setInput($input) {
        $this->input = $input;
    }

    public function recordActivityTaskHeartbeat($details = null) {
        $taskToken = $this->activityTaskData['taskToken'];
        $this->getDomain()->getSwfClient()->recordActivityTaskHeartbeat(array(
            'taskToken' => $taskToken,
            'details' => $details
        ));
    }
}