<?php


namespace PHPMentors\Workflower\Workflow\Activity;


interface ItemInterface
{
    /**
     * @return int|string
     */
    public function getId();

    /**
     * @param ActivityInterface $activity
     */
    public function setParentActivity(ActivityInterface $activity);

    /**
     * @return ActivityInterface
     */
    public function getParentActivity();

    /**
     * @return void
     */
    public function cancel();
}