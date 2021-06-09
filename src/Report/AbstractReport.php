<?php

declare(strict_types=1);

namespace Tlc\ReportBundle\Report;

use Tlc\ReportBundle\Dataset\AbstractDataset;
use DatePeriod;

abstract class AbstractReport
{
    // const DECIMAL_FORMAT = 4;
    const FORMAT_DATE_TIME = 'd.m.Y H:i:s';
    const FORMAT_DATE_FROM_DB = 'Y-m-d H:i:s';
    const FORMAT_DATE_SECOND_TIMEZONE_FROM_DB = self::FORMAT_DATE_FROM_DB . '.uP';

    private array $datasets = [];
    protected array $labels = [];
    protected array $summaryStats = [];
    protected DatePeriod $period;
    /**
     * @param People[] $peoples
     */
    protected array $peoples;
    protected array $sqlWhere;

    abstract public function getNameReport(): string;
    abstract protected function updateDataset(): bool;

    /**
     * @return SummaryStat[]
     */
    abstract public function getSummaryStats(): array;
    /**
     * @return SummaryStatMaterial[]
     */
    abstract public function getSummaryStatsMaterial(): array;
    /**
     *
     * @param DatePeriod $period
     * @param People[] $peoples
     */
    public function __construct(DatePeriod $period, array $peoples = [], array $sqlWhere = [])
    {
        $this->period = $period;
        $this->peoples = $peoples;
        $this->sqlWhere = $sqlWhere;
    }

    public function addDataset(AbstractDataset $dataset): self
    {
        $this->datasets[] = $dataset;
        return $this;
    }

    public function getPeriod(): DatePeriod
    {
        return $this->period;
    }

    public function init(): bool
    {
        if (empty($this->datasets))
            return $this->updateDataset();

        return true;
    }
    public function getSqlWhere()
    {
        return $this->sqlWhere;
    }
    /**
     * @return People[]
     */
    public function getPeoples(): array
    {
        return $this->peoples;
    }
    /**
     * @return AbstractDataset[] Returns an array of AbstractDataset objects
     */
    public function getDatasets(): array
    {
        return $this->datasets;
    }

    /**
     * @return string[] Return an array string
     */
    public function getLabels(): array
    {
        return $this->labels;
    }

    public function setLabels(array $labels): self
    {
        $this->labels = $labels;
        return $this;
    }

    public function getNameReportTranslit()
    {
        $str = transliterator_transliterate('Latin-ASCII', transliterator_transliterate('Latin', $this->getNameReport()));
        $str = str_replace(' ', '_', $str);
        return $str;
    }
}
