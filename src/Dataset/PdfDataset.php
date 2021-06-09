<?php

declare(strict_types=1);

namespace Tlc\ReportBundle\Dataset;

use Tlc\ReportBundle\Entity\BaseEntity;
use Tlc\ReportBundle\Entity\Column;
use DateInterval;
use DateTime;
use Exception;
use Symfony\Component\Validator\Constraints\Date;

class PdfDataset extends AbstractDataset
{

    private array $keysSubTotal = [];

    /**
     * @param Column[] $nameColumns
     */
    public function __construct(
        private array $columns = [],
        private ?string $textSubTotal = null,
        private ?string $textTotal = null,

    ) {
        $this->data = [];
    }

    public function getKeysSubTotal(): array
    {
        return $this->keysSubTotal;
    }

    /**
     * Добавляет значения в массив данных
     *
     * @param array $arv Массив значений
     * @return void
     */
    public function addRow(array $arv)
    {
        if (count($arv) != count($this->columns)) {
            throw new Exception(
                'Кол-во аргументов не соотвествует количеству столбцов',
                1
            );
        }

        $this->data[] = $arv;
    }

    /**
     * Возращает кол-во столбцов
     *
     * @return integer
     */
    public function getCountColumn(): int
    {
        return count($this->columns);
    }

    /**
     * Возращает названия столбцов
     *
     * @return string[]
     */
    public function getNameColumns(): array
    {
        $namesColumns = [];
        $columns = $this->columns;

        foreach ($columns as $column) {
            $namesColumns[] = $column->getTitle();
        }
        return $namesColumns;
    }

    /**
     * Возращает ширину столбцов в процентах
     *
     * @return int[]
     */
    public function getWidthInPrecent(): array
    {
        $widthsColumns = [];
        foreach ($this->columns as $column) {
            if ($column instanceof Column)
                $widthsColumns[] = $column->getPrecentWidth();
        }
        return $widthsColumns;
    }
    /**
     * Возращает названия столбцов
     *
     * @return string[]
     */
    public function getAlignForColumns(): array
    {
        $alignColumns = [];
        foreach ($this->columns as $column) {
            if ($column instanceof Column)
                $alignColumns[] = $column->getAlign();
        }
        return $alignColumns;
    }

    /**
     * Возращает колонки для итогов
     *
     * @return Column[]
     */
    public function getTotalColumns(): array
    {
        $columns = [];

        foreach ($this->columns as $column) {
            if ($column instanceof Column && $column->isTotal())
                $columns[] =  $column;
        }

        return $columns;
    }

    /**
     * Возращает колонки для группировки
     *
     * @return Column[]
     */
    public function getGroupColumns(): array
    {
        $columns = [];

        foreach ($this->columns as $column) {
            if ($column instanceof Column && $column->isGroup())
                $columns[] =  $column;
        }

        return $columns;
    }

    // private function isValidNameColumn(array $nameColumns): bool
    // {
    //     $nameColumnsInDataset = $this->getNameColumns();
    //     foreach ($nameColumns as $column) {
    //         if (!isset($column, $nameColumnsInDataset)) {
    //             throw new Exception("$column отсутствует в массиве");
    //         }
    //     }
    //     return true;
    // }


    // /**
    //  * Проверяет на валидность шаблон кол-во аргументов в строке
    //  *
    //  * @param array $array Массив значений
    //  * @param string $templateRow
    //  * @return boolean
    //  */
    // private function isValidTemplate(array $array, string $templateRow): bool
    // {
    //     if (count($array) != preg_match_all('/\%\d/m', $templateRow)) {
    //         throw new Exception('Кол-во аргументов не соотвествует количеству в шаблоне', 1);
    //     }

    //     return true;
    // }

    /**
     * Возращает ключи по названию столбцов
     *
     * @param Column[] $nameColumns названия стообца в датасете
     * @return int[]
     */
    private function getKeysOnColumns(array $columns): array
    {
        $result = [];
        foreach ($columns as $column) {
            if (isset($column, $this->columns)) {
                $result[$column->getTitle()] = array_search($column, $this->columns);
            }
        }
        return $result;
    }

    public function addTotal()
    {
        $totalColumns = $this->getTotalColumns();
        $keysColumn = $this->getKeysOnColumns($totalColumns);
        $stringTotal = "$this->textTotal{" . (string)($this->getCountColumn() - count($totalColumns)) . '}';

        $total = [];
        foreach ($this->data as $key => $value) {
            if (in_array($key, $this->keysSubTotal)) {
                continue;
            }
            $total = $this->getTotal($keysColumn, $value, $total);
        }

        foreach ($total as $value) {
            if ($value instanceof DateTime) {
                $nowTime = new DateTime('00:00:00');
                $strValue = BaseEntity::intervalToString($nowTime->diff($value));
            } else {
                $strValue = $value;
            }
            $stringTotal .= $strValue . '{1}';
        }
        $this->pushTotalStr($stringTotal);
    }

