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

namespace PHPMentors\Workflower\Workflow\Operation;

use PHPMentors\Workflower\Workflow\Participant\ParticipantInterface;
use PHPMentors\Workflower\Workflow\Workflow;

/**
 * @since 1.2.0
 */
interface OperationRunnerInterface
{
    /**
     * @param OperationalInterface $operational
     * @param Workflow             $workflow
     *
     * @return ParticipantInterface
     */
    public function provideParticipant(OperationalInterface $operational, Workflow $workflow);

    /**
     * @param OperationalInterface $operational
     * @param Workflow             $workflow
     */
    public function run(OperationalInterface $operational, Workflow $workflow);
}
