<?php

declare(strict_types=1);

namespace Tlc\ReportBundle\Entity;

class SummaryStatMaterial extends SummaryStat
{
    public function __construct(
        string $name,
        $value,
        private int $count,
        private string $suffixValue = '',
        private string $suffixCount = '',
        private int $precision = 3
    ) {
        parent::__construct($name, $value, $suffixValue);
        $this->suffixCount = $suffixCount;
    }

    public function getValue()
    {
        return round($this->value, $this->precision);
    }
    public function getCount(): int
    {
        return $this->count;
    }

    public function getSuffixCount(): string
    {
        return $this->suffixCount;
    }
}
