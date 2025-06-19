<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Yajra\DataTables\Facades\DataTables;
use Carbon\Carbon;
use Illuminate\Validation\Rule;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\PelangganExport;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class PelangganController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    public function index()
    {
        $wilayahs = DB::table('wilayah')->select('wilayah_id', 'nama_wilayah')->orderBy('nama_wilayah')->get();
        $tarifs = DB::table('tarif')
            ->select('tarif_id', 'kode_tarif', 'nama_tarif')
            ->where('status', 'Aktif')
            ->orderBy('kode_tarif')->get();
        $statuses = ['Baru', 'Aktif', 'NonAktif', 'PemutusanSementara'];

        return view('master.pelanggan.index', compact('wilayahs', 'tarifs', 'statuses'));
    }

    private function buildFilteredPelangganQueryBase(Request $request)
    {
        $query = DB::table('pelanggan as p')
            ->join('wilayah as w', 'p.wilayah_id', '=', 'w.wilayah_id')
            ->join('tarif as t', 'p.tarif_id', '=', 't.tarif_id');

        // Filter Wilayah
        if ($request->filled('filter_wilayah_id') && $request->filter_wilayah_id != '') {
            $query->where('p.wilayah_id', $request->filter_wilayah_id);
        }
        // Filter Status
        if ($request->filled('filter_status_pelanggan') && $request->filter_status_pelanggan != '') {
            $query->where('p.status_pelanggan', $request->filter_status_pelanggan);
        }

        // Filter Pencarian Umum
        $searchTerm = $request->input('search.value') ?: $request->filter_search_pelanggan;
        if ($searchTerm) {
            $query->where(function ($q) use ($searchTerm) {
                $q->where('p.nama_pelanggan', 'LIKE', '%' . $searchTerm . '%')
                    ->orWhere('p.id_pelanggan_unik', 'LIKE', '%' . $searchTerm . '%')
                    ->orWhere('p.no_meter', 'LIKE', '%' . $searchTerm . '%')
                    ->orWhere('p.alamat', 'LIKE', '%' . $searchTerm . '%');
            });
        }
        return $query;
    }

    public function getPelangganData(Request $request)
    {
        if ($request->ajax()) {
            $baseQuery = $this->buildFilteredPelangganQueryBase($request);

            $datatablesQuery = $baseQuery->select(
                'p.pelanggan_id',
                'p.id_pelanggan_unik',
                'p.no_meter',
                'p.nama_pelanggan',
                'w.nama_wilayah',
                't.kode_tarif',
                'p.status_pelanggan',
                'p.alamat'
            )
                ->orderBy('p.nama_pelanggan', 'asc');

            return DataTables::of($datatablesQuery)
                ->addIndexColumn()
                ->editColumn('status_pelanggan', function ($row) {
                    $badgeClass = 'badge-secondary';
                    if ($row->status_pelanggan == 'Aktif') $badgeClass = 'badge-success';
                    else if ($row->status_pelanggan == 'NonAktif') $badgeClass = 'badge-danger';
                    else if ($row->status_pelanggan == 'PemutusanSementara') $badgeClass = 'badge-warning';
                    return '<span class="badge ' . $badgeClass . '">' . htmlspecialchars($row->status_pelanggan) . '</span>';
                })
                ->addColumn('action', function ($row) {
                    $btn = '<button class="editButton btn btn-sm btn-warning mr-1 mb-1" data-id="' . $row->pelanggan_id . '"><i class="fas fa-edit fa-xs"></i> Edit</button>';
                    $btn .= '<button class="deleteButton btn btn-sm btn-danger mb-1" data-id="' . $row->pelanggan_id . '"><i class="fas fa-trash fa-xs"></i> Delete</button>';
                    return $btn;
                })
                ->rawColumns(['action', 'status_pelanggan'])
                ->make(true);
        }
    }

    private function getValidationRules()
    {
        return [
            'nama_pelanggan' => 'required|string|max:255',
            'alamat' => 'required|string',
            'wilayah_id' => 'required|integer|exists:wilayah,wilayah_id',
            'tarif_id' => 'required|integer|exists:tarif,tarif_id',
            'status_pelanggan' => 'required|in:Aktif,NonAktif,PemutusanSementara,Baru',
            'tanggal_registrasi' => 'nullable|date_format:Y-m-d',
            'meter_awal_saat_pemasangan' => 'required|numeric|min:0',
            'tanggal_reset_meter_terakhir' => 'nullable|date_format:Y-m-d\TH:i',
            'nilai_meter_saat_reset_terakhir' => 'nullable|numeric|min:0',
            'email_kontak' => 'nullable|email|max:255',
            'no_telepon' => 'nullable|string|max:20',
            'keterangan' => 'nullable|string|max:500',
        ];
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), $this->getValidationRules());
        if ($validator->fails()) {
            return response()->json(['success' => false, 'errors' => $validator->errors()], 422);
        }

        DB::beginTransaction();
        try {
            // GENERATE NOMOR METER OTOMATIS
            $wilayahId = $request->wilayah_id;
            $maxNoMeter = DB::table('pelanggan')
                ->where('wilayah_id', $wilayahId)
                ->selectRaw('MAX(CAST(no_meter AS UNSIGNED)) as max_meter')
                ->first();
            $nextMeterNumber = ($maxNoMeter && $maxNoMeter->max_meter) ? (int)$maxNoMeter->max_meter + 1 : 1;
            $newNoMeter = str_pad($nextMeterNumber, 4, '0', STR_PAD_LEFT);

            // GENERATE ID PELANGGAN UNIK OTOMATIS
            $prefix = "PEL";
            $yearMonth = Carbon::now()->format('Ym');
            $baseIdPelanggan = $prefix . "-" . $yearMonth . "-";
            $lastPelanggan = DB::table('pelanggan')
                ->where('id_pelanggan_unik', 'LIKE', $baseIdPelanggan . '%')
                ->orderBy('id_pelanggan_unik', 'desc')->first();
            $nextSequence = 1;
            if ($lastPelanggan) {
                $parts = explode('-', $lastPelanggan->id_pelanggan_unik);
                if (count($parts) === 3) {
                    $lastSequence = (int) $parts[2];
                    $nextSequence = $lastSequence + 1;
                }
            }
            $newIdPelangganUnik = $baseIdPelanggan . str_pad($nextSequence, 4, '0', STR_PAD_LEFT);

            DB::table('pelanggan')->insert([
                'id_pelanggan_unik' => $newIdPelangganUnik,
                'no_meter' => $newNoMeter,
                'nama_pelanggan' => $request->nama_pelanggan,
                'alamat' => $request->alamat,
                'wilayah_id' => $wilayahId,
                'tarif_id' => $request->tarif_id,
                'status_pelanggan' => $request->status_pelanggan,
                'tanggal_registrasi' => $request->tanggal_registrasi ? Carbon::parse($request->tanggal_registrasi)->format('Y-m-d') : null,
                'meter_awal_saat_pemasangan' => $request->meter_awal_saat_pemasangan,
                'tanggal_reset_meter_terakhir' => $request->tanggal_reset_meter_terakhir ? Carbon::parse($request->tanggal_reset_meter_terakhir)->format('Y-m-d H:i:s') : null,
                'nilai_meter_saat_reset_terakhir' => $request->nilai_meter_saat_reset_terakhir,
                'email_kontak' => $request->email_kontak,
                'no_telepon' => $request->no_telepon,
                'keterangan' => $request->keterangan,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => "Pelanggan berhasil ditambahkan. ID: {$newIdPelangganUnik}, No. Meter: {$newNoMeter}"
            ]);
        } catch (\Illuminate\Database\QueryException $e) {
            DB::rollBack();
            if ($e->errorInfo[1] == 1062) {
                if (str_contains($e->getMessage(), 'uq_no_meter_wilayah')) {
                    return response()->json(['success' => false, 'message' => 'Gagal membuat Nomor Meter unik karena race condition. Coba simpan lagi.'], 500);
                }
            }
            Log::error("Error simpan pelanggan: " . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Gagal menambahkan pelanggan karena kesalahan database.'], 500);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("Error simpan pelanggan: " . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Gagal menambahkan pelanggan.'], 500);
        }
    }

    public function edit($pelanggan_id)
    {
        $pelanggan = DB::table('pelanggan')->where('pelanggan_id', $pelanggan_id)->first();
        if ($pelanggan) {
            if ($pelanggan->tanggal_registrasi) {
                $pelanggan->tanggal_registrasi = Carbon::parse($pelanggan->tanggal_registrasi)->format('Y-m-d');
            }
            if ($pelanggan->tanggal_reset_meter_terakhir) {
                $pelanggan->tanggal_reset_meter_terakhir = Carbon::parse($pelanggan->tanggal_reset_meter_terakhir)->format('Y-m-d\TH:i');
            }
            return response()->json($pelanggan);
        }
        return response()->json(['success' => false, 'message' => 'Pelanggan tidak ditemukan.'], 404);
    }

    public function update(Request $request, $pelanggan_id)
    {
        // ID Pelanggan Unik dan No Meter diasumsikan tidak bisa diubah.
        $validator = Validator::make($request->all(), $this->getValidationRules());

        if ($validator->fails()) {
            return response()->json(['success' => false, 'errors' => $validator->errors()], 422);
        }

        try {
            $updateData = [
                'nama_pelanggan' => $request->nama_pelanggan,
                'alamat' => $request->alamat,
                'wilayah_id' => $request->wilayah_id,
                'tarif_id' => $request->tarif_id,
                'status_pelanggan' => $request->status_pelanggan,
                'tanggal_registrasi' => $request->tanggal_registrasi ? Carbon::parse($request->tanggal_registrasi)->format('Y-m-d') : null,
                'meter_awal_saat_pemasangan' => $request->meter_awal_saat_pemasangan,
                'tanggal_reset_meter_terakhir' => $request->tanggal_reset_meter_terakhir ? Carbon::parse($request->tanggal_reset_meter_terakhir)->format('Y-m-d H:i:s') : null,
                'nilai_meter_saat_reset_terakhir' => $request->nilai_meter_saat_reset_terakhir,
                'email_kontak' => $request->email_kontak,
                'no_telepon' => $request->no_telepon,
                'keterangan' => $request->keterangan,
                'updated_at' => now(),
            ];
            $affected = DB::table('pelanggan')->where('pelanggan_id', $pelanggan_id)->update($updateData);

            if ($affected) {
                return response()->json(['success' => true, 'message' => 'Pelanggan berhasil diperbarui.']);
            }
            $exists = DB::table('pelanggan')->where('pelanggan_id', $pelanggan_id)->exists();
            if (!$exists) {
                return response()->json(['success' => false, 'message' => 'Pelanggan tidak ditemukan.']);
            }
            return response()->json(['success' => true, 'message' => 'Tidak ada perubahan pada data pelanggan.']);
        } catch (\Exception $e) {
            Log::error("Error update pelanggan: " . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Gagal memperbarui pelanggan.'], 500);
        }
    }

    public function destroy($pelanggan_id)
    {
        $ada_pencatatan = DB::table('pencatatan_meter')->where('pelanggan_id', $pelanggan_id)->exists();
        $ada_tagihan = DB::table('tagihan')->where('pelanggan_id', $pelanggan_id)->exists();
        if ($ada_pencatatan || $ada_tagihan) {
            return response()->json(['success' => false, 'message' => 'Pelanggan tidak dapat dihapus karena sudah memiliki data transaksi.'], 400);
        }
        try {
            $deleted = DB::table('pelanggan')->where('pelanggan_id', $pelanggan_id)->delete();
            if ($deleted) {
                return response()->json(['success' => true, 'message' => 'Pelanggan berhasil dihapus.']);
            }
            return response()->json(['success' => false, 'message' => 'Pelanggan tidak ditemukan.'], 404);
        } catch (\Exception $e) {
            Log::error("Error hapus pelanggan: " . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Gagal menghapus pelanggan.'], 500);
        }
    }

    public function exportExcel(Request $request)
    {
        $timestamp = Carbon::now()->format('Ymd_His');
        return Excel::download(new PelangganExport($request), "master_pelanggan_{$timestamp}.xlsx");
    }

    public function exportPdf(Request $request)
    {
        $queryBuilder = $this->buildFilteredPelangganQueryBase($request);
        $pelanggans = $queryBuilder->select(
            'p.pelanggan_id',
            'p.id_pelanggan_unik',
            'p.no_meter',
            'p.nama_pelanggan',
            'p.alamat',
            'w.nama_wilayah',
            't.kode_tarif',
            'p.status_pelanggan',
            'p.tanggal_registrasi',
            'p.email_kontak',
            'p.no_telepon'
        )
            ->orderBy('p.nama_pelanggan', 'asc')
            ->limit(500)->get();

        foreach ($pelanggans as $pelanggan) {
            $pelanggan->tanggal_registrasi_formatted = $pelanggan->tanggal_registrasi ? Carbon::parse($pelanggan->tanggal_registrasi)->format('d-m-Y') : '-';
        }

        $filterWilayahNama = null;
        if ($request->filled('filter_wilayah_id')) {
            $filterWilayahNama = DB::table('wilayah')->where('wilayah_id', $request->filter_wilayah_id)->value('nama_wilayah');
        }

        $pdf = Pdf::loadView('master.pelanggan.export_pdf', compact('pelanggans', 'request', 'filterWilayahNama'));
        $timestamp = Carbon::now()->format('Ymd_His');
        return $pdf->setPaper('a4', 'landscape')->download("master_pelanggan_{$timestamp}.pdf");
    }
}
