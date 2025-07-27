@php
    // ==========================================
    // PROSES QUERY DATA DASHBOARD PLAZAFEST
    // ==========================================

    // Mendapatkan bulan saat ini untuk filter
    $currentMonth = date('Y-m');

    // 1. TOTAL FASILITAS
    $totalFasilitas = DB::table('facility')->count();

    // 2. TRANSAKSI BULAN INI
    $totalTransaksiBulanIni = DB::table('transaction')
        ->whereRaw("DATE_FORMAT(created_at, '%Y-%m') = ?", [$currentMonth])
        ->count();
    $totalNilaiBulanIni = DB::table('transaction')
        ->whereRaw("DATE_FORMAT(created_at, '%Y-%m') = ?", [$currentMonth])
        ->where('status', 'capture')
        ->sum('price');
    // 3. TOTAL USER AKTIF
    $totalUsers = DB::table('users')->where('is_active', 1)->count();

    // 4. USER BARU BULAN INI
    $usersBulanIni = DB::table('users')
        ->whereRaw("DATE_FORMAT(created_at, '%Y-%m') = ?", [$currentMonth])
        ->where('is_active', 1)
        ->count();

    // 5. STATUS TRANSAKSI BULAN INI
    $statusTransaksi = DB::table('transaction')
        ->select('status', DB::raw('COUNT(*) as total'))
        ->whereRaw("DATE_FORMAT(created_at, '%Y-%m') = ?", [$currentMonth])
        ->groupBy('status')
        ->get();

    // 6. FASILITAS POPULER BULAN INI
    $fasilitasPopuler = DB::table('transaction')
        ->join('sub_facility', 'transaction.idsubfacility', '=', 'sub_facility.id')
        ->join('facility', 'sub_facility.idfacility', '=', 'facility.id')
        ->select('facility.nama as nama_fasilitas', 'sub_facility.name as subfacility_name', DB::raw('COUNT(*) as booking_count'))
        ->whereRaw("DATE_FORMAT(transaction.created_at, '%Y-%m') = ?", [$currentMonth])
        ->groupBy('facility.id', 'sub_facility.id', 'facility.nama', 'sub_facility.name')
        ->orderBy('booking_count', 'desc')
        ->limit(5)
        ->get();
    // dd($fasilitasPopuler);

    // 7. TREND TRANSAKSI 7 HARI TERAKHIR
    $trendMingguan = collect();
    for($i = 6; $i >= 0; $i--) {
        $date = date('Y-m-d', strtotime("-$i days"));
        $count = DB::table('transaction')
            ->whereDate('created_at', $date)
            ->count();
        $trendMingguan->push([
            'date' => $date,
            'day_name' => date('D', strtotime($date)),
            'count' => $count
        ]);
    }

    // 8. STATISTIK TAMBAHAN
    $bulanNama = date('F Y'); // Nama bulan dan tahun saat ini

    // dd(
    //     $totalFasilitas,
    //     $totalTransaksiBulanIni,
    //     $totalNilaiBulanIni,
    //     $totalUsers,
    //     $usersBulanIni,
    //     $statusTransaksi,
    //     $fasilitasPopuler,
    //     $trendMingguan
    // )
@endphp

