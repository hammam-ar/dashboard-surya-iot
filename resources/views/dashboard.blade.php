@extends('layouts.main')

@section('content')
    <div class="container-fluid">
        <h2 class="mb-4">Status Real-time</h2>

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

        <div class="row mt-4">
            <div class="col-md-8">
                <div class="card p-4 shadow-sm">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h5 class="m-0">Grafik Performa (15 Data Terakhir)</h5>
                        <div class="btn-group">
                            <button id="btn-tegangan" class="btn btn-primary btn-sm shadow-sm" onclick="ubahModeGrafik('tegangan')">Tegangan (V)</button>
                            <button id="btn-arus" class="btn btn-outline-primary btn-sm shadow-sm" onclick="ubahModeGrafik('arus')">Arus (mA)</button>
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
                        <div class="btn-group">
                            <button class="btn btn-sm btn-success shadow-sm" onclick="kirimPerintah('fan', 'ON')">ON</button>
                            <button class="btn btn-sm btn-danger shadow-sm" onclick="kirimPerintah('fan', 'OFF')">OFF</button>
                        </div>
                    </div>

                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <span>Lampu 1 <br><small class="text-muted" id="status_lamp1">Status: -</small></span>
                        <div class="btn-group">
                            <button class="btn btn-sm btn-success shadow-sm" onclick="kirimPerintah('lamp1', 'ON')">ON</button>
                            <button class="btn btn-sm btn-danger shadow-sm" onclick="kirimPerintah('lamp1', 'OFF')">OFF</button>
                        </div>
                    </div>

                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <span>Lampu 2 <br><small class="text-muted" id="status_lamp2">Status: -</small></span>
                        <div class="btn-group">
                            <button class="btn btn-sm btn-success shadow-sm" onclick="kirimPerintah('lamp2', 'ON')">ON</button>
                            <button class="btn btn-sm btn-danger shadow-sm" onclick="kirimPerintah('lamp2', 'OFF')">OFF</button>
                        </div>
                    </div>

                    <div class="mt-3 p-2 bg-light rounded">
                        <small class="text-muted">Status Charging: <b id="status_charging">{{ $dataTerbaru->s_charge ?? '-' }}</b></small>
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
                    { label: 'Input', data: [], borderColor: '#3498db', backgroundColor: 'rgba(52, 152, 219, 0.1)', tension: 0.4 },
                    { label: 'Output', data: [], borderColor: '#e74c3c', backgroundColor: 'rgba(231, 76, 60, 0.1)', tension: 0.4 }
                ]
            },
            options: {
                responsive: true,
                scales: { y: { type: 'linear', display: true, position: 'left' } }
            }
        });

        function ubahModeGrafik(mode) {
            modeGrafik = mode;

            if (mode === 'tegangan') {
                document.getElementById('btn-tegangan').className = 'btn btn-primary btn-sm shadow-sm';
                document.getElementById('btn-arus').className = 'btn btn-outline-primary btn-sm shadow-sm';
            } else {
                document.getElementById('btn-tegangan').className = 'btn btn-outline-primary btn-sm shadow-sm';
                document.getElementById('btn-arus').className = 'btn btn-primary btn-sm shadow-sm';
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

        function kirimPerintah(perangkat, status) {
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
                    }
                });
        }

        function updateData() {
            fetch('/api/riwayat')
                .then(res => res.json())
                .then(data => {
                    riwayatDataLokal = data;
                    renderGrafik();
                });

            fetch('/api/data')
                .then(res => res.json())
                .then(data => {
                    if(data) {
                        document.getElementById('tegangan_layar').innerText = data.v_in + ' V';
                        document.getElementById('arus_layar').innerText = data.a_in + ' mA';
                        document.getElementById('tegangan_out_layar').innerText = data.v_out + ' V';
                        document.getElementById('arus_out_layar').innerText = data.a_out + ' mA';
                        document.getElementById('suhu_layar').innerText = data.suhu + ' °C';
                        document.getElementById('soc_layar').innerText = data.soc + ' %';
                        document.getElementById('status_charging').innerText = data.s_charge;

                        document.getElementById('status_fan').innerText = 'Status: ' + data.s_fan;
                        document.getElementById('status_lamp1').innerText = 'Status: ' + data.s_lamp1;
                        document.getElementById('status_lamp2').innerText = 'Status: ' + data.s_lamp2;
                    }
                });
        }

        updateData();
        setInterval(updateData, 2000);
    </script>
@endsection
