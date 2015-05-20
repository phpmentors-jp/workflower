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

namespace PHPMentors\Workflower\Workflow;

use PHPMentors\DomainKata\Entity\EntityInterface;

class WorkflowRepository implements WorkflowRepositoryInterface
{
    /**
     * @var array
     */
    private $workflows = array();

    public function __construct()
    {
        $this->add($this->createLoanRequestProcess());
    }

    /**
     * {@inheritDoc}
     */
    public function add(EntityInterface $entity)
    {
        assert($entity instanceof Workflow);

        $this->workflows[$entity->getId()] = $entity;
    }

    /**
     * {@inheritDoc}
     */
    public function remove(EntityInterface $entity)
    {
        assert($entity instanceof Workflow);
    }

    /**
     * {@inheritDoc}
     */
    public function findById($id)
    {
        if (!array_key_exists($id, $this->workflows)) {
            return null;
        }

        return $this->workflows[$id];
    }

    /**
     * @return Workflow
     */
    private function createLoanRequestProcess()
    {
        $workflowBuilder = new WorkflowBuilder();
        $workflowBuilder->setWorkflowId('LoanRequestProcess');
        $workflowBuilder->addRole('ROLE_BRANCH', 'Branch');
        $workflowBuilder->addRole('ROLE_CREDIT_FACTORY', 'Credit Factory');
        $workflowBuilder->addRole('ROLE_BACK_OFFICE', 'Back Office');
        $workflowBuilder->addStartEvent('Start', 'ROLE_BRANCH');
        $workflowBuilder->addTask('Record Loan Application Information', 'ROLE_BRANCH');
        $workflowBuilder->addTask('Check Applicant Information', 'ROLE_BRANCH');
        $workflowBuilder->addTask('Loan Study', 'ROLE_CREDIT_FACTORY');
        $workflowBuilder->addTask('Inform Rejection', 'ROLE_CREDIT_FACTORY');
        $workflowBuilder->addTask('Disbursement', 'ROLE_BACK_OFFICE');
        $workflowBuilder->addExclusiveGateway('Applicaion Approved?', 'ROLE_CREDIT_FACTORY');
        $workflowBuilder->addEndEvent('End', 'ROLE_CREDIT_FACTORY');
        $workflowBuilder->addSequenceFlow('Start', 'Record Loan Application Information');
        $workflowBuilder->addSequenceFlow('Record Loan Application Information', 'Check Applicant Information');
        $workflowBuilder->addSequenceFlow('Check Applicant Information', 'Loan Study', null, 'Ok', true);
        $workflowBuilder->addSequenceFlow('Check Applicant Information', 'End', null, 'Rejected', false, 'rejected === true');
        $workflowBuilder->addSequenceFlow('Loan Study', 'Applicaion Approved?');
        $workflowBuilder->addSequenceFlow('Applicaion Approved?', 'Disbursement', null, 'Ok', true);
        $workflowBuilder->addSequenceFlow('Applicaion Approved?', 'Inform Rejection', null, 'Rejected', false, 'rejected === true');
        $workflowBuilder->addSequenceFlow('Inform Rejection', 'End');
        $workflowBuilder->addSequenceFlow('Disbursement', 'End');

        return $workflowBuilder->build();
    }
}
