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

namespace PHPMentors\Workflower\Workflow\EventDefinition;

class TimerEventDefinition implements EventDefinitionInterface
{
    /**
     * @var string
     */
    private $timeDuration;

    /**
     * @var string
     */
    private $timeCycle;

    /**
     * @param string    $timeDuration
     * @param string    $timeCycle
     */
    public function __construct($timeDuration, $timeCycle)
    {
        $this->timeDuration = $timeDuration;
        $this->timeCycle = $timeCycle;
    }

    /**
     * @return string
     */
    public function getTimeDuration()
    {
        return $this->timeDuration;
    }

    /**
     * @return string
     */
    public function getTimeCycle()
    {
        return $this->timeCycle;
    }
}
