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
     *
     * @return void
     */
    public function setProcessInstance(ProcessInstanceInterface $processInstance);

    /**
     * @return ProcessInstanceInterface|null
     */
    public function getProcessInstance();

    /**
     * @param ActivityInterface $activity
     */
    public function setActivity(ActivityInterface $activity);

    /**
     * @return ActivityInterface
     */
    public function getActivity();

    /**
     * @return string
     */
    public function getState();

    /**
     * @return void
     */
    public function cancel();
}
