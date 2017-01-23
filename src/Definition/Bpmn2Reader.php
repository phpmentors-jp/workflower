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

        return $this->readDocument($document);
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
     * @param DOMDocument $document
     *
     * @return Workflow
     *
     * @throws IdAttributeNotFoundException
     *
     * @since Method available since Release 1.3.0
     */
    private function readDocument(\DOMDocument $document)
    {
        $errorToExceptionContext = new ErrorToExceptionContext(E_WARNING, function () use ($document) {
            $document->schemaValidate(dirname(__DIR__).'/Resources/config/workflower/schema/BPMN20.xsd');
        });
        $errorToExceptionContext->invoke();

        $workflowBuilder = new WorkflowBuilder();

        foreach ($document->getElementsByTagNameNs('http://www.omg.org/spec/BPMN/20100524/MODEL', 'process') as $element) {
            if ($element->hasAttribute('id')) {
                $workflowId = $element->getAttribute('id');
            }

            if ($element->hasAttribute('name')) {
                $workflowBuilder->setWorkflowName($element->getAttribute('name'));
            }
        }
        if (!isset($workflowId)) {
            $workflowId = pathinfo($file, PATHINFO_FILENAME);
        }
        $workflowBuilder->setWorkflowId($workflowId);

        $flowObjectRoles = array();
        foreach ($document->getElementsByTagNameNs('http://www.omg.org/spec/BPMN/20100524/MODEL', 'lane') as $element) {
            if (!$element->hasAttribute('id')) {
                throw $this->createIdAttributeNotFoundException($element, $file);
            }

            $workflowBuilder->addRole(
                $element->getAttribute('id'),
                $element->hasAttribute('name') ? $element->getAttribute('name') : null
            );

            foreach ($element->getElementsByTagNameNs('http://www.omg.org/spec/BPMN/20100524/MODEL', 'flowNodeRef') as $childElement) {
                $flowObjectRoles[$childElement->nodeValue] = $element->getAttribute('id');
            }
        }

        if (count($flowObjectRoles) <= 0) { // laneless flow, use participant name as role
            $participants = array();
            foreach ($document->getElementsByTagNameNs('http://www.omg.org/spec/BPMN/20100524/MODEL', 'participant') as $element) {
                $participants[$element->getAttribute('processRef')] = $element->getAttribute('id');
                $workflowBuilder->addRole(
                    $element->getAttribute('id'),
                    $element->hasAttribute('name') ? $element->getAttribute('name') : null
                );
            }

            foreach ($document->getElementsByTagNameNs('http://www.omg.org/spec/BPMN/20100524/MODEL', 'process') as $element) {
                foreach ($element->childNodes as $childElement) {
                    if (!$childElement->hasAttributes()) continue;
                    $flowObjectRoles[$childElement->getAttribute('id')] = $participants[$element->getAttribute('id')];
                }
            }
        }

        $operations = array();
        foreach ($document->getElementsByTagNameNs('http://www.omg.org/spec/BPMN/20100524/MODEL', 'operation') as $element) {
            if (!$element->hasAttribute('id')) {
                throw $this->createIdAttributeNotFoundException($element, $file);
            }

            $operations[$element->getAttribute('id')] = $element->getAttribute('name');
        }

        foreach ($document->getElementsByTagNameNs('http://www.omg.org/spec/BPMN/20100524/MODEL', 'startEvent') as $element) {
            if (!$element->hasAttribute('id')) {
                throw $this->createIdAttributeNotFoundException($element, $file);
            }

            $workflowBuilder->addStartEvent(
                $element->getAttribute('id'),
                $flowObjectRoles[$element->getAttribute('id')],
                $element->hasAttribute('name') ? $element->getAttribute('name') : null,
                $element->hasAttribute('default') ? $element->getAttribute('default') : null
            );
        }

        foreach ($document->getElementsByTagNameNs('http://www.omg.org/spec/BPMN/20100524/MODEL', 'task') as $element) {
            if (!$element->hasAttribute('id')) {
                throw $this->createIdAttributeNotFoundException($element, $file);
            }

            $workflowBuilder->addTask(
                $element->getAttribute('id'),
                $flowObjectRoles[$element->getAttribute('id')],
                $element->hasAttribute('name') ? $element->getAttribute('name') : null,
                $element->hasAttribute('default') ? $element->getAttribute('default') : null
            );
        }

        foreach ($document->getElementsByTagNameNs('http://www.omg.org/spec/BPMN/20100524/MODEL', 'serviceTask') as $element) {
            if (!$element->hasAttribute('id')) {
                throw $this->createIdAttributeNotFoundException($element, $file);
            }

            $workflowBuilder->addServiceTask(
                $element->getAttribute('id'),
                $flowObjectRoles[$element->getAttribute('id')],
                $operations[$element->getAttribute('operationRef')],
                $element->hasAttribute('name') ? $element->getAttribute('name') : null,
                $element->hasAttribute('default') ? $element->getAttribute('default') : null
            );
        }

        foreach ($document->getElementsByTagNameNs('http://www.omg.org/spec/BPMN/20100524/MODEL', 'exclusiveGateway') as $element) {
            if (!$element->hasAttribute('id')) {
                throw $this->createIdAttributeNotFoundException($element, $file);
            }

            $workflowBuilder->addExclusiveGateway(
                $element->getAttribute('id'),
                $flowObjectRoles[$element->getAttribute('id')],
                $element->hasAttribute('name') ? $element->getAttribute('name') : null,
                $element->hasAttribute('default') ? $element->getAttribute('default') : null
            );
        }

        foreach ($document->getElementsByTagNameNs('http://www.omg.org/spec/BPMN/20100524/MODEL', 'endEvent') as $element) {
            if (!$element->hasAttribute('id')) {
                throw $this->createIdAttributeNotFoundException($element, $file);
            }

            $workflowBuilder->addEndEvent($element->getAttribute('id'), $flowObjectRoles[$element->getAttribute('id')], $element->hasAttribute('name') ? $element->getAttribute('name') : null);
        }

        foreach ($document->getElementsByTagNameNs('http://www.omg.org/spec/BPMN/20100524/MODEL', 'sequenceFlow') as $element) {
            if (!$element->hasAttribute('id')) {
                throw $this->createIdAttributeNotFoundException($element, $file);
            }

            $condition = null;
            foreach ($element->getElementsByTagNameNs('http://www.omg.org/spec/BPMN/20100524/MODEL', 'conditionExpression') as $childElement) {
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
     * @param string      $file
     *
     * @return IdAttributeNotFoundException
     */
    private function createIdAttributeNotFoundException(\DOMElement $element, $file)
    {
        return new IdAttributeNotFoundException(sprintf('The id attribute of the "%s" element is not found in "%s" on line %d', $element->tagName, $file, $element->getLineNo()));
    }
}
