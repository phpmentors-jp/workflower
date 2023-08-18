<?php

namespace PHPMentors\Workflower\Workflow\Activity;

use PHPMentors\Workflower\Workflow\ProcessDefinitionInterface;

class ProcessTask extends Task
{
    /**
     * @var ProcessDefinitionInterface
     */
    protected $processDefinition;

    public function __construct(array $config = [])
    {
        parent::__construct($config);

        foreach ($config as $name => $value) {
            if (property_exists(self::class, $name)) {
                $this->{$name} = $value;
            }
        }
    }

    /**
     * @return ProcessDefinitionInterface
     */
    public function getProcessDefinition()
    {
        return $this->processDefinition;
    }

    protected function createWorkItem($data)
    {
        if ($this->isClosed()) {
            throw new UnexpectedActivityStateException(sprintf('The activity "%s" is closed.', $this->getId()));
        }

        $instance = $this->getProcessDefinition()->createProcessInstance();
        $instance->setProcessInstance($this->getProcessInstance());
        $instance->setActivity($this);

        $instance->setProcessData($data);
        $this->getWorkItems()->add($instance);

        $instance->start($instance->getFirstStartEvent());

        return $instance;
    }
}
