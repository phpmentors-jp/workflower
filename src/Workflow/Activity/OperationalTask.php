<?php

namespace PHPMentors\Workflower\Workflow\Activity;

use PHPMentors\Workflower\Workflow\Operation\OperationalInterface;

abstract class OperationalTask extends Task implements OperationalInterface
{
    /**
     * @var int|string
     */
    protected $operation;

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
     * {@inheritdoc}
     */
    public function serialize()
    {
        return serialize([
            get_parent_class($this) => parent::serialize(),
            'operation' => $this->operation,
        ]);
    }

    /**
     * {@inheritdoc}
     */
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
    }

    /**
     * {@inheritdoc}
     */
    public function getOperation()
    {
        return $this->operation;
    }

    /**
     * @param int|string $operation
     */
    public function setOperation($operation): void
    {
        $this->operation = $operation;
    }

    public function createWork(): void
    {
        // create work items
        parent::createWork();

        $workflow = $this->getWorkflow();
        $operationRunner = $workflow->getOperationRunner();

        $participant = $operationRunner->provideParticipant($this, $workflow);

        // execute work items
        foreach ($this->workItems as $workItem) {
            if (!$workItem->isEnded()) {
                $workItem->allocate($participant);
                $workItem->start();
                $operationRunner->runWorkItem($workItem);
                $workItem->complete($participant);
            }
        }

        $this->end();
    }

    public function completeWork(): void
    {
        // do nothing here because we completed the work inside "createWork"
    }
}
