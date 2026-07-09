<?php

namespace App\Exports;

use App\Models\SensorData;
use Carbon\Carbon;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class SensorDataExport implements FromQuery, WithHeadings, WithMapping, ShouldAutoSize, WithStyles
{
    protected $rentang;
    protected $start;
    protected $end;

    public function __construct($rentang = '1', $start = null, $end = null)
    {
        $this->rentang = $rentang;
        $this->start   = $start;
        $this->end     = $end;
    }

    public function query()
    {
        $query = SensorData::query();

        if ($this->rentang === 'custom' && $this->start && $this->end) {
            $query->whereBetween('created_at', [$this->start, $this->end]);
        } else {
            $query->where('created_at', '>=', Carbon::now()->subHours((int) $this->rentang));
        }

        return $query->orderBy('id', 'desc');
    }

    public function headings(): array
    {
        return [
            'Waktu',
            'Tegangan Input (V)',
            'Tegangan Output (V)',
            'Arus Masuk (mA)',
            'Arus Keluar (mA)',
            'Suhu (°C)',
            'SoC (%)',
            'Status Charge',
            'Relay Surya (Input)',
            'Status Kipas',
            'Lampu 1',
            'Lampu 2',
            'Status USB',
        ];
    }

    public function map($row): array
    {
        return [
            $row->created_at->format('Y-m-d H:i:s'),
            $row->v_in,
            $row->v_out,
            $row->a_in,
            $row->a_out,
            $row->suhu,
            $row->soc,
            $row->s_charge,
            $row->s_relay_charge,
            $row->s_fan,
            $row->s_lamp1,
            $row->s_lamp2,
            $row->s_usb,
        ];
    }

    public function styles(Worksheet $sheet)
    {
        return [
            1 => ['font' => ['bold' => true]], // Baris header ditebalkan
        ];
    }
}
