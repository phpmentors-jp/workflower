<?php

namespace PHPMentors\Workflower\Workflow;

interface ProcessDefinitionInterface
{
    /**
     * @return string
     */
    public function getId();

    /**
     * @return string
     */
    public function getName();

    /**
     * @return string
     */
    public function getDescription();

    /**
     * @return int
     */
    public function getVersion();

    /**
     * @return bool
     */
    public function isSuspended();

    /**
     * Set a reference to the global process definitions collection.
     *
     * @param ProcessDefinitionRepositoryInterface $collection
     *
     * @return void
     */
    public function setProcessDefinitions(ProcessDefinitionRepositoryInterface $collection);

    /**
     * Returns the global process definitions collection.
     *
     * @return ProcessDefinitionRepositoryInterface
     */
    public function getProcessDefinitions();

    /**
     * @return Workflow
     */
    public function createProcessInstance();
}
