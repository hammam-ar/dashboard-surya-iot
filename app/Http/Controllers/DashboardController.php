<?php
//file DashboardController.php
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\SensorData;
use PhpMqtt\Client\MqttClient;
use PhpMqtt\Client\ConnectionSettings;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Carbon\Carbon;

class DashboardController extends Controller
{
    public function index()
    {
        $dataTerbaru = SensorData::latest()->first();
        return view('dashboard', compact('dataTerbaru'));
    }

    public function ambilDataTerbaru()
    {
        $data = SensorData::latest()->first();

        if ($data) {
            $data->waktu_format = $data->created_at->format('d M Y, H:i:s');
        }

        return response()->json($data);
    }

    public function riwayatData()
    {
        $data = SensorData::latest()->take(30)->get()->sortBy('id')->values();

        $waktu = $data->map(function ($item) {
            return $item->created_at->format('H:i:s');
        });

        return response()->json([
            'waktu'        => $waktu,
            'tegangan_in'  => $data->pluck('v_in'),
            'tegangan_out' => $data->pluck('v_out'),
            'arus_in'      => $data->pluck('a_in'),
            'arus_out'     => $data->pluck('a_out')
        ]);
    }

    public function kontrolRelay(Request $request)
    {
        $perangkat = $request->input('perangkat');
        $status = $request->input('status');

        $server   = env('MQTT_HOST');
        $port     = env('MQTT_PORT', 8883);
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

            $payload = json_encode([$perangkat => $status]);
            $mqtt->publish($topic, $payload, 0);
            $mqtt->disconnect();

            return response()->json(['success' => true, 'message' => "Perintah $status berhasil dikirim ke $perangkat"]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'error' => "Gagal mengirim: " . $e->getMessage()]);
        }
    }

    public function history()
    {
        return view('history');
    }

    /* =========================================================================
       FUNGSI HISTORY FILTER YANG SUDAH DIOPTIMALKAN (ANTI-LAG & BERKECEPATAN TINGGI)
       ========================================================================= */
    public function historyFilter(Request $request)
    {
        $rentang = $request->input('rentang', '1');
        $start = $request->input('start');
        $end = $request->input('end');

        // Kita inisialisasi query dasarnya
        $queryBase = SensorData::query();

        if ($rentang === 'custom' && $start && $end) {
            $queryBase->whereBetween('created_at', [$start, $end]);
        } else {
            $queryBase->where('created_at', '>=', Carbon::now()->subHours((int)$rentang));
        }

        // --- OPTIMASI 1: TARIK DATA TABEL SECARA TERPISAH (Cepat & Kontinu) ---
        // Mengkloning query dasar khusus untuk mengambil 100 data asli paling baru tanpa pemotongan sampling grafik
        $tableDataRaw = (clone $queryBase)->orderBy('id', 'desc')->limit(100)->get();

        $tableData = $tableDataRaw->map(function($item) {
            return [
                'waktu_tabel' => $item->created_at->format('Y-m-d H:i:s'),
                'v_in'        => $item->v_in,
                'v_out'       => $item->v_out,
                'a_in'        => $item->a_in,
                'a_out'       => $item->a_out,
                'suhu'        => $item->suhu,
                'soc'         => $item->soc,
            ];
        });

        // --- OPTIMASI 2: DOWNSAMPLING DATA GRAFIK DARI SISI DATABASE ---
        // Kita hitung jumlah total data di database menggunakan query yang ringan
        $totalRows = (clone $queryBase)->count();
        $maksimalTitik = 120; // 120 titik sudah sangat padat dan rapi untuk grafik per jam/hari

        // Ambil data mentah grafik dengan select kolom spesifik saja agar hemat RAM
        $chartQuery = (clone $queryBase)->orderBy('id', 'asc')
            ->select(['created_at', 'v_in', 'v_out', 'a_in', 'a_out', 'suhu', 'soc']);

        $waktu = [];
        $v_in = []; $v_out = [];
        $a_in = []; $a_out = [];
        $suhu = []; $soc = [];

        if ($totalRows > $maksimalTitik) {
            $step = floor($totalRows / $maksimalTitik);

            // Chunking data agar aplikasi tidak overload saat membaca data besar
            $index = 0;
            $chartQuery->chunk(1000, function ($rows) use ($step, &$index, &$waktu, &$v_in, &$v_out, &$a_in, &$a_out, &$suhu, &$soc) {
                foreach ($rows as $row) {
                    if ($index % $step == 0) {
                        $waktu[]        = $row->created_at->format('d/m/y H:i:s');
                        $v_in[]         = $row->v_in;
                        $v_out[]        = $row->v_out;
                        $a_in[]         = $row->a_in;
                        $a_out[]        = $row->a_out;
                        $suhu[]         = $row->suhu;
                        $soc[]          = $row->soc;
                    }
                    $index++;
                }
            });
        } else {
            // Jika datanya masih sedikit, langsung ambil semuanya tanpa penyusutan
            $chartData = $chartQuery->get();
            foreach ($chartData as $row) {
                $waktu[]        = $row->created_at->format('d/m/y H:i:s');
                $v_in[]         = $row->v_in;
                $v_out[]        = $row->v_out;
                $a_in[]         = $row->a_in;
                $a_out[]        = $row->a_out;
                $suhu[]         = $row->suhu;
                $soc[]          = $row->soc;
            }
        }

        return response()->json([
            'waktu'      => $waktu,
            'v_in'       => $v_in,
            'v_out'      => $v_out,
            'a_in'       => $a_in,
            'a_out'      => $a_out,
            'suhu'       => $suhu,
            'soc'        => $soc,
            'table_data' => $tableData
        ]);
    }

    public function exportCsv(Request $request)
    {
        $rentang = $request->input('rentang', '1');
        $start = $request->input('start');
        $end = $request->input('end');

        $query = SensorData::query();

        if ($rentang === 'custom' && $start && $end) {
            $query->whereBetween('created_at', [$start, $end]);
        } else {
            $query->where('created_at', '>=', Carbon::now()->subHours((int)$rentang));
        }

        $filename = "laporan_sensor_surya_" . now()->format('Ymd_His') . ".csv";

        $response = new StreamedResponse(function () use ($query) {
            $handle = fopen('php://output', 'w');

            fputcsv($handle, [
                'Waktu', 'Tegangan Input (V)', 'Tegangan Output (V)',
                'Arus Masuk (mA)', 'Arus Keluar (mA)', 'Suhu (°C)',
                'SoC (%)', 'Status Charge', 'Status Kipas', 'Lampu 1', 'Lampu 2', 'Status USB'
            ]);

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
                    $row->s_lamp2,
                    $row->s_usb
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
