<?php
/*
 * Copyright (c) KUBO Atsuhiro <kubo@iteman.jp> and contributors,
 * All rights reserved.
 *
 * This file is part of Workflower.
 *
 * This program and the accompanying materials are made available under
 * the terms of the BSD 2-Clause License which accompanies this
 * distribution, and is available at http://opensource.org/licenses/BSD-2-Clause
 */

namespace PHPMentors\Workflower\Workflow\Gateway;

use PHPMentors\DomainKata\Entity\EntityInterface;
use PHPMentors\Workflower\Workflow\Participant\Role;

class ExclusiveGateway implements GatewayInterface
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

    /**
     * @var int|string
     */
    private $defaultSequenceFlowId;

    /**
     * @param  int|string  $id
     * @param  Role  $role
     * @param  string  $name
     */
    public function __construct($id, Role $role, $name = null)
    {
        $this->id = $id;
        $this->role = $role;
        $this->name = $name;
    }

    /**
     * {@inheritdoc}
     */
    public function __serialize()
    {
        return serialize([
            'id' => $this->id,
            'name' => $this->name,
            'role' => $this->role,
            'defaultSequenceFlowId' => $this->defaultSequenceFlowId,
        ]);
    }

    /**
     * {@inheritdoc}
     */
    public function __unserialize($serialized)
    {
        foreach (unserialize($serialized) as $name => $value) {
            if (property_exists($this, $name)) {
                $this->$name = $value;
            }
        }
    }

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
    public function equals(EntityInterface $target)
    {
        if (! ($target instanceof self)) {
            return false;
        }

        return $this->id === $target->getId();
    }

    /**
     * {@inheritdoc}
     */
    public function setDefaultSequenceFlowId($sequenceFlowId)
    {
        $this->defaultSequenceFlowId = $sequenceFlowId;
    }

    /**
     * {@inheritdoc}
     */
    public function getDefaultSequenceFlowId()
    {
        return $this->defaultSequenceFlowId;
    }
}
