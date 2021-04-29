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

/**
 * @since Class available since Release 2.0.0
 */
interface ProcessDefinitionRepositoryInterface
{
    /**
     * @param ProcessDefinitionInterface $definition
     *
     * @return ProcessDefinitionInterface}
     */
    public function add(ProcessDefinitionInterface $definition);

    /**
     * @param string $id
     *
     * @return ProcessDefinitionInterface
     */
    public function getLatestById(string $id);

    /**
     * @param string $name
     *
     * @return ProcessDefinitionInterface
     */
    public function getLatestByName(string $name);

    /**
     * @param string $id
     * @param int    $version
     *
     * @return ProcessDefinitionInterface
     */
    public function getVersionById(string $id, int $version);

    /**
     * @param string $name
     * @param int    $version
     *
     * @return ProcessDefinitionInterface
     */
    public function getVersionByName(string $name, int $version);
}
