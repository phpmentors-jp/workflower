<?php

namespace PHPMentors\Workflower\Workflow\Event;

use DateTime;

class IntermediateCatchEvent extends Event
{
    protected string $timerEventDuration;

    public function getEndDate(): DateTime
    {
        // treat as seconds by default
        if (is_numeric($this->timerEventDuration)) {
            return (new DateTime("+{$this->timerEventDuration} seconds"));
        }

        return new DateTime($this->timerEventDuration);
    }

    /**
     * {@inheritdoc}
     */
    public function end(): void
    {
        $this->getProcessInstance()->wait($this);
    }
}
