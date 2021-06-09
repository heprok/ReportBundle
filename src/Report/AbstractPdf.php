<?php

namespace Tlc\ReportBundle\Report;

use Tlc\ReportBundle\Dataset\PdfDataset;
use Tlc\ReportBundle\Dataset\SummaryPdfDataset;
use Tlc\ReportBundle\Entity\SummaryStat;
use Tlc\ReportBundle\Entity\SummaryStatMaterial;
use DateInterval;
use DatePeriod;
use Symfony\Component\Asset\Package;
use Symfony\Component\Asset\VersionStrategy\EmptyVersionStrategy;
use TCPDF;

abstract class AbstractPdf extends TCPDF
{
    protected AbstractReport $report;
    protected PdfDataset $currentDataset;
    private bool $duplicateHeader;
    const COLOR_GRAY = 238;
    const DATETIME_FORMAT = 'd.m.Y H:i:s';
    const TIME_FORMAT = 'H:i:s';
    const DATE_FORMAT = 'd.m.Y';
    const TIME_FORMAT_FOR_INTERVAL = '%H:%I:%S';
    const DATETIME_FORMAT_FOR_DOWNLOAD = 'd-m-Y H:i';
    const REG_EXP_FOR_TOTAL = '/([а-яА-Я\_\№ё\-a-zA-Z\s\d\(\)\.\:\,\×\⨯]+){(\d)}/um';
    const MARGIN_LEFT = 20;
    const MARGIN_TOP = 28;
    const WIDTH_LOGO = 14;
    const WIDTH_LOGO_BIG = 70;
    const HEIGHT_LOGO_BIG = 50;
    const PRECISION_FOR_FLOAT = 3;
    const COLORSRGB = [
        "greenLight" => [224, 241, 224],
        'greenDark' => [0, 140, 0],
        'gray' => [227, 227, 227],
        'black' => [0, 0, 0]
    ];

    abstract protected function getHeightCell(): int;
    /**
     * Кегль для текста
     * @return integer
     */

    abstract protected function getPointFontText(): int;
    /**
     * Кегль для шапки
     * @return integer
     */
    abstract protected function getPointFontHeader(): int;


    public function __constructor(
        string $orientation = 'P',
        bool $duplicateHeader = false,
    ) {
        parent::__construct($orientation);
        $this->SetCreator('TechoLesCom');
        $this->SetMargins(self::MARGIN_LEFT, self::MARGIN_TOP);
        $this->SetAutoPageBreak(true, 20);
        $this->SetTitle('Отчёт ' . $this->report->getNameReport() . ' ' . $this->getPeriod()->getStartDate()->format(self::DATETIME_FORMAT));
        $this->startPageGroup();
        $this->setPrintFooter(false);
        $this->setPrintHeader(false);
        $this->AddPage();
        $this->SetFont('dejavusans', '', 11);
        $this->SetXY(self::MARGIN_LEFT, self::MARGIN_TOP);
        $this->paintTitle($orientation == 'P');
        if (count($this->report->getDatasets()) > 1)
            $this->AddPage();

        $datasets = $this->report->getDatasets();
        foreach ($datasets as $dataset) {
            if (get_class($dataset) === SummaryPdfDataset::class) {
                if ($dataset instanceof SummaryPdfDataset) {
                    $this->duplicateHeader = false;
                    $this->currentDataset = $dataset;
                    $this->paintTableSummary();
                }
            }
            if (get_class($dataset) === PdfDataset::class)
                $mainDataset = $dataset;
        }
        $this->currentDataset = $mainDataset;
        $this->endPage();
        $this->startPageGroup();
        $this->duplicateHeader = $duplicateHeader;
        $this->setPrintFooter(true);
        $this->setPrintHeader(true);
        $this->AddPage();
        $this->paintTable();
    }


    /**
     * Возращает ширины для столбцов в мм
     * @return array
     */
    protected function getPuntForColumns(array $widthColums): array
    {
        $widthColumnsInPunt = [];

        foreach ($widthColums as $widthColumn) {
            $widthColumnsInPunt[] = floor(($this->getPageWidth() - self::MARGIN_LEFT - self::MARGIN_LEFT) * $widthColumn / 100);
        }
        return $widthColumnsInPunt;
    }


