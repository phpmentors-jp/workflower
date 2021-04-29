<?php

namespace PHPMentors\Workflower\Workflow\Activity;

/**
 * @since Class available since Release 2.0.0
 */
class CallTask extends ProcessTask
{
    /**
     * @var string
     */
    private $calledElement;

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
            'calledElement' => $this->calledElement,
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
     * @return string
     */
    public function getCalledElement()
    {
        return $this->calledElement;
    }

    /**
     * {@inheritdoc}
     */
    public function getProcessDefinition()
    {
        if ($this->processDefinition === null) {
            // by the time this is called we assume that our process definition
            // is already in our repository. Maybe we should throw an error
            // if we don't find it there
            $this->processDefinition = $this->getWorkflow()->getProcessDefinition()->getProcessDefinitions()->getLatestById($this->calledElement);
        }

        return $this->processDefinition;
    }
}
