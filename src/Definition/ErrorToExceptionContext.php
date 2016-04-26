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

class ErrorToExceptionContext
{
    /**
     * @var int
     */
    private $errorReportingLevel;

    /**
     * @var \Closure
     */
    private $target;

    /**
     * @param int      $errorReportingLevel
     * @param \Closure $target
     */
    public function __construct($errorReportingLevel, \Closure $target)
    {
        $this->errorReportingLevel = $errorReportingLevel;
        $this->target = $target;
    }

    /**
     * @return mixed
     *
     * @throws \Exception
     */
    public function invoke()
    {
        set_error_handler(function ($code, $message, $file, $line) {
            throw new \ErrorException($message, 0, $code, $file, $line);
        }, $this->errorReportingLevel === null ? error_reporting() : $this->errorReportingLevel
        );

        try {
            $returnValue = call_user_func($this->target);
        } catch (\Exception $e) {
            restore_error_handler();
            throw $e;
        }

        restore_error_handler();

        return $returnValue;
    }
}