    protected function getWidthColumnForSpan(int $rowspan): int
    {
        $puntColumns = $this->getPuntForColumns($this->currentDataset->getWidthInPrecent());
        $result = 0;
        for ($i = 0; $i < $rowspan; $i++) {
            $result += floor($puntColumns[$i]);
        }
        return $result;
    }


    public function render()
    {
        return $this->Output($this->getNameFile());
    }

    public function header()
    {
        $package = new Package(new EmptyVersionStrategy());
        // dd($package->getUrl('build/logo.png'));
        $this->SetFont('dejavusans', '', 14);
        $image_file = $package->getUrl('build/images/logosmall.svg');
        $this->ImageSVG($image_file, self::MARGIN_LEFT, 3, self::WIDTH_LOGO, 15, 'www.techno-les.com', 'L', false, 0, 0);
        $this->setX(self::WIDTH_LOGO + self::MARGIN_LEFT + 15);
        $this->Cell(0, 20, 'Отчёт ' . $this->getNameReport(), 0, 0, 'L', 0, '',  0, false, 'М', 'М');
        $this->Ln(10, true);
        $this->SetFont('dejavusans', '', 10);
        $this->SetY(self::MARGIN_TOP / 2 / 2, false);
        $this->Cell($this->getPageWidth() - self::MARGIN_LEFT - self::MARGIN_LEFT / 2, 0, 'с ' . $this->getPeriod()->getStartDate()->format(self::DATETIME_FORMAT), 0, 0, 'R', 0, '',  0, false, 'М', 'М');
        $this->ln();
        $this->SetY(self::MARGIN_TOP / 2, false);
        $this->Cell($this->getPageWidth() - self::MARGIN_LEFT - self::MARGIN_LEFT / 2, 0, 'до ' . $this->getPeriod()->getEndDate()->format(self::DATETIME_FORMAT), 0, 0, 'R', 0, '',  0, false, 'М', 'М');


        $this->SetFillColor(self::COLOR_GRAY);
        $this->SetTextColor(0);
        $this->SetDrawColor(0, 0, 0);

        $this->SetLineWidth(0.3);
        $this->SetFont('', 'B', $this->getPointFontHeader());
        // Header
        $this->Ln();
        $this->ln();
        if ($this->duplicateHeader) {
            $header = $this->currentDataset->getNameColumns();
            $puntColumns = $this->getPuntForColumns($this->currentDataset->getWidthInPrecent());
            $num_headers = count($header);
            for ($i = 0; $i < $num_headers; ++$i) {
                $this->Cell($puntColumns[$i], $this->getHeightCell(), $header[$i], 1, 0, 'C', 1);
            }
        }
        $this->Ln();

        // $this->SetY(29);
        // $this->SetLineStyle(array('width' => 2, 'color' => ['#fff']));
        // $this->Line(20, 29, $this->getPageWidth() - 20, $this->getPageHeight() - 20 );
    }

    public function footer()
    {
        $this->SetY(-15);
        $this->SetFont('dejavusans', 'I', 8);
        $this->Cell(0, 10, 'Страница ' . $this->getAliasNumPage() . '/' . $this->getAliasNbPages(), 0, false, 'C', 0, '', 0, false, 'T', 'M');
    }



    public function setNameReport(string $name_report)
    {
        $this->name_report = $name_report;
    }

    protected function setReport(AbstractReport $report): self
    {
        $this->report = $report;
        return $this;
    }
    public function getNameReport(): string
    {
        return $this->report->getNameReport();
    }

    public function getPeriod(): DatePeriod
    {
        return $this->report->getPeriod();
    }

    protected function getNameFile(): string
    {
        return $this->report->getNameReportTranslit() . '_' . $this->report->getPeriod()->getStartDate()->format(self::DATETIME_FORMAT_FOR_DOWNLOAD) . '.pdf';
    }


    private function paintTableSummary()
    {
        $dataset = $this->currentDataset;
        if ($dataset instanceof SummaryPdfDataset) {
            $this->SetFillColor(self::COLOR_GRAY);
            $this->SetTextColor(0);
            $this->SetDrawColor(0, 0, 0);
            $this->SetFont('', 'B', 14);
            $widthPage = $this->getPageWidth();
            $this->Cell($widthPage - self::MARGIN_LEFT * 2, 10, $dataset->getNameSummary(), 0, 1, 'C');

            $this->paintTable();
            $this->ln();
        }
    }

