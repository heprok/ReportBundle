<?php

declare(strict_types=1);

namespace Tlc\ReportBundle\Dataset;

final class ChartDataset extends AbstractDataset
{
    public function __construct(
        private string $label,
        private string $backgroundColor = '#007bff',
        private string $borderColor = '#000',
        private string $pointBorderColor = 'white',
        private string $pointBackgroundColor = 'white',
        private int $borderWidth = 1,
        private bool $fill = false,
    ) {
        $this->data = [];
    }

    public function __serialize(): array
    {
        return [
            'label' => $this->getLabel(),
            'fill' => $this->getFill(),
            'borderColor' => $this->getBorderColor(),
            'backgroundColor' => $this->getBackgroundColor(),
            'pointBorderColor' => $this->getPointBorderColor(),
            'pointBackgroundColor' => $this->getPointBackgroundColor(),
            'borderWidth' => $this->getBorderWidth(),
            'data' => $this->getData(),
        ];
    }

    public function setData(array $data)
    {
        $this->data = $data;
    }

    public function getBackgroundColor(): string
    {
        return $this->backgroundColor;
    }

    public function setBackgroundColor(string $backgroundColor): self
    {
        $this->backgroundColor = $backgroundColor;

        return $this;
    }

    public function getBorderColor(): string
    {
        return $this->borderColor;
    }

    public function setBorderColor(string $borderColor): self
    {
        $this->borderColor = $borderColor;

        return $this;
    }

    public function getPointBorderColor(): string
    {
        return $this->pointBorderColor;
    }

    public function setPointBorderColor(string $pointBorderColor): self
    {
        $this->pointBorderColor = $pointBorderColor;

        return $this;
    }

    public function getPointBackgroundColor(): string
    {
        return $this->pointBackgroundColor;
    }

    public function setPointBackgroundColor(string $pointBackgroundColor): self
    {
        $this->pointBackgroundColor = $pointBackgroundColor;

        return $this;
    }

    public function getBorderWidth(): int
    {
        return $this->borderWidth;
    }

    public function setBorderWidth(int $borderWidth): self
    {
        $this->borderWidth = $borderWidth;

        return $this;
    }

    public function getLabel(): string
    {
        return $this->label;
    }

    public function setLabel(string $label): self
    {
        $this->label = $label;

        return $this;
    }

    public function getFill(): bool
    {
        return $this->fill;
    }

    public function setFill(bool $fill): self
    {
        $this->fill = $fill;

        return $this;
    }
}
