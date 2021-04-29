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

namespace PHPMentors\Workflower\Workflow\Activity;

use PHPMentors\Workflower\Workflow\ItemInterface;
use PHPMentors\Workflower\Workflow\Participant\ParticipantInterface;

interface WorkItemInterface extends ItemInterface
{
    const STATE_CREATED = 'created';
    const STATE_ALLOCATED = 'allocated';
    const STATE_STARTED = 'started';
    const STATE_ENDED = 'ended';
    const STATE_CANCELLED = 'cancelled';
    const END_RESULT_COMPLETION = 'completion';

    /**
     * @param array $data
     *
     * @return void
     */
    public function setData($data);

    /**
     * @return array
     */
    public function getData();

    /**
     * @return bool
     */
    public function isAllocatable();

    /**
     * @return bool
     */
    public function isStartable();

    /**
     * @return bool
     */
    public function isCompletable();

    /**
     * @return bool
     */
    public function isCancelled();

    /**
     * @return bool
     */
    public function isEnded();

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

    /**
     * @return void
     */
    public function start(): void;

    /**
     * @param ParticipantInterface $participant
     */
    public function complete(ParticipantInterface $participant);

    /**
     * @return void
     */
    public function cancel(): void;
}
