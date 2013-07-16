<?php

namespace Aws\Swf\Fluent;

/**
 * Class WorkflowTask
 * @package Aws\Swf\Fluent
 */
class WorkflowTask implements WorkflowItem {

    const ACTIVITY_TYPE = 'activity';
    const CHILD_WORKFLOW_TYPE = 'childWorkflow';
    const DECISION_TYPE = 'decision';

    /**
     * @var null
     */
    protected $type = null;
    /**
     * @var null
     */
    protected $name = null;

    /**
     * @var string
     */
    protected $version = '1.0';

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
     * @var array
     */
    protected $options = array();

    /**
     * @param $uri
     * @param array $options
     */
    public function __construct($uri, $options = array()) {
        $parsedUri = parse_url($uri);
        $this->type = $parsedUri['scheme'];
        $this->name = $parsedUri['host'];
        $this->options = $options;
    }

    /**
     *
     */
    public function getId() {
        return $this->getType() . '_' . $this->getName();
    }

    /**
     * @return mixed
     */
    public function getType() {
        return $this->type;
    }

    /**
     * @return mixed
     */
    public function getName() {
        return $this->name;
    }

    /**
     * @return mixed
     */
    public function getOptions() {
        return $this->options;
    }

    /**
     * @param $option
     * @return bool
     */
    public function getOption($option) {
        $result = null;
        if (array_key_exists($option, $this->options)) {
            $result = $this->options[$option];
        }
        return $result;
    }
}