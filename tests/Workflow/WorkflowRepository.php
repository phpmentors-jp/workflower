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

namespace PHPMentors\Workflower\Workflow;

use PHPMentors\Workflower\Definition\Bpmn2Reader;
use PHPMentors\Workflower\Definition\ProcessDefinitionRepository;

class WorkflowRepository implements WorkflowRepositoryInterface
{
    /**
     * @var array
     */
    private $workflows = [];
    private $definitions;

    public function __construct()
    {
        $this->definitions = new ProcessDefinitionRepository();

        $this->add($this->createLoanRequestProcess());
        $this->add($this->createMultipleWorkItemsProcess());
        $this->add($this->createServiceTasksProcess());
        $this->add($this->createNoLanesProcess());
        $this->add($this->createSendTasksProcess());
        $this->add($this->createParallelGatewayProcess());
        $this->add($this->createMultipleEndEventsProcess());
        $this->add($this->createParallelSequenceFlowsProcess());
        $this->add($this->createParallelUserTasksProcess());
        $this->add($this->createSequentialUserTasksProcess());
        $this->add($this->createSubProcessTaskProcess());
        $this->add($this->createCallActivityProcess());
    }

    /**
     * {@inheritdoc}
     */
    public function add($workflow): void
    {
        assert($workflow instanceof Workflow);

        $this->workflows[$workflow->getId()] = $workflow;
    }

    /**
     * {@inheritdoc}
     */
    public function remove($workflow): void
    {
        assert($workflow instanceof Workflow);
    }

    /**
     * {@inheritdoc}
     */
    public function findById($id): ?Workflow
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
        $processDefinition = new ProcessDefinition([
            'id' => 'LoanRequestProcess',
            'name' => 'Loan Request Process',
            'roles' => [
                ['id' => 'ROLE_BRANCH', 'name' => 'Branch'],
                ['id' => 'ROLE_CREDIT_FACTORY', 'name' => 'Credit Factory'],
                ['id' => 'ROLE_BACK_OFFICE', 'name' => 'Back Office'],
            ],
            'startEvents' => [
                ['id' => 'Start', 'roleId' => 'ROLE_BRANCH']
            ],
            'tasks' => [
                ['id' => 'RecordLoanApplicationInformation', 'name' => 'Record Loan Application Information', 'roleId' => 'ROLE_BRANCH'],
                ['id' => 'CheckApplicantInformation', 'name' => 'Check Applicant Information', 'roleId' => 'ROLE_BRANCH', 'defaultSequenceFlowId' => 'ResultOfVerification.LoanStudy'],
                ['id' => 'LoanStudy', 'name' => 'Loan Study', 'roleId' => 'ROLE_CREDIT_FACTORY'],
                ['id' => 'InformRejection', 'name' => 'Inform Rejection', 'roleId' => 'ROLE_CREDIT_FACTORY'],
                ['id' => 'Disbursement', 'name' => 'Disbursement', 'roleId' => 'ROLE_BACK_OFFICE']
            ],
            'exclusiveGateways' => [
                ['id' => 'ResultOfVerification', 'name' => 'Result of Verification', 'roleId' => 'ROLE_BRANCH', 'defaultSequenceFlowId' => 'ResultOfVerification.LoanStudy'],
                ['id' => 'ApplicationApproved', 'name' => 'Application Approved?', 'roleId' => 'ROLE_CREDIT_FACTORY', 'defaultSequenceFlowId' => 'ApplicationApproved.Disbursement']
            ],
            'endEvents' => [
                ['id' => 'End', 'roleId' => 'ROLE_CREDIT_FACTORY']
            ],
            'sequenceFlows' => [
                ['id' => 'Start.RecordLoanApplicationInformation', 'source' => 'Start', 'destination' => 'RecordLoanApplicationInformation'],
                ['id' => 'RecordLoanApplicationInformation.CheckApplicantInformation', 'source' => 'RecordLoanApplicationInformation', 'destination' => 'CheckApplicantInformation'],
                ['id' => 'CheckApplicantInformation.ResultOfVerification', 'source' => 'CheckApplicantInformation', 'destination' => 'ResultOfVerification'],
                ['id' => 'ResultOfVerification.LoanStudy', 'source' => 'ResultOfVerification', 'destination' => 'LoanStudy', 'name' => 'Ok'],
                ['id' => 'ResultOfVerification.End', 'source' => 'ResultOfVerification', 'destination' => 'End', 'name' => 'Rejected', 'condition' => 'rejected === true'],
                ['id' => 'LoanStudy.ApplicationApproved', 'source' => 'LoanStudy', 'destination' => 'ApplicationApproved'],
                ['id' => 'ApplicationApproved.Disbursement', 'source' => 'ApplicationApproved', 'destination' => 'Disbursement', 'name' => 'Ok'],
                ['id' => 'ApplicationApproved.InformRejection', 'source' => 'ApplicationApproved', 'destination' => 'InformRejection', 'name' => 'Rejected', 'condition' => 'rejected === true'],
                ['id' => 'InformRejection.End', 'source' => 'InformRejection', 'destination' => 'End'],
                ['id' => 'Disbursement.End', 'source' => 'Disbursement', 'destination' => 'End'],

            ]
        ]);

