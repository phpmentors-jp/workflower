<?php


namespace PHPMentors\Workflower\Workflow;


class ProcessInstancesCollection extends WorkItemsCollection
{
    public function getActiveInstances()
    {
        return array_filter($this->items, function (ItemInterface $item) {
            $state = $item->getState();
            return $state !== ProcessInstanceInterface::STATE_ENDED && $state !== ProcessInstanceInterface::STATE_CANCELLED;
        });
    }

    public function getCompletedInstances()
    {
        return array_filter($this->items, function (ItemInterface $item) {
            $state = $item->getState();
            return $state === ProcessInstanceInterface::STATE_ENDED || $state === ProcessInstanceInterface::STATE_CANCELLED;
        });
    }

}