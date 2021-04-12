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

use PHPMentors\Workflower\Workflow\Element\ConditionalInterface;
use PHPMentors\Workflower\Workflow\Element\FlowObjectInterface;
use PHPMentors\Workflower\Workflow\Element\TransitionalInterface;

interface ActivityInterface extends FlowObjectInterface, TransitionalInterface, ConditionalInterface
{
    const STATE_INACTIVE = 'inactive';
    const STATE_READY = 'ready';
    const STATE_ACTIVE = 'active';
    const STATE_COMPLETED = 'completed';
    const STATE_FAILED = 'failed';
    const STATE_CLOSED = 'closed';

    /**
     * @return ItemsCollectionInterface
     */
    public function getWorkItems();

    /**
     * @param ItemsCollectionInterface $collection
     * @return void
     */
    public function setWorkItems(ItemsCollectionInterface $collection);

    /**
     * @return void
     */
    public function createWork(): void;

    /**
     * @return void
     */
    public function completeWork(): void;

    /**
     * @return string
     */
    public function getCurrentState();

    /**
     * @return bool
     */
    public function isClosed();

    /**
     * @return bool
     */
    public function isFailed();
}
