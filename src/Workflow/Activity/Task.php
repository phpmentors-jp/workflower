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

class Task implements ActivityInterface
{
    /**
     * @var string
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
     * @var bool
     */
    private $active = false;

    /**
     * @var \DateTime
     */
    private $startDate;

    /**
     * @var ParticipantInterface
     */
    private $participant;

    /**
     * @var \DateTime
     */
    private $endDate;

    /**
     * @var string
     */
    private $endedWith;

    /**
     * @var EventDispatcherInterface
     */
    private $eventDispatcher;

    /**
     * @param string $id
     * @param Role   $role
     * @param string $name
     */
    public function __construct($id, Role $role, $name = null)
    {
        $this->id = $id;
        $this->role = $role;
        $this->name = $name;
    }

    /**
     * {@inheritDoc}
     *
     * @return string
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
        return $this->startDate;
    }

    /**
     * {@inheritDoc}
     */
    public function getParticipant()
    {
        return $this->participant;
    }

    /**
     * {@inheritDoc}
     */
    public function isActive()
    {
        return $this->active;
    }

    /**
     * {@inheritDoc}
     */
    public function start(ParticipantInterface $assignee)
    {
        if ($this->active) {
            throw new ActivityAlreadyStartedException(sprintf('The activity "%s" is already started.', $this->getId()));
        }

        $this->startDate = new \DateTime();
        $this->endDate = null;
        $this->endedWith = null;
        $this->participant = $assignee;
        $this->active = true;
    }

    /**
     * {@inheritDoc}
     */
    public function complete(ParticipantInterface $participant)
    {
        if (!$this->active) {
            throw new ActivityNotActiveException(sprintf('The activity "%s" is not active.', $this->getId()));
        }

        $this->endDate = new \DateTime();
        $this->endedWith = self::ENDED_WITH_COMPLETION;
        $this->active = false;
    }

    /**
     * {@inheritDoc}
     */
    public function isEnded()
    {
        return $this->endDate !== null;
    }

    /**
     * {@inheritDoc}
     */
    public function getEndDate()
    {
        return $this->endDate;
    }

    /**
     * {@inheritDoc}
     */
    public function getEndedWith()
    {
        return $this->endedWith;
    }
}
