<?php

namespace Aws\Swf\Fluent;

/**
 * Class Domain
 * @package Aws\Swf\Fluent
 */
class Domain {
    /* @var $swfClient \Aws\Swf\SwfClient */
    protected $domainName = null;
    /**
     * @var null
     */
    protected $swfClient = null;
    /**
     * @var array
     */
    protected $workflows = array();
    /**
     * @var int
     */
    protected $workflowExecutionRetentionPeriodInDays = 10;
    /**
     * @var string
     */
    protected $taskList = 'main';

    /**
     * @var bool
     */
    protected $isConfigured = false;
    /**
     * @var bool
     */
    protected $isRegistered = false;

    /**
     * @var null
     */
    protected $cachedActivities = null;

    /**
     * @var string
     */
    protected $deciderIdentity = 'decider';
    /**
     * @var string
     */
    protected $workerIdentity = 'worker';

    /**
     *
     */
    protected function configure() {

    }

    /**
     *
     */
    public function lazyInitialization($skipRegistration = false) {
        if (!$this->isConfigured) {
            $this->configure();
            $this->isConfigured = true;
        }
        if (!$skipRegistration) {
            $this->register();
        }
    }

    /**
     *
     */
    public function register() {
        if (!$this->isRegistered) {
            try {
                $this->registerDomain();
                $this->registerWorkflowTypes();
                $this->registerActivityTypes();

            }
            catch (Aws\Swf\Exception\TypeAlreadyExistsException $e) {
                // ignore registration in progress concurrency
            }

            $this->isRegistered = true;
        }
    }

    /**
     *
     */
    protected function registerDomain() {
        $isDomainRegistered = true;
        try {
            $this->getSwfClient()->describeDomain(array('name' => $this->getDomainName()));
        }
        catch (Aws\Swf\Exception\UnknownResourceException $e) {
            $isDomainRegistered = false;
        }

        if (!$isDomainRegistered) {
            $this->getSwfClient()->registerDomain(array(
                'name' => $this->getDomainName(),
                'workflowExecutionRetentionPeriodInDays' => $this->getWorkflowExecutionRetentionPeriodInDays()
            ));
        }
    }

    /**
     *
     */
    protected function registerWorkflowTypes() {
        $registeredWorkflowTypesResponse = $this->getSwfClient()->listWorkflowTypes(array(
            'domain' => $this->getDomainName(),
            'registrationStatus' => 'REGISTERED'
        ));

        $registeredWorkflowTypes = array();
        foreach ($registeredWorkflowTypesResponse['typeInfos'] as $workflowType) {
            $workflowName = $workflowType['workflowType']['name'];
            $workflowVersion = $workflowType['workflowType']['version'];
            $registeredWorkflowTypes[$workflowName . $workflowVersion] = 1;
        }

        foreach ($this->getWorkflows() as $workflow) {
            if (!array_key_exists($workflow->getName() . $workflow->getVersion(), $registeredWorkflowTypes)) {
                $this->getSwfClient()->registerWorkflowType(array(
                    'name' => $workflow->getName(),
                    'version' => $workflow->getVersion(),
                    'domain' => $this->getDomainName()));
            }
        }
    }

    /**
     *
     */
    protected function registerActivityTypes() {
        $registeredActivityTypesResponse = $this->getSwfClient()->listActivityTypes(array(
            'domain' => $this->getDomainName(),
            'registrationStatus' => 'REGISTERED'));

        $registeredActivityTypes = array();
        foreach ($registeredActivityTypesResponse['typeInfos'] as $activityType) {
            $activityName = $activityType['activityType']['name'];
            $activityVersion = $activityType['activityType']['version'];
            $registeredActivityTypes[$activityName . $activityVersion] = 1;
        }

        foreach ($this->getCachedAllActivities() as $activity) {
            if (!array_key_exists($activity->getName() . $activity->getVersion(), $registeredActivityTypes)) {
                $this->getSwfClient()->registerActivityType(array(
                    'name' => $activity->getName(),
                    'version' => $activity->getVersion(),
                    'domain' => $this->getDomainName()));
            }
        }
    }

    /**
     * @param int $workflowExecutionRetentionPeriodInDays
     */
    public function setWorkflowExecutionRetentionPeriodInDays($workflowExecutionRetentionPeriodInDays) {
        $this->workflowExecutionRetentionPeriodInDays = $workflowExecutionRetentionPeriodInDays;
    }

