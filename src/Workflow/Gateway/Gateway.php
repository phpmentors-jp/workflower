<?php

namespace PHPMentors\Workflower\Workflow\Gateway;

use PHPMentors\Workflower\Workflow\Element\FlowObject;
use PHPMentors\Workflower\Workflow\Participant\Role;

abstract class Gateway extends FlowObject implements GatewayInterface
{
    /**
     * @var int|string
     */
    private $id;

    /**
     * @var string
     */
    private $name;

    /**
     * @var Role
     */
    private $role;

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
    /*public function serialize()
    {
        return serialize([
            get_parent_class($this) => parent::serialize(),
            'id' => $this->id,
            'name' => $this->name,
            'role' => $this->role,
        ]);
    }*/

    /**
     * {@inheritdoc}
     */
    /*public function unserialize($serialized)
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

    /**
     * {@inheritdoc}
     *
     * @return int|string
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * {@inheritdoc}
     */
    public function getRole()
    {
        return $this->role;
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * {@inheritdoc}
     */
    public function equals($target)
    {
        if (!($target instanceof self)) {
            return false;
        }

        return $this->id === $target->getId();
    }
}
