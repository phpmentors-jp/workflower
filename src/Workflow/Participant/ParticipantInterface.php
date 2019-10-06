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

namespace PHPMentors\Workflower\Workflow\Participant;

use PHPMentors\Workflower\Workflow\Resource\ResourceInterface;

interface ParticipantInterface extends OperationInterface
{
    /**
     * @param string $role
     *
     * @return bool
     */
    public function hasRole($role);

    /**
     * @param ResourceInterface $resource
     */
    public function setResource(ResourceInterface $resource);

    /**
     * @return ResourceInterface
     */
    public function getResource();

    /**
     * @return string
     */
    public function getName();
}
