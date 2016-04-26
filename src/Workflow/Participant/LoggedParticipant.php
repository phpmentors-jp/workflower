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

namespace PHPMentors\Workflower\Workflow\Participant;

use PHPMentors\Workflower\Workflow\Resource\ResourceInterface;

class LoggedParticipant implements ParticipantInterface, \Serializable
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
     * @param ParticipantInterface $participant
     */
    public function __construct(ParticipantInterface $participant)
    {
        $this->id = $participant->getId();
        $this->name = $participant->getName();
    }

    /**
     * {@inheritdoc}
     */
    public function serialize()
    {
        return serialize(array(
            'id' => $this->id,
            'name' => $this->name,
        ));
    }

    /**
     * {@inheritdoc}
     */
    public function unserialize($serialized)
    {
        foreach (unserialize($serialized) as $name => $value) {
            if (property_exists($this, $name)) {
                $this->$name = $value;
            }
        }
    }

    /**
     * {@inheritdoc}
     */
    public function hasRole($role)
    {
        throw $this->createOperationNotFoundException(__FUNCTION__);
    }

    /**
     * {@inheritdoc}
     */
    public function setResource(ResourceInterface $resource)
    {
        throw $this->createOperationNotFoundException(__FUNCTION__);
    }

    /**
     * {@inheritdoc}
     */
    public function getResource()
    {
        throw $this->createOperationNotFoundException(__FUNCTION__);
    }

    /**
     * {@inheritdoc}
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
     * @param string $method
     *
     * @throws OperationNotSupportedException
     */
    private function createOperationNotFoundException($method)
    {
        return new OperationNotSupportedException(sprintf(
            'The method "%s" is not supported by "%s". Use your ParticipantInterface object instead.',
            $method,
            get_class($this)
        ));
    }
}
