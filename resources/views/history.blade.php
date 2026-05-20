@extends('layouts.main')

@section('content')
    <div class="container-fluid">
        <h2 class="mb-4">Riwayat Data Sensor</h2>

        <div class="card p-4 mb-4 shadow-sm">
            <div class="row gy-3">
                <div class="col-md-12">
                    <label class="form-label fw-bold">Parameter Grafik:</label><br>
                    <div class="btn-group" role="group">
                        <button type="button" class="btn btn-primary param-btn" onclick="setParam('tegangan', this)">
                            Tegangan
                        </button>
                        <button type="button" class="btn btn-outline-primary param-btn"
                                onclick="setParam('arus', this)">Arus
                        </button>
                        <button type="button" class="btn btn-outline-primary param-btn"
                                onclick="setParam('suhu', this)">Suhu Baterai
                        </button>
                        <button type="button" class="btn btn-outline-primary param-btn" onclick="setParam('soc', this)">
                            SoC
                        </button>
                    </div>
                </div>

                <div class="col-md-12">
                    <label class="form-label fw-bold">Rentang Waktu:</label><br>
                    <div class="btn-group" role="group">
                        <button type="button" class="btn btn-dark waktu-btn" onclick="setWaktu('1', this)">1 Jam
                        </button>
                        <button type="button" class="btn btn-outline-dark waktu-btn" onclick="setWaktu('3', this)">3
                            Jam
                        </button>
                        <button type="button" class="btn btn-outline-dark waktu-btn" onclick="setWaktu('6', this)">6
                            Jam
                        </button>
                        <button type="button" class="btn btn-outline-dark waktu-btn" onclick="setWaktu('12', this)">12
                            Jam
                        </button>
                        <button type="button" class="btn btn-outline-dark waktu-btn" onclick="setWaktu('24', this)">24
                            Jam
                        </button>
                        <button type="button" class="btn btn-outline-dark waktu-btn" onclick="setWaktu('custom', this)">
                            Manual...
                        </button>
                    </div>
                </div>

                <div class="col-md-12" id="customDatePanel" style="display: none;">
                    <div class="d-flex align-items-end gap-3 p-3 bg-light rounded border">
                        <div>
                            <label class="form-label mb-1 small">Dari Tanggal/Jam:</label>
                            <input type="datetime-local" id="startDate" class="form-control form-control-sm">
                        </div>
                        <div>
                            <label class="form-label mb-1 small">Sampai Tanggal/Jam:</label>
                            <input type="datetime-local" id="endDate" class="form-control form-control-sm">
                        </div>
                        <button class="btn btn-success btn-sm px-4" onclick="ambilDataHistory()">Cari Data</button>
                    </div>
                </div>
            </div>
        </div>

        <div class="card p-4 mb-4 shadow-sm">
            <canvas id="grafikHistory" style="max-height: 400px;"></canvas>
        </div>

        <div class="card p-0 shadow-sm overflow-hidden">
            <div class="p-3 bg-light border-bottom d-flex justify-content-between align-items-center">
                <h6 class="m-0 text-muted">Tabel Data (100 Baris Terakhir dari Rentang Pencarian)</h6>
                <button class="btn btn-success btn-sm shadow-sm font-weight-bold" onclick="downloadLaporan()">
                    <i class="fa fa-file-excel me-1"></i> Unduh Laporan (.CSV)
                </button>
            </div>
            <div class="table-responsive" style="max-height: 400px; overflow-y: auto;">
                <table class="table table-hover table-striped mb-0 text-center">
                    <thead class="table-dark" style="position: sticky; top: 0; z-index: 1;">
                    <tr>
                        <th>Waktu</th>
                        <th>V Input</th>
                        <th>V Output</th>
                        <th>Arus In</th>
                        <th>Arus Out</th>
                        <th>Suhu</th>
                        <th>SoC</th>
                    </tr>
                    </thead>
                    <tbody id="tabelBody">
                    <tr>
                        <td colspan="7">Memuat data...</td>
                    </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <script>
        let aktifParam = 'tegangan';
        let aktifWaktu = '1';
        let dataGlobal = {}; // Menyimpan data tarikan terakhir agar perpindahan parameter instan
        let chartInstance = null;

        // Inisialisasi Chart
        // const ctx = document.getElementById('grafikHistory').getContext('2d');
        // chartInstance = new Chart(ctx, {
        //     type: 'line',
        //     data: { labels: [], datasets: [] },
        //     options: { responsive: true, maintainAspectRatio: false, elements: { point: { radius: 1 } } }
        // });

        // Inisialisasi Chart yang Dioptimalkan
        const ctx = document.getElementById('grafikHistory').getContext('2d');
        chartInstance = new Chart(ctx, {
            type: 'line',
            data: {labels: [], datasets: []},
            options: {
                responsive: true,
                maintainAspectRatio: false,
                elements: {
                    point: {radius: 1}, // MENGHILANGKAN TITIK: Hanya menggambar garis murni
                    line: {borderWidth: 2} // Mengatur ketebalan garis
                },
                scales: {
                    x: {
                        ticks: {
                            maxTicksLimit: 12, // MAKSIMAL LABEL: Hanya tampilkan 12 jam di sumbu X agar rapi
                            maxRotation: 45,
                            minRotation: 45
                        }
                    }
                }
            }
        });

        // Fungsi klik tombol Parameter
        function setParam(param, btnElement) {
            aktifParam = param;
            document.querySelectorAll('.param-btn').forEach(btn => {
                btn.classList.remove('btn-primary');
                btn.classList.add('btn-outline-primary');
            });
            btnElement.classList.remove('btn-outline-primary');
            btnElement.classList.add('btn-primary');

            renderGrafik(); // Langsung ganti grafik tanpa request ke server
        }

        // Fungsi klik tombol Waktu
        function setWaktu(waktu, btnElement) {
            aktifWaktu = waktu;
            document.querySelectorAll('.waktu-btn').forEach(btn => {
                btn.classList.remove('btn-dark');
                btn.classList.add('btn-outline-dark');
            });
            btnElement.classList.remove('btn-outline-dark');
            btnElement.classList.add('btn-dark');

            if (waktu === 'custom') {
                document.getElementById('customDatePanel').style.display = 'block';
            } else {
                document.getElementById('customDatePanel').style.display = 'none';
                ambilDataHistory(); // Ambil data baru dari server
            }
        }

        // Fungsi Menggambar Grafik Berdasarkan Parameter Terpilih
        function renderGrafik() {
            if (!dataGlobal.waktu) return;

            chartInstance.data.labels = dataGlobal.waktu;
            chartInstance.data.datasets = []; // Kosongkan dataset sebelumnya

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

        // Fungsi Merender Tabel
        function renderTabel(tabelData) {
            let tbody = document.getElementById('tabelBody');
            tbody.innerHTML = ''; // Bersihkan tabel

            if (tabelData.length === 0) {
                tbody.innerHTML = '<tr><td colspan="7" class="text-muted">Tidak ada data di rentang waktu ini.</td></tr>';
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

        // Fungsi Tarik Data dari Server (Laravel API)
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

            // Tampilkan loading di tabel
            document.getElementById('tabelBody').innerHTML = '<tr><td colspan="7">Memuat data dari server...</td></tr>';

            fetch(url)
                .then(res => res.json())
                .then(data => {
                    dataGlobal = data; // Simpan ke global
                    renderGrafik();
                    renderTabel(data.table_data);
                })
                .catch(err => console.error("Error mengambil data:", err));
        }

        // Panggil saat halaman pertama kali dimuat
        ambilDataHistory();

        // Fungsi untuk mengarahkan browser mendownload data sesuai filter aktif
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

            // Jalankan perintah unduh file
            window.location.href = url;
        }
    </script>
@endsection
