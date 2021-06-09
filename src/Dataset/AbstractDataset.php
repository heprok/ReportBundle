<?php

declare(strict_types=1);

namespace Tlc\ReportBundle\Dataset;

abstract class AbstractDataset
{
    protected array $data;
    const TIME_FORMAT = 'H:i:s';
    const DURATION_TIME_FROMAT = '%H:%I:%S';
    const DURATION_DAY_TIME_FROMAT = '%d д. ' . self::DURATION_TIME_FROMAT;
    const DURATION_MOUNT_DAY_TIME_FROMAT = '%m м. ' . self::DURATION_DAY_TIME_FROMAT;

    public function getData(): array
    {
        return $this->data;
    }
}
