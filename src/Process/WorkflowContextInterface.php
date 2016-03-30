<?php
/*
 * Copyright (c) 2016 KUBO Atsuhiro <kubo@iteman.jp>,
 * All rights reserved.
 *
 * This file is part of Workflower.
 *
 * This program and the accompanying materials are made available under
 * the terms of the BSD 2-Clause License which accompanies this
 * distribution, and is available at http://opensource.org/licenses/BSD-2-Clause
 */

namespace PHPMentors\Workflower\Process;

use PHPMentors\DomainKata\Entity\EntityInterface;

interface WorkflowContextInterface extends EntityInterface
{
    /**
     * @return int|string
     */
    public function getWorkflowId();
}
