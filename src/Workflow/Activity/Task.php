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
     * @return \DateTime
     */
    public function getStartDate()
    {
        if (count($this->workItems) == 0) {
            return null;
        }

        return $this->workItems[count($this->workItems) - 1]->getStartDate();
    }

    /**
     * @return ParticipantInterface
     */
    public function getStartParticipant()
    {
        if (count($this->workItems) == 0) {
            return null;
        }

        return $this->workItems[count($this->workItems) - 1]->getStartParticipant();
    }

    /**
     * {@inheritDoc}
     */
    public function isActive()
    {
        if (count($this->workItems) == 0) {
            return false;
        }

        return !$this->workItems[count($this->workItems) - 1]->isEnded();
    }

    /**
     * {@inheritDoc}
     */
    public function start(ParticipantInterface $startParticipant)
    {
        if ($this->isActive()) {
            throw new ActivityAlreadyStartedException(sprintf('The activity "%s" is already started.', $this->getId()));
        }

        $this->workItems[] = new WorkItem($startParticipant);
    }

    /**
     * {@inheritDoc}
     */
    public function complete(ParticipantInterface $participant)
    {
        if (!$this->isActive()) {
            throw new ActivityNotActiveException(sprintf('The activity "%s" is not active.', $this->getId()));
        }

        $this->workItems[count($this->workItems) - 1]->end($participant, WorkItem::ENDED_WITH_COMPLETION);
    }

    /**
     * {@inheritDoc}
     */
    public function isEnded()
    {
        if (count($this->workItems) == 0) {
            return false;
        }

        return $this->workItems[count($this->workItems) - 1]->isEnded();
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
     * @return ParticipantInterface
     */
    public function getEndedBy()
    {
        if (count($this->workItems) == 0) {
            return null;
        }

        return $this->workItems[count($this->workItems) - 1]->getEndedBy();
    }

    /**
     * {@inheritDoc}
     */
    public function getEndedWith()
    {
        if (count($this->workItems) == 0) {
            return null;
        }

        return $this->workItems[count($this->workItems) - 1]->getEndedWith();
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
}
