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

class Bpmn2Reader
{
    /**
     * @param string $file
     *
     * @return ProcessDefinition[]
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
     * @return ProcessDefinition[]
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
     * @param \DOMDocument $document
     * @param int|string   $workflowId
     *
     * @return ProcessDefinition[]
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

        $processes = [];
        $globalData = [
            'messages' => [],
            'operations' => []
        ];

        foreach ($document->getElementsByTagNameNs('http://www.omg.org/spec/BPMN/20100524/MODEL', 'message') as $element) {
            if (!$element->hasAttribute('id')) {
                throw new IdAttributeNotFoundException(sprintf('Element "%s" has no id', $element->tagName));
            }

            $globalData['messages'][$element->getAttribute('id')] = $element->getAttribute('name');
        }

        foreach ($document->getElementsByTagNameNs('http://www.omg.org/spec/BPMN/20100524/MODEL', 'operation') as $element) {
            if (!$element->hasAttribute('id')) {
                throw new IdAttributeNotFoundException(sprintf('Element "%s" has no id', $element->tagName));
            }

            $globalData['operations'][$element->getAttribute('id')] = $element->getAttribute('name');
        }

        foreach ($document->getElementsByTagNameNs('http://www.omg.org/spec/BPMN/20100524/MODEL', 'process') as $element) {
            $processes[] = new ProcessDefinition($this->readProcess($globalData, $element));
        }

        return $processes;
    }

    /**
     * @param array $globalData
     * @param \DOMElement $element
     * @return array
     */
    private function readProcess(array $globalData, \DOMElement $rootElement)
    {
        if (!$rootElement->hasAttribute('id')) {
            throw new IdAttributeNotFoundException(sprintf('Element "%s" has no id', $rootElement->tagName));
        }

        $process = [
            'id' => $rootElement->getAttribute('id'),
            'name' => $rootElement->hasAttribute('name') ? $rootElement->getAttribute('name') : null,
            'roles' => [],
            'objectRoles' => []
        ];

        foreach ($rootElement->getElementsByTagNameNs('http://www.omg.org/spec/BPMN/20100524/MODEL', 'lane') as $element) {
            if (!$element->hasAttribute('id')) {
                throw new IdAttributeNotFoundException(sprintf('Element "%s" has no id', $rootElement->tagName));
            }

            $id = $element->getAttribute('id');

            $process['roles'][] = [
                'id' => $id,
                'name' => $element->hasAttribute('name') ? $element->getAttribute('name') : null
            ];

            foreach ($element->getElementsByTagNameNs('http://www.omg.org/spec/BPMN/20100524/MODEL', 'flowNodeRef') as $childElement) {
                $process['objectRoles'][$childElement->nodeValue] = $id;
            }
        }

        if (count($process['roles']) == 0) {
            $process['roles'][] = [
                'id' => Workflow::DEFAULT_ROLE_ID
            ];
        }

        $process['startEvents'] = $this->readEvents($globalData, $process, $rootElement->getElementsByTagNameNs('http://www.omg.org/spec/BPMN/20100524/MODEL', 'startEvent'));
        $process['endEvents'] = $this->readEvents($globalData, $process, $rootElement->getElementsByTagNameNs('http://www.omg.org/spec/BPMN/20100524/MODEL', 'endEvent'));

        $process['tasks'] = $this->readTasks($globalData, $process, $rootElement->getElementsByTagNameNs('http://www.omg.org/spec/BPMN/20100524/MODEL', 'task'));
        $process['userTasks'] = $this->readTasks($globalData, $process, $rootElement->getElementsByTagNameNs('http://www.omg.org/spec/BPMN/20100524/MODEL', 'userTask'));
        $process['manualTasks'] = $this->readTasks($globalData, $process, $rootElement->getElementsByTagNameNs('http://www.omg.org/spec/BPMN/20100524/MODEL', 'manualTask'));
        $process['serviceTasks'] = $this->readTasks($globalData, $process, $rootElement->getElementsByTagNameNs('http://www.omg.org/spec/BPMN/20100524/MODEL', 'serviceTask'));
        $process['sendTasks'] = $this->readTasks($globalData, $process, $rootElement->getElementsByTagNameNs('http://www.omg.org/spec/BPMN/20100524/MODEL', 'sendTask'));
        $process['callActivities'] = $this->readTasks($globalData, $process, $rootElement->getElementsByTagNameNs('http://www.omg.org/spec/BPMN/20100524/MODEL', 'callActivity'));

        foreach($rootElement->getElementsByTagNameNs('http://www.omg.org/spec/BPMN/20100524/MODEL', 'subProcess') as $element) {
            $task = $this->readTask($globalData, $process, $element);
            $task['processDefinition'] = $this->readProcess($globalData, $element);
            $process['subProcesses'][] = $task;
        }

        $process['exclusiveGateways'] = $this->readGateways($globalData, $process, $rootElement->getElementsByTagNameNs('http://www.omg.org/spec/BPMN/20100524/MODEL', 'exclusiveGateway'));
        $process['parallelGateways'] = $this->readGateways($globalData, $process, $rootElement->getElementsByTagNameNs('http://www.omg.org/spec/BPMN/20100524/MODEL', 'parallelGateway'));
        //$process['inclusiveGateways'] = $this->readGateways($globalData, $process, $rootElement->getElementsByTagNameNs('http://www.omg.org/spec/BPMN/20100524/MODEL', 'inclusiveGateway'));

        $process['sequenceFlows'] = $this->readSequenceFlows($globalData, $process, $rootElement->getElementsByTagNameNs('http://www.omg.org/spec/BPMN/20100524/MODEL', 'sequenceFlow'));

        return $process;
    }

