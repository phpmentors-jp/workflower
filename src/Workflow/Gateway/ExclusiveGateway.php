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
use PHPMentors\Workflower\Workflow\Element\ConditionalInterface;
use PHPMentors\Workflower\Workflow\SequenceFlowNotSelectedException;
use Symfony\Component\ExpressionLanguage\ExpressionLanguage;

/**
 * @since Class available since Release 2.0.0
 */
class ExclusiveGateway extends Gateway implements ConditionalInterface
{
    /**
     * @var int|string
     */
    private $defaultSequenceFlowId;

    public function __construct(array $config = [])
    {
        parent::__construct($config);

        foreach ($config as $name => $value) {
            if (property_exists(self::class, $name)) {
                $this->{$name} = $value;
            }
        }
    }

    /**
     * {@inheritdoc}
     */
    public function serialize()
    {
        return serialize([
            get_parent_class($this) => parent::serialize(),
            'defaultSequenceFlowId' => $this->defaultSequenceFlowId,
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
    public function setDefaultSequenceFlowId($sequenceFlowId)
    {
        $this->defaultSequenceFlowId = $sequenceFlowId;
    }

    /**
     * {@inheritdoc}
     */
    public function getDefaultSequenceFlowId()
    {
        return $this->defaultSequenceFlowId;
    }

    /**
     * {@inheritdoc}
     */
    public function end(): void
    {
        $workflow = $this->getWorkflow();
        $selectedSequenceFlow = null;

        // Each token arriving at any incoming Sequence Flows activates the
        // gateway and is routed to exactly one of the outgoing Sequence Flows.
        // In order to determine the outgoing Sequence Flows that receives the
        // token, the conditions are evaluated in order. The first condition that
        // evaluates to true determines the Sequence Flow the token is sent to.
        // No more conditions are henceforth evaluated.
        // If and only if none of the conditions evaluates to true, the token is passed
        // on the default Sequence Flow.
        // In case all conditions evaluate to false and a default flow has not been
        // specified, an exception is thrown.

        foreach ($workflow->getConnectingObjectCollectionBySource($this) as $outgoing) {
            if ($outgoing instanceof SequenceFlow && $outgoing->getId() !== $this->getDefaultSequenceFlowId()) {
                $condition = $outgoing->getCondition();
                if ($condition === null) {
                    // find the next one that has a condition
                    continue;
                } else {
                    $expressionLanguage = $workflow->getExpressionLanguage() ?: new ExpressionLanguage();
                    if ($expressionLanguage->evaluate($condition, $workflow->getProcessData())) {
                        $selectedSequenceFlow = $outgoing;
                        break;
                    }
                }
            }
        }

        if (!$selectedSequenceFlow) {
            $selectedSequenceFlow = $workflow->getConnectingObject($this->getDefaultSequenceFlowId());
        }

        if (!$selectedSequenceFlow) {
            throw new SequenceFlowNotSelectedException(sprintf('No sequence flow can be selected on "%s".', $this->getId()));
        }

        $token = $this->getToken();
        assert(count($token) === 1);
        $token = current($token);

        $selectedSequenceFlow->getDestination()->run($token);
        parent::end();
    }
}
