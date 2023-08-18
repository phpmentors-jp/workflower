<?php

namespace PHPMentors\Workflower\Workflow\Activity;

/**
 * @since Class available since Release 2.0.0
 */
class UserTask extends Task
{
    /*public function serialize()
    {
        return serialize([
            //get_parent_class($this) => parent::serialize(),
            'id' => $this->id,
            'role' => $this->role,
            'name' => $this->name,
            'state' => $this->state,
            'defaultSequenceFlowId' => $this->defaultSequenceFlowId,
            'multiInstance' => $this->multiInstance,
            'sequential' => $this->sequential,
            'completionCondition' => $this->completionCondition,
            'workItems' => $this->workItems,
            'token' => $this->token ?? null,
            'started' => $this->started ?? null,
            'processInstance' => $this->processInstance,
        ]);
    }

    public function unserialize($serialized)
    {
        foreach (unserialize($serialized) as $name => $value) {
            if ($name == get_parent_class($this)) {
                parent::unserialize($value);
                continue;
            }

            if (property_exists($this, $name)) {
                $this->$name = $value;
            }
        }
    }*/
}
