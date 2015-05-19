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

namespace PHPMentors\Workflower\Workflow;

use PHPMentors\Workflower\Workflow\Activity\ActivityAlreadyStartedException;
use PHPMentors\Workflower\Workflow\Activity\ActivityInterface;
use PHPMentors\Workflower\Workflow\Activity\UnexpectedActivityException;
use PHPMentors\Workflower\Workflow\Event\StartEvent;
use PHPMentors\Workflower\Workflow\Participant\ParticipantInterface;
use PHPMentors\Workflower\Workflow\Type\FlowObjectInterface;

interface WorkflowInterface
{
    /**
     * @return string|null
     */
    public function getName();

    /**
     * @param string $id
     *
     * @return FlowObjectInterface|null
     */
    public function getFlowObject($id);

    /**
     * @return FlowObjectInterface|null
     */
    public function getCurrentFlowObject();

    /**
     * @return FlowObjectInterface|null
     */
    public function getPreviousFlowObject();

    /**
     * @return bool
     */
    public function isActive();

    /**
     * @return bool
     */
    public function isEnded();

    /**
     * @param StartEvent $event
     */
    public function start(StartEvent $event);

    /**
     * @param ActivityInterface    $activity
     * @param ParticipantInterface $participant
     *
     * @throws AccessDeniedException
     * @throws ActivityAlreadyStartedException
     * @throws UnexpectedActivityException
     */
    public function assignActivity(ActivityInterface $activity, ParticipantInterface $participant);

    /**
     * @param ActivityInterface    $activity
     * @param ParticipantInterface $participant
     *
     * @throws AccessDeniedException
     * @throws ActivityNotActiveException
     * @throws UnexpectedActivityException
     */
    public function completeActivity(ActivityInterface $activity, ParticipantInterface $participant);

    /**
     * @param string $key
     * @param mixed  $value
     */
    public function setProcessData($key, $value);
}