<div class="center container">
    <div class="row">
        <!-- Dashboard Statistics Widgets -->

        <!-- Total Fasilitas -->
        <div class="col-lg-3 col-md-6 col-sm-12 mb-3">
            <div class="card h-100">
                <div class="card-img-top hidden d-flex align-items-center justify-content-center" style="height: 120px; background: linear-gradient(135deg, #3498db, #2980b9);">
                    {{-- <i class="voyager-home text-white" style="font-size: 4em; opacity: 0.9;"></i> --}}
                </div>
                <div class="card-body text-center d-flex flex-column">
                    <h5 class="card-title text-wrap">Total Fasilitas</h5>
                    <h2 class="text-primary mb-3" style="font-size: 2.5em; font-weight: bold;">
                        {{ number_format($totalFasilitas) }}
                    </h2>
                    <p class="card-text flex-grow-1">Semua fasilitas yang tersedia</p>
                    <a href="{{ route('voyager.facility.index') }}" class="btn btn-primary mt-auto">Lihat Detail</a>
                </div>
            </div>
        </div>

        <!-- Total Transaksi Bulan Ini -->
        <div class="col-lg-3 col-md-6 col-sm-12 mb-3">
            <div class="card h-100">
                <div class="card-img-top hidden d-flex align-items-center justify-content-center" style="height: 120px; background: linear-gradient(135deg, #27ae60, #229954);">
                    {{-- <i class="voyager-credit-cards text-white" style="font-size: 4em; opacity: 0.9;"></i> --}}
                </div>
                <div class="card-body text-center d-flex flex-column">
                    <h5 class="card-title text-wrap">Transaksi Bulan Ini</h5>
                    <h2 class="text-success mb-2" style="font-size: 2.5em; font-weight: bold;">
                        {{ number_format($totalTransaksiBulanIni) }}
                    </h2>
                    <p class="card-text flex-grow-1">
                        Total nilai: <strong>Rp {{ number_format($totalNilaiBulanIni, 0, ',', '.') }}</strong>
                    </p>
                    <a href="{{ route('voyager.transaction.index') }}" class="btn btn-success mt-auto">Lihat Transaksi</a>
                </div>
            </div>
        </div>

        <!-- Total User Terdaftar -->
        <div class="col-lg-3 col-md-6 col-sm-12 mb-3">
            <div class="card h-100">
                <div class="card-img-top hidden d-flex align-items-center justify-content-center" style="height: 120px; background: linear-gradient(135deg, #e74c3c, #c0392b);">
                    {{-- <i class="voyager-group text-white" style="font-size: 4em; opacity: 0.9;"></i> --}}
                </div>
                <div class="card-body text-center d-flex flex-column">
                    <h5 class="card-title text-wrap">Total User Aktif</h5>
                    <h2 class="text-danger mb-3" style="font-size: 2.5em; font-weight: bold;">
                        {{ number_format($totalUsers) }}
                    </h2>
                    <p class="card-text flex-grow-1">User yang terdaftar dan aktif menggunakan aplikasi</p>
                    <div class="mt-auto">
                        {{-- <a href="{{ route('voyager.users.index') }}" class="btn btn-danger">Kelola User</a> --}}
                        <span class="text-muted">Data pengguna aktif</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- User Terdaftar Bulan Ini -->
        <div class="col-lg-3 col-md-6 col-sm-12 mb-3">
            <div class="card h-100">
                <div class="card-img-top hidden d-flex align-items-center justify-content-center" style="height: 120px; background: linear-gradient(135deg, #9b59b6, #8e44ad);">
                    {{-- <i class="voyager-person text-white" style="font-size: 4em; opacity: 0.9;"></i> --}}
                </div>
                <div class="card-body text-center d-flex flex-column">
                    <h5 class="card-title text-wrap">User Baru Bulan Ini</h5>
                    <h2 class="text-info mb-3" style="font-size: 2.5em; font-weight: bold;">
                        {{ number_format($usersBulanIni) }}
                    </h2>
                    <p class="card-text flex-grow-1">Registrasi baru pada {{ $bulanNama }}</p>
                    <a href="{{ route('voyager.users.index') }}?filter=new" class="btn btn-info mt-auto">Lihat User Baru</a>
                </div>
            </div>
        </div>
    </div>

    <!-- Additional Statistics Row -->
    <div class="row">
        <!-- Status Transaksi Chart -->
        <div class="col-lg-6 col-md-12 mb-3">
            <div class="card" style="width: 100%;">
                <div class="card-img-top hidden d-flex align-items-center justify-content-center" style="height: 80px; background: linear-gradient(135deg, #34495e, #2c3e50);">
                    {{-- <i class="voyager-pie-chart text-white" style="font-size: 3em; opacity: 0.9;"></i> --}}
                </div>
                <div class="card-body">
                    <h5 class="card-title text-wrap">Status Transaksi Bulan Ini</h5>
                    <div class="row">
                        @foreach($statusTransaksi as $status)
                            <div class="col-md-6 col-sm-12 mb-2">
                                <div class="p-2 border rounded">
                                    <div class="d-flex justify-content-between align-items-center">
                                        <span class="text-wrap text-capitalize">
                                            {{ ucfirst($status->status ?? 'Unknown') }}
                                        </span>
                                        <span class="badge bg-secondary">
                                            {{ number_format($status->total) }}
                                        </span>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                    <a href="{{ route('voyager.transaction.index') }}" class="btn btn-secondary mt-3">Lihat Semua Status</a>
                </div>
            </div>
        </div>

        <!-- Fasilitas Populer -->
        <div class="col-lg-6 col-md-12 mb-3">
            <div class="card" style="width: 100%;">
                <div class="card-img-top hidden d-flex align-items-center justify-content-center" style="height: 80px; background: linear-gradient(135deg, #16a085, #138d75);">
                    {{-- <i class="voyager-heart text-white" style="font-size: 3em; opacity: 0.9;"></i> --}}
                </div>
                <div class="card-body">
                    <h5 class="card-title text-wrap">Fasilitas Populer Bulan Ini</h5>
                    @if($fasilitasPopuler->count() > 0)
                        @foreach($fasilitasPopuler as $index => $fasilitas)
                            <div class="mb-2 p-2 border rounded">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <span class="badge bg-primary me-2">
                                            #{{ $index + 1 }}
                                        </span>
                                        <span class="text-wrap">{{ $fasilitas->nama_fasilitas }}</span>
                                    </div>
                                    <span class="badge bg-success">
                                        {{ number_format($fasilitas->booking_count) }} booking
                                    </span>
                                </div>
                            </div>
                        @endforeach
                        <a href="{{ route('voyager.facility.index') }}" class="btn btn-primary mt-3">Kelola Fasilitas</a>
                    @else
                        <div class="text-center py-4">
                            {{-- <i class="voyager-info-circled text-muted" style="font-size: 3em;"></i> --}}
                            <p class="mt-3 mb-0 text-muted">
                                Belum ada data booking bulan ini
                            </p>
                            <a href="{{ route('voyager.facility.index') }}" class="btn btn-outline-primary mt-3">Lihat Fasilitas</a>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <!-- Performance Metrics Row -->
    <div class="row">
        <!-- Grafik Trend Mingguan -->
        <div class="col-lg-12 mb-3">
            <div class="card" style="width: 100%;">
                <div class="card-img-top hidden d-flex align-items-center justify-content-center" style="height: 80px; background: linear-gradient(135deg, #2c3e50, #34495e);">
                    <i class="voyager-activity text-white" style="font-size: 3em; opacity: 0.9;"></i>
                </div>
                <div class="card-body">
                    <h5 class="card-title text-wrap">Trend Transaksi 7 Hari Terakhir</h5>
                    @if($trendMingguan->count() > 0)
                        <!-- Chart Container -->
                        <div id="trendChart" style="width: 100%;"></div>

                        <!-- Data Table (Optional - can be hidden) -->
                        <div class="mt-4" style="max-height: 200px; overflow-y: auto;">
                            <h6>Detail Data:</h6>
                            @foreach($trendMingguan as $trend)
                                <div class="mb-1 p-2 border rounded-sm">
                                    <div class="d-flex justify-content-between align-items-center">
                                        <div>
                                            <span class="text-wrap">
                                                {{ \Carbon\Carbon::parse($trend["date"])->format('d M Y') }}
                                            </span>
                                            <small class="ms-2 text-muted">
                                                ({{ $trend["day_name"] }})
                                            </small>
                                        </div>
                                        <span class="badge bg-dark">
                                            {{ number_format($trend["count"]) }} transaksi
                                        </span>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @else
                        <div class="text-center py-4">
                            <i class="voyager-info-circled text-muted" style="font-size: 3em;"></i>
                            <p class="mt-3 mb-0 text-muted">
                                Belum ada data transaksi
                            </p>
                        </div>
                    @endif
                    <a href="{{ route('voyager.transaction.index') }}" class="btn btn-dark mt-3">Analisis Lengkap</a>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- ApexCharts Script -->
