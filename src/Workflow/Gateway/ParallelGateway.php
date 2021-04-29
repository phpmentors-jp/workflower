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

namespace PHPMentors\Workflower\Workflow\Gateway;

use PHPMentors\Workflower\Workflow\Connection\SequenceFlow;

/**
 * @since Class available since Release 2.0.0
 */
class ParallelGateway extends Gateway
{
    /**
     * {@inheritdoc}
     */
    public function serialize()
    {
        return serialize([
            get_parent_class($this) => parent::serialize(),
        ]);
    }

    /**
     * {@inheritdoc}
     */
    public function unserialize($serialized)
    {
        foreach (unserialize($serialized) as $name => $value) {
            if ($name == get_parent_class($this)) {
                parent::unserialize($value);
                continue;
            }

            if (property_exists($this, $name)) {
                $this->$name = $value;
            }
        }
    }

    /**
     * {@inheritdoc}
     */
    public function end(): void
    {
        $processInstance = $this->getProcessInstance();
        $incoming = $processInstance->getConnectingObjectCollectionByDestination($this);
        $incomingTokens = $this->getToken();

        // The Parallel Gateway is activated if there is at least one token on each
        // incoming Sequence Flow.
        // The Parallel Gateway consumes exactly one token from each incoming
        // Sequence Flow and produces exactly one token at each outgoing Sequence
        // Flow.
        // If there are excess tokens at an incoming Sequence Flow, these tokens remain at
        // this Sequence Flow after execution of the Gateway.

        if (count($incomingTokens) == count($incoming)) {
            foreach ($incomingTokens as $incomingToken) {
                $processInstance->removeToken($this, $incomingToken);
            }

            foreach ($processInstance->getConnectingObjectCollectionBySource($this) as $outgoing) {
                if ($outgoing instanceof SequenceFlow) {
                    $token = $processInstance->generateToken($this);
                    $destination = $outgoing->getDestination();
                    $destination->run($token);
                }
            }
        }

        parent::end();
    }
}
