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

namespace PHPMentors\Workflower\Workflow\Element;

use PHPMentors\DomainKata\Entity\EntityCollectionInterface;
use PHPMentors\DomainKata\Entity\EntityInterface;
use Traversable;

class FlowObjectCollection implements EntityCollectionInterface
{
    /**
     * @var array
     */
    private $flowObjects = [];

    /**
     * {@inheritdoc}
     */
    public function __serialize()
    {
        return serialize([
            'flowObjects' => $this->flowObjects,
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
     */
    public function add(EntityInterface $entity)
    {
        assert($entity instanceof FlowObjectInterface);

        $this->flowObjects[$entity->getId()] = $entity;
    }

    /**
     * {@inheritdoc}
     *
     * @return FlowObjectInterface|null
     */
    public function get($key)
    {
        if (! array_key_exists($key, $this->flowObjects)) {
            return null;
        }

        return $this->flowObjects[$key];
    }

    /**
     * {@inheritdoc}
     */
    public function remove(EntityInterface $entity)
    {
        assert($entity instanceof FlowObjectInterface);
    }

    /**
     * {@inheritdoc}
     */
    public function count(): int
    {
        return count($this->flowObjects);
    }

    /**
     * {@inheritdoc}
     */
    public function getIterator(): Traversable
    {
        return new \ArrayIterator($this->flowObjects);
    }

    /*
     * {@inheritDoc}
     */
    public function toArray()
    {
        return $this->flowObjects;
    }
}
