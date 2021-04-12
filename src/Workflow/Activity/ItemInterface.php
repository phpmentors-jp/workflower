<?php


namespace PHPMentors\Workflower\Workflow\Activity;


interface ItemInterface
{
    /**
     * @return int|string
     */
    public function getId();

    /**
     * @return void
     */
    public function cancel();
}