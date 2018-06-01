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

use PHPMentors\DomainKata\Service\ServiceInterface;
use PHPMentors\Workflower\Workflow\Workflow;
use PHPMentors\Workflower\Workflow\WorkflowBuilder;

class Bpmn2Reader implements ServiceInterface
{
    const BPMN_NS = 'http://www.omg.org/spec/BPMN/20100524/MODEL';
    /**
     * @param string $file
     *
     * @return Workflow
     *
     * @throws IdAttributeNotFoundException
     */
    public function read($file)
    {
        $document = new \DOMDocument();
        $errorToExceptionContext = new ErrorToExceptionContext(E_WARNING, function () use ($file, $document) {
            $document->load($file);
        });
        $errorToExceptionContext->invoke();

        return $this->readDocument($document, pathinfo($file, PATHINFO_FILENAME));
    }

    /**
     * @param string $source
     *
     * @return Workflow
     *
     * @throws IdAttributeNotFoundException
     *
     * @since Method available since Release 1.3.0
     */
    public function readSource($source)
    {
        $document = new \DOMDocument();
        $errorToExceptionContext = new ErrorToExceptionContext(E_WARNING, function () use ($source, $document) {
            $document->loadXML($source);
        });
        $errorToExceptionContext->invoke();

        return $this->readDocument($document);
    }

    /**
     * @param WorkflowBuilder   $workflowBuilder
     * @param \DOMElement       $element
     */
    private function readEventDefinition(WorkflowBuilder $workflowBuilder, \DOMElement $element)
    {
        $eventDefinition = null;
        foreach ($element->getElementsByTagNameNs(self::BPMN_NS, 'timerEventDefinition') as $childElement) {
            $timeDuration = $childElement->getElementsByTagNameNs(self::BPMN_NS, 'timeDuration')->item(0);
            if ($timeDuration) $timeDuration = $timeDuration->nodeValue;
            $timeCycle = $childElement->getElementsByTagNameNs(self::BPMN_NS, 'timeCycle')->item(0);
            if ($timeCycle) $timeCycle = $timeCycle->nodeValue;
            $eventDefinition = $workflowBuilder->buildTimerEventDefinition($timeDuration, $timeCycle);
            break;
        }
        return $eventDefinition;
    }

