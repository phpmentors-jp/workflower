<?php
/*
 * Copyright (c) KUBO Atsuhiro <kubo@iteman.jp> and contributors,
 * All rights reserved.p
 *
 * This file is part of Workflower.
 *
 * This program and the accompanying materials are made available under
 * the terms of the BSD 2-Clause License which accompanies this
 * distribution, and is available at http://opensource.org/licenses/BSD-2-Clause
 */

namespace PHPMentors\Workflower\Workflow\Event;

use PHPMentors\Workflower\Workflow\Participant\Role;
use PHPMentors\Workflower\Workflow\Element\TransitionalInterface;
use PHPMentors\Workflower\Workflow\EventDefinition\EventDefinitionInterface;

class IntermediateCatchEvent extends Event implements EventInterface, TransitionalInterface
{
    /**
     * @var EventDefinitionInterface
     */
    private $eventDefinition;

    /**
     * @param int|string $id
     * @param Role       $role
     * @param string     $name
     * @param EventDefinitionInterface  $eventDefinition
     */
    public function __construct($id, Role $role, $name = null, EventDefinitionInterface $eventDefinition = null)
    {
        parent::__construct($id, $role, $name);
        $this->eventDefinition = $eventDefinition;
    }

    /**
     * @return EventDefinitionInterface
     */
    public function getEventDefinition()
    {
        return $this->eventDefinition;
    }
}
