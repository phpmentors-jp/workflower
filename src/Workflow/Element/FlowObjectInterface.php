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

use PHPMentors\Workflower\Workflow\Participant\RoleAwareInterface;

interface FlowObjectInterface extends RoleAwareInterface, WorkflowElementInterface
{
    /**
     * @param Token $token
     *
     * @since Method available since Release 2.0.0
     */
    public function attachToken(Token $token): void;
}
