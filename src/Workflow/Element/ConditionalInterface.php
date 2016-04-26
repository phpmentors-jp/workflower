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

namespace PHPMentors\Workflower\Workflow\Element;

interface ConditionalInterface extends OperationInterface
{
    /**
     * @param int|string $sequenceFlowId
     */
    public function setDefaultSequenceFlowId($sequenceFlowId);

    /**
     * @return int|string
     */
    public function getDefaultSequenceFlowId();
}
