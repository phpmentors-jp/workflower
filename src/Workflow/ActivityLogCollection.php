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

namespace PHPMentors\Workflower\Workflow;

use PHPMentors\DomainKata\Entity\EntityCollectionInterface;
use PHPMentors\DomainKata\Entity\EntityInterface;
use PHPMentors\Workflower\Workflow\Activity\ActivityInterface;

class ActivityLogCollection implements EntityCollectionInterface
{
    /**
     * @var ActivityLog[]
     */
    private $activityLogs = array();

    /**
     * @var array
     */
    private $lastWorkItemIndexByActivity = array();

    /**
     * {@inheritDoc}
     */
    public function add(EntityInterface $entity)
    {
        assert($entity instanceof ActivityLog);

        $this->activityLogs[] = $entity;

        if (array_key_exists($entity->getActivity()->getId(), $this->lastWorkItemIndexByActivity)) {
            ++$this->lastWorkItemIndexByActivity[$entity->getActivity()->getId()];
        } else {
            $this->lastWorkItemIndexByActivity[$entity->getActivity()->getId()] = 0;
        }

        $entity->setWorkItem($entity->getActivity()->getWorkItem($this->lastWorkItemIndexByActivity[$entity->getActivity()->getId()]));
    }

    /**
     * {@inheritDoc}
     *
     * @return ActivityLog|null
     */
    public function get($key)
    {
        if (!array_key_exists($key, $this->activityLogs)) {
            return null;
        }

        return $this->activityLogs[$key];
    }

    /**
     * {@inheritDoc}
     */
    public function remove(EntityInterface $entity)
    {
        assert($entity instanceof ActivityInterface);
    }

    /**
     * {@inheritDoc}
     */
    public function count()
    {
        return count($this->activityLogs);
    }

    /**
     * {@inheritDoc}
     */
    public function getIterator()
    {
        return new \ArrayIterator($this->activityLogs);
    }

    /*
     * {@inheritDoc}
     */
    public function toArray()
    {
        return $this->activityLogs;
    }
}
