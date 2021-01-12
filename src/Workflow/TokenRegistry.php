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

namespace PHPMentors\Workflower\Workflow;

use PHPMentors\Workflower\Workflow\Element\Token;
use PHPMentors\Workflower\Workflow\Event\EndEvent;

/**
 * @since 2.0.0
 */
class TokenRegistry
{
    private $tokens = [];

    public function register(Token $token): void
    {
        $this->tokens[$token->getId()] = $token;
    }

    public function remove(Token $token): void
    {
        unset($this->tokens[$token->getId()]);
    }

    public function getTokens(): array
    {
        return array_values($this->tokens);
    }

    public function getActiveTokens(): array
    {
        return array_values(array_filter($this->tokens, function (Token $token) {
            return !($token->getCurrentFlowObject() instanceof EndEvent);
        }));
    }
}
