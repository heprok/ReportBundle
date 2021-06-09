<?php

declare(strict_types=1);

namespace Tlc\ReportBundle\Dataset;

class SummaryPdfDataset extends PdfDataset
{

    /**
     * @param Column[] $columns
     */
    public function __construct(
        private string $nameSummary,
        array $columns,
        ?string $textSubTotal = null,
        ?string $textTotal = null,

    ) {
        parent::__construct(columns: $columns, textSubTotal: $textSubTotal, textTotal: $textTotal);
    }

    /**
     * Get the value of nameSummary
     */
    public function getNameSummary(): string
    {
        return $this->nameSummary;
    }
}
