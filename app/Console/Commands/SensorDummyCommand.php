<?php
//file SensorDummyCommand.php
namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\SensorData;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class SensorDummyCommand extends Command
{
    // Nama perintah yang akan diketik di terminal nanti
    protected $signature = 'sensor:dummy';
    protected $description = 'Menghapus data lama dan meng-generate 216.000 data dummy untuk rentang 5 hari';

    public function handle()
    {
        // 1. MENGHAPUS SEMUA DATA (TRUNCATE)
        $this->info('Langkah 1: Menghapus seluruh data sensor lama...');
        // Mengosongkan tabel dan mereset ID Auto Increment kembali ke angka 1
        SensorData::truncate();
        $this->info('Selesai! Tabel sensor_data sekarang kosong.');

        // 2. GENERATE DATA DUMMY 5 HARI (216.000 BARIS)
        $this->info('Langkah 2: Memulai kalkulasi 216.000 data dummy...');

        $totalHari = 5;
        $intervalDetik = 2;
        $totalData = ($totalHari * 24 * 60 * 60) / $intervalDetik; // Hasilnya 216.000

        // Waktu mulai adalah 5 hari yang lalu dari detik ini
        $waktuMulai = Carbon::now()->subDays($totalHari);

        $dataChunk = [];
        // Batas placeholder MySQL per statement adalah 65.535.
        // Sekarang ada 14 kolom per baris, jadi maksimal aman: 65535/14 ≈ 4681.
        // Dibulatkan ke bawah ke 4000 supaya ada margin aman kalau nanti nambah kolom lagi.
        $ukuranChunk = 4000;

        // Membuka progress bar di terminal agar terlihat proses jalannya
        $bar = $this->output->createProgressBar($totalData);
        $bar->start();

        for ($i = 0; $i < $totalData; $i++) {
            // Waktu maju bertahap 2 detik setiap perulangan
            $waktuSekarang = $waktuMulai->copy()->addSeconds($i * $intervalDetik);

            // Logika acak sesuai request (Tegangan: 12.5 - 14.4 V)
            $v_in = mt_rand(1250, 1440) / 100; // Menghasilkan float misal 13.42
            $v_out = mt_rand(1180, 1380) / 100; // Tegangan keluar disesuaikan agak turun sedikit

            // Logika acak sesuai request (Ampere: 100 - 2000 mA)
            $a_in = mt_rand(100, 2000);
            $a_out = mt_rand(50, 1500); // Arus keluar disesuaikan beban

            // Penyesuaian parameter Suhu (28 - 42 °C) & SoC Baterai (40 - 100 %)
            $suhu = mt_rand(280, 420) / 10;
            $soc = mt_rand(40, 100);

            // --- s_charge: meniru logika firmware asli (arusBersih = a_in - a_out) ---
            $arusBersih = $a_in - $a_out;
            $s_charge = ($arusBersih > 0) ? 'Charge' : 'Discharge';

            // --- s_relay_charge: meniru logika cutoff charging (v_out >= 14.4V -> relay OFF) ---
            $s_relay_charge = ($v_out >= 14.4) ? 'OFF' : 'ON';

            // --- s_usb: default ON, OFF kalau meniru kondisi statusBateraiHabis / statusOverheat ---
            $s_usb = ($soc <= 20 || $suhu >= 35) ? 'OFF' : 'ON';

            // --- s_fan: dibuat sedikit dinamis, ON kalau suhu cukup tinggi (>=33), supaya tidak selalu OFF ---
            $s_fan = ($suhu >= 33) ? 'ON' : 'OFF';

            // Masukkan ke array penampung sementara
            $dataChunk[] = [
                'v_in'           => $v_in,
                'v_out'          => $v_out,
                'a_in'           => $a_in,
                'a_out'          => $a_out,
                'suhu'           => $suhu,
                'soc'            => $soc,
                's_relay_charge' => $s_relay_charge,
                's_charge'       => $s_charge,
                's_fan'          => $s_fan,
                's_lamp1'        => 'OFF',
                's_lamp2'        => 'OFF',
                's_usb'          => $s_usb,
                'created_at'     => $waktuSekarang,
                'updated_at'     => $waktuSekarang,
            ];

            // Jika tampungan sudah mencapai 5.000 baris, langsung "boom" masukkan ke database
            if (count($dataChunk) >= $ukuranChunk) {
                DB::table('sensor_data')->insert($dataChunk);
                $dataChunk = []; // Kosongkan memori RAM penampung
            }

            $bar->advance();
        }

        // Memasukkan sisa data yang belum genap 5.000 di akhir perulangan
        if (!empty($dataChunk)) {
            DB::table('sensor_data')->insert($dataChunk);
        }

        $bar->finish();
        $this->newLine();
        $this->info("BERHASIL! 216.000 data dummy sukses membanjiri database kamu.");
    }
}
