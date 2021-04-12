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

namespace PHPMentors\Workflower\Workflow\Activity;

use PHPMentors\Workflower\Workflow\Participant\Role;

/**
 * @since Class available since Release 1.2.0
 */
class ServiceTask extends OperationalTask
{
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

}
