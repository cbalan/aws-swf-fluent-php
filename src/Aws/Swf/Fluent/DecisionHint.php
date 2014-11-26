<?php

namespace Aws\Swf\Fluent;

/**
 * Class DecisionHint
 * @package Aws\Swf\Fluent
 */
class DecisionHint {
    /**
     * @var null
     */
    protected $item = null;
    /**
     * @var null
     */
    protected $decisionType = null;
    /**
     * @var null
     */
    protected $lastEvent = null;

    /**
     * @var null
     */
    protected $lastException = null;

    /**
     * @param null $decisionType
     */
    public function setDecisionType($decisionType) {
        $this->decisionType = $decisionType;
    }

    /**
     * @return null
     */
    public function getDecisionType() {
        return $this->decisionType;
    }

    /**
     * @param null $item
     */
    public function setItem($item) {
        $this->item = $item;
    }

    /**
     * @return null
     */
    public function getItem() {
        return $this->item;
    }

    /**
     * @param null $lastEvent
     */
    public function setLastEvent($lastEvent) {
        $this->lastEvent = $lastEvent;
    }

    /**
     * @return null
     */
    public function getLastEvent() {
        return $this->lastEvent;
    }

    /**
     * @param null $lastException
     */
    public function setLastException($lastException) {
        $this->lastException = $lastException;
    }

    /**
     * @return null
     */
    public function getLastException() {
        return $this->lastException;
    }

    /**
     * @return null
     */
    public function getLastEventResult() {
        $result = null;
        $event = $this->getLastEvent();
        if ($event) {
            switch ($event['eventType']) {
                case \Aws\Swf\Enum\EventType::CHILD_WORKFLOW_EXECUTION_COMPLETED:
                    $result = array_key_exists('result', $event['childWorkflowExecutionCompletedEventAttributes']) ? $event['childWorkflowExecutionCompletedEventAttributes']['result'] : null;
                    break;
                case \Aws\Swf\Enum\EventType::ACTIVITY_TASK_COMPLETED:
                    $result = array_key_exists('result', $event['activityTaskCompletedEventAttributes']) ? $event['activityTaskCompletedEventAttributes']['result'] : null;
                    break;
                case \Aws\Swf\Enum\EventType::WORKFLOW_EXECUTION_STARTED:
                    $result = array_key_exists('input', $event['workflowExecutionStartedEventAttributes']) ? $event['workflowExecutionStartedEventAttributes']['input'] : null;
                    break;
            }
        }
        return $result;
    }
}