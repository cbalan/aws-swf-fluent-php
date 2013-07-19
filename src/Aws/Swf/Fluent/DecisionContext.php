<?php

namespace Aws\Swf\Fluent;

use Aws\Swf\Enum;

/**
 * Class DecisionContext
 * @package Aws\Swf\Fluent
 */
class DecisionContext {
    /* @var $workflow Workflow */
    protected $workflow = null;
    /**
     * @var array
     */
    protected $events = array();

    protected $eventsByType = array();
    /**
     * @var null
     */
    protected $lastEvent = null;

    protected $workflowInput = null;

    protected $domain = null;

    /**
     * @param null $domain
     */
    public function setDomain($domain) {
        $this->domain = $domain;
    }

    /**
     * @return null
     */
    public function getDomain() {
        return $this->domain;
    }

    /**
     * @param Workflow $workflow
     */
    public function setWorkflow($workflow) {
        $this->workflow = $workflow;
    }

    /**
     * @return Workflow
     */
    public function getWorkflow() {
        if (is_null($this->workflow)) {
            throw new Exception('Current workflow not set');
        }
        return $this->workflow;
    }

    /**
     * @param $events array
     */
    public function loadReversedEventHistory($events) {
        $knownEventTypes = $this->getWorkflow()->getKnownStates();
        foreach ($events as $event) {
            $this->addEvent($event);
            if (is_null($this->getLastEvent())) {
                if (in_array($event['eventType'], $knownEventTypes)) {
                    $this->setLastEvent($event);
                }
            }
        }
    }

    /**
     * @param array $lastEvent
     */
    protected function setLastEvent($lastEvent) {
        $this->lastEvent = $lastEvent;
    }

    /**
     * @return array
     */
    public function getLastEvent() {
        return $this->lastEvent;
    }

    /**
     * @param $event
     */
    public function addEvent($event) {
        if (!array_key_exists($event['eventType'], $this->eventsByType)) {
            $this->eventsByType[$event['eventType']] = array();
        }
        $this->eventsByType[$event['eventType']][$event['eventId']] = $event;
        $this->events[$event['eventId']] = $event;
    }

    /**
     * @param int $eventId
     * @return null
     */
    public function getEvent($eventId) {
        $result = null;
        if (array_key_exists($eventId, $this->events)) {
            $result = $this->events[$eventId];
        }
        return $result;
    }

    /**
     *
     */
    public function getDecisionHint() {
        $lastEvent = $this->getLastEvent();

        $workflowDecisionHint = null;
        switch ($lastEvent['eventType']) {
            case Enum\EventType::WORKFLOW_EXECUTION_STARTED:
                $item = $this->getWorkflow();
                $workflowDecisionHint = $this->getWorkflow()->getDecisionHint($item, Enum\EventType::WORKFLOW_EXECUTION_STARTED);
                break;

            case Enum\EventType::ACTIVITY_TASK_COMPLETED:
                $scheduledEvent = $this->getEvent($lastEvent['activityTaskCompletedEventAttributes']['scheduledEventId']);
                $taskId = $scheduledEvent['activityTaskScheduledEventAttributes']['control'];
                $item = $this->getWorkflow()->getTask($taskId);
                $workflowDecisionHint = $this->getWorkflow()->getDecisionHint($item, Enum\EventType::ACTIVITY_TASK_COMPLETED);
                break;

            case Enum\EventType::ACTIVITY_TASK_FAILED:
                $scheduledEvent = $this->getEvent($lastEvent['activityTaskFailedEventAttributes']['scheduledEventId']);
                $taskId = $scheduledEvent['activityTaskScheduledEventAttributes']['control'];
                $item = $this->getWorkflow()->getTask($taskId);
                $workflowDecisionHint = $this->getWorkflow()->getDecisionHint($item, Enum\EventType::ACTIVITY_TASK_FAILED);
                break;

            case Enum\EventType::CHILD_WORKFLOW_EXECUTION_COMPLETED:
                $scheduledEvent = $this->getEvent($lastEvent['childWorkflowExecutionCompletedEventAttributes']['initiatedEventId']);
                $taskId = $scheduledEvent['startChildWorkflowExecutionInitiatedEventAttributes']['control'];
                $item = $this->getWorkflow()->getTask($taskId);
                $workflowDecisionHint = $this->getWorkflow()->getDecisionHint($item, Enum\EventType::ACTIVITY_TASK_COMPLETED);
                break;

            case Enum\EventType::CHILD_WORKFLOW_EXECUTION_FAILED:
                $scheduledEvent = $this->getEvent($lastEvent['childWorkflowExecutionFailedEventAttributes']['initiatedEventId']);
                $taskId = $scheduledEvent['startChildWorkflowExecutionInitiatedEventAttributes']['control'];
                $item = $this->getWorkflow()->getTask($taskId);
                $workflowDecisionHint = $this->getWorkflow()->getDecisionHint($item, Enum\EventType::ACTIVITY_TASK_FAILED);
                break;

            case Enum\EventType::START_CHILD_WORKFLOW_EXECUTION_INITIATED:
            case Enum\EventType::ACTIVITY_TASK_SCHEDULED:
                $workflowDecisionHint = new DecisionHint();
                $workflowDecisionHint->setDecisionType(Workflow::NOOP);
                $workflowDecisionHint->setItem($this->getWorkflow());
                break;
        }

        $decisionHint = new DecisionHint();
        $decisionHint->setLastEvent($lastEvent);
        if (!is_null($workflowDecisionHint)) {
            $decisionHint->setItem($workflowDecisionHint->getItem());
            $decisionHint->setDecisionType($workflowDecisionHint->getDecisionType());

            // if current workflow item is of type decision, the next
            if ($workflowDecisionHint->getDecisionType() == Workflow::EXECUTE_DECISION_WORKFLOW_TASK_DECISION) {
                $this->handleDecisionWorkflowTask($decisionHint);
            }
        }
        else {
            // unable to determine next decision. Fail workflow execution
            $decisionHint->setLastException(new \Exception('Unable to determine next decision'));
            $decisionHint->setItem($this->getWorkflow());
            $decisionHint->setDecisionType(Enum\DecisionType::FAIL_WORKFLOW_EXECUTION);
        }

        return $decisionHint;
    }

    /**
     * @param $decisionHint
     */
    protected function handleDecisionWorkflowTask($decisionHint) {
        $decisionItem = $decisionHint->getItem();
        $nextWorkflowDecisionHint = $this->getWorkflow()->getDecisionHint($decisionItem, Enum\EventType::ACTIVITY_TASK_COMPLETED);
        if ($nextWorkflowDecisionHint) {
            $decisionHint->setItem($nextWorkflowDecisionHint->getItem());
            $decisionHint->setDecisionType($nextWorkflowDecisionHint->getDecisionType());
        }

        $object = $decisionItem->getOption('object');
        if (is_null($object)) {
            $object = $this->getDomain();
        }

        try {
            call_user_func_array(array($object, $decisionItem->getName()), array($this, $decisionHint));
        }
        catch (Exception $e) {
            $decisionHint->setLastException($e);
            $decisionHint->setItem($this->getWorkflow());
            $decisionHint->setDecisionType(Enum\DecisionType::FAIL_WORKFLOW_EXECUTION);
        }
    }
}