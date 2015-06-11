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

namespace PHPMentors\Workflower\Workflow\Activity;

use PHPMentors\DomainKata\Entity\EntityInterface;
use PHPMentors\Workflower\Workflow\Participant\ParticipantInterface;
use PHPMentors\Workflower\Workflow\Participant\Role;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

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
     * @var WorkItem[]
     */
    private $workItems = array();

    /**
     * @var EventDispatcherInterface
     */
    private $eventDispatcher;

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
     * {@inheritDoc}
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
     *
     * @return int|string
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * {@inheritDoc}
     */
    public function getRole()
    {
        return $this->role;
    }

    /**
     * {@inheritDoc}
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * {@inheritDoc}
     */
    public function equals(EntityInterface $target)
    {
        assert($target instanceof self);

        return $this->id === $target->getId();
    }

    /**
     * {@inheritDoc}
     */
    public function setDefaultSequenceFlowId($sequenceFlowId)
    {
        $this->defaultSequenceFlowId = $sequenceFlowId;
    }

    /**
     * {@inheritDoc}
     */
    public function getDefaultSequenceFlowId()
    {
        return $this->defaultSequenceFlowId;
    }

    /**
     * {@inheritDoc}
     */
    public function getCurrentState()
    {
        if (count($this->workItems) == 0) {
            return null;
        }

        return $this->workItems[count($this->workItems) - 1]->getCurrentState();
    }

    /**
     * {@inheritDoc}
     */
    public function getParticipant()
    {
        if (count($this->workItems) == 0) {
            return null;
        }

        return $this->workItems[count($this->workItems) - 1]->getParticipant();
    }

    /**
     * {@inheritDoc}
     */
    public function getStartDate()
    {
        if (count($this->workItems) == 0) {
            return null;
        }

        return $this->workItems[count($this->workItems) - 1]->getStartDate();
    }

    /**
     * {@inheritDoc}
     */
    public function getEndDate()
    {
        if (count($this->workItems) == 0) {
            return null;
        }

        return $this->workItems[count($this->workItems) - 1]->getEndDate();
    }

    /**
     * {@inheritDoc}
     */
    public function getEndParticipant()
    {
        if (count($this->workItems) == 0) {
            return null;
        }

        return $this->workItems[count($this->workItems) - 1]->getEndParticipant();
    }

    /**
     * {@inheritDoc}
     */
    public function getEndResult()
    {
        if (count($this->workItems) == 0) {
            return null;
        }

        return $this->workItems[count($this->workItems) - 1]->getEndResult();
    }

    /**
     * {@inheritDoc}
     */
    public function createWorkItem()
    {
        if (!(count($this->workItems) == 0 || $this->workItems[count($this->workItems) - 1]->getCurrentState() == WorkItem::STATE_ENDED)) {
            throw new UnexpectedActivityStateException(sprintf('The current work item of the activity "%s" is not ended.', $this->getId()));
        }

        $this->workItems[] = new WorkItem();
    }

    /**
     * {@inheritDoc}
     */
    public function allocate(ParticipantInterface $participant)
    {
        if (!(count($this->workItems) > 0 && $this->workItems[count($this->workItems) - 1]->getCurrentState() == WorkItem::STATE_CREATED)) {
            throw new UnexpectedActivityStateException(sprintf('There is no work item to be allocated.', $this->getId()));
        }

        $this->workItems[count($this->workItems) - 1]->allocate($participant);
    }

    /**
     * {@inheritDoc}
     */
    public function start()
    {
        if (!(count($this->workItems) > 0 && $this->workItems[count($this->workItems) - 1]->getCurrentState() == WorkItem::STATE_ALLOCATED)) {
            throw new UnexpectedActivityStateException(sprintf('There is no work item to be started.', $this->getId()));
        }

        $this->workItems[count($this->workItems) - 1]->start();
    }

    /**
     * {@inheritDoc}
     */
    public function complete(ParticipantInterface $participant)
    {
        if (!(count($this->workItems) > 0 && $this->workItems[count($this->workItems) - 1]->getCurrentState() == WorkItem::STATE_STARTED)) {
            throw new UnexpectedActivityStateException(sprintf('There is no work item to be completed.', $this->getId()));
        }

        $this->workItems[count($this->workItems) - 1]->complete($participant);
    }
}