    /**
     * Рисует данные в таблице
     *
     * @param string[] $header
     * @param PdfDataset[] $data
     * @return void
     */
    protected function paintTable()
    {
        // $count_dataset = count($data);
        $headers = $this->currentDataset->getNameColumns();
        $count_labels = count($headers);
        $puntColumns = $this->getPuntForColumns($this->currentDataset->getWidthInPrecent());
        $alignForColmns = $this->currentDataset->getAlignForColumns();
        // Colors, line width and bold font
        $this->SetFillColor(self::COLOR_GRAY);
        $this->SetTextColor(0);
        $this->SetDrawColor(0, 0, 0);
        $this->SetLineWidth(0.3);
        $this->SetFont('', 'B', $this->getPointFontHeader());
        // Header
        if (!$this->duplicateHeader) {
            for ($i = 0; $i < $count_labels; ++$i) {
                $this->Cell($puntColumns[$i], $this->getHeightCell(), $headers[$i], 1, 0, 'C', 1);
            }
            $this->Ln();
        }

        // Color and font restoration
        $this->SetFillColor(224, 241, 224);
        $this->SetTextColor(0);
        $this->SetFont('', '', $this->getPointFontText());
        // Data
        // $fill = 0;
        if ($this->currentDataset instanceof PdfDataset) {
            $keys_sub_total = $this->currentDataset->getKeysSubTotal();
            $data = $this->currentDataset->getData();
            foreach ($data as $key => $row) {
                if (!in_array($key, $keys_sub_total)) {
                    for ($j = 0; $j < $count_labels; $j++) {
                        if ($row[$j] instanceof DateInterval) {
                            $this->Cell($puntColumns[$j], $this->getHeightCell(), $row[$j]->format(self::TIME_FORMAT_FOR_INTERVAL), 1, 0, $alignForColmns[$j], 0);
                        } elseif (is_float($row[$j])) {
                            $this->Cell($puntColumns[$j], $this->getHeightCell(), number_format($row[$j], self::PRECISION_FOR_FLOAT, '.', ' '), 1, 0, $alignForColmns[$j], 0);
                        } else {
                            $this->Cell($puntColumns[$j], $this->getHeightCell(), $row[$j], 1, 0, $alignForColmns[$j], 0);
                        }
                    }
                    $this->Ln();
                } else {
                    $reg = self::REG_EXP_FOR_TOTAL;
                    preg_match_all($reg, $row, $matches, PREG_SET_ORDER, 0);
                    $buff['currentColumn'] = 0;
                    foreach ($matches as $key => $match) {
                        $rowspan = $match[2];
                        $text = $match[1];
                        if ($rowspan >= 2) {
                            $widthColumn = $this->getWidthColumnForSpan($rowspan);
                            $this->Cell($widthColumn, $this->getHeightCell(), $text, 1, 0, 'R', 1);
                            // $buff['currentColumn'] += $widthColumn;
                            $buff['currentColumn'] += $rowspan;
                        } else {
                            //Если выводится интвервал ( 1 д. 03:00:00 ), то пропускается, иначе форматирует float до PRECISION_FOR_FLOAT
                            if (!$this->isInterval($text))
                                $text = strripos($text, '.') ? number_format($text, self::PRECISION_FOR_FLOAT, '.', ' ') : $text;
                            $this->Cell($puntColumns[$buff['currentColumn'] + $rowspan - 1], $this->getHeightCell(), $text, 1, 0, $alignForColmns[$buff['currentColumn'] + $rowspan - 1], 1);
                            $buff['currentColumn'] += $rowspan;
                        }
                    }

                    $this->Ln();
                }
            }
        }
        // $this->setPage(1);
        // $this->SetY(self::MARGIN_LEFT + 10);
        // $this->Cell(array_sum($puntColumns), 0, '', 'T');
    }
    private function isInterval(string $interval): bool
    {
        $isContainDay = (bool)stripos($interval, 'д.');
        $isContainMounth = (bool)stripos($interval, 'м.');
        $isContainTime = (bool)stripos($interval, ':');
        return $isContainDay || $isContainMounth || $isContainTime;
    }

