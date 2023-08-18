<?php
/*
 * Copyright (c) Atsuhiro Kubo <kubo@iteman.jp> and contributors,
 * All rights reserved.
 *
 * This file is part of Workflower.
 *
 * This program and the accompanying materials are made available under
 * the terms of the BSD 2-Clause License which accompanies this
 * distribution, and is available at http://opensource.org/licenses/BSD-2-Clause
 */

namespace PHPMentors\Workflower\Workflow\Element;

class ConnectingObjectCollection implements \Countable, \IteratorAggregate//, \Serializable
{
    /**
     * @var array
     */
    private $connectingObjects = [];

    /**
     * {@inheritdoc}
     */
    /*public function serialize()
    {
        return serialize([
            'connectingObjects' => $this->connectingObjects,
        ]);
    }*/

    /**
     * {@inheritdoc}
     */
    /*public function unserialize($serialized)
    {
        foreach (unserialize($serialized) as $name => $value) {
            if (property_exists($this, $name)) {
                $this->$name = $value;
            }
        }
    }*/

    /**
     * {@inheritdoc}
     */
    public function add(ConnectingObjectInterface $connectingObject)
    {
        $this->connectingObjects[$connectingObject->getId()] = $connectingObject;
    }

    /**
     * {@inheritdoc}
     *
     * @return ConnectingObjectInterface|null
     */
    public function get($key)
    {
        if (!array_key_exists($key, $this->connectingObjects)) {
            return null;
        }

        return $this->connectingObjects[$key];
    }

    /**
     * {@inheritdoc}
     */
    public function count()
    {
        return count($this->connectingObjects);
    }

    /**
     * {@inheritdoc}
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
        if (is_null($flowObject)) return $collection;

        foreach ($this as $connectingObject) { /* @var $connectingObject ConnectingObjectInterface */
            if (!$connectingObject || !$connectingObject->getSource()) continue;
            if ($connectingObject->getSource()->getId() === $flowObject->getId()) {
                $collection->add($connectingObject);
            }
        }

        return $collection;
    }

    /**
     * @param TransitionalInterface $flowObject
     *
     * @return ConnectingObjectCollection
     *
     * @since Method available since Release 2.0.0
     */
    public function filterByDestination(TransitionalInterface $flowObject): ConnectingObjectCollection
    {
        $collection = new static();

        foreach ($this as $connectingObject) { /* @var $connectingObject ConnectingObjectInterface */
            if ($connectingObject->getDestination()->getId() === $flowObject->getId()) {
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
