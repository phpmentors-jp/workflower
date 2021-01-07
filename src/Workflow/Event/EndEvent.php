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

namespace PHPMentors\Workflower\Workflow\Event;

use PHPMentors\Workflower\Workflow\Element\Token;

class EndEvent extends Event implements EventInterface
{
    /**
     * @var Token
     *
     * @since Property available since Release 2.0.0
     */
    private $token;

    /**
     * @var \DateTime
     *
     * @since Property available since Release 2.0.0
     */
    private $endDate;

    /**
     * {@inheritdoc}
     *
     * @since Method available since Release 2.0.0
     */
    public function serialize()
    {
        return serialize([
            get_parent_class($this) => parent::serialize(),
            'token' => $this->token,
            'endDate' => $this->endDate,
        ]);
    }

    /**
     * {@inheritdoc}
     */
    public function getToken(): Token
    {
        return $this->token;
    }

    /**
     * {@inheritdoc}
     */
    public function attachToken(Token $token): void
    {
        $this->token = $token;
        $this->endDate = new \DateTime();
    }

    /**
     * {@inheritdoc}
     */
    public function detachToken(Token $token): void
    {
        assert($this->token->getId() == $token->getId());
    }

    /**
     * @return \DateTime|null
     */
    public function getEndDate(): ?\DateTime
    {
        return $this->endDate;
    }
}
