<?php

namespace Scheduler\Job;

use DateTimeInterface;
use Recurr\Rule as RecurrRule;
use Recurr\Recurrence;
use Recurr\Transformer\ArrayTransformer;
use Recurr\Transformer\Constraint\BetweenConstraint;

/**
 * Class RRule
 * @package Scheduler\Job
 * @author Aleh Hutnikau, <goodnickoff@gmail.com>
 */
class RRule extends AbstractRule
{

    /**
     * @param DateTimeInterface $from
     * @param DateTimeInterface $to
     * @param boolean $inc
     * @throws
     * @return DateTimeInterface[]
     */
    public function getRecurrences(DateTimeInterface $from, DateTimeInterface $to, $inc = true)
    {
        $rRule = new RecurrRule($this->getRrule(), $this->getStartDate());
        $rRuleTransformer = new ArrayTransformer();
        $constraint = new BetweenConstraint($from, $to, $inc);
        $recurrenceCollection = $rRuleTransformer->transform($rRule, $constraint);
        $result = [];
        /** @var Recurrence $recurrence */
        foreach ($recurrenceCollection as $recurrence) {
            $result[] = $recurrence->getStart();
        }
        return $result;
    }
}