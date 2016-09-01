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

namespace PHPMentors\Workflower\Workflow\Activity;

use PHPMentors\Workflower\Workflow\Operation\OperationalInterface;
use PHPMentors\Workflower\Workflow\Participant\Role;

/**
 * @since Class available since Release 1.2.0
 */
class ServiceTask extends Task implements OperationalInterface
{
    /**
     * @var int|string
     */
    private $operation;

    /**
     * @param int|string $id
     * @param Role       $role
     * @param int|string $operation
     * @param string     $name
     */
    public function __construct($id, Role $role, $operation, $name = null)
    {
        parent::__construct($id, $role, $name);

        $this->operation = $operation;
    }

    /**
     * {@inheritdoc}
     */
    public function serialize()
    {
        return serialize(array(
            get_parent_class($this) => parent::serialize(),
            'operation' => $this->operation,
        ));
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
    public function getOperation()
    {
        return $this->operation;
    }
}