    /**
     * @param array $globalData
     * @param array $process
     * @param \DOMNodeList $nodes
     */
    private function readTasks(array $globalData, array $process, $nodes)
    {
        $items = [];

        foreach ($nodes as $element) {
            $items[] = $this->readTask($globalData, $process, $element);
        }

        return $items;
    }

    /**
     * @param array $globalData
     * @param array $process
     * @param \DOMElement $element
     */
    private function readTask(array $globalData, array $process, $element)
    {
        if (!$element->hasAttribute('id')) {
            throw new IdAttributeNotFoundException(sprintf('Element "%s" has no id', $element->tagName));
        }

        $id = $element->getAttribute('id');
        $message = $element->hasAttribute('messageRef') ? $globalData['messages'][$element->getAttribute('messageRef')] : null;
        $operation = $element->hasAttribute('operationRef') ? $globalData['operations'][$element->getAttribute('operationRef')] : null;
        $defaultSequenceFlowId = $element->hasAttribute('default') ? $element->getAttribute('default') : null;
        $calledElement = $element->hasAttribute('calledElement') ? $element->getAttribute('calledElement') : null;
        $multiInstance = null;
        $sequential = null;
        $completionCondition = null;

        foreach ($element->getElementsByTagNameNs('http://www.omg.org/spec/BPMN/20100524/MODEL', 'multiInstanceLoopCharacteristics') as $childElement) {
            $multiInstance = true;
            $sequential = $childElement->hasAttribute('isSequential') ? ($childElement->getAttribute('isSequential') === 'true') : false;
            foreach ($childElement->getElementsByTagNameNs('http://www.omg.org/spec/BPMN/20100524/MODEL', 'completionCondition') as $conditionElement) {
                $completionCondition = $conditionElement->nodeValue;
            }
        }

        $config = [
            'id' => $id,
            'name' => $element->hasAttribute('name') ? $element->getAttribute('name') : null,
            'roleId' => $this->provideRoleIdForFlowObject($process['objectRoles'], $id)
        ];

        if ($multiInstance !== null) {
            $config['multiInstance'] = $multiInstance;
        }
        if ($sequential !== null) {
            $config['sequential'] = $sequential;
        }
        if ($completionCondition !== null) {
            $config['completionCondition'] = $completionCondition;
        }
        if ($defaultSequenceFlowId !== null) {
            $config['defaultSequenceFlowId'] = $defaultSequenceFlowId;
        }
        if ($message !== null) {
            $config['message'] = $message;
        }
        if ($operation !== null) {
            $config['operation'] = $operation;
        }
        if ($calledElement !== null) {
            $config['calledElement'] = $calledElement;
        }

        return $config;
    }

    /**
     * @param array $globalData
     * @param array $process
     * @param \DOMNodeList $nodes
     */
    private function readGateways(array $globalData, array $process, $nodes)
    {
        $items = [];

        foreach ($nodes as $element) {
            if (!$element->hasAttribute('id')) {
                throw new IdAttributeNotFoundException(sprintf('Element "%s" has no id', $element->tagName));
            }

            $id = $element->getAttribute('id');
            $defaultSequenceFlowId = $element->hasAttribute('default') ? $element->getAttribute('default') : null;

            $config = [
                'id' => $id,
                'name' => $element->hasAttribute('name') ? $element->getAttribute('name') : null,
                'roleId' => $this->provideRoleIdForFlowObject($process['objectRoles'], $id)
            ];

            if ($defaultSequenceFlowId !== null) {
                $config['defaultSequenceFlowId'] = $defaultSequenceFlowId;
            }

            $items[] = $config;
        }

        return $items;
    }

    /**
     * @param array $globalData
     * @param array $process
     * @param \DOMNodeList $nodes
     */
    private function readEvents(array $globalData, array $process, $nodes)
    {
        $items = [];

        foreach ($nodes as $element) {
            if (!$element->hasAttribute('id')) {
                throw new IdAttributeNotFoundException(sprintf('Element "%s" has no id', $element->tagName));
            }

            $id = $element->getAttribute('id');
            $defaultSequenceFlowId = $element->hasAttribute('default') ? $element->getAttribute('default') : null;
            $name = $element->hasAttribute('name') ? $element->getAttribute('name') : null;

            $config = [
                'id' => $id,
                'roleId' => $this->provideRoleIdForFlowObject($process['objectRoles'], $id)
            ];

            if ($name !== null) {
                $config['name'] = $name;
            }
            if ($defaultSequenceFlowId !== null) {
                $config['defaultSequenceFlowId'] = $defaultSequenceFlowId;
            }

            $items[] = $config;
        }

        return $items;
    }

    /**
     * @param array $globalData
     * @param array $process
     * @param \DOMNodeList $nodes
     */
    private function readSequenceFlows(array $globalData, array $process, $nodes)
    {
        $items = [];

        foreach ($nodes as $element) {
            if (!$element->hasAttribute('id')) {
                throw new IdAttributeNotFoundException(sprintf('Element "%s" has no id', $element->tagName));
            }

            $id = $element->getAttribute('id');
            $name = $element->hasAttribute('name') ? $element->getAttribute('name') : null;
            $condition = null;
            foreach ($element->getElementsByTagNameNs('http://www.omg.org/spec/BPMN/20100524/MODEL', 'conditionExpression') as $childElement) {
                $condition = $childElement->nodeValue;
                break;
            }

            $config = [
                'id' => $id,
                'source' => $element->getAttribute('sourceRef'),
                'destination' => $element->getAttribute('targetRef'),
            ];

            if ($name !== null) {
                $config['name'] = $name;
            }
            if ($condition !== null) {
                $config['condition'] = $condition;
            }

            $items[] = $config;
        }

        return $items;
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