    public function addSubTotal()
    {
        $totalColumns = $this->getTotalColumns();
        $groupColumns = $this->getGroupColumns();
        $keysColumnTotal = $this->getKeysOnColumns($totalColumns);
        $keysColumnGroup = array_values($this->getKeysOnColumns($groupColumns));
        $data_array_reserve = array_reverse($this->data);
        $countData = count($this->data);
        $total = [];
        $stringSubTotal = $this->textSubTotal;

        for ($i = max(empty($this->keysSubTotal) ? [-1] : $this->keysSubTotal) + 1; $i < count($this->data); $i++) {
            if ((!$this->keysSubTotal && $i == 0) || in_array($i - 1, $this->keysSubTotal)) {
                if ($keysColumnGroup) $stringSubTotal .= '(';
                foreach ($keysColumnGroup as $keyArray => $keyColumn) {
                    $stringSubTotal .= $this->data[$i][$keyColumn] . ($keyArray + 1 == count($keysColumnGroup)  ? ')' : ', ');
                }
            }
            $total = $this->getTotal($keysColumnTotal, $this->data[$i], $total);
        }
        // foreach ($data_array_reserve as $key => $value) {
        //     // if(count($this->keysSubTotal) == 2) dd($data_array_reserve);
        //     if ((!$this->keysSubTotal && $key == 0) || in_array($countData - $key, $this->keysSubTotal)) {
        //         if ($keysColumnGroup) $stringSubTotal .= '(';
        //         foreach ($keysColumnGroup as $keyArray => $keyColumn) {
        //             $stringSubTotal .= $value[$keyColumn] . ($keyArray + 1 == count($keysColumnGroup)  ? ')' : ', ');
        //         }
        //         if ($this->keysSubTotal)
        //             break;
        //     }
        //     $total = $this->getTotal($keysColumnTotal, $value, $total);
        // }
        $stringSubTotal .= "{" . (string)($this->getCountColumn() - count($totalColumns)) . '}';

        foreach ($total as $value) {
            if ($value instanceof DateTime) {
                $nowTime = new DateTime('00:00');
                $strValue = BaseEntity::intervalToString($nowTime->diff($value));
            } else {
                $strValue = $value;
            }
            $stringSubTotal .= $strValue . '{1}';
        }
        $this->pushTotalStr($stringSubTotal);
    }

    public function getTotalResultInColumn(Column $column)
    {
        // $this->isValidcolumn([$column]);
        $keyColumn = $this->getKeysOnColumns([$column])[$column->getTitle()];

        $total = null;
        foreach ($this->data as $key => $row) {
            if (!in_array($key, $this->keysSubTotal)) {
                $value = $row[$keyColumn];
                if (is_string($value)) {
                    continue;
                }
                if ($value instanceof DateInterval) {
                    $total = $total ?? new DateTime('00:00');
                    $total->add($value);
                    continue;
                }
                $total = $total ?? 0;
                $total += $value;
            }
        }
        return $total;
    }

    private function getTotal(array $keysColumn, $value, array $total)
    {
        foreach ($keysColumn as $column => $key) {
            if (is_string($value[$key])) {
                continue;
            }
            if ($value[$key] instanceof DateInterval) {
                $total[$column] = $total[$column] ?? new DateTime('00:00');
                $total[$column]->add($value[$key]);
                continue;
            }
            $total[$column] = $total[$column] ?? 0;
            $total[$column] += $value[$key];
        }
        return $total;
    }

    private function pushTotalStr(string $total_str)
    {
        $this->data[] = $total_str;
        $this->keysSubTotal[] = array_key_last($this->data);
    }

    // /**
    //  * Возращает готовую строку, шаблон берётся
    //  * из $templateRow, вместо %0, %1 ... %N
    //  * Поставляет $total, важно чтобы кол-во элементов name_colums
    //  * совпадало с аргументами и $total
    //  *
    //  * @param array $nameColumns Имя столбцов из таблицы(по полю какому делать итог)
    //  * @param string $templateRow Шаблон строки
    //  * @param array $total Массив итогов, поставляется вместо %0, %1
    //  * @return string
    //  */
    // private function getPrepareTemplate(
    //     array $nameColumns,
    //     string $templateRow,
    //     array $total
    // ): string {
    //     $this->isValidTemplate($nameColumns, $templateRow);
    //     $pattern = [];
    //     $replacements = [];
    //     foreach ($nameColumns as $key => $value) {
    //         $pattern[] = "/%$key/";
    //         // dd(preg_match("/%$key/", $templateRow));
    //         if ($total[$value] instanceof DateTime) {
    //             $nowTime = new DateTime('00:00');
    //             $duration = $nowTime->diff($total[$value]);
    //             if ($duration->m > 0)
    //                 $replacements[] = $duration->format(self::DURATION_MOUNT_DAY_TIME_FROMAT);
    //             elseif ($duration->d > 0)
    //                 $replacements[] = $duration->format(self::DURATION_DAY_TIME_FROMAT);
    //             else
    //                 $replacements[] = $duration->format(self::DURATION_TIME_FROMAT);
    //             continue;
    //         }
    //         $replacements[] = $total[$value];
    //     }
    //     $str = preg_replace($pattern, $replacements, $templateRow);
    //     return $str;
    // }
}
