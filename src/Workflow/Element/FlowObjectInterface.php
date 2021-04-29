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

namespace PHPMentors\Workflower\Workflow\Element;

use PHPMentors\Workflower\Workflow\Participant\RoleAwareInterface;
use PHPMentors\Workflower\Workflow\ProcessInstance;

interface FlowObjectInterface extends RoleAwareInterface, WorkflowElementInterface
{
    /**
     * @param Token $token
     *
     * @since Method available since Release 2.0.0
     */
    public function attachToken(Token $token): void;

    /**
     * @param Token $token
     *
     * @since Method available since Release 2.0.0
     */
    public function detachToken(Token $token): void;

    /**
     * @param ProcessInstance $processInstance
     *
     * @since Method available since Release 2.0.0
     */
    public function setProcessInstance(ProcessInstance $processInstance): void;

    /**
     * @return ProcessInstance
     *
     * @since Method available since Release 2.0.0
     */
    public function getProcessInstance(): ProcessInstance;

    /**
     * @return bool
     *
     * @since Method available since Release 2.0.0
     */
    public function isStarted(): bool;

    /**
     * @since Method available since Release 2.0.0
     */
    public function start(): void;

    /**
     * @since Method available since Release 2.0.0
     */
    public function end(): void;

    /**
     * @param Token $token
     *
     * @since Method available since Release 2.0.0
     */
    public function run(Token $token): void;
}
