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

interface WorkItemInterface extends EntityInterface
{
    const STATE_CREATED = 'created';
    const STATE_ALLOCATED = 'allocated';
    const STATE_STARTED = 'started';
    const STATE_ENDED = 'ended';
    const END_RESULT_COMPLETION = 'completion';

    /**
     * @return ParticipantInterface
     */
    public function getCurrentState();

    /**
     * @return ParticipantInterface
     */
    public function getParticipant();

    /**
     * @return \DateTime
     */
    public function getCreationDate();

    /**
     * @return \DateTime
     */
    public function getAllocationDate();

    /**
     * @return \DateTime
     */
    public function getStartDate();

    /**
     * @return \DateTime
     */
    public function getEndDate();

    /**
     * @return ParticipantInterface
     */
    public function getEndParticipant();

    /**
     * @return string
     */
    public function getEndResult();

    /**
     * @param ParticipantInterface $participant
     */
    public function allocate(ParticipantInterface $participant);

    public function start();

    /**
     * @param ParticipantInterface $participant
     */
    public function complete(ParticipantInterface $participant);
}
