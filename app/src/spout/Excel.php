<?php


namespace app\src\spout;


use Box\Spout\Common\Entity\Cell;
use Box\Spout\Common\Entity\Style\CellAlignment;
use Box\Spout\Common\Entity\Style\Color;
use Box\Spout\Writer\Common\Creator\Style\StyleBuilder;
use Box\Spout\Writer\Common\Creator\WriterEntityFactory;


use function array_map;
use function end;
use function is_a;
use function is_array;
use function is_object;
use function ob_start;
use function uniqid;


/**
 * Экспорт в excel
 *
 * Class Excel
 *
 * @package app\components\spout
 */
class Excel
{
    
    /**
     * @param string $type
     * @param string $filename
     * @param array  $columns
     * @param array  $data
     * @param bool   $toBrowser
     *
     * @return void
     * @throws \Box\Spout\Common\Exception\IOException
     * @throws \Box\Spout\Common\Exception\InvalidArgumentException
     * @throws \Box\Spout\Common\Exception\UnsupportedTypeException
     * @throws \Box\Spout\Writer\Exception\WriterNotOpenedException
     */
    public static function export(
        string $type = 'xlsx',
        string $filename = '',
        array $columns = [],
        array $data = [],
        bool $toBrowser = false
    ): void {
        
        $styleHead = (new StyleBuilder())
            ->setFontBold()
            ->setFontSize(9)
            ->setFontName('Montserrat')
            ->setFontColor(Color::rgb(38, 38, 43))
            ->setShouldWrapText()
            ->setCellAlignment(CellAlignment::LEFT)
            ->setBackgroundColor(Color::rgb(236, 242, 246))
            ->build();
        
        $styleDefault = (new StyleBuilder())
            ->setFontSize(9)
            ->setFontName('Montserrat')
            ->setFontColor(Color::rgb(38, 38, 43))
            ->setShouldWrapText()
            ->setCellAlignment(CellAlignment::LEFT)
            ->build();
        
        if (empty($filename)) {
            $filename = uniqid('', true);
        }
        
        if (isset($columns[0]) && is_a($columns[0], ExcelColumn::class)) {
            $columns = [$columns];
        }
        
        $writer = (WriterEntityFactory::createWriter($type));
        if ($toBrowser) {
            ob_start();
            $writer->openToBrowser($filename . '.' . $type);
        } else {
            $writer->openToFile($filename);
        }
        $writer->setDefaultRowStyle($styleDefault);
        
        foreach ($columns as $rowColumns) {
            $writer->addRow(
                WriterEntityFactory::createRowFromArray(
                    array_map(static function (ExcelColumn $column) {
                        
                        return $column->title;
                    }, $rowColumns), $styleHead
                )
            );
        }
        
        foreach ($data as $row) {
            $cells = array_map(static function (ExcelColumn $column) use ($row) {
                
                $v = '';
                if (!empty($column->field)) {
                    if (is_array($row)) {
                        $v = $row[$column->field] ?? $column->default;
                    } elseif (is_object($row)) {
                        $v = $row->{$column->field} ?? $column->default;
                    }
                }
                $cell = new Cell($v);
                if ($column->type !== null) {
                    $cell->setType($column->type);
                }
                
                return $cell;
            }, end($columns));
            
            $writer->addRow(WriterEntityFactory::createRow($cells));
        }
        
        $writer->close();
    }
}
