<?php

declare(strict_types=1);

namespace Tlc\ReportBundle\Entity;

class Column
{

    public function  __construct(
        public string $title,
        public int $precentWidth,
        public bool $group = false,
        public string $align = 'C',
        public bool $total = false
    ) {
    }

    /**
     * Get the value of precentWidth
     */
    public function getPrecentWidth(): int
    {
        return $this->precentWidth;
    }

    /**
     * Get the value of title
     */
    public function getTitle(): string
    {
        return $this->title;
    }

    /**
     * Get the value of align
     */
    public function getAlign(): string
    {
        return $this->align;
    }

    /**
     * Get the value of total
     */
    public function isTotal(): bool
    {
        return $this->total;
    }

    /**
     * Get the value of group
     */
    public function isGroup(): bool
    {
        return $this->group;
    }
}