    /**
     * @return int
     */
    public function getWorkflowExecutionRetentionPeriodInDays() {
        return $this->workflowExecutionRetentionPeriodInDays;
    }

    /**
     * @return \Aws\Swf\SwfClient|null
     * @throws Exception
     */
    public function getSwfClient() {
        if (is_null($this->swfClient)) {
            throw new Exception('swf client not set');
        }
        return $this->swfClient;
    }

    /**
     * @param $swfClient
     * @return $this
     */
    public function setSwfClient($swfClient) {
        $this->swfClient = $swfClient;
        return $this;
    }

    /**
     * @param $domainName
     */
    public function setDomainName($domainName) {
        $this->domainName = $domainName;
    }

    /**
     * @return null
     */
    public function getDomainName() {
        return $this->domainName;
    }

    /**
     * @return string
     */
    public function getTaskList() {
        return $this->taskList;
    }

    /**
     * @param $taskList
     */
    public function setTaskList($taskList) {
        $this->taskList = $taskList;
    }

    /**
     * @param $workflowName
     * @param array $options
     * @return Workflow
     */
    public function addWorkflow($workflowName, $options = array()) {
        $workflow = new Workflow($workflowName, $options);
        $this->workflows[$workflowName] = $workflow;
        return $workflow;
    }

    /**
     * @param $workflowName
     * @return null
     */
    public function getWorkflow($workflowName) {
        $result = null;
        if (array_key_exists($workflowName, $this->workflows)) {
            $result = $this->workflows[$workflowName];
        }
        return $result;
    }

    /**
     * @return string
     */
    public function getDeciderIdentity() {
        return $this->deciderIdentity;
    }

    /**
     * @return string
     */
    public function setDeciderIdentity($deciderIdentity) {
        $this->deciderIdentity = $deciderIdentity;
    }

    /**
     * @param $workflowName
     * @param null $input
     * @return Model
     */
    public function startWorkflowExecution($workflowName, $input = null, $skipRegistration = false) {
        $this->lazyInitialization($skipRegistration);
        $workflow = $this->getWorkflow($workflowName);
        $result = $this->getSwfClient()->startWorkflowExecution(array(
            "domain" => $this->getDomainName(),
            "workflowId" => microtime(),
            "workflowType" => array(
                "name" => $workflow->getName(),
                "version" => $workflow->getVersion()),
            "taskList" => array("name" => $this->getTaskList()),
            "input" => $input,
            "executionStartToCloseTimeout" => "1800",
            "taskStartToCloseTimeout" => "600",
            "childPolicy" => "TERMINATE"));
        return $result;
    }

    /**
     *
     */
    public function pollForDecisionTask() {
        $this->lazyInitialization();
        while (true) {
            $decisionTaskData = $this->getSwfClient()->pollForDecisionTask(array(
                'domain' => $this->getDomainName(),
                'taskList' => array('name' => $this->getTaskList()),
                'identity' => $this->getDeciderIdentity(),
                'reverseOrder' => true
            ));

            if ($decisionTaskData['taskToken']) {
                $decisions = $this->processDecisionTask($decisionTaskData);
                $this->getSwfClient()->respondDecisionTaskCompleted(array(
                    'taskToken' => $decisionTaskData['taskToken'],
                    'decisions' => $decisions
                ));
            }
        }
    }

    /**
     *
     */
    public function pollForActivityTask() {
        $this->lazyInitialization();
        while (true) {
            $activityTaskData = $this->getSwfClient()->pollForActivityTask(array(
                'domain' => $this->getDomainName(),
                'taskList' => array('name' => $this->getTaskList()),
                'identity' => $this->getWorkerIdentity()
            ));

            if ($activityTaskData['taskToken']) {
                try {
                    $result = $this->processActivityTask($activityTaskData);
                    $this->getSwfClient()->respondActivityTaskCompleted(array(
                        'taskToken' => $activityTaskData['taskToken'],
                        'result' => $result
                    ));
                }
                catch (Exception $e) {
                    return $this->getSwfClient()->respondActivityTaskFailed(array(
                        'taskToken' => $activityTaskData['taskToken'],
                        'details' => $e->getTraceAsString(),
                        'reason' => $e->getMessage()
                    ));
                }
            }
        }
    }

