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

class FlowObjectCollection implements EntityCollectionInterface
{
    /**
     * @var array
     */
    private $flowObjects = array();

    /**
     * {@inheritDoc}
     */
    public function add(EntityInterface $entity)
    {
        assert($entity instanceof FlowObjectInterface);

        $this->flowObjects[$entity->getId()] = $entity;
    }

    /**
     * {@inheritDoc}
     *
     * @return FlowObjectInterface|null
     */
    public function get($key)
    {
        if (array_key_exists($key, $this->flowObjects)) {
            return $this->flowObjects[$key];
        } else {
            return null;
        }
    }

    /**
     * {@inheritDoc}
     */
    public function remove(EntityInterface $entity)
    {
        assert($entity instanceof FlowObjectInterface);
    }

    /**
     * {@inheritDoc}
     */
    public function count()
    {
        return count($this->flowObjects);
    }

    /**
     * {@inheritDoc}
     */
    public function getIterator()
    {
        return new \ArrayIterator($this->flowObjects);
    }
}
