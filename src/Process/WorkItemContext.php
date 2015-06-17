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

namespace PHPMentors\Workflower\Process;

use PHPMentors\Workflower\Workflow\Participant\ParticipantInterface;

class WorkItemContext implements WorkItemContextInterface
{
    /**
     * @var int|string
     */
    private $activityId;

    /**
     * @var ParticipantInterface
     */
    private $participant;

    /**
     * @var ProcessContextInterface
     */
    private $processContext;

    /**
     * @param ParticipantInterface $participant
     */
    public function __construct(ParticipantInterface $participant)
    {
        $this->participant = $participant;
    }

    /**
     * @param int|string $activityId
     */
    public function setActivityId($activityId)
    {
        $this->activityId = $activityId;
    }

    /**
     * @param ProcessContextInterface $processContext
     */
    public function setProcessContext(ProcessContextInterface $processContext)
    {
        $this->processContext = $processContext;
    }

    /**
     * {@inheritDoc}
     */
    public function getActivityId()
    {
        return $this->activityId;
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
    public function getProcessContext()
    {
        return $this->processContext;
    }
}
