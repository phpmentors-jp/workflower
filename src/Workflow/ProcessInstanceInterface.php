<?php


namespace PHPMentors\Workflower\Workflow;


interface ProcessInstanceInterface extends ItemInterface
{
    const STATE_STARTED = 'started';
    const STATE_ENDED = 'completed';
    const STATE_CANCELLED = 'cancelled';
    const STATE_ERROR = 'error';
    const STATE_ABNORMAL = 'abnormal';

    /**
     * @return string
     */
    public function getName();

    /**
     * @param ProcessDefinitionInterface $definition
     * @return ProcessDefinitionInterface
     */
    public function setProcessDefinition(ProcessDefinitionInterface $definition);

    /**
     * @return ProcessDefinitionInterface
     */
    public function getProcessDefinition();
}