    /**
     * @param \DOMDocument $document
     * @param int|string  $workflowId
     *
     * @return Workflow
     *
     * @throws IdAttributeNotFoundException
     *
     * @since Method available since Release 1.3.0
     */
    private function readDocument(\DOMDocument $document, $workflowId = null)
    {
        $errorToExceptionContext = new ErrorToExceptionContext(E_WARNING, function () use ($document) {
            $document->schemaValidate(dirname(__DIR__).'/Resources/config/workflower/schema/BPMN20.xsd');
        });
        $errorToExceptionContext->invoke();

        $workflowBuilder = new WorkflowBuilder($workflowId);

        foreach ($document->getElementsByTagNameNs(self::BPMN_NS, 'process') as $element) {
            /* @var $element \DOMElement */
            if ($element->hasAttribute('id')) {
                $workflowBuilder->setWorkflowId($element->getAttribute('id'));
            }

            if ($element->hasAttribute('name')) {
                $workflowBuilder->setWorkflowName($element->getAttribute('name'));
            }
        }

        $flowObjectRoles = array();
        foreach ($document->getElementsByTagNameNs(self::BPMN_NS, 'lane') as $element) {
            if (!$element->hasAttribute('id')) {
                throw $this->createIdAttributeNotFoundException($element, $workflowId);
            }

            $workflowBuilder->addRole(
                $element->getAttribute('id'),
                $element->hasAttribute('name') ? $element->getAttribute('name') : null
            );

            foreach ($element->getElementsByTagNameNs(self::BPMN_NS, 'flowNodeRef') as $childElement) {
                $flowObjectRoles[$childElement->nodeValue] = $element->getAttribute('id');
            }
        }

        if (count($flowObjectRoles) == 0) {
            $workflowBuilder->addRole(Workflow::DEFAULT_ROLE_ID);
        }

        $messages = array();
        foreach ($document->getElementsByTagNameNs(self::BPMN_NS, 'message') as $element) {
            if (!$element->hasAttribute('id')) {
                throw $this->createIdAttributeNotFoundException($element, $workflowId);
            }

            $messages[$element->getAttribute('id')] = $element->getAttribute('name');
        }

        $operations = array();
        foreach ($document->getElementsByTagNameNs(self::BPMN_NS, 'operation') as $element) {
            if (!$element->hasAttribute('id')) {
                throw $this->createIdAttributeNotFoundException($element, $workflowId);
            }

            $operations[$element->getAttribute('id')] = $element->getAttribute('name');
        }

        foreach ($document->getElementsByTagNameNs(self::BPMN_NS, 'startEvent') as $element) {
            if (!$element->hasAttribute('id')) {
                throw $this->createIdAttributeNotFoundException($element, $workflowId);
            }

            $workflowBuilder->addStartEvent(
                $element->getAttribute('id'),
                $this->provideRoleIdForFlowObject($flowObjectRoles, $element->getAttribute('id')),
                $element->hasAttribute('name') ? $element->getAttribute('name') : null,
                $element->hasAttribute('default') ? $element->getAttribute('default') : null
            );
        }

        foreach ($document->getElementsByTagNameNs(self::BPMN_NS, 'task') as $element) {
            if (!$element->hasAttribute('id')) {
                throw $this->createIdAttributeNotFoundException($element, $workflowId);
            }

            $workflowBuilder->addTask(
                $element->getAttribute('id'),
                $this->provideRoleIdForFlowObject($flowObjectRoles, $element->getAttribute('id')),
                $element->hasAttribute('name') ? $element->getAttribute('name') : null,
                $element->hasAttribute('default') ? $element->getAttribute('default') : null
            );
        }

        foreach ($document->getElementsByTagNameNs(self::BPMN_NS, 'serviceTask') as $element) {
            if (!$element->hasAttribute('id')) {
                throw $this->createIdAttributeNotFoundException($element, $workflowId);
            }

            $workflowBuilder->addServiceTask(
                $element->getAttribute('id'),
                $this->provideRoleIdForFlowObject($flowObjectRoles, $element->getAttribute('id')),
                $element->hasAttribute('operationRef') ? $operations[$element->getAttribute('operationRef')] : null,
                $element->hasAttribute('name') ? $element->getAttribute('name') : null,
                $element->hasAttribute('default') ? $element->getAttribute('default') : null
            );
        }

        foreach ($document->getElementsByTagNameNs(self::BPMN_NS, 'sendTask') as $element) {
            if (!$element->hasAttribute('id')) {
                throw $this->createIdAttributeNotFoundException($element, $workflowId);
            }

            $workflowBuilder->addSendTask(
                $element->getAttribute('id'),
                $this->provideRoleIdForFlowObject($flowObjectRoles, $element->getAttribute('id')),
                $element->hasAttribute('messageRef') ? $messages[$element->getAttribute('messageRef')] : null,
                $element->hasAttribute('operationRef') ? $operations[$element->getAttribute('operationRef')] : null,
                $element->hasAttribute('name') ? $element->getAttribute('name') : null,
                $element->hasAttribute('default') ? $element->getAttribute('default') : null
            );
        }

        foreach ($document->getElementsByTagNameNs(self::BPMN_NS, 'exclusiveGateway') as $element) {
            if (!$element->hasAttribute('id')) {
                throw $this->createIdAttributeNotFoundException($element, $workflowId);
            }

            $workflowBuilder->addExclusiveGateway(
                $element->getAttribute('id'),
                $this->provideRoleIdForFlowObject($flowObjectRoles, $element->getAttribute('id')),
                $element->hasAttribute('name') ? $element->getAttribute('name') : null,
                $element->hasAttribute('default') ? $element->getAttribute('default') : null
            );
        }

        foreach ($document->getElementsByTagNameNs(self::BPMN_NS, 'parallelGateway') as $element) {
            if (!$element->hasAttribute('id')) {
                throw $this->createIdAttributeNotFoundException($element, $workflowId);
            }

            $workflowBuilder->addParallelGateway(
                $element->getAttribute('id'),
                $this->provideRoleIdForFlowObject($flowObjectRoles, $element->getAttribute('id')),
                $element->hasAttribute('name') ? $element->getAttribute('name') : null,
                $element->hasAttribute('default') ? $element->getAttribute('default') : null
            );
        }

        foreach ($document->getElementsByTagNameNs(self::BPMN_NS, 'endEvent') as $element) {
            if (!$element->hasAttribute('id')) {
                throw $this->createIdAttributeNotFoundException($element, $workflowId);
            }

            $workflowBuilder->addEndEvent($element->getAttribute('id'), $this->provideRoleIdForFlowObject($flowObjectRoles, $element->getAttribute('id')), $element->hasAttribute('name') ? $element->getAttribute('name') : null);
        }

        foreach ($document->getElementsByTagNameNs(self::BPMN_NS, 'intermediateCatchEvent') as $element) {
            if (!$element->hasAttribute('id')) {
                throw $this->createIdAttributeNotFoundException($element, $workflowId);
            }

            $eventDefinition = $this->readEventDefinition($workflowBuilder, $element);

            $workflowBuilder->addIntermediateCatchEvent(
                $element->getAttribute('id'),
                $this->provideRoleIdForFlowObject($flowObjectRoles, $element->getAttribute('id')),
                $element->hasAttribute('name') ? $element->getAttribute('name') : null,
                $eventDefinition
            );
        }

        foreach ($document->getElementsByTagNameNs(self::BPMN_NS, 'boundaryEvent') as $element) {
            if (!$element->hasAttribute('id')) {
                throw $this->createIdAttributeNotFoundException($element, $workflowId);
            }
            if (!$element->hasAttribute('attachedToRef')) {
                throw new \Exception("BoundaryEvent needs attachment");
            }

            $eventDefinition = $this->readEventDefinition($workflowBuilder, $element);

            $workflowBuilder->addBoundaryEvent(
                $element->getAttribute('id'),
                $this->provideRoleIdForFlowObject($flowObjectRoles, $element->getAttribute('id')),
                $element->hasAttribute('name') ? $element->getAttribute('name') : null,
                $element->getAttribute('attachedToRef'),
                $element->getAttribute('cancelActivity') === 'false' ? false : true,
                $eventDefinition
            );
        }

        foreach ($document->getElementsByTagNameNs(self::BPMN_NS, 'sequenceFlow') as $element) {
            if (!$element->hasAttribute('id')) {
                throw $this->createIdAttributeNotFoundException($element, $workflowId);
            }

            $condition = null;
            foreach ($element->getElementsByTagNameNs(self::BPMN_NS, 'conditionExpression') as $childElement) {
                $condition = $childElement->nodeValue;
                break;
            }

            $workflowBuilder->addSequenceFlow(
                $element->getAttribute('sourceRef'),
                $element->getAttribute('targetRef'),
                $element->getAttribute('id'),
                $element->hasAttribute('name') ? $element->getAttribute('name') : null,
                $condition === null ? null : $condition
            );
        }

        return $workflowBuilder->build();
    }

    /**
     * @param \DOMElement $element
     * @param int|string  $workflowId
     *
     * @return IdAttributeNotFoundException
     */
    private function createIdAttributeNotFoundException(\DOMElement $element, $workflowId)
    {
        return new IdAttributeNotFoundException(sprintf('The id attribute of the "%s" element is not found in workflow "%s" on line %d', $element->tagName, $workflowId, $element->getLineNo()));
    }

    /**
     * @param array  $flowObjectRoles
     * @param string $flowObjectId
     *
     * @return string
     *
     * @since Method available since Release 1.3.0
     */
    private function provideRoleIdForFlowObject(array $flowObjectRoles, $flowObjectId)
    {
        return count($flowObjectRoles) ? $flowObjectRoles[$flowObjectId] : Workflow::DEFAULT_ROLE_ID;
    }
}
