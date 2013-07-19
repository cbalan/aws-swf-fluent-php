<?php

namespace Aws\Swf\Fluent;

use Aws\Swf\Enum;

/**
 * Class Workflow
 * @package Aws\Swf\Fluent
 */
class Workflow implements WorkflowItem {

    /**
     *
     */
    const EXECUTE_DECISION_WORKFLOW_TASK_DECISION = 'executeDecisionWorkflowTaskDecision';

    /**
     *
     */
    const WORKFLOW_ITEM_COMPLETED = 'workflowItemCompleted';
    /**
     *
     */
    const WORKFLOW_ITEM_FAILED = 'workflowItemFailed';

    /**
     * @var array
     */
    protected $eventTypeAliases = array(
        Enum\EventType::WORKFLOW_EXECUTION_STARTED => self::WORKFLOW_ITEM_COMPLETED,
        Enum\EventType::ACTIVITY_TASK_COMPLETED => self::WORKFLOW_ITEM_COMPLETED,
        Enum\EventType::ACTIVITY_TASK_FAILED => self::WORKFLOW_ITEM_FAILED,
        Enum\EventType::CHILD_WORKFLOW_EXECUTION_COMPLETED => self::WORKFLOW_ITEM_COMPLETED,
        Enum\EventType::CHILD_WORKFLOW_EXECUTION_FAILED => self::WORKFLOW_ITEM_FAILED,
    );

    protected $knownStates = array(
        Enum\EventType::WORKFLOW_EXECUTION_STARTED,
        Enum\EventType::ACTIVITY_TASK_COMPLETED,
        Enum\EventType::ACTIVITY_TASK_FAILED,
        Enum\EventType::CHILD_WORKFLOW_EXECUTION_COMPLETED,
        Enum\EventType::CHILD_WORKFLOW_EXECUTION_FAILED,
    );

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

    /**
     * @param $workflowName
     * @param $options
     */
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
        $task = new WorkflowTask($uri, $options);

        switch ($task->getType()) {
            case WorkflowTask::ACTIVITY_TYPE:
                $this->toActivity($task);
                break;

            case WorkflowTask::CHILD_WORKFLOW_TYPE:
                $this->toChildWorkflow($task);
                break;

            case WorkflowTask::DECISION_TYPE:
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
        $task = new WorkflowTask($uri, $options);

        // on activity complete, complete workflow execution, unless there was another activity added
        $this->addTransition(
            $task, self::WORKFLOW_ITEM_COMPLETED,
            $this, Enum\DecisionType::COMPLETE_WORKFLOW_EXECUTION);

        // on activity fail, fail workflow
        $this->addTransition(
            $task, self::WORKFLOW_ITEM_FAILED,
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
                $this->lastTask, self::WORKFLOW_ITEM_COMPLETED,
                $task, Enum\DecisionType::SCHEDULE_ACTIVITY_TASK);
        }

        // on activity complete, complete workflow execution, unless there was another activity added
        $this->addTransition(
            $task, self::WORKFLOW_ITEM_COMPLETED,
            $this, Enum\DecisionType::COMPLETE_WORKFLOW_EXECUTION);

        // on activity fail, fail workflow
        $this->addTransition(
            $task, self::WORKFLOW_ITEM_FAILED,
            $this, Enum\DecisionType::FAIL_WORKFLOW_EXECUTION);
    }

    /**
     * @todo: Add error handling for decision tasks
     * @param $task
     */
    protected function toDecision($task) {
        $this->toActivity($task);

        // on last task complete, execute decision workflow task
        $this->addTransition(
            $this->lastTask, self::WORKFLOW_ITEM_COMPLETED,
            $task, self::EXECUTE_DECISION_WORKFLOW_TASK_DECISION);

        // on last task fail, execute decision workflow task
        $this->addTransition(
            $this->lastTask, self::WORKFLOW_ITEM_FAILED,
            $task, self::EXECUTE_DECISION_WORKFLOW_TASK_DECISION);
    }

    /**
     * @param $task
     * @throws Exception
     */
    protected function toChildWorkflow($task) {
        if (is_null($this->lastTask)) {
            $this->addTransition(
                $this, Enum\EventType::WORKFLOW_EXECUTION_STARTED,
                $task, Enum\DecisionType::START_CHILD_WORKFLOW_EXECUTION);
        }
        else {
            // schedule current task after previous task complete
            $this->addTransition(
                $this->lastTask, self::WORKFLOW_ITEM_COMPLETED,
                $task, Enum\DecisionType::START_CHILD_WORKFLOW_EXECUTION);
        }

        // on activity complete, complete workflow execution, unless there was another activity added
        $this->addTransition(
            $task, self::WORKFLOW_ITEM_COMPLETED,
            $this, Enum\DecisionType::COMPLETE_WORKFLOW_EXECUTION);

        // on activity fail, fail workflow
        $this->addTransition(
            $task, self::WORKFLOW_ITEM_FAILED,
            $this, Enum\DecisionType::FAIL_WORKFLOW_EXECUTION);
    }

    /**
     * @param WorkflowItem $sourceItem
     * @param $stateHint
     * @param WorkflowItem $targetItem
     * @param $decisionHint
     * @return $this
     */
    protected function addTransition(WorkflowItem $sourceItem, $stateHint, WorkflowItem $targetItem, $decisionType) {
        $stateId = $this->getStateId($sourceItem, $stateHint);

        $decisionHint = new DecisionHint();
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
     * @param WorkflowItem $item
     * @param $state
     * @return string
     */
    public function getStateId(WorkflowItem $item, $state) {
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
     * @param WorkflowItem $item
     * @param $state
     * @return null
     */
    public function getDecisionHint(WorkflowItem $item, $state) {
        $result = null;
        $stateId = $this->getStateId($item, $state);
        if (array_key_exists($stateId, $this->transitions)) {
            $result = $this->transitions[$stateId];
        }
        else {
            $stateByAlias = $this->getStateByAlias($state);
            if ($stateByAlias) {
                $stateByAliasId = $this->getStateId($item, $stateByAlias);
                if (array_key_exists($stateByAliasId, $this->transitions)) {
                    $result = $this->transitions[$stateByAliasId];
                }
            }
        }

        return $result;
    }

    /**
     * @param $stateAlias
     * @return mixed
     */
    public function getStateByAlias($stateAlias) {
        $result = null;
        if (array_key_exists($stateAlias, $this->eventTypeAliases)) {
            $result = $this->eventTypeAliases[$stateAlias];
        }
        return $result;
    }

    /**
     * Event types that could trigger a workflow transition
     *
     * @return array
     */
    public function getKnownStates() {
        return $this->knownStates;
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