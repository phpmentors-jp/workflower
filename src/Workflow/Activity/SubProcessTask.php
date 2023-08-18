<?php

namespace PHPMentors\Workflower\Workflow\Activity;

/**
 * @since Class available since Release 2.0.0
 */
class SubProcessTask extends ProcessTask
{
    private $triggeredByEvent = false;

    public function __construct(array $config = [])
    {
        parent::__construct($config);

        foreach ($config as $name => $value) {
            if (property_exists(self::class, $name)) {
                $this->{$name} = $value;
            }
        }
    }
}
