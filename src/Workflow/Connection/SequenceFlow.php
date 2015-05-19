<?php
/*
 * Copyright (c) 2015 KUBO Atsuhiro <kubo@iteman.jp>,
 * All rights reserved.
 *
 * This file is part of Workflower.
 *
 * This program and the accompanying materials are made available under
 * the terms of the BSD 2-Clause License which accompanies this
 * distribution, and is available at http://opensource.org/licenses/BSD-2-Clause
 */

namespace PHPMentors\Workflower\Workflow\Connection;

use PHPMentors\DomainKata\Entity\EntityInterface;
use PHPMentors\Workflower\Workflow\Type\FlowObjectInterface;
use PHPMentors\Workflower\Workflow\Type\TransitionalFlowObjectInterface;
use Symfony\Component\ExpressionLanguage\Expression;

class SequenceFlow implements ConnectionInterface
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
     * @var TransitionalFlowObjectInterface
     */
    private $source;

    /**
     * @var FlowObjectInterface
     */
    private $destination;

    /**
     * @var bool
     */
    private $default;

    /**
     * @var Expression
     */
    private $condition;

    /**
     * @param int|string
     * @param TransitionalFlowObjectInterface $source
     * @param FlowObjectInterface             $destination
     * @param string                          $name
     * @param bool                            $default
     * @param Expression                      $condition
     */
    public function __construct($id, TransitionalFlowObjectInterface $source, FlowObjectInterface $destination, $name = null, $default = false, Expression $condition = null)
    {
        $this->id = $id;
        $this->source = $source;
        $this->destination = $destination;
        $this->name = $name;
        $this->default = $default;
        $this->condition = $condition;
    }

    /**
     * {@inheritDoc}
     *
     * @return string
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * {@inheritDoc}
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * {@inheritDoc}
     */
    public function getSource()
    {
        return $this->source;
    }

    /**
     * {@inheritDoc}
     */
    public function getDestination()
    {
        return $this->destination;
    }

    /**
     * @return bool
     */
    public function isDefault()
    {
        return $this->default;
    }

    /**
     * @return Expression
     */
    public function getCondition()
    {
        return $this->condition;
    }

    /**
     * {@inheritDoc}
     */
    public function equals(EntityInterface $target)
    {
        assert($target instanceof self);

        return $this->id === $target->getId();
    }
}
