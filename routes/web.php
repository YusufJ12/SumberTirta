<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Auth;

use App\Http\Controllers\HomeController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\RoleController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\WilayahController;
use App\Http\Controllers\GolonganTarifController;
use App\Http\Controllers\TarifController;
use App\Http\Controllers\PelangganController;
use App\Http\Controllers\AturanDendaController;
use App\Http\Controllers\PencatatanMeterController;
use App\Http\Controllers\PembuatanTagihanController;
use App\Http\Controllers\ManajemenTagihanController;
use App\Http\Controllers\PembayaranController;
use App\Http\Controllers\LaporanTagihanController;
use App\Http\Controllers\LaporanTunggakanController;
use App\Http\Controllers\LaporanPembayaranController;
use App\Http\Controllers\DaftarCatatMeterController;
use App\Http\Controllers\GantiMeterController;
use App\Http\Controllers\CekTagihanController;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

Route::get('/', function () {
    return view('../auth/login');
});

Auth::routes();

Route::get('/check-session-status', function () {
    if (auth()->check()) {
        // Pengguna masih terotentikasi
        return response()->json(['status' => 'active']);
    } else {
        // Sesinya telah habis
        return response()->json(['status' => 'expired']);
    }
})->name('check-session-status');

Route::middleware(['middleware' => 'auth'])->group(function () {
    Route::get('/home', [HomeController::class, 'index'])->name('home');
    Route::get('/profile', [ProfileController::class, 'index'])->name('profile');
    Route::put('/profile', [ProfileController::class, 'update'])->name('profile.update');


    Route::middleware(['type:1'])->group(function () {
        Route::resource('roles', RoleController::class);
        Route::resource('users', UserController::class);

        Route::get('/cek-tagihan', [CekTagihanController::class, 'index'])->name('cek_tagihan.index');
        Route::get('/cek-tagihan/search-pelanggan', [CekTagihanController::class, 'searchPelanggan'])->name('cek_tagihan.search_pelanggan');
        Route::get('/cek-tagihan/get-data', [CekTagihanController::class, 'getTagihanData'])->name('cek_tagihan.get_data');

        Route::get('/master/wilayah', [WilayahController::class, 'index'])->name('wilayah.index');
        Route::get('/master/wilayah-data', [WilayahController::class, 'getWilayahData'])->name('wilayah.data');
        Route::post('/master/wilayah/store', [WilayahController::class, 'store'])->name('wilayah.store');
        Route::get('/master/wilayah/edit/{wilayah_id}', [WilayahController::class, 'edit'])->name('wilayah.edit');
        Route::post('/master/wilayah/update/{wilayah_id}', [WilayahController::class, 'update'])->name('wilayah.update');
        Route::delete('/master/wilayah/delete/{wilayah_id}', [WilayahController::class, 'destroy'])->name('wilayah.destroy');

        Route::get('/master/golongan-tarif', [GolonganTarifController::class, 'index'])->name('golongan_tarif.index');
        Route::get('/master/golongan-tarif-data', [GolonganTarifController::class, 'getGolonganTarifData'])->name('golongan_tarif.data');
        Route::post('/master/golongan-tarif/store', [GolonganTarifController::class, 'store'])->name('golongan_tarif.store');
        Route::get('/master/golongan-tarif/edit/{golongan_id}', [GolonganTarifController::class, 'edit'])->name('golongan_tarif.edit');
        Route::post('/master/golongan-tarif/update/{golongan_id}', [GolonganTarifController::class, 'update'])->name('golongan_tarif.update');
        Route::delete('/master/golongan-tarif/delete/{golongan_id}', [GolonganTarifController::class, 'destroy'])->name('golongan_tarif.destroy');

        Route::get('/master/tarif', [TarifController::class, 'index'])->name('tarif.index');
        Route::get('/master/tarif-data', [TarifController::class, 'getTarifData'])->name('tarif.data');
        Route::get('/master/tarif/create-data', [TarifController::class, 'getCreateData'])->name('tarif.create_data'); // Untuk data dropdown di form
        Route::post('/master/tarif/store', [TarifController::class, 'store'])->name('tarif.store');
        Route::get('/master/tarif/edit/{tarif_id}', [TarifController::class, 'edit'])->name('tarif.edit');
        Route::post('/master/tarif/update/{tarif_id}', [TarifController::class, 'update'])->name('tarif.update');
        Route::delete('/master/tarif/delete/{tarif_id}', [TarifController::class, 'destroy'])->name('tarif.destroy');

        Route::get('/master/pelanggan', [PelangganController::class, 'index'])->name('pelanggan.index');
        Route::get('/master/pelanggan-data', [PelangganController::class, 'getPelangganData'])->name('pelanggan.data');
        Route::post('/master/pelanggan/store', [PelangganController::class, 'store'])->name('pelanggan.store');
        Route::get('/master/pelanggan/edit/{pelanggan_id}', [PelangganController::class, 'edit'])->name('pelanggan.edit');
        Route::post('/master/pelanggan/update/{pelanggan_id}', [PelangganController::class, 'update'])->name('pelanggan.update');
        Route::delete('/master/pelanggan/delete/{pelanggan_id}', [PelangganController::class, 'destroy'])->name('pelanggan.destroy');
        Route::get('/master/pelanggan/export-excel', [PelangganController::class, 'exportExcel'])->name('pelanggan.export_excel');
        Route::get('/master/pelanggan/export-pdf', [PelangganController::class, 'exportPdf'])->name('pelanggan.export_pdf');

        Route::get('/master/aturan-denda', [AturanDendaController::class, 'index'])->name('aturan_denda.index');
        Route::get('/master/aturan-denda-data', [AturanDendaController::class, 'getAturanDendaData'])->name('aturan_denda.data');
        Route::post('/master/aturan-denda/store', [AturanDendaController::class, 'store'])->name('aturan_denda.store');
        Route::get('/master/aturan-denda/edit/{aturan_denda_id}', [AturanDendaController::class, 'edit'])->name('aturan_denda.edit');
        Route::post('/master/aturan-denda/update/{aturan_denda_id}', [AturanDendaController::class, 'update'])->name('aturan_denda.update');
        Route::delete('/master/aturan-denda/delete/{aturan_denda_id}', [AturanDendaController::class, 'destroy'])->name('aturan_denda.destroy');

        Route::get('/proses/pembuatan-tagihan', [PembuatanTagihanController::class, 'index'])->name('pembuatan_tagihan.index');
        Route::post('/proses/pembuatan-tagihan/preview', [PembuatanTagihanController::class, 'previewPelangganUntukDitagih'])->name('pembuatan_tagihan.preview');
        Route::post('/proses/pembuatan-tagihan/generate', [PembuatanTagihanController::class, 'generate'])->name('pembuatan_tagihan.generate');

        Route::get('/tagihan/manajemen', [ManajemenTagihanController::class, 'index'])->name('tagihan.manage.index');
        Route::get('/tagihan/manajemen-data', [ManajemenTagihanController::class, 'getTagihanData'])->name('tagihan.manage.data');
        Route::get('/tagihan/detail/{tagihan_id}', [ManajemenTagihanController::class, 'show'])->name('tagihan.manage.show');
        Route::get('/tagihan/cetak/{tagihan_id}', [ManajemenTagihanController::class, 'printInvoice'])->name('tagihan.manage.print'); // Rute Cetak
        Route::post('/tagihan/batalkan/{tagihan_id}', [ManajemenTagihanController::class, 'cancelBill'])->name('tagihan.manage.cancel'); // Rute Batalkan (menggunakan POST)

        Route::get('/laporan/tagihan', [LaporanTagihanController::class, 'index'])->name('laporan.tagihan.index');
        Route::get('/laporan/tagihan-data', [LaporanTagihanController::class, 'getTagihanData'])->name('laporan.tagihan.data');
        Route::get('/laporan/tagihan/export-excel', [LaporanTagihanController::class, 'exportExcel'])->name('laporan.tagihan.export_excel'); // Rute Export Excel
        Route::get('/laporan/tagihan/export-pdf', [LaporanTagihanController::class, 'exportPdf'])->name('laporan.tagihan.export_pdf');     // Rute Export PDF

        Route::get('/laporan/tunggakan', [LaporanTunggakanController::class, 'index'])->name('laporan.tunggakan.index');
        Route::get('/laporan/tunggakan-data', [LaporanTunggakanController::class, 'getTunggakanData'])->name('laporan.tunggakan.data');
        Route::get('/laporan/tunggakan/export-excel', [LaporanTunggakanController::class, 'exportExcel'])->name('laporan.tunggakan.export_excel');
        Route::get('/laporan/tunggakan/export-pdf', [LaporanTunggakanController::class, 'exportPdf'])->name('laporan.tunggakan.export_pdf');
        Route::get('/laporan/tunggakan/export-rekap-excel', [LaporanTunggakanController::class, 'exportRekap5Bulan'])->name('laporan.tunggakan.export_rekap');
        Route::get('/laporan/tunggakan/export-rekap-pdf', [LaporanTunggakanController::class, 'exportRekap5BulanPdf'])->name('laporan.tunggakan.export_rekap_pdf');

        Route::get('/laporan/pembayaran', [LaporanPembayaranController::class, 'index'])->name('laporan.pembayaran.index');
        Route::get('/laporan/pembayaran-data', [LaporanPembayaranController::class, 'getPembayaranData'])->name('laporan.pembayaran.data');
        Route::post('/laporan/pembayaran/cancel/{pembayaran_id}', [LaporanPembayaranController::class, 'cancelPayment'])->name('laporan.pembayaran.cancel');
        Route::get('/laporan/pembayaran/export-excel', [LaporanPembayaranController::class, 'exportExcel'])->name('laporan.pembayaran.export_excel');
        Route::get('/laporan/pembayaran/export-pdf', [LaporanPembayaranController::class, 'exportPdf'])->name('laporan.pembayaran.export_pdf');

        Route::get('/manajemen-meter', [GantiMeterController::class, 'index'])->name('ganti_meter.index');
        Route::get('/manajemen-meter/cari-pelanggan', [GantiMeterController::class, 'searchPelanggan'])->name('ganti_meter.search_pelanggan');
        Route::post('/manajemen-meter/simpan', [GantiMeterController::class, 'store'])->name('ganti_meter.store');
    });

    Route::middleware(['type:1,2'])->group(function () {
        Route::get('/transaksi/pencatatan-meter', [PencatatanMeterController::class, 'index'])->name('pencatatan_meter.index');
        Route::get('/transaksi/pencatatan-meter/search-pelanggan', [PencatatanMeterController::class, 'searchPelangganUntukDicatat'])->name('pencatatan_meter.search_pelanggan');
        Route::get('/transaksi/pencatatan-meter/get-detail-pelanggan', [PencatatanMeterController::class, 'getDetailPelangganUntukDicatat'])->name('pencatatan_meter.get_detail');
        Route::post('/transaksi/pencatatan-meter/store-single', [PencatatanMeterController::class, 'storeSingleReading'])->name('pencatatan_meter.store_single');

        Route::get('/transaksi/pembayaran', [PembayaranController::class, 'index'])->name('pembayaran.index');
        Route::get('/transaksi/pembayaran/cari-tagihan', [PembayaranController::class, 'searchTagihan'])->name('pembayaran.search_tagihan'); // AJAX
        Route::post('/transaksi/pembayaran/hitung-denda', [PembayaranController::class, 'hitungDenda'])->name('pembayaran.hitung_denda'); // AJAX
        Route::post('/transaksi/pembayaran/store', [PembayaranController::class, 'store'])->name('pembayaran.store');
        Route::get('/pembayaran/struk/{pembayaran_id}', [PembayaranController::class, 'cetakStruk'])->name('pembayaran.cetak_struk');
        Route::get('/monitoring/catat-meter', [DaftarCatatMeterController::class, 'index'])->name('monitoring.catat_meter.index');
        Route::get('/monitoring/catat-meter-data', [DaftarCatatMeterController::class, 'getData'])->name('monitoring.catat_meter.data');
        Route::get('/monitoring/catat-meter/export-excel', [DaftarCatatMeterController::class, 'exportExcel'])->name('monitoring.catat_meter.export_excel');
        Route::get('/monitoring/catat-meter/export-pdf', [DaftarCatatMeterController::class, 'exportPdf'])->name('monitoring.catat_meter.export_pdf');
    });

    Route::get('/users/password/{id}', [UserController::class, 'password'])->name('users.password');
    Route::post('/users/updatepassword/{id}', [UserController::class, 'updatepassword'])->name('users.updatepassword');

    Route::get('user-data', [UserController::class, 'getUserData'])->name('users.data');
    Route::post('/user/save', [UserController::class, 'store'])->name('user.save');
    Route::delete('/user/delete/{id}', [UserController::class, 'destroy'])->name('user.destroy');
    Route::get('/user/edit/{id}', [UserController::class, 'edit'])->name('user.edit');
    Route::post('/user/update/{id}', [UserController::class, 'update'])->name('user.update');

    Route::get('/role-data', [RoleController::class, 'getRoleData']);
    Route::post('/roles/store', [RoleController::class, 'store']);
    Route::put('/roles/update/{id}', [RoleController::class, 'update']);
    Route::delete('/roles/delete/{id}', [RoleController::class, 'destroy']);
    Route::get('/roles/{id}', [RoleController::class, 'show']);
});
