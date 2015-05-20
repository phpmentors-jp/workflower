<?php
/*
 * Copyright (c) 2015 KUBO Atsuhiro <kubo@iteman.jp>,
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
     * @param int|string $id
     * @param Role       $role
     * @param string     $name
     */
    public function __construct($id, Role $role, $name = null)
    {
        $this->id = $id;
        $this->role = $role;
        $this->name = $name;
    }

    /**
     * {@inheritDoc}
     *
     * @return int|string
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * {@inheritDoc}
     */
    public function getRole()
    {
        return $this->role;
    }

    /**
     * {@inheritDoc}
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * {@inheritDoc}
     */
    public function equals(EntityInterface $target)
    {
        assert($target instanceof self);

        return $this->id === $target->getId();
    }
}
