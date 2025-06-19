<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class HomeController extends Controller
{
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('auth');
    }

    /**
     * Show the application dashboard based on user role.
     *
     * @return \Illuminate\Contracts\Support\Renderable
     */
    public function index()
    {
        $userType = Auth::user()->type;

        // **Untuk Administrator, tampilkan halaman Input Pembayaran**
        if ($userType == 1) {
            // Logika ini diambil dari PembayaranController@index
            $metode_pembayaran_list = ['Tunai', 'TransferBank', 'Lainnya'];
            // Kita juga bisa mengirim data user jika ingin ada sapaan
            $datauser = ['nama' => Auth::user()->name];

            // Langsung render view milik Input Pembayaran
            return view('cek_tagihan.index', compact('metode_pembayaran_list', 'datauser'));
        }

        // **Untuk Operator, tampilkan halaman Input Pencatatan Meter**
        elseif ($userType == 2) {
            // Logika ini diambil dari PencatatanMeterController@index
            $wilayahs = DB::table('wilayah')->select('wilayah_id', 'nama_wilayah')->orderBy('nama_wilayah')->get();
            $tahun_sekarang = Carbon::now()->year;
            $tahuns = [];
            for ($i = 0; $i < 5; $i++) {
                $tahuns[] = $tahun_sekarang - $i;
            }
            $bulans = [
                1 => 'Januari',
                2 => 'Februari',
                3 => 'Maret',
                4 => 'April',
                5 => 'Mei',
                6 => 'Juni',
                7 => 'Juli',
                8 => 'Agustus',
                9 => 'September',
                10 => 'Oktober',
                11 => 'November',
                12 => 'Desember'
            ];
            $datauser = ['nama' => Auth::user()->name];

            // Langsung render view milik Input Pencatatan Meter
            return view('transaksi.pencatatan_meter.index', compact('wilayahs', 'tahuns', 'bulans', 'datauser'));
        }

        // **Untuk role lain atau sebagai fallback, tampilkan halaman selamat datang biasa**
        return view('welcome'); // atau view home.blade.php yang sederhana
    }
}
