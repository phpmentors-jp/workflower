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

class ConnectingObjectCollection implements EntityCollectionInterface
{
    /**
     * @var ConnectingObjectInterface[]
     */
    private $connectingObjects = array();

    /**
     * {@inheritDoc}
     */
    public function add(EntityInterface $entity)
    {
        assert($entity instanceof ConnectingObjectInterface);

        $this->connectingObjects[] = $entity;
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
     * @param TransitionalFlowObjectInterface $flowObject
     *
     * @return ConnectingObjectCollection
     */
    public function filterBySource(TransitionalFlowObjectInterface $flowObject)
    {
        $collection = new static();

        foreach ($this as $connectingObject) { /* @var $connectingObject ConnectingObjectInterface */
            if ($connectingObject->getSource()->getId() === $flowObject->getId()) {
                $collection->add($connectingObject);
            }
        }

        return $collection;
    }
}
