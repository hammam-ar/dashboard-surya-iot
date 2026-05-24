@extends('layouts.main')

@section('content')
    <style>
        /* Transisi halus saat ganti tema */
        body { transition: background-color 0.3s, color 0.3s; }
        .card { transition: background-color 0.3s, border-color 0.3s; }



        /* Modifikasi Switch agar lebih besar bergaya IoT */
        .form-switch .form-check-input {
            width: 3.5em;
            height: 1.75em;
            cursor: pointer;
        }
    </style>

    <div class="container-fluid">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2 class="m-0">Status Real-time</h2>
        </div>

        <div class="row g-3">
            <div class="col-md-4">
                <div class="card p-3 border-start border-primary border-5 shadow-sm">
                    <h6>Tegangan Input</h6>
                    <h3 id="tegangan_layar">{{ $dataTerbaru->v_in ?? 0 }} V</h3>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card p-3 border-start border-danger border-5 shadow-sm">
                    <h6>Arus Masuk</h6>
                    <h3 id="arus_layar">{{ $dataTerbaru->a_in ?? 0 }} mA</h3>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card p-3 border-start border-warning border-5 shadow-sm">
                    <h6>Suhu Sistem</h6>
                    <h3 id="suhu_layar">{{ $dataTerbaru->suhu ?? 0 }} °C</h3>
                </div>
            </div>

            <div class="col-md-4">
                <div class="card p-3 border-start border-info border-5 shadow-sm">
                    <h6>Tegangan Keluar</h6>
                    <h3 id="tegangan_out_layar">{{ $dataTerbaru->v_out ?? 0 }} V</h3>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card p-3 border-start border-secondary border-5 shadow-sm">
                    <h6>Arus Keluar</h6>
                    <h3 id="arus_out_layar">{{ $dataTerbaru->a_out ?? 0 }} mA</h3>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card p-3 border-start border-success border-5 shadow-sm">
                    <h6>Baterai (SoC)</h6>
                    <h3 id="soc_layar">{{ $dataTerbaru->soc ?? 0 }} %</h3>
                </div>
            </div>
        </div>

        <div class="row mt-4 g-4">
            <div class="col-md-8">
                <div class="card p-4 shadow-sm">
                    <div class="d-flex flex-wrap justify-content-between align-items-center mb-3">
                        <h5 class="m-0">Grafik Performa (30 Data Terakhir)</h5>
                        <div class="p-1 bg-secondary bg-opacity-10 rounded-pill d-inline-flex align-items-center border" style="backdrop-filter: blur(4px);">
                            <button id="btn-tegangan" class="btn btn-sm rounded-pill px-3 fw-medium text-primary bg-white shadow-sm transition-all"
                                    onclick="ubahModeGrafik('tegangan')" style="border: none;">
                                <i class="fa fa-bolt me-1 small"></i> Tegangan (V)
                            </button>
                            <button id="btn-arus" class="btn btn-sm rounded-pill px-3 fw-medium text-muted transition-all"
                                    onclick="ubahModeGrafik('arus')" style="border: none; background: transparent;">
                                <i class="fa fa-chart-line me-1 small"></i> Arus (mA)
                            </button>
                        </div>
                    </div>
                    <canvas id="grafikSensor" style="max-height: 300px;"></canvas>
                </div>
            </div>

            <div class="col-md-4">
                <div class="card p-4 shadow-sm">
                    <h5>Kontrol Beban</h5>
                    <hr>

                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <span>Kipas Pendingin <br><small class="text-muted" id="status_fan">Status: -</small></span>
                        <div class="form-check form-switch m-0 p-0 d-flex align-items-center">
                            <input class="form-check-input ms-0" type="checkbox" id="switch_fan" onchange="kirimPerintahToggle('fan', this.checked)">
                        </div>
                    </div>

                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <span>Lampu 1 <br><small class="text-muted" id="status_lamp1">Status: -</small></span>
                        <div class="form-check form-switch m-0 p-0 d-flex align-items-center">
                            <input class="form-check-input ms-0" type="checkbox" id="switch_lamp1" onchange="kirimPerintahToggle('lamp1', this.checked)">
                        </div>
                    </div>

                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <span>Lampu 2 <br><small class="text-muted" id="status_lamp2">Status: -</small></span>
                        <div class="form-check form-switch m-0 p-0 d-flex align-items-center">
                            <input class="form-check-input ms-0" type="checkbox" id="switch_lamp2" onchange="kirimPerintahToggle('lamp2', this.checked)">
                        </div>
                    </div>

                    <div class="mt-3 p-3 bg-light rounded shadow-sm border">
                        <div class="mb-1"><small class="text-muted">Relay Surya (Input): <b id="status_relay_charging" class="text-primary">{{ $dataTerbaru->s_relay_charge ?? '-' }}</b></small></div>
                        <div><small class="text-muted">Mode Charging: <b id="status_charging" class="text-success">{{ $dataTerbaru->s_charge ?? '-' }}</b></small></div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        let modeGrafik = 'tegangan';
        let riwayatDataLokal = {};

        const ctx = document.getElementById('grafikSensor').getContext('2d');
        const grafikSensor = new Chart(ctx, {
            type: 'line',
            data: {
                labels: [],

                datasets: [
                    {
                        label: 'Tegangan Input (V)', /* <-- UBAH DI SINI */
                        data: [],
                        borderColor: '#3498db',
                        backgroundColor: 'rgba(52, 152, 219, 0.1)',
                        tension: 0.4
                    },
                    {
                        label: 'Tegangan Keluar (V)', /* <-- UBAH DI SINI */
                        data: [],
                        borderColor: '#e74c3c',
                        backgroundColor: 'rgba(231, 76, 60, 0.1)',
                        tension: 0.4
                    }
                ]
            },
            options: {
                responsive: true,
                scales: { y: { type: 'linear', display: true, position: 'left' } }
            }
        });

        function ubahModeGrafik(mode) {
            modeGrafik = mode;

            const btnTegangan = document.getElementById('btn-tegangan');
            const btnArus = document.getElementById('btn-arus');

            if (mode === 'tegangan') {
                // Tegangan Aktif
                btnTegangan.className = 'btn btn-sm rounded-pill px-3 fw-medium text-primary bg-white shadow-sm transition-all';
                btnTegangan.style.background = '#ffffff';

                // Arus Mati
                btnArus.className = 'btn btn-sm rounded-pill px-3 fw-medium text-muted transition-all';
                btnArus.style.background = 'transparent';
            } else {
                // Tegangan Mati
                btnTegangan.className = 'btn btn-sm rounded-pill px-3 fw-medium text-muted transition-all';
                btnTegangan.style.background = 'transparent';

                // Arus Aktif
                btnArus.className = 'btn btn-sm rounded-pill px-3 fw-medium text-primary bg-white shadow-sm transition-all';
                btnArus.style.background = '#ffffff';
            }

            renderGrafik();
        }

        function renderGrafik() {
            if (!riwayatDataLokal.waktu) return;

            grafikSensor.data.labels = riwayatDataLokal.waktu;

            if (modeGrafik === 'tegangan') {
                grafikSensor.data.datasets[0].label = 'Tegangan Input (V)';
                grafikSensor.data.datasets[0].data = riwayatDataLokal.tegangan_in;
                grafikSensor.data.datasets[1].label = 'Tegangan Keluar (V)';
                grafikSensor.data.datasets[1].data = riwayatDataLokal.tegangan_out;
            } else {
                grafikSensor.data.datasets[0].label = 'Arus Masuk (mA)';
                grafikSensor.data.datasets[0].data = riwayatDataLokal.arus_in;
                grafikSensor.data.datasets[1].label = 'Arus Keluar (mA)';
                grafikSensor.data.datasets[1].data = riwayatDataLokal.arus_out;
            }
            grafikSensor.update();
        }

        // === FUNGSI KONTROL BEBAN UNTUK TOGGLE SWITCH ===
        function kirimPerintahToggle(perangkat, isChecked) {
            let status = isChecked ? 'ON' : 'OFF';
            let token = document.querySelector('meta[name="csrf-token"]').getAttribute('content');

            fetch('/kontrol', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': token
                },
                body: JSON.stringify({ perangkat: perangkat, status: status })
            })
                .then(response => response.json())
                .then(data => {
                    if(data.success) {
                        console.log(data.message);
                    } else {
                        alert('Terjadi kesalahan: ' + data.error);
                        // Kembalikan posisi sakelar jika gagal
                        document.getElementById('switch_' + perangkat).checked = !isChecked;
                    }
                });
        }

        function updateData() {
            fetch('/api/riwayat')
                .then(res => res.json())
                .then(data => {
                    riwayatDataLokal = data;
                    renderGrafik();
                })
                .catch(err => console.error("Gagal memuat riwayat", err));

            fetch('/api/data')
                .then(res => res.json())
                .then(data => {
                    // Validasi berlapis agar tidak muncul tulisan "undefined" jika API bermasalah
                    if(data && data.v_in !== undefined) {
                        document.getElementById('tegangan_layar').innerText = data.v_in + ' V';
                        document.getElementById('arus_layar').innerText = data.a_in + ' mA';
                        document.getElementById('tegangan_out_layar').innerText = data.v_out + ' V';
                        document.getElementById('arus_out_layar').innerText = data.a_out + ' mA';
                        document.getElementById('suhu_layar').innerText = data.suhu + ' °C';
                        document.getElementById('soc_layar').innerText = data.soc + ' %';

                        document.getElementById('status_relay_charging').innerText = data.s_relay_charge || '-';
                        document.getElementById('status_charging').innerText = data.s_charge || '-';

                        document.getElementById('status_fan').innerText = 'Status: ' + (data.s_fan || '-');
                        document.getElementById('status_lamp1').innerText = 'Status: ' + (data.s_lamp1 || '-');
                        document.getElementById('status_lamp2').innerText = 'Status: ' + (data.s_lamp2 || '-');

                        // Sinkronkan posisi sakelar dengan data dari server
                        document.getElementById('switch_fan').checked = (data.s_fan === 'ON');
                        document.getElementById('switch_lamp1').checked = (data.s_lamp1 === 'ON');
                        document.getElementById('switch_lamp2').checked = (data.s_lamp2 === 'ON');
                    }
                })
                .catch(err => console.error("Gagal memuat data realtime", err));
        }

        updateData();
        setInterval(updateData, 2000);
    </script>
@endsection
