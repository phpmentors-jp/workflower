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

use PHPMentors\Workflower\Workflow\Participant\ParticipantInterface;
use PHPMentors\Workflower\Workflow\Type\ConditionalFlowObjectInterface;
use PHPMentors\Workflower\Workflow\Type\FlowObjectInterface;
use PHPMentors\Workflower\Workflow\Type\TransitionalInterface;

interface ActivityInterface extends FlowObjectInterface, TransitionalInterface, ConditionalFlowObjectInterface
{
    /**
     * @return \DateTime
     */
    public function getStartDate();

    /**
     * @return ParticipantInterface
     */
    public function getStartedBy();

    /**
     * @return ParticipantInterface
     */
    public function getParticipant();

    /**
     * @return bool
     */
    public function isActive();

    /**
     * @param ParticipantInterface $assignee
     *
     * @throws ActivityAlreadyStartedException
     */
    public function start(ParticipantInterface $assignee);

    /**
     * @param ParticipantInterface $participant
     */
    public function complete(ParticipantInterface $participant);

    /**
     * @return bool
     */
    public function isEnded();

    /**
     * @return \DateTime
     */
    public function getEndDate();

    /**
     * @return ParticipantInterface
     */
    public function getEndedBy();

    /**
     * @return string
     */
    public function getEndedWith();
}
