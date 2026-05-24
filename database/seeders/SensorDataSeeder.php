<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\SensorData;
use Carbon\Carbon;

class SensorDataSeeder extends Seeder
{
    public function run()
    {
        // U-umm, peringatan: Ini akan mengosongkan tabelmu terlebih dahulu agar bersih
        SensorData::truncate();

        $data = [];
        $waktuSekarang = Carbon::now();
        // Mundur ke 24 jam yang lalu dari waktu saat ini
        $waktuMulai = Carbon::now()->subHours(24);

        // Kita buat simulasi data yang masuk setiap 1 menit sekali
        while ($waktuMulai <= $waktuSekarang) {
            $jam = (int) $waktuMulai->format('H');

            // Logika Simulasi Realistis: Siang ada matahari, malam gelap
            if ($jam >= 6 && $jam <= 17) {
                // Kondisi Siang: Tegangan 12.0V - 18.0V, Arus 500mA - 2000mA
                $v_in = rand(12000, 18000) / 1000;
                $a_in = rand(500, 2000);
                $s_relay = 'ON';
                $s_charge = 'Charge';
            } else {
                // Kondisi Malam: Tegangan bocor sedikit 0 - 1.0V, Arus mati
                $v_in = rand(0, 1000) / 1000;
                $a_in = 0;
                $s_relay = 'OFF';
                $s_charge = 'Discharge';
            }

            $data[] = [
                'v_in'           => $v_in,
                'v_out'          => rand(11500, 13500) / 1000, // Fluktuasi aki 11.5V - 13.5V
                'a_in'           => $a_in,
                'a_out'          => rand(200, 1500),           // Beban pemakaian 200mA - 1500mA
                'suhu'           => rand(280, 380) / 10,       // Suhu 28.0C - 38.0C
                'soc'            => rand(60, 100),             // Baterai 60% - 100%
                's_relay_charge' => $s_relay,
                's_charge'       => $s_charge,
                's_fan'          => rand(0, 1) ? 'ON' : 'OFF', // Status acak kipas dan lampu
                's_lamp1'        => rand(0, 1) ? 'ON' : 'OFF',
                's_lamp2'        => rand(0, 1) ? 'ON' : 'OFF',
                'created_at'     => $waktuMulai->copy(),
                'updated_at'     => $waktuMulai->copy(),
            ];

            // Mencegah RAM kelebihan beban dengan menyimpan per 500 baris
            if (count($data) >= 500) {
                SensorData::insert($data);
                $data = [];
            }

            // Tambahkan 1 menit untuk perulangan selanjutnya
            $waktuMulai->addMinute();
        }

        // Simpan sisa data yang belum masuk kelipatan 500
        if (count($data) > 0) {
            SensorData::insert($data);
        }
    }
}
