<?php

require_once __DIR__ . '/Workflow/Item.php';
require_once __DIR__ . '/Workflow/Task.php';
require_once __DIR__ . '/Decision/Hint.php';

use Aws\Swf\Enum;

/**
 * Class Aws_Swf_Workflow
 */
class Aws_Swf_Workflow implements Aws_Swf_Workflow_Item {

    const EXECUTE_DECISION_WORKFLOW_TASK_DECISION = 'executeDecisionWorkflowTaskDecision';

    /**
     * @var array
     */
    protected $tasks = array();

    /**
     * @var array
     */
    protected $tasksByType = array();

    /**
     * @var array
     */
    protected $transitions = array();
    /**
     * @var null
     */
    protected $lastTask = null;
    /**
     * @var null
     */
    protected $name = null;

    /**
     * @var array
     */
    protected $options = array();

    /**
     * @var string
     */
    protected $version = '1.0';

    public function __construct($workflowName, $options) {
        $this->setName($workflowName);
        $this->setOptions($options);
    }

    /**
     * @param string $version
     */
    public function setVersion($version) {
        $this->version = $version;
    }

    /**
     * @return string
     */
    public function getVersion() {
        return $this->version;
    }

    /**
     * @param null $name
     */
    public function setName($name) {
        $this->name = $name;
    }

    /**
     * @return null
     */
    public function getName() {
        return $this->name;
    }

    /**
     * @param array $options
     */
    public function setOptions($options) {
        $this->options = $options;
    }

    /**
     * @return array
     */
    public function getOptions() {
        return $this->options;
    }

    /**
     * @return null
     */
    public function getId() {
        return $this->getName();
    }

    /**
     * @param array $options
     * @throws Exception
     */
    public function split($options = array()) {
        throw new Exception('Not supported');
    }

    /**
     * @param $uri
     * @param array $options
     * @return $this
     */
    public function to($uri, $options = array()) {
        $task = new Aws_Swf_Workflow_Task($uri, $options);

        switch ($task->getType()) {
            case Aws_Swf_Workflow_Task::ACTIVITY_TYPE:
                $this->toActivity($task);
                break;

            case Aws_Swf_Workflow_Task::CHILD_WORKFLOW_TYPE:
                $this->toChildWorkflow($task);
                break;

            case Aws_Swf_Workflow_Task::DECISION_TYPE:
                $this->toDecision($task);
        }

        $this->addTask($task);
        $this->setLastTask($task);

        return $this;
    }

    /**
     * @param $uri
     * @param array $options
     * @return $this
     */
    public function from($uri, $options = array()) {
        return $this->to($uri, $options);
    }

    /**
     * @param $uri
     * @param array $options
     */
    public function on($uri, $options = array()) {
        return $this->to($uri, $options);
    }

    /**
     *
     *
     * @param $uri
     * @param array $options
     */
    public function registerTask($uri, $options = array()) {
        $task = new Aws_Swf_Workflow_Task($uri, $options);

        // on activity complete, complete workflow execution, unless there was another activity added
        $this->addTransition(
            $task, Enum\EventType::ACTIVITY_TASK_COMPLETED,
            $this, Enum\DecisionType::COMPLETE_WORKFLOW_EXECUTION);

        // on activity fail, fail workflow
        $this->addTransition(
            $task, Enum\EventType::ACTIVITY_TASK_FAILED,
            $this, Enum\DecisionType::FAIL_WORKFLOW_EXECUTION);

        $this->addTask($task);

        return $this;
    }

    /**
     * @param $task
     */
    protected function toActivity($task) {
        if (is_null($this->lastTask)) {
            $this->addTransition(
                $this, Enum\EventType::WORKFLOW_EXECUTION_STARTED,
                $task, Enum\DecisionType::SCHEDULE_ACTIVITY_TASK);
        }
        else {
            // schedule current task after previous task complete
            $this->addTransition(
                $this->lastTask, Enum\EventType::ACTIVITY_TASK_COMPLETED,
                $task, Enum\DecisionType::SCHEDULE_ACTIVITY_TASK);
        }

        // on activity complete, complete workflow execution, unless there was another activity added
        $this->addTransition(
            $task, Enum\EventType::ACTIVITY_TASK_COMPLETED,
            $this, Enum\DecisionType::COMPLETE_WORKFLOW_EXECUTION);

        // on activity fail, fail workflow
        $this->addTransition(
            $task, Enum\EventType::ACTIVITY_TASK_FAILED,
            $this, Enum\DecisionType::FAIL_WORKFLOW_EXECUTION);
    }

