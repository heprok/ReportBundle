<?php

declare(strict_types=1);

namespace Tlc\ReportBundle\Entity;

class SummaryStat
{
    public function __construct(
        private string $name,
        protected $value,
        private string $suffix = ''
    ) {
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getValue()
    {
        return $this->value;
    }

    public function getSuffix(): string
    {
        return $this->suffix;
    }
}
