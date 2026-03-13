<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithTitle;

class TemplateExport implements FromArray, WithHeadings, WithTitle
{
    protected string $type;

    protected array $sampleData;

    public function __construct(string $type)
    {
        $this->type = $type;
        $this->sampleData = $this->getSampleData();
    }

    /**
     * Get the sample data for the template.
     */
    protected function getSampleData(): array
    {
        switch ($this->type) {
            case 'individual':
                return [
                    [
                        'no_kp' => '',
                        'nama_penuh' => 'Ahmad bin Ali',
                        'no_telefon' => '0123456789',
                        'nama_pasukan' => 'Pasukan Strike',
                        'jantina' => 'lelaki',
                        'g1' => 180,
                        'g2' => 195,
                        'g3' => 210,
                        'g4' => 200,
                        'g5' => 185,
                    ],
                ];

            case 'team-beregu':
                return [
                    [
                        'ketua_kp' => '',
                        'ketua_nama' => 'Ahmad bin Ali',
                        'ketua_telefon' => '0123456789',
                        'nama_pasukan' => 'Pasukan Beregu',
                        'jantina' => 'lelaki',
                        'member_2_kp' => '',
                        'member_2_nama' => 'Salleh bin Ahmad',
                        'g1' => 180,
                        'g2' => 195,
                        'g3' => 210,
                        'g4' => 200,
                        'g5' => 185,
                    ],
                ];

            case 'team-trio':
                return [
                    [
                        'ketua_kp' => '',
                        'ketua_nama' => 'Ahmad bin Ali',
                        'ketua_telefon' => '0123456789',
                        'nama_pasukan' => 'Pasukan Trio',
                        'jantina' => 'lelaki',
                        'member_2_kp' => '',
                        'member_2_nama' => 'Salleh bin Ahmad',
                        'member_3_kp' => '',
                        'member_3_nama' => 'Rashid bin Salleh',
                        'g1' => 180,
                        'g2' => 195,
                        'g3' => 210,
                        'g4' => 200,
                        'g5' => 185,
                    ],
                ];

            case 'team-berkumpulan':
                return [
                    [
                        'ketua_kp' => '',
                        'ketua_nama' => 'Ahmad bin Ali',
                        'ketua_telefon' => '0123456789',
                        'nama_pasukan' => 'Pasukan Berkumpulan',
                        'jantina' => 'lelaki',
                        'member_2_kp' => '',
                        'member_2_nama' => 'Salleh bin Ahmad',
                        'member_3_kp' => '',
                        'member_3_nama' => 'Rashid bin Salleh',
                        'member_4_kp' => '',
                        'member_4_nama' => 'Khalid bin Rashid',
                        'member_5_kp' => '',
                        'member_5_nama' => 'Omar bin Khalid',
                        'member_6_kp' => '',
                        'member_6_nama' => 'Hamzah bin Omar',
                        'g1' => 180,
                        'g2' => 195,
                        'g3' => 210,
                        'g4' => 200,
                        'g5' => 185,
                    ],
                ];

            default:
                return [];
        }
    }

    /**
     * Get the headings for the template.
     */
    public function headings(): array
    {
        switch ($this->type) {
            case 'individual':
                return [
                    'no_kp',
                    'nama_penuh',
                    'no_telefon',
                    'nama_pasukan',
                    'jantina',
                    'g1',
                    'g2',
                    'g3',
                    'g4',
                    'g5',
                ];

            case 'team-beregu':
                return [
                    'ketua_kp',
                    'ketua_nama',
                    'ketua_telefon',
                    'nama_pasukan',
                    'jantina',
                    'member_2_kp',
                    'member_2_nama',
                    'g1',
                    'g2',
                    'g3',
                    'g4',
                    'g5',
                ];

            case 'team-trio':
                return [
                    'ketua_kp',
                    'ketua_nama',
                    'ketua_telefon',
                    'nama_pasukan',
                    'jantina',
                    'member_2_kp',
                    'member_2_nama',
                    'member_3_kp',
                    'member_3_nama',
                    'g1',
                    'g2',
                    'g3',
                    'g4',
                    'g5',
                ];

            case 'team-berkumpulan':
                return [
                    'ketua_kp',
                    'ketua_nama',
                    'ketua_telefon',
                    'nama_pasukan',
                    'jantina',
                    'member_2_kp',
                    'member_2_nama',
                    'member_3_kp',
                    'member_3_nama',
                    'member_4_kp',
                    'member_4_nama',
                    'member_5_kp',
                    'member_5_nama',
                    'member_6_kp',
                    'member_6_nama',
                    'g1',
                    'g2',
                    'g3',
                    'g4',
                    'g5',
                ];

            default:
                return [];
        }
    }

    /**
     * Get the sample data.
     */
    public function array(): array
    {
        return $this->sampleData;
    }

    /**
     * Get the title of the export.
     */
    public function title(): string
    {
        switch ($this->type) {
            case 'individual':
                return 'Individu';
            case 'team-beregu':
                return 'Beregu';
            case 'team-trio':
                return 'Trio';
            case 'team-berkumpulan':
                return 'Berkumpulan';
            default:
                return 'Template';
        }
    }
}