<script src="https://cdn.jsdelivr.net/npm/apexcharts"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    @if($trendMingguan->count() > 0)
    // Prepare data for ApexCharts
    const trendData = @json($trendMingguan);

    const labels = trendData.map(item => {
        const date = new Date(item.date);
        return date.toLocaleDateString('id-ID', {
            weekday: 'short',
            day: 'numeric',
            month: 'short'
        });
    });

    const data = trendData.map(item => item.count);

    // ApexCharts configuration
    const options = {
        series: [{
            name: 'Jumlah Transaksi',
            data: data
        }],
        chart: {
            type: 'line',
            height: 400,
            toolbar: {
                show: true,
                tools: {
                    download: true,
                    selection: false,
                    zoom: true,
                    zoomin: true,
                    zoomout: true,
                    pan: false,
                    reset: true
                }
            },
            animations: {
                enabled: true,
                easing: 'easeinout',
                speed: 800,
                animateGradually: {
                    enabled: true,
                    delay: 150
                },
                dynamicAnimation: {
                    enabled: true,
                    speed: 350
                }
            }
        },
        colors: ['#2c3e50'],
        stroke: {
            width: 4,
            curve: 'smooth'
        },
        fill: {
            type: 'gradient',
            gradient: {
                shade: 'light',
                type: 'vertical',
                shadeIntensity: 0.5,
                gradientToColors: ['#34495e'],
                inverseColors: false,
                opacityFrom: 0.8,
                opacityTo: 0.1,
                stops: [0, 100]
            }
        },
        markers: {
            size: 6,
            colors: ['#2c3e50'],
            strokeColors: '#ffffff',
            strokeWidth: 2,
            hover: {
                size: 8,
                sizeOffset: 2
            }
        },
        grid: {
            borderColor: 'rgba(44, 62, 80, 0.1)',
            strokeDashArray: 5,
            xaxis: {
                lines: {
                    show: true
                }
            },
            yaxis: {
                lines: {
                    show: true
                }
            }
        },
        xaxis: {
            categories: labels,
            title: {
                text: 'Tanggal',
                style: {
                    color: '#2c3e50',
                    fontSize: '14px',
                    fontWeight: 'bold'
                }
            },
            labels: {
                style: {
                    colors: '#2c3e50',
                    fontSize: '12px'
                }
            },
            axisBorder: {
                show: true,
                color: 'rgba(44, 62, 80, 0.2)'
            },
            axisTicks: {
                show: true,
                color: 'rgba(44, 62, 80, 0.2)'
            }
        },
        yaxis: {
            title: {
                text: 'Jumlah Transaksi',
                style: {
                    color: '#2c3e50',
                    fontSize: '14px',
                    fontWeight: 'bold'
                }
            },
            labels: {
                style: {
                    colors: '#2c3e50',
                    fontSize: '12px'
                },
                formatter: function(value) {
                    return value.toLocaleString('id-ID');
                }
            }
        },
        tooltip: {
            theme: 'dark',
            style: {
                fontSize: '14px',
                fontFamily: 'inherit'
            },
            x: {
                formatter: function(value, { dataPointIndex }) {
                    const dateInfo = trendData[dataPointIndex];
                    return `${labels[dataPointIndex]} (${dateInfo.day_name})`;
                }
            },
            y: {
                formatter: function(value) {
                    return value.toLocaleString('id-ID') + ' transaksi';
                }
            },
            marker: {
                show: true
            }
        },
        legend: {
            show: true,
            position: 'top',
            horizontalAlign: 'left',
            fontSize: '14px',
            fontWeight: 'bold',
            labels: {
                colors: '#2c3e50'
            }
        },
        dataLabels: {
            enabled: false
        },
        responsive: [{
            breakpoint: 768,
            options: {
                chart: {
                    height: 300
                },
                legend: {
                    position: 'bottom'
                }
            }
        }]
    };

    const chart = new ApexCharts(document.querySelector("#trendChart"), options);
    chart.render();
    @endif
});
</script>