        $this->definitions->add($processDefinition);

        return $processDefinition->createProcessInstance();
    }

    /**
     * @return Workflow
     */
    private function createMultipleWorkItemsProcess()
    {
        $processDefinition = new ProcessDefinition([
            'id' => 'MultipleWorkItemsProcess',
            'roles' => [
                ['id' => 'ROLE_USER', 'name' => 'User']
            ],
            'startEvents' => [
                ['id' => 'Start', 'roleId' => 'ROLE_USER']
            ],
            'tasks' => [
                ['id' => 'Task1', 'roleId' => 'ROLE_USER'],
                ['id' => 'Task2', 'roleId' => 'ROLE_USER', 'defaultSequenceFlowId' => 'Task2.End']
            ],
            'endEvents' => [
                ['id' => 'End', 'roleId' => 'ROLE_USER']
            ],
            'sequenceFlows' => [
                ['source' => 'Start', 'destination' => 'Task1', 'id' => 'Start.Task1'],
                ['source' => 'Task1', 'destination' => 'Task2', 'id' => 'Task1.Task2'],
                ['source' => 'Task2', 'destination' => 'End', 'id' => 'Task2.End'],
                ['source' => 'Task2', 'destination' => 'Task1', 'id' => 'Task2.Task1', 'condition' => 'satisfied !== true'],
            ]
        ]);

        return $processDefinition->createProcessInstance();
    }

    /**
     * @return Workflow
     *
     * @since Method available since Release 1.2.0
     */
    private function createServiceTasksProcess()
    {
        $this->definitions->importFromFile(dirname(__DIR__).'/Resources/config/workflower/ServiceTasksProcess.bpmn');
        return $this->definitions->getLatestById('ServiceTasksProcess')->createProcessInstance();
    }

    /**
     * @return Workflow
     *
     * @since Method available since Release 1.3.0
     */
    private function createNoLanesProcess()
    {
        $this->definitions->importFromFile(dirname(__DIR__).'/Resources/config/workflower/NoLanesProcess.bpmn');
        return $this->definitions->getLatestById('NoLanesProcess')->createProcessInstance();
    }

    /**
     * @return Workflow
     *
     * @since Method available since Release 1.3.0
     */
    private function createSendTasksProcess()
    {
        $this->definitions->importFromFile(dirname(__DIR__).'/Resources/config/workflower/SendTasksProcess.bpmn');
        return $this->definitions->getLatestById('SendTasksProcess')->createProcessInstance();
    }

    /**
     * @return Workflow
     *
     * @since Method available since Release 2.0.0
     */
    private function createParallelGatewayProcess(): Workflow
    {
        $this->definitions->importFromFile(dirname(__DIR__).'/Resources/config/workflower/ParallelGatewayProcess.bpmn');
        return $this->definitions->getLatestById('ParallelGatewayProcess')->createProcessInstance();
    }

    /**
     * @return Workflow
     *
     * @since Method available since Release 2.0.0
     */
    private function createMultipleEndEventsProcess(): Workflow
    {
        $this->definitions->importFromFile(dirname(__DIR__).'/Resources/config/workflower/MultipleEndEvents.bpmn');
        return $this->definitions->getLatestById('MultipleEndEventsProcess')->createProcessInstance();
    }

    /**
     * @return Workflow
     *
     * @since Method available since Release 2.0.0
     */
    private function createParallelSequenceFlowsProcess(): Workflow
    {
        $this->definitions->importFromFile(dirname(__DIR__).'/Resources/config/workflower/ParallelSequenceFlows.bpmn');
        return $this->definitions->getLatestById('ParallelSequenceFlows')->createProcessInstance();
    }

    /**
     * @return Workflow
     *
     * @since Method available since Release 2.0.0
     */
    private function createParallelUserTasksProcess(): Workflow
    {
        $this->definitions->importFromFile(dirname(__DIR__).'/Resources/config/workflower/ParallelUserTasks.bpmn');
        return $this->definitions->getLatestById('ParallelUserTasks')->createProcessInstance();
    }

    /**
     * @return Workflow
     *
     * @since Method available since Release 2.0.0
     */
    private function createSequentialUserTasksProcess(): Workflow
    {
        $this->definitions->importFromFile(dirname(__DIR__).'/Resources/config/workflower/SequentialUserTasks.bpmn');
        return $this->definitions->getLatestById('SequentialUserTasks')->createProcessInstance();
    }

    /**
     * @return Workflow
     *
     * @since Method available since Release 2.0.0
     */
    private function createSubProcessTaskProcess(): Workflow
    {
        $this->definitions->importFromFile(dirname(__DIR__).'/Resources/config/workflower/SubProcess.bpmn');
        return $this->definitions->getLatestById('SubProcess')->createProcessInstance();
    }

    /**
     * @return Workflow
     *
     * @since Method available since Release 2.0.0
     */
    private function createCallActivityProcess(): Workflow
    {
        $this->definitions->importFromFile(dirname(__DIR__).'/Resources/config/workflower/CallActivity.bpmn');
        return $this->definitions->getLatestById('CallActivity')->createProcessInstance();
    }
}
