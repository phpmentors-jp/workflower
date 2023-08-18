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

namespace PHPMentors\Workflower\Workflow;

class ActivityLogCollection implements \Countable, \IteratorAggregate//, \Serializable
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

    /*public function __serialize()
    {
        $fields = [
            'activityLogs',
            'lastWorkItemIndexByActivity',
        ];

        $data = [];
        foreach($fields as $field)
            $data[$field] = $this->{$field};

        return $data;
    }

    public function __unserialize($serialized)
    {
        foreach ($serialized as $name => $value) {
            $this->$name = $value;
        }
    }*/

    /*public function serialize()
    {
        return serialize([
            'activityLogs',
            //'lastWorkItemIndexByActivity',
        ]);
    }*/

    /*public function unserialize($serialized)
    {
        foreach (unserialize($serialized) as $name => $value) {
            if (property_exists($this, $name)) {
                $this->$name = $value;
            }
        }
    }*/
}
