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

namespace PHPMentors\Workflower\Definition;

use PHPMentors\Workflower\Workflow\WorkflowRepository;

class Bpmn2ReaderTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @test
     */
    public function read()
    {
        $workflowRepository = new WorkflowRepository();
        $bpmn2Reader = new Bpmn2Reader();
        $workflow = $bpmn2Reader->read(dirname(__DIR__).'/Resources/config/workflower/LoanRequestProcess.bpmn');

        $this->assertThat($workflow, $this->equalTo($workflowRepository->findById('LoanRequestProcess')));
    }
}
