@extends('layouts.main')

@section('content')
    <style>
        /* === PENYESUAIAN TABEL UNTUK DARK MODE === */
        html.dark-mode .table {
            /* Memaksa variabel Bootstrap untuk menggunakan warna terang */
            --bs-table-color: #f8fafc;
            --bs-table-striped-color: #f8fafc;
            --bs-table-hover-color: #f8fafc;

            --bs-table-bg: transparent;
            --bs-table-striped-bg: rgba(255, 255, 255, 0.03);
            --bs-table-hover-bg: rgba(255, 255, 255, 0.08);
            border-color: #334155;
        }

        /* Memastikan seluruh teks di dalam sel tabel berwarna abu-abu terang keperakan */
        html.dark-mode .table td {
            color: #cbd5e1 !important;
            vertical-align: middle;
        }

        /* Menyesuaikan Kepala Tabel di Dark Mode */
        html.dark-mode .table-dark th {
            background-color: #0f172a !important;
            border-color: #334155 !important;
            color: #f8fafc !important;
        }

        /* Mengubah warna biru dan merah bawaan Bootstrap menjadi versi 'Pastel/Neon' agar menyala di gelap */
        html.dark-mode .table .text-primary {
            color: #60a5fa !important; /* Blue 400 */
        }
        html.dark-mode .table .text-danger {
            color: #f87171 !important; /* Red 400 */
        }

        html.dark-mode .empty-row {
            background-color: rgba(255,255,255,0.02) !important;
            color: #94a3b8 !important;
        }

        /* Memperbaiki warna teks kosong agar tidak hilang di latar terang/gelap */
        html.dark-mode .empty-row {
            background-color: rgba(255,255,255,0.02) !important;
            color: #94a3b8 !important;
        }
        /* === MEMPERBAIKI KONTRAS TOMBOL OUTLINE DI DARK MODE === */
        html.dark-mode .btn-outline-dark {
            color: #94a3b8 !important; /* Slate 400 - Abu-abu terang */
            border-color: #475569 !important; /* Slate 600 - Garis tepi lebih tegas */
        }
        html.dark-mode .btn-outline-dark:hover {
            background-color: #334155 !important;
            color: #f8fafc !important;
        }

        html.dark-mode .btn-outline-primary {
            color: #60a5fa !important; /* Biru terang (Blue 400) agar menyala di gelap */
            border-color: #3b82f6 !important;
        }
        html.dark-mode .btn-outline-primary:hover {
            background-color: rgba(59, 130, 246, 0.15) !important;
        }
        /* === MEMPERBAIKI KONTRAS TOMBOL AKTIF DI DARK MODE === */
        html.dark-mode .btn-dark {
            background-color: #475569 !important; /* Slate 600 - Abu-abu terang elegan */
            border-color: #475569 !important;
            color: #f8fafc !important;
        }
        html.dark-mode .btn-dark:hover {
            background-color: #64748b !important; /* Menyala sedikit saat disorot mouse */
            border-color: #64748b !important;
        }

        /* Menyelaraskan biru tombol utama agar senada dengan halaman Dasbor */
        html.dark-mode .btn-primary {
            background-color: #3b82f6 !important; /* Biru cerah */
            border-color: #3b82f6 !important;
            color: #ffffff !important;
        }
        html.dark-mode .btn-primary:hover {
            background-color: #2563eb !important;
        }
    </style>

    <div class="container-fluid">
        <h2 class="mb-4">Riwayat Data Sensor</h2>

        <div class="card p-4 mb-4 shadow-sm">
            <div class="row gy-4">
                <div class="col-md-12">
                    <label class="form-label fw-bold mb-2">Parameter Grafik:</label><br>
                    <div class="d-flex flex-wrap gap-2">
                        <button type="button" class="btn btn-sm btn-primary param-btn rounded-pill px-3 shadow-sm transition-all" onclick="setParam('tegangan', this)">
                            <i class="fa fa-bolt me-1"></i> Tegangan
                        </button>
                        <button type="button" class="btn btn-sm btn-outline-primary param-btn rounded-pill px-3 transition-all" onclick="setParam('arus', this)">
                            <i class="fa fa-chart-line me-1"></i> Arus
                        </button>
                        <button type="button" class="btn btn-sm btn-outline-primary param-btn rounded-pill px-3 transition-all" onclick="setParam('suhu', this)">
                            <i class="fa fa-thermometer-half me-1"></i> Suhu Baterai
                        </button>
                        <button type="button" class="btn btn-sm btn-outline-primary param-btn rounded-pill px-3 transition-all" onclick="setParam('soc', this)">
                            <i class="fa fa-battery-half me-1"></i> SoC
                        </button>
                    </div>
                </div>

                <div class="col-md-12">
                    <label class="form-label fw-bold mb-2">Rentang Waktu:</label><br>
                    <div class="d-flex flex-wrap gap-2">
                        <button type="button" class="btn btn-sm btn-dark waktu-btn rounded-pill px-3 shadow-sm transition-all" onclick="setWaktu('1', this)">1 Jam</button>
                        <button type="button" class="btn btn-sm btn-outline-dark waktu-btn rounded-pill px-3 transition-all" onclick="setWaktu('3', this)">3 Jam</button>
                        <button type="button" class="btn btn-sm btn-outline-dark waktu-btn rounded-pill px-3 transition-all" onclick="setWaktu('6', this)">6 Jam</button>
                        <button type="button" class="btn btn-sm btn-outline-dark waktu-btn rounded-pill px-3 transition-all" onclick="setWaktu('12', this)">12 Jam</button>
                        <button type="button" class="btn btn-sm btn-outline-dark waktu-btn rounded-pill px-3 transition-all" onclick="setWaktu('24', this)">24 Jam</button>
                        <button type="button" class="btn btn-sm btn-outline-dark waktu-btn rounded-pill px-3 transition-all" onclick="setWaktu('custom', this)">
                            <i class="fa fa-calendar-alt me-1"></i> Manual...
                        </button>
                    </div>
                </div>

                <div class="col-md-12" id="customDatePanel" style="display: none;">
                    <div class="d-flex flex-wrap align-items-end gap-3 p-3 bg-light rounded border mt-2">
                        <div>
                            <label class="form-label mb-1 small">Dari Tanggal/Jam:</label>
                            <input type="datetime-local" id="startDate" class="form-control form-control-sm border-secondary">
                        </div>
                        <div>
                            <label class="form-label mb-1 small">Sampai Tanggal/Jam:</label>
                            <input type="datetime-local" id="endDate" class="form-control form-control-sm border-secondary">
                        </div>
                        <button class="btn btn-success btn-sm px-4 shadow-sm rounded-pill" onclick="ambilDataHistory()">
                            <i class="fa fa-search me-1"></i> Cari Data
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <div class="card p-4 mb-4 shadow-sm">
            <canvas id="grafikHistory" style="min-height: 300px; max-height: 400px;width: 100%;"></canvas>
        </div>

        <div class="card p-0 shadow-sm overflow-hidden mb-5">
            <div class="p-3 bg-light border-bottom d-flex flex-wrap justify-content-between align-items-center gap-2">
                <h6 class="m-0 fw-bold">Tabel Data (100 Baris Terakhir)</h6>
                <button class="btn btn-success btn-sm shadow-sm rounded-pill px-3" onclick="downloadLaporan()">
                    <i class="fa fa-file-excel me-1"></i> Unduh Laporan (.CSV)
                </button>
            </div>
            <div class="table-responsive" style="max-height: 400px; overflow-y: auto;">
                <table class="table table-hover table-striped mb-0 text-center">
                    <thead class="table-dark" style="position: sticky; top: 0; z-index: 1;">
                    <tr>
                        <th class="py-3">Waktu</th>
                        <th class="py-3">V Input</th>
                        <th class="py-3">V Output</th>
                        <th class="py-3">Arus In</th>
                        <th class="py-3">Arus Out</th>
                        <th class="py-3">Suhu</th>
                        <th class="py-3">SoC</th>
                    </tr>
                    </thead>
                    <tbody id="tabelBody">
                    <tr>
                        <td colspan="7" class="py-4 empty-row">Memuat data...</td>
                    </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <script>
        let aktifParam = 'tegangan';
        let aktifWaktu = '1';
        let dataGlobal = {};
        let chartInstance = null;

        const ctx = document.getElementById('grafikHistory').getContext('2d');
        chartInstance = new Chart(ctx, {
            type: 'line',
            data: {labels: [], datasets: []},
            options: {
                responsive: true,
                maintainAspectRatio: false,
                elements: {
                    point: {radius: 1},
                    line: {borderWidth: 2}
                },
                scales: {
                    x: {
                        ticks: {
                            maxTicksLimit: 12,
                            maxRotation: 45,
                            minRotation: 45
                        }
                    }
                }
            }
        });

        function setParam(param, btnElement) {
            aktifParam = param;
            document.querySelectorAll('.param-btn').forEach(btn => {
                btn.classList.remove('btn-primary');
                btn.classList.add('btn-outline-primary');
                btn.classList.remove('shadow-sm');
            });
            btnElement.classList.remove('btn-outline-primary');
            btnElement.classList.add('btn-primary');
            btnElement.classList.add('shadow-sm');

            renderGrafik();
        }

        function setWaktu(waktu, btnElement) {
            aktifWaktu = waktu;
            document.querySelectorAll('.waktu-btn').forEach(btn => {
                btn.classList.remove('btn-dark');
                btn.classList.add('btn-outline-dark');
                btn.classList.remove('shadow-sm');
            });
            btnElement.classList.remove('btn-outline-dark');
            btnElement.classList.add('btn-dark');
            btnElement.classList.add('shadow-sm');

            if (waktu === 'custom') {
                document.getElementById('customDatePanel').style.display = 'block';
            } else {
                document.getElementById('customDatePanel').style.display = 'none';
                ambilDataHistory();
            }
        }

        function renderGrafik() {
            if (!dataGlobal.waktu) return;

            chartInstance.data.labels = dataGlobal.waktu;
            chartInstance.data.datasets = [];

            if (aktifParam === 'tegangan') {
                chartInstance.data.datasets.push({
                    label: 'Tegangan Input (V)',
                    data: dataGlobal.v_in,
                    borderColor: '#3498db',
                    borderWidth: 2,
                    tension: 0.1
                });
                chartInstance.data.datasets.push({
                    label: 'Tegangan Output (V)',
                    data: dataGlobal.v_out,
                    borderColor: '#e74c3c',
                    borderWidth: 2,
                    tension: 0.1
                });
            } else if (aktifParam === 'arus') {
                chartInstance.data.datasets.push({
                    label: 'Arus Masuk (mA)',
                    data: dataGlobal.a_in,
                    borderColor: '#f1c40f',
                    borderWidth: 2,
                    tension: 0.1
                });
                chartInstance.data.datasets.push({
                    label: 'Arus Keluar (mA)',
                    data: dataGlobal.a_out,
                    borderColor: '#e67e22',
                    borderWidth: 2,
                    tension: 0.1
                });
            } else if (aktifParam === 'suhu') {
                chartInstance.data.datasets.push({
                    label: 'Suhu Baterai (°C)',
                    data: dataGlobal.suhu,
                    borderColor: '#9b59b6',
                    borderWidth: 2,
                    tension: 0.1,
                    fill: true,
                    backgroundColor: 'rgba(155, 89, 182, 0.2)'
                });
            } else if (aktifParam === 'soc') {
                chartInstance.data.datasets.push({
                    label: 'State of Charge (%)',
                    data: dataGlobal.soc,
                    borderColor: '#2ecc71',
                    borderWidth: 2,
                    tension: 0.1,
                    fill: true,
                    backgroundColor: 'rgba(46, 204, 113, 0.2)'
                });
            }

            chartInstance.update();
        }

        function renderTabel(tabelData) {
            let tbody = document.getElementById('tabelBody');
            tbody.innerHTML = '';

            if (tabelData.length === 0) {
                tbody.innerHTML = '<tr><td colspan="7" class="py-4 empty-row">Tidak ada data di rentang waktu ini.</td></tr>';
                return;
            }

            tabelData.forEach(row => {
                let tr = `<tr>
                <td>${row.waktu_tabel}</td>
                <td><b class="text-primary">${row.v_in} V</b></td>
                <td><b class="text-danger">${row.v_out} V</b></td>
                <td>${row.a_in} mA</td>
                <td>${row.a_out} mA</td>
                <td>${row.suhu} °C</td>
                <td>${row.soc} %</td>
            </tr>`;
                tbody.innerHTML += tr;
            });
        }

        function ambilDataHistory() {
            let url = `/api/history-filter?rentang=${aktifWaktu}`;

            if (aktifWaktu === 'custom') {
                let start = document.getElementById('startDate').value;
                let end = document.getElementById('endDate').value;
                if (!start || !end) {
                    alert("Harap isi kedua tanggal batas manual!");
                    return;
                }
                url += `&start=${start}&end=${end}`;
            }

            document.getElementById('tabelBody').innerHTML = '<tr><td colspan="7" class="py-4 empty-row">Memuat data dari server...</td></tr>';

            fetch(url)
                .then(res => res.json())
                .then(data => {
                    dataGlobal = data;
                    renderGrafik();
                    renderTabel(data.table_data);
                })
                .catch(err => console.error("Error mengambil data:", err));
        }
        //history
        ambilDataHistory();

        function downloadLaporan() {
            let url = `/history/export?rentang=${aktifWaktu}`;

            if (aktifWaktu === 'custom') {
                let start = document.getElementById('startDate').value;
                let end = document.getElementById('endDate').value;
                if (!start || !end) {
                    alert("Harap isi kedua tanggal batas manual!");
                    return;
                }
                url += `&start=${start}&end=${end}`;
            }

            window.location.href = url;
        }
    </script>
@endsection
