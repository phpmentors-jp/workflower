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

namespace PHPMentors\Workflower\Workflow\Participant;

use PHPMentors\DomainKata\Entity\EntityCollectionInterface;
use PHPMentors\DomainKata\Entity\EntityInterface;

class RoleCollection implements EntityCollectionInterface, \Serializable
{
    /**
     * @var array
     */
    private $roles = array();

    /**
     * {@inheritDoc}
     */
    public function serialize()
    {
        return serialize(array(
            'roles' => $this->roles,
        ));
    }

    /**
     * {@inheritDoc}
     */
    public function unserialize($serialized)
    {
        foreach (unserialize($serialized) as $name => $value) {
            if (property_exists($this, $name)) {
                $this->$name = $value;
            }
        }
    }

    /**
     * {@inheritDoc}
     */
    public function add(EntityInterface $entity)
    {
        assert($entity instanceof Role);

        $this->roles[$entity->getId()] = $entity;
    }

    /**
     * {@inheritDoc}
     *
     * @return Role|null
     */
    public function get($key)
    {
        if (array_key_exists($key, $this->roles)) {
            return $this->roles[$key];
        } else {
            return null;
        }
    }

    /**
     * {@inheritDoc}
     */
    public function remove(EntityInterface $entity)
    {
        assert($entity instanceof Role);
    }

    /**
     * {@inheritDoc}
     */
    public function count()
    {
        return count($this->roles);
    }

    /**
     * {@inheritDoc}
     */
    public function getIterator()
    {
        return new \ArrayIterator($this->roles);
    }

    /*
     * {@inheritDoc}
     */
    public function toArray()
    {
        return $this->roles;
    }
}
