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

namespace PHPMentors\Workflower\Definition;

use PHPMentors\Workflower\Workflow\ProcessDefinition;
use PHPMentors\Workflower\Workflow\Workflow;
use PHPMentors\Workflower\Workflow\WorkflowRepository;
use PHPUnit\Framework\TestCase;

class Bpmn2ReaderTest extends TestCase
{
    /**
     * @test
     */
    public function read()
    {
        $workflowRepository = new WorkflowRepository();
        $bpmn2Reader = new Bpmn2Reader();
        $definitions = $bpmn2Reader->read(dirname(__DIR__).'/Resources/config/workflower/LoanRequestProcess.bpmn');

        $instance = $definitions[0]->createProcessInstance();
        $dest = $workflowRepository->findById('LoanRequestProcess');
        $definitions[0]->setProcessDefinitions($dest->getProcessDefinition()->getProcessDefinitions());

        $this->assertThat($instance, $this->equalTo($dest));
    }

    /**
     * @test
     *
     * @since Method available since Release 1.3.0
     */
    public function readSource()
    {
        $workflowRepository = new WorkflowRepository();
        $bpmn2Reader = new Bpmn2Reader();
        $definitions = $bpmn2Reader->readSource(file_get_contents(dirname(__DIR__).'/Resources/config/workflower/LoanRequestProcess.bpmn'));

        $instance = $definitions[0]->createProcessInstance();
        $dest = $workflowRepository->findById('LoanRequestProcess');
        $definitions[0]->setProcessDefinitions($dest->getProcessDefinition()->getProcessDefinitions());

        $this->assertThat($instance, $this->equalTo($dest));
    }
}