    /**
     * @param $task
     */
    protected function toDecision($task) {
        $this->toActivity($task);

        $this->addTransition(
            $this->lastTask, Enum\EventType::ACTIVITY_TASK_COMPLETED,
            $task, self::EXECUTE_DECISION_WORKFLOW_TASK_DECISION);

        $this->addTransition(
            $this->lastTask, Enum\EventType::ACTIVITY_TASK_FAILED,
            $task, self::EXECUTE_DECISION_WORKFLOW_TASK_DECISION);
    }

    /**
     * @param $task
     * @throws Exception
     */
    protected function toChildWorkflow($task) {
        throw new Exception('Not supported');
    }

    /**
     * @param Aws_Swf_Workflow_Item $sourceItem
     * @param $stateHint
     * @param Aws_Swf_Workflow_Item $targetItem
     * @param $decisionHint
     * @return $this
     */
    protected function addTransition(Aws_Swf_Workflow_Item $sourceItem, $stateHint, Aws_Swf_Workflow_Item $targetItem, $decisionType) {
        $stateId = $this->getStateId($sourceItem, $stateHint);

        $decisionHint = new Aws_Swf_Decision_Hint();
        $decisionHint->setItem($targetItem);
        $decisionHint->setDecisionType($decisionType);

        $this->transitions[$stateId] = $decisionHint;
        return $this;
    }

    /**
     * @return array
     */
    public function getTransitions() {
        return $this->transitions;
    }

    /**
     * @param Aws_Swf_Workflow_Item $item
     * @param $state
     * @return string
     */
    public function getStateId(Aws_Swf_Workflow_Item $item, $state) {
        $result = null;
        switch ($state) {
            case Enum\EventType::WORKFLOW_EXECUTION_STARTED:
                $result = $state;
                break;
            default:
                $result = implode('_', array($item->getId(), $state));
        }

        return $result;
    }

    /**
     * @param Aws_Swf_Workflow_Item $item
     * @param $state
     * @return null
     */
    public function getDecisionHint(Aws_Swf_Workflow_Item $item, $state) {
        $result = null;
        $stateId = $this->getStateId($item, $state);
        if (array_key_exists($stateId, $this->transitions)) {
            $result = $this->transitions[$stateId];
        }
        return $result;
    }

    /**
     * @return array
     */
    public function getKnownStates() {
        return array(
            Enum\EventType::WORKFLOW_EXECUTION_STARTED,
            Enum\EventType::ACTIVITY_TASK_COMPLETED,
            Enum\EventType::ACTIVITY_TASK_FAILED
        );
    }

    /**
     * @param $task
     */
    protected function addTask($task) {
        if (!array_key_exists($task->getType(), $this->tasksByType)) {
            $this->tasksByType[$task->getType()] = array();
        }
        $this->tasksByType[$task->getType()][$task->getId()] = $task;
        $this->tasks[$task->getId()] = $task;
    }

    /**
     * @param $type
     * @return array
     */
    public function getTasksByType($type) {
        $result = array();
        if (array_key_exists($type, $this->tasksByType)) {
            $result = $this->tasksByType[$type];
        }
        return $result;
    }

    /**
     * @param $taskId
     * @return null
     */
    public function getTask($taskId) {
        $result = null;
        if (array_key_exists($taskId, $this->tasks)) {
            $result = $this->tasks[$taskId];
        }
        return $result;
    }

    /**
     * @param null $lastTask
     */
    protected function setLastTask($lastTask) {
        $this->lastTask = $lastTask;
    }

    /**
     * @return null
     */
    protected function getLastTask() {
        return $this->lastTask;
    }
}