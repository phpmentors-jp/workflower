<?php


namespace PHPMentors\Workflower\Workflow\Activity;


/**
 * @since Class available since Release 2.0.0
 */
class SubProcessTask extends Task
{
    // @todo on setWorkflow generate a new WorkItemsCollection that stores all sub-process instances


    protected function createWorkItem($data)
    {
        // create a new process instance for the specified sub-process definition
    }
}