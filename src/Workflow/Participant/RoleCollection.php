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

namespace PHPMentors\Workflower\Workflow\Participant;

class RoleCollection implements \Countable, \IteratorAggregate, \Serializable
{
    /**
     * @var array
     */
    private $roles = [];

    /**
     * {@inheritdoc}
     */
    public function serialize()
    {
        return serialize([
            'roles' => $this->roles,
        ]);
    }

    /**
     * {@inheritdoc}
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
     * {@inheritdoc}
     */
    public function add(Role $role)
    {
        assert($role instanceof Role);

        $this->roles[$role->getId()] = $role;
    }

    /**
     * {@inheritdoc}
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
     * {@inheritdoc}
     */
    public function count()
    {
        return count($this->roles);
    }

    /**
     * {@inheritdoc}
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
