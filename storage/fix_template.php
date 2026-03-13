<?php

require __DIR__ . '/../vendor/autoload.php';

\$spreadsheet = new PhpOffice\PhpSpreadsheet\Spreadsheet();
\$sheet = \$spreadsheet->getActiveSheet();

// Add headers
\$sheet->setCellValue('A1', 'no_kp');
\$sheet->setCellValue('B1', 'nama_penuh');
\$sheet->setCellValue('C1', 'no_telefon');
\$sheet->setCellValue('D1', 'nama_pasukan');
\$sheet->setCellValue('E1', 'jantina');
\$sheet->setCellValue('F1', 'g1');
\$sheet->setCellValue('G1', 'g2');
\$sheet->setCellValue('H1', 'g3');
\$sheet->setCellValue('I1', 'g4');
\$sheet->setCellValue('J1', 'g5');

// Format IC column as text
\$sheet->getStyle('A')->getNumberFormat()->setFormatCode(PhpOffice\PhpSpreadsheet\Style\NumberFormat::FORMAT_TEXT);

// Add sample data
\$sheet->setCellValue('A2', '');
\$sheet->setCellValue('B2', 'Ahmad bin Ali');
\$sheet->setCellValue('C2', '0123456789');
\$sheet->setCellValue('D2', 'Pasukan Strike');
\$sheet->setCellValue('E2', 'lelaki');
\$sheet->setCellValue('F2', 180);
\$sheet->setCellValue('G2', 195);
\$sheet->setCellValue('H2', 210);
\$sheet->setCellValue('I2', 200);
\$sheet->setCellValue('J2', 185);

\$writer = new PhpOffice\PhpSpreadsheet\Writer\Xlsx(\$spreadsheet);
\$writer->save(__DIR__ . '/public/templates/individual.xlsx');
echo "Template created\n";
