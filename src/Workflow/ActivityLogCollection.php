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

namespace PHPMentors\Workflower\Workflow;

class ActivityLogCollection implements \Countable, \IteratorAggregate
{
    /**
     * @var ActivityLog[]
     */
    private $activityLogs = [];

    /**
     * @var array
     */
    private $lastWorkItemIndexByActivity = [];

    /**
     * {@inheritdoc}
     */
    public function add(ActivityLog $activityLog)
    {
        $this->activityLogs[] = $activityLog;

        if (array_key_exists($activityLog->getActivity()->getId(), $this->lastWorkItemIndexByActivity)) {
            ++$this->lastWorkItemIndexByActivity[$activityLog->getActivity()->getId()];
        } else {
            $this->lastWorkItemIndexByActivity[$activityLog->getActivity()->getId()] = 0;
        }

        $activityLog->setWorkItem($activityLog->getActivity()->getWorkItem($this->lastWorkItemIndexByActivity[$activityLog->getActivity()->getId()]));
    }

    /**
     * {@inheritdoc}
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
     * {@inheritdoc}
     */
    public function count()
    {
        return count($this->activityLogs);
    }

    /**
     * {@inheritdoc}
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
