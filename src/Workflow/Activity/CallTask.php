<?php


namespace PHPMentors\Workflower\Workflow\Activity;


use PHPMentors\Workflower\Workflow\Workflow;

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

    public function setWorkflow(Workflow $workflow): void
    {
        parent::setWorkflow($workflow);
        $this->processDefinition = $workflow->getProcessDefinition()->getProcessDefinitions()->getLatestById($this->calledElement);
    }

}