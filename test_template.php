<?php

require __DIR__ . '/vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;

$spreadsheet = new Spreadsheet();
$sheet = $spreadsheet->getActiveSheet();
$sheet->setCellValue('A1', 'no_kp');
$sheet->getStyle('A')->getNumberFormat()->setFormatCode(NumberFormat::FORMAT_TEXT);
$sheet->setCellValue('A2', '950101015555');
$writer = new Xlsx($spreadsheet);
$writer->save('storage/app/public/templates/test.xlsx');

echo "Test template created\n";

$reader = new \PhpOffice\PhpSpreadsheet\IOFactory::load('storage/app/public/templates/test.xlsx');
echo "Cell A1 format: " . $reader->getActiveSheet()->getCell('A1')->getStyle()->getNumberFormat()->getFormatCode() . "\n";
echo "Cell A2 value: " . $reader->getActiveSheet()->getCell('A2')->getValue() . "\n";
