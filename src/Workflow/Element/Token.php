<?php
/*
 * Copyright (c) Atsuhiro Kubo <kubo@iteman.jp> and contributors,
 * All rights reserved.p
 *
 * This file is part of Workflower.
 *
 * This program and the accompanying materials are made available under
 * the terms of the BSD 2-Clause License which accompanies this
 * distribution, and is available at http://opensource.org/licenses/BSD-2-Clause
 */

namespace PHPMentors\Workflower\Workflow\Element;

class Token // implements \Serializable
{
    /**
     * @var string
     */
    protected $id;

    /**
     * @var FlowObjectInterface
     */
    protected $currentFlowObject;

    /**
     * @var FlowObjectInterface
     */
    protected $previousFlowObject;

    /**
     * @param string              $id
     * @param FlowObjectInterface $flowObject
     */
    public function __construct(string $id, FlowObjectInterface $flowObject)
    {
        $this->id = $id;
        $this->currentFlowObject = $flowObject;
        $this->currentFlowObject->attachToken($this);
    }

    /**
     * @return string
     */
    public function getId(): string
    {
        return $this->id;
    }

    /**
     * @return FlowObjectInterface
     */
    public function getCurrentFlowObject(): FlowObjectInterface
    {
        return $this->currentFlowObject;
    }

    /**
     * @return FlowObjectInterface|null
     */
    public function getPreviousFlowObject(): ?FlowObjectInterface
    {
        return $this->previousFlowObject;
    }

    /**
     * @param FlowObjectInterface $flowObject
     *
     * @return Token
     */
    public function flow(FlowObjectInterface $flowObject): Token
    {
        $this->currentFlowObject->detachToken($this);
        $flowObject->attachToken($this);
        $this->previousFlowObject = $this->currentFlowObject;
        $this->currentFlowObject = $flowObject;

        return $this;
    }

    /*public function serialize()
    {
        return serialize([
            'id',
            'currentFlowObject',
            'previousFlowObject',
        ]);
    }*/

    /**
     * {@inheritdoc}
     */
    /*public function unserialize($serialized)
    {
        foreach (unserialize($serialized) as $name => $value) {
            if (property_exists($this, $name)) {
                $this->$name = $value;
            }
        }
    }*/
}
