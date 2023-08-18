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

namespace PHPMentors\Workflower\Workflow\Connection;

use PHPMentors\Workflower\Workflow\Element\ConnectingObjectInterface;
use PHPMentors\Workflower\Workflow\Element\FlowObjectInterface;
use PHPMentors\Workflower\Workflow\Element\TransitionalInterface;
use Symfony\Component\ExpressionLanguage\Expression;

class SequenceFlow implements ConnectingObjectInterface
{
    /**
     * @var int|string
     */
    private $id;

    /**
     * @var string
     */
    private $name;

    /**
     * @var TransitionalInterface
     */
    private $source;

    /**
     * @var FlowObjectInterface
     */
    private $destination;

    /**
     * @var Expression
     */
    private $condition;

    public function __construct(array $config = [])
    {
        foreach ($config as $name => $value) {
            if (property_exists(self::class, $name)) {
                $this->{$name} = $value;
            }
        }
    }

    /**
     * {@inheritdoc}
     *
     * @return int|string
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * {@inheritdoc}
     */
    public function getSource()
    {
        return $this->source;
    }

    /**
     * {@inheritdoc}
     */
    public function getDestination()
    {
        return $this->destination;
    }

    /**
     * @return Expression
     */
    public function getCondition()
    {
        return $this->condition;
    }

    /**
     * {@inheritdoc}
     */
    public function equals($target)
    {
        if (!($target instanceof self)) {
            return false;
        }

        return $this->id === $target->getId();
    }
}