    /**
     * @param $decisionTaskData
     * @return array
     */
    protected function processActivityTask($activityTaskData) {
        $activityType = $activityTaskData['activityType'];
        $activity = $this->getActivity($activityType['name']);

        $activityContext = new ActivityContext();
        $activityContext->setDomain($this);
        $activityContext->setActivityTaskData($activityTaskData);
        $activityContext->setInput($activityTaskData['input']);

        $methodName = $activity->getName();
        $object = $activity->getOption('object');
        if (is_null($object)) {
            $object = $this;
        }

        // execute activity
        $result = call_user_func_array(array($object, $methodName), array($activityContext));

        return $result;
    }

    /**
     * @param $decisionTaskData
     * @return array
     */
    protected function processDecisionTask($decisionTaskData) {
        $workflowType = $decisionTaskData['workflowType'];
        $workflow = $this->getWorkflow($workflowType['name']);
        $decisionHint = new DecisionHint();

        try {
            $decisionContext = new DecisionContext();
            $decisionContext->setDomain($this);
            $decisionContext->setWorkflow($workflow);
            $decisionContext->loadReversedEventHistory($decisionTaskData['events']);
            $decisionHint = $decisionContext->getDecisionHint();
        }
        catch (Exception $e) {
            $decisionHint->setDecisionType(\Aws\Swf\Enum\DecisionType::FAIL_WORKFLOW_EXECUTION);
            $decisionHint->setLastException($e);
        }

        return $this->getDecisions($decisionHint);
    }

    /**
     * @param $decisionHint DecisionHint
     * @return array
     */
    protected function getDecisions($decisionHint) {
        $decisionType = $decisionHint->getDecisionType();
        $item = $decisionHint->getItem();
        $lastEvent = $decisionHint->getLastEvent();
        $lastEventResult = $decisionHint->getLastEventResult();
        $decisions = array();

        switch ($decisionType) {
            case \Aws\Swf\Enum\DecisionType::SCHEDULE_ACTIVITY_TASK:
                $decisions[] = array(
                    "decisionType" => "ScheduleActivityTask",
                    "scheduleActivityTaskDecisionAttributes" => array(
                        'control' => $item->getId(),
                        "activityType" => array(
                            "name" => $item->getName(),
                            "version" => $item->getVersion()
                        ),
                        "activityId" => $item->getName() . time(),
                        "input" => $lastEventResult,
                        "scheduleToCloseTimeout" => "900",
                        "taskList" => array("name" => $this->getTaskList()),
                        "scheduleToStartTimeout" => "300",
                        "startToCloseTimeout" => "600",
                        "heartbeatTimeout" => "120")
                );
                break;

            case \Aws\Swf\Enum\DecisionType::COMPLETE_WORKFLOW_EXECUTION:
                $decisions[] = array(
                    "decisionType" => "CompleteWorkflowExecution",
                    "completeWorkflowExecutionDecisionAttributes" => array(
                        'result' => $lastEventResult));
                break;

            case \Aws\Swf\Enum\DecisionType::FAIL_WORKFLOW_EXECUTION:
            default:
                $details = 'error';
                $reason = 'error';
                $lastException = $decisionHint->getLastException();
                if ($lastException) {
                    $details = $lastException->getTraceAsString();
                    $reason = $lastException->getMessage();
                }

                $decisions = array(array(
                    "decisionType" => "FailWorkflowExecution",
                    "failWorkflowExecutionDecisionAttributes" => array(
                        'details' => $details,
                        'reason' => $reason)));
                break;
        }

        return $decisions;
    }

    /**
     *
     */
    public function getWorkerIdentity() {
        return $this->workerIdentity;
    }

    /**
     *
     */
    public function setWorkerIdentity($workerIdentity) {
        $this->workerIdentity = $workerIdentity;
    }

    /**
     * @return array
     */
    public function getAllActivities() {
        $activities = array();
        foreach ($this->getWorkflows() as $workflow) {
            foreach ($workflow->getTasksByType(WorkflowTask::ACTIVITY_TYPE) as $activity) {
                $activities[$activity->getName()] = $activity;
            }
        }

        return $activities;
    }

    /**
     * @return array|null
     */
    public function getCachedAllActivities() {
        if (is_null($this->cachedActivities)) {
            $this->cachedActivities = $this->getAllActivities();
        }
        return $this->cachedActivities;
    }

    /**
     * @param $activityName
     * @return null
     */
    public function getActivity($activityName) {
        $result = null;
        $activities = $this->getCachedAllActivities();
        if (array_key_exists($activityName, $activities)) {
            $result = $activities[$activityName];
        }
        return $result;
    }

    /**
     * @return array
     */
    public function getWorkflows() {
        return $this->workflows;
    }
}