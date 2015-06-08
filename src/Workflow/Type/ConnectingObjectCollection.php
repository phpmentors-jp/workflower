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

namespace PHPMentors\Workflower\Workflow\Type;

use PHPMentors\DomainKata\Entity\EntityCollectionInterface;
use PHPMentors\DomainKata\Entity\EntityInterface;

class ConnectingObjectCollection implements EntityCollectionInterface, \Serializable
{
    /**
     * @var array
     */
    private $connectingObjects = array();

    /**
     * {@inheritDoc}
     */
    public function serialize()
    {
        return serialize(array(
            'connectingObjects' => $this->connectingObjects,
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
        assert($entity instanceof ConnectingObjectInterface);

        $this->connectingObjects[$entity->getId()] = $entity;
    }

    /**
     * {@inheritDoc}
     *
     * @return ConnectingObjectInterface|null
     */
    public function get($key)
    {
        if (array_key_exists($key, $this->connectingObjects)) {
            return $this->connectingObjects[$key];
        } else {
            return null;
        }
    }

    /**
     * {@inheritDoc}
     */
    public function remove(EntityInterface $entity)
    {
        assert($entity instanceof ConnectingObjectInterface);
    }

    /**
     * {@inheritDoc}
     */
    public function count()
    {
        return count($this->connectingObjects);
    }

    /**
     * {@inheritDoc}
     */
    public function getIterator()
    {
        return new \ArrayIterator($this->connectingObjects);
    }

    /**
     * @param TransitionalInterface $flowObject
     *
     * @return ConnectingObjectCollection
     */
    public function filterBySource(TransitionalInterface $flowObject)
    {
        $collection = new static();

        foreach ($this as $connectingObject) { /* @var $connectingObject ConnectingObjectInterface */
            if ($connectingObject->getSource()->getId() === $flowObject->getId()) {
                $collection->add($connectingObject);
            }
        }

        return $collection;
    }

    /*
     * {@inheritDoc}
     */
    public function toArray()
    {
        return $this->connectingObjects;
    }
}
