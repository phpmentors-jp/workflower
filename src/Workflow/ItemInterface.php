<?php


namespace PHPMentors\Workflower\Workflow;


use PHPMentors\Workflower\Workflow\Activity\ActivityInterface;

interface ItemInterface
{
    /**
     * @return int|string
     */
    public function getId();

    /**
     * @param ProcessInstanceInterface $processInstance
     * @return void
     */
    public function setParentProcessInstance(ProcessInstanceInterface $processInstance);

    /**
     * @return ProcessInstanceInterface|null
     */
    public function getParentProcessInstance();

    /**
     * @param ActivityInterface $activity
     */
    public function setParentActivity(ActivityInterface $activity);

    /**
     * @return ActivityInterface
     */
    public function getParentActivity();

    /**
     * @return string
     */
    public function getState();

    /**
     * @return void
     */
    public function cancel();
}