    private function paintTitle(bool $isVerical)
    {
        $widthPage = $this->getPageWidth();
        $heightPage = $this->getPageHeight();
        $nameReport = $this->report->getNameReport();
        $thirtPage = self::MARGIN_TOP + $heightPage / 2 / 2 / 2;
        $borderStyleRect = array('width' => 0, 'cap' => 'round', 'join' => 'round', 'dash' => '2,10', 'color' => self::COLORSRGB['greenDark']);

        //logogtype big
        $package = new Package(new EmptyVersionStrategy());
        //paint circle right top 
        $circleMill = $package->getUrl('build/images/circleMill.svg');
        $this->ImageSVG($circleMill, $widthPage - 60, $thirtPage, 80, 0, '', 'L', false, 0, 0);

        $logotypeBig = $package->getUrl('build/images/logotypeBig.svg');

        $this->ImageSVG($logotypeBig, self::MARGIN_LEFT, self::MARGIN_TOP / 2, self::WIDTH_LOGO_BIG, 0, 'www.techno-les.com', 'L', false, 0, 0);
        // $this->ImageSVG($logotypeBig, $widthPage - self::WIDTH_LOGO_BIG - self::MARGIN_LEFT / 2, self::MARGIN_TOP / 2, self::WIDTH_LOGO_BIG, 0, 'www.techno-les.com', 'L', false, 0, 0);

        $pathToLogotypeOtherCompany = realpath('C:\tlc\logotype\logo.svg') ?: 'build/images/whiteLogo.svg';
        // $siberiaGroupLogo = $package->getUrl('build/images/siberiaGroupLogo.svg');
        $siberiaGroupLogo = $package->getUrl($pathToLogotypeOtherCompany);
        $this->ImageSVG($siberiaGroupLogo, $widthPage - 100 - self::MARGIN_LEFT / 2, self::MARGIN_TOP / 2, 100, 0, '', 'L', false, 0, 0);


        $this->SetFontSize(50);
        $this->SetXY(self::MARGIN_LEFT, $thirtPage);
        $this->SetLineStyle(array('width' => 1, 'cap' => 'butt', 'join' => 'miter', 'dash' => 0, 'color' => array(0, 0, 0)));
        //paint Title nameReport

        $this->SetFont($this->getFontFamily(), 'B', 38);
        $this->Text(self::MARGIN_LEFT, $thirtPage, 'Отчёт');
        $this->Text(self::MARGIN_LEFT, $thirtPage + 17, $nameReport);
        $yTitleReport = $thirtPage + 17;

        $this->SetDrawColor(self::COLORSRGB['gray']);
        $heightRectPeriod = 24;
        $widthRectPeriod = 50;


        //paint squary
        $heightSquare = $heightRectPeriod + 20 + $heightRectPeriod + 4 + ($this->report->getPeoples() ? 20 + count($this->report->getPeoples()) * 5 : 0);
        $this->SetFillColor(self::COLORSRGB['greenDark']);
        $this->Rect(0, $thirtPage, 12, $heightSquare, 'DF', $borderStyleRect, self::COLORSRGB['greenDark']);

        $yPeriod = $yTitleReport + 20;

        $this->paintPeriod($yPeriod, $heightRectPeriod, $widthRectPeriod);
        $yOperators = $yPeriod + 30;
        $this->paintOperators($yOperators);


        if ($isVerical) {
            $this->SetY($thirtPage + $yOperators);
            $this->paintSummaryStatMaterial([50, 25, 25]);
            $this->Ln();
            $this->Ln();
            $this->paintSummaryStat();
        } else {
            $this->SetY($yPeriod);
            $this->setX($widthPage / 2);

            $this->paintSummaryStatMaterial([25, 13, 13], true);

            $this->SetY($yOperators + 40);
            $this->paintSummaryStat();
        }
    }

