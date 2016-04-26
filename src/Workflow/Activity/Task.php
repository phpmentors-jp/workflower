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

namespace PHPMentors\Workflower\Workflow\Activity;

use PHPMentors\DomainKata\Entity\EntityInterface;
use PHPMentors\Workflower\Workflow\Participant\ParticipantInterface;
use PHPMentors\Workflower\Workflow\Participant\Role;

class Task implements ActivityInterface, \Serializable
{
    /**
     * @var int|string
     */
    private $id;

    /**
     * @var Role
     */
    private $role;

    /**
     * @var string
     */
    private $name;

    /**
     * @var WorkItemInterface[]
     */
    private $workItems = array();

    /**
     * @var int|string
     */
    private $defaultSequenceFlowId;

    /**
     * @param int|string $id
     * @param Role       $role
     * @param string     $name
     */
    public function __construct($id, Role $role, $name = null)
    {
        $this->id = $id;
        $this->role = $role;
        $this->name = $name;
    }

    /**
     * {@inheritdoc}
     */
    public function serialize()
    {
        return serialize(array(
            'id' => $this->id,
            'role' => $this->role,
            'name' => $this->name,
            'workItems' => $this->workItems,
            'defaultSequenceFlowId' => $this->defaultSequenceFlowId,
        ));
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
     *
     * @return int|string
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * {@inheritdoc}
     */
    public function getRole()
    {
        return $this->role;
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * {@inheritdoc}
     */
    public function equals(EntityInterface $target)
    {
        if (!($target instanceof self)) {
            return false;
        }

        return $this->id === $target->getId();
    }

    /**
     * {@inheritdoc}
     */
    public function setDefaultSequenceFlowId($sequenceFlowId)
    {
        $this->defaultSequenceFlowId = $sequenceFlowId;
    }

    /**
     * {@inheritdoc}
     */
    public function getDefaultSequenceFlowId()
    {
        return $this->defaultSequenceFlowId;
    }

    /**
     * {@inheritdoc}
     */
    public function getCurrentState()
    {
        if (count($this->workItems) == 0) {
            return null;
        }

        return $this->workItems[count($this->workItems) - 1]->getCurrentState();
    }

    /**
     * {@inheritdoc}
     */
    public function getParticipant()
    {
        if (count($this->workItems) == 0) {
            return null;
        }

        return $this->workItems[count($this->workItems) - 1]->getParticipant();
    }

    /**
     * {@inheritdoc}
     */
    public function getCreationDate()
    {
        if (count($this->workItems) == 0) {
            return null;
        }

        return $this->workItems[count($this->workItems) - 1]->getCreationDate();
    }

    /**
     * {@inheritdoc}
     */
    public function getAllocationDate()
    {
        if (count($this->workItems) == 0) {
            return null;
        }

        return $this->workItems[count($this->workItems) - 1]->getAllocationDate();
    }

    /**
     * {@inheritdoc}
     */
    public function getStartDate()
    {
        if (count($this->workItems) == 0) {
            return null;
        }

        return $this->workItems[count($this->workItems) - 1]->getStartDate();
    }

    /**
     * {@inheritdoc}
     */
    public function getEndDate()
    {
        if (count($this->workItems) == 0) {
            return null;
        }

        return $this->workItems[count($this->workItems) - 1]->getEndDate();
    }

    /**
     * {@inheritdoc}
     */
    public function getEndParticipant()
    {
        if (count($this->workItems) == 0) {
            return null;
        }

        return $this->workItems[count($this->workItems) - 1]->getEndParticipant();
    }

    /**
     * {@inheritdoc}
     */
    public function getEndResult()
    {
        if (count($this->workItems) == 0) {
            return null;
        }

        return $this->workItems[count($this->workItems) - 1]->getEndResult();
    }

    /**
     * {@inheritdoc}
     */
    public function createWorkItem()
    {
        if (!(count($this->workItems) == 0 || $this->isEnded())) {
            throw new UnexpectedActivityStateException(sprintf('The current work item of the activity "%s" is not ended.', $this->getId()));
        }

        $this->workItems[] = new WorkItem();
    }

    /**
     * {@inheritdoc}
     */
    public function allocate(ParticipantInterface $participant)
    {
        if (!$this->isAllocatable()) {
            throw new UnexpectedActivityStateException(sprintf('The current work item of the activity "%s" is not allocatable.', $this->getId()));
        }

        $this->workItems[count($this->workItems) - 1]->allocate($participant);
    }

    /**
     * {@inheritdoc}
     */
    public function start()
    {
        if (!$this->isStartable()) {
            throw new UnexpectedActivityStateException(sprintf('The current work item of the activity "%s" is not startable.', $this->getId()));
        }

        $this->workItems[count($this->workItems) - 1]->start();
    }

    /**
     * {@inheritdoc}
     */
    public function complete(ParticipantInterface $participant)
    {
        if (!$this->isCompletable()) {
            throw new UnexpectedActivityStateException(sprintf('The current work item of the activity "%s" is not completable.', $this->getId()));
        }

        $this->workItems[count($this->workItems) - 1]->complete($participant);
    }

    /**
     * {@inheritdoc}
     */
    public function isAllocatable()
    {
        return count($this->workItems) > 0 && $this->workItems[count($this->workItems) - 1]->getCurrentState() == WorkItem::STATE_CREATED;
    }

    /**
     * {@inheritdoc}
     */
    public function isStartable()
    {
        return count($this->workItems) > 0 && $this->workItems[count($this->workItems) - 1]->getCurrentState() == WorkItem::STATE_ALLOCATED;
    }

    /**
     * {@inheritdoc}
     */
    public function isCompletable()
    {
        return count($this->workItems) > 0 && $this->workItems[count($this->workItems) - 1]->getCurrentState() == WorkItem::STATE_STARTED;
    }

    /**
     * {@inheritdoc}
     */
    public function isEnded()
    {
        return count($this->workItems) > 0 && $this->workItems[count($this->workItems) - 1]->getCurrentState() == WorkItem::STATE_ENDED;
    }

    /**
     * {@inheritdoc}
     */
    public function getWorkItem($index)
    {
        if (!array_key_exists($index, $this->workItems)) {
            throw new \OutOfBoundsException(sprintf('The index "%d" is not in the range [0, %d].', $index, count($this->workItems) - 1));
        }

        return $this->workItems[$index];
    }
}
