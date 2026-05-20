<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\SensorData;
use PhpMqtt\Client\MqttClient;
use PhpMqtt\Client\ConnectionSettings;
use Symfony\Component\HttpFoundation\StreamedResponse;

class DashboardController extends Controller
{
    public function index()
    {
        $dataTerbaru = SensorData::latest()->first();
        return view('dashboard', compact('dataTerbaru'));
    }

    // Fungsi baru untuk merespons permintaan JavaScript
    public function ambilDataTerbaru()
    {
        $data = SensorData::latest()->first();

        // Membungkus format waktu agar mudah dibaca oleh JavaScript
        if ($data) {
            $data->waktu_format = $data->created_at->format('d M Y, H:i:s');
        }

        return response()->json($data);
    }

    //grafik dashboard
    public function riwayatData()
    {
        // Ambil 30 data terakhir, lalu balik urutannya (terlama ke terbaru)
        $data = SensorData::latest()->take(30)->get()->sortBy('id')->values();

        $waktu = $data->map(function ($item) {
            return $item->created_at->format('H:i:s');
        });
        
        // Kita kirimkan semua 4 parameter ke JavaScript
        return response()->json([
            'waktu'        => $waktu,
            'tegangan_in'  => $data->pluck('v_in'),
            'tegangan_out' => $data->pluck('v_out'),
            'arus_in'      => $data->pluck('a_in'),
            'arus_out'     => $data->pluck('a_out')
        ]);
    }

    //kontrol dashboard
    public function kontrolRelay(Request $request)
    {
        $perangkat = $request->input('perangkat'); // Contoh: 'lamp1'
        $status = $request->input('status');       // Contoh: 'ON' atau 'OFF'

        $server   = env('MQTT_HOST');
        $port     = env('MQTT_PORT', 8883);
        // Tambahkan angka acak agar ID-nya tidak tabrakan dengan worker yang sedang berjalan
        $clientId = 'laravel_web_publisher_' . rand(1000, 9999); 
        $username = env('MQTT_USERNAME');
        $password = env('MQTT_PASSWORD');
        $topic    = 'kontrol/panel_surya'; 

        $connectionSettings = (new ConnectionSettings)
            ->setUsername($username)
            ->setPassword($password)
            ->setUseTls(true)
            ->setTlsVerifyPeer(false);

        try {
            $mqtt = new MqttClient($server, $port, $clientId);
            $mqtt->connect($connectionSettings, true);

            // Membuat pesan JSON, contoh: {"lamp1": "ON"}
            $payload = json_encode([$perangkat => $status]);
            
            // Mengirim (Publish) pesan ke HiveMQ
            $mqtt->publish($topic, $payload, 0);
            $mqtt->disconnect();

            return response()->json(['success' => true, 'message' => "Perintah $status berhasil dikirim ke $perangkat"]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'error' => "Gagal mengirim: " . $e->getMessage()]);
        }
    }

    // Fungsi untuk memuat kerangka halaman web History
    public function history()
    {
        return view('history');
    }
    //grafik history data
    public function historyFilter(Request $request)
    {
        $rentang = $request->input('rentang', '1'); // Default 1 jam
        $start = $request->input('start');
        $end = $request->input('end');

        $query = SensorData::query();

        // Logika Rentang Waktu
        if ($rentang === 'custom' && $start && $end) {
            // Jika memilih tanggal manual
            $query->whereBetween('created_at', [$start, $end]);
        } else {
            // Jika memilih jam (1, 3, 6, 12, 24)
            $query->where('created_at', '>=', now()->subHours((int)$rentang));
        }

        // Mengambil seluruh data mentah terlebih dahulu
        $data = $query->orderBy('id', 'asc')->get();

        // LOGIKA PENYUSUTAN DATA (AUTO-SCALING SAMPLING)
        $jumlahData = $data->count();
        $maksimalTitik = 300; // Batas aman titik di grafik (tidak akan bikin lag)

        // Jika data melebihi 300 baris, lakukan pemotongan otomatis
        if ($jumlahData > $maksimalTitik) {
            // Contoh: Ada 1.800 data. 1800 / 300 = 6. 
            // Maka fungsi ceil() akan membulatkan ke atas, dan sistem akan mengambil setiap data ke-6.
            $step = ceil($jumlahData / $maksimalTitik); 
            $data = $data->nth($step);
        }

        // Memformat sumbu X (Waktu) untuk Grafik
        $waktu = $data->map(function ($item) {
            // Tambahkan :s di belakang H:i untuk memunculkan detik
            return $item->created_at->format('d/m/y H:i:s'); 
        });

        // FORMAT BARU UNTUK TABEL: Membalik data dan menyesuaikan zona waktu
        $tableData = $data->sortByDesc('id')->take(100)->values()->map(function($item) {
            $item->waktu_tabel = $item->created_at->format('Y-m-d H:i:s');
            return $item;
        });

        return response()->json([
            'waktu'      => $waktu,
            'v_in'       => $data->pluck('v_in'),
            'v_out'      => $data->pluck('v_out'),
            'a_in'       => $data->pluck('a_in'),
            'a_out'      => $data->pluck('a_out'),
            'suhu'       => $data->pluck('suhu'),
            'soc'        => $data->pluck('soc'),
            'table_data' => $tableData // <-- Sekarang menggunakan data yang sudah diformat
        ]);
    }

    //export data history
    public function exportCsv(Request $request)
    {
        $rentang = $request->input('rentang', '1');
        $start = $request->input('start');
        $end = $request->input('end');

        $query = SensorData::query();

        // Sesuaikan query dengan filter yang dipilih user
        if ($rentang === 'custom' && $start && $end) {
            $query->whereBetween('created_at', [$start, $end]);
        } else {
            $query->where('created_at', '>=', now()->subHours((int)$rentang));
        }

        $filename = "laporan_sensor_surya_" . now()->format('Ymd_His') . ".csv";

        // Menggunakan StreamedResponse agar Laravel mencetak data baris demi baris secara langsung ke file download (Hemat RAM)
        $response = new StreamedResponse(function () use ($query) {
            $handle = fopen('php://output', 'w');
            
            // Membuat baris judul kolom di Excel/CSV
            fputcsv($handle, [
                'Waktu', 'Tegangan Input (V)', 'Tegangan Output (V)', 
                'Arus Masuk (mA)', 'Arus Keluar (mA)', 'Suhu (°C)', 
                'SoC (%)', 'Status Charge', 'Status Kipas', 'Lampu 1', 'Lampu 2'
            ]);

            // Menggunakan cursor() milik Laravel untuk mengambil data secara malas (Lazy Loading) satu per satu
            foreach ($query->orderBy('id', 'desc')->cursor() as $row) {
                fputcsv($handle, [
                    $row->created_at->format('Y-m-d H:i:s'),
                    $row->v_in,
                    $row->v_out,
                    $row->a_in,
                    $row->a_out,
                    $row->suhu,
                    $row->soc,
                    $row->s_charge,
                    $row->s_fan,
                    $row->s_lamp1,
                    $row->s_lamp2
                ]);
            }

            fclose($handle);
        }, 200, [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ]);

        return $response;
    }
}