    private function paintPeriod(int $y, int $height, int $width)
    {
        $borderStyleRect = array('width' => 0, 'cap' => 'round', 'join' => 'round', 'dash' => '2,10', 'color' => self::COLORSRGB['greenDark']);
        $startPeriod = $this->report->getPeriod()->getStartDate();
        $endPeriod = $this->report->getPeriod()->getEndDate();
        $marginRect = 4;
        // 1 period rect
        $this->Rect(self::MARGIN_LEFT, $y, $width, $height, 'DF', $borderStyleRect, self::COLORSRGB['gray']);
        // - 
        $this->Rect(self::MARGIN_LEFT + $width + 2, $y + $height / 2, 6, 1, 'DF', $borderStyleRect, self::COLORSRGB['black']);
        // 2 period rect
        $this->Rect(self::MARGIN_LEFT + $width + 10, $y, $width, $height, 'DF', $borderStyleRect, self::COLORSRGB['gray']);

        $this->SetFontSize(18);
        //period startdate text
        $this->Text(self::MARGIN_LEFT + $marginRect, $y + $marginRect, $startPeriod->format(self::DATE_FORMAT));
        //period enddate text
        $this->Text(self::MARGIN_LEFT + $width + 10 + $marginRect, $y + $marginRect, $endPeriod->format(self::DATE_FORMAT));

        // period time text
        $this->SetFont($this->getFontFamily(), '', 10);
        $this->Text(self::MARGIN_LEFT + $marginRect, $y + $height - $marginRect - $marginRect, $startPeriod->format(self::TIME_FORMAT));
        $this->Text(self::MARGIN_LEFT + $width + 10 + $marginRect, $y + $height - $marginRect - $marginRect, $endPeriod->format(self::TIME_FORMAT));
    }
    private function paintOperators(int $y)
    {
        $namesOperator = '';
        $peoples = $this->report->getPeoples();
        if (count($peoples) == 1) {
            $namesOperator = 'Оператор: ' . $peoples[0]->getFullFio();
        } else if (count($peoples) > 0) {
            $namesOperator = 'Операторы: ';
            foreach ($peoples as $people) {
                $namesOperator .= $people->getFullFio() . "<br />";
            }
        }
        $this->SetXY(self::MARGIN_LEFT, $y);
        if ($peoples) {
            $this->SetFont($this->getFontFamily(), 'B', 20);
            $this->Cell($this->getPageWidth() - self::MARGIN_LEFT * 2, 10, count($peoples) == 1 ? 'Оператор' : 'Операторы', 0, 1);
            $this->SetFontSize(16);
            foreach ($peoples as $people) {
                $this->Cell($this->getPageWidth(), 5, $people->getFullFio(), 0, 1);
            }
        }
    }

    private function paintSummaryStatMaterial(array $precentColumn, bool $isHorizontal = false)
    {
        $summaryStatsMaterial = $this->report->getSummaryStatsMaterial();
        if (!$summaryStatsMaterial)
            return;
        $headers = ['Материал', 'Объём', 'Количество'];
        $this->SetFillColor(237, 247, 237); //light green
        $this->SetDrawColor(154, 209, 154);
        $this->SetLineWidth(0.0);
        $this->SetFont('', 'B', 10);
        // Header
        $w = $this->getPuntForColumns($precentColumn);
        $this->Cell($w[0], 10, $headers[0], 'TLB', 0, 'C', true);
        $this->Cell($w[1], 10, $headers[1], 'TB', 0, 'C', true);
        $this->Cell($w[2], 10, $headers[2], 'RBT', 0, 'C', true);

        $this->Ln();
        // Color and font restoration
        $this->SetFillColor(217, 238, 217);
        $this->SetTextColor(0);
        $this->SetFont('');
        // Data
        foreach ($summaryStatsMaterial as $stat) {
            if ($stat instanceof SummaryStatMaterial) {
                if ($isHorizontal) $this->setX($this->getPageWidth() / 2);
                $this->Cell($w[0], 8, $stat->getName(), 'LB', 0, 'L', 1);
                $this->Cell($w[1], 8, $stat->getValue() . ' ' . $stat->getSuffix(), 'B', 0, 'C', 1);
                $this->Cell($w[2], 8, $stat->getCount() . ' ' . $stat->getSuffixCount(), 'RB', 0, 'C', 1);
                $this->Ln();
            }
        }

        if ($isHorizontal) $this->setX($this->getPageWidth() / 2);

        $this->Cell(array_sum($w), 0, '', 'T');
    }

    private function paintSummaryStat()
    {
        $summaryStats = $this->report->getSummaryStats();
        if (!$summaryStats)
            return;
        $this->setFontSize(16);
        // Header
        $w = $this->getPuntForColumns([80, 20]);
        $this->SetFillColor(255, 255, 255);
        $this->SetTextColor(0);
        $this->SetFont('');
        // Data
        foreach ($summaryStats as $stat) {
            if ($stat instanceof SummaryStat) {
                $this->Cell($w[0], 8, $stat->getName(), '', 0, 'L', 1);
                $this->Cell($w[1], 8, $stat->getValue() . ' ' . $stat->getSuffix(), '', 0, 'R', 1);
                $this->Ln();
            }
        }
    }
}
