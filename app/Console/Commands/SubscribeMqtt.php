<?php
//file SubscribeMqtt.php
namespace App\Console\Commands;

use Illuminate\Console\Command;
use PhpMqtt\Client\MqttClient;
use PhpMqtt\Client\ConnectionSettings;
use App\Models\SensorData;
use Illuminate\Support\Facades\Log;

class SubscribeMqtt extends Command
{
    // Nama perintah yang akan dijalankan di terminal
    protected $signature = 'mqtt:subscribe';

    // Deskripsi perintah
    protected $description = 'Menjalankan background worker untuk listen data dari HiveMQ Cloud';

    public function handle()
    {
        $server   = env('MQTT_HOST');
        $port     = env('MQTT_PORT', 8883);
        $clientId = env('MQTT_CLIENT_ID', 'laravel_subscriber');
        $username = env('MQTT_USERNAME');
        $password = env('MQTT_PASSWORD');
        $topic    = env('MQTT_TOPIC', 'sensor/panel_surya');

        // Pengaturan koneksi khusus untuk HiveMQ Cloud (wajib TLS)
        $connectionSettings = (new ConnectionSettings)
            ->setUsername($username)
            ->setPassword($password)
            ->setUseTls(true)
            ->setTlsVerifyPeer(false);

        $mqtt = new MqttClient($server, $port, $clientId);

        try {
            $this->info("Menghubungkan ke broker HiveMQ...");
            $mqtt->connect($connectionSettings, true);
            $this->info("Berhasil terhubung! Menunggu data di topik: {$topic}");

            // Subscribe ke topik
            $mqtt->subscribe($topic, function ($topic, $message) {
                $this->info("Data diterima: " . $message);

                // Parsing pesan JSON dari ESP32
                $data = json_decode($message, true);

                if (json_last_error() === JSON_ERROR_NONE) {
                    try {
                        // Simpan ke database dengan tambahan s_relay_charge
                        SensorData::create([
                            'v_in'           => $data['v_in'] ?? 0,
                            'v_out'          => $data['v_out'] ?? 0,
                            'a_in'           => $data['a_in'] ?? 0,
                            'a_out'          => $data['a_out'] ?? 0,
                            'suhu'           => $data['suhu'] ?? 0,
                            'soc'            => $data['soc'] ?? 0,
                            's_relay_charge' => $data['s_relay_charge'] ?? 'OFF', // <-- AMBIL DATA BARU
                            's_charge'       => $data['s_charge'] ?? 'Non-Aktif',
                            's_fan'          => $data['s_fan'] ?? 'OFF',
                            's_lamp1'        => $data['s_lamp1'] ?? 'OFF',
                            's_lamp2'        => $data['s_lamp2'] ?? 'OFF',
                            's_usb'          => $data['s_usb'] ?? 'OFF', // <-- TAMBAHAN
                        ]);
                        $this->info("Data berhasil disimpan ke database!");
                    } catch (\Exception $e) {
                        // BONGKAR ERROR MYSQL YANG TERSEMBUNYI
                        $this->error("GAGAL SIMPAN KE DB: " . $e->getMessage());
                    }
                } else {
                    $this->error("Format pesan bukan JSON yang valid.");
                }
            }, 0);

            // Jalankan loop permanen agar tidak tertutup
            $mqtt->loop(true);

        } catch (\Exception $e) {
            $this->error("Terjadi kesalahan koneksi MQTT: " . $e->getMessage());
            Log::error("MQTT Error: " . $e->getMessage());
        }
    }
}
