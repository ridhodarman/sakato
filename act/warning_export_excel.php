<?php
require_once '../inc/koneksi.php';

// =====================================================
// HEADER UNTUK DOWNLOAD EXCEL
// =====================================================
$filename = "Monitoring_Berkas_Rutin_" . date('Y-m-d_H-i-s') . ".xls";
header("Content-Type: application/vnd.ms-excel; charset=utf-8");
header("Content-Disposition: attachment; filename=\"$filename\"");
header("Pragma: no-cache");
header("Expires: 0");

// =====================================================
// TANGGAL HARI INI & HARI LIBUR
// =====================================================
$hariIni = new DateTime();
$today = $hariIni->format('Y-m-d');

$hariLibur = [];
$sqlHariLibur = "
    SELECT tanggal
    FROM hari_libur
    WHERE tanggal IS NOT NULL
      AND tanggal <> '0000-00-00'
";

$resultHariLibur = $koneksi->query($sqlHariLibur);
if ($resultHariLibur) {
    while ($libur = $resultHariLibur->fetch_assoc()) {
        $tanggalLibur = $libur['tanggal'];
        $hari = (int)date('N', strtotime($tanggalLibur));
        if ($hari <= 5) {
            $hariLibur[] = $tanggalLibur;
        }
    }
}
sort($hariLibur);

// =====================================================
// FUNGSI PERHITUNGAN HARI KERJA
// =====================================================
function lowerBoundDate($array, $target) {
    $low = 0;
    $high = count($array);
    while ($low < $high) {
        $mid = intdiv($low + $high, 2);
        if ($array[$mid] < $target) {
            $low = $mid + 1;
        } else {
            $high = $mid;
        }
    }
    return $low;
}

function countHolidayBetween($startDate, $endDate, $hariLibur) {
    if (empty($hariLibur) || $endDate < $startDate) return 0;
    $startIndex = lowerBoundDate($hariLibur, $startDate);
    $endIndex = lowerBoundDate($hariLibur, $endDate);
    $count = $endIndex - $startIndex;
    if (isset($hariLibur[$endIndex]) && $hariLibur[$endIndex] === $endDate) {
        $count++;
    }
    return $count;
}

function getWorkingDays($startDate, $endDate, $hariLibur = []) {
    if (empty($startDate) || empty($endDate) || $startDate === '0000-00-00' || $endDate === '0000-00-00' || $endDate < $startDate) {
        return 0;
    }
    $start = new DateTime($startDate);
    $end   = new DateTime($endDate);
    $totalDays = (int)$start->diff($end)->days + 1;
    $fullWeeks = intdiv($totalDays, 7);
    $workingDays = $fullWeeks * 5;
    $remainingDays = $totalDays % 7;
    $startDay = (int)$start->format('N');

    for ($i = 0; $i < $remainingDays; $i++) {
        $day = (($startDay - 1 + $i) % 7) + 1;
        if ($day <= 5) $workingDays++;
    }

    $jumlahHariLibur = countHolidayBetween($startDate, $endDate, $hariLibur);
    $workingDays -= $jumlahHariLibur;
    return max(0, $workingDays);
}

// =====================================================
// QUERY DATA BERKAS
// =====================================================
$sql = "
    SELECT
        b.id, b.no_berkas, b.tahun, b.nama_pemohon, b.tanggal_mulai, b.status, b.catatan, b.tanggal_selesai, b.posisi_id,
        l.nama_layanan, l.jatuh_tempo, l.waspada, l.kritis,
        p.nama AS nama_pic, ps.nama_posisi AS nama_posisi
    FROM berkas_rutin b
    LEFT JOIN layanan l ON l.id = b.layanan_id
    LEFT JOIN pic p ON p.id = l.pic_id
    LEFT JOIN posisi ps ON ps.id = b.posisi_id
    WHERE (b.status <> 'selesai' OR b.status IS NULL)
      AND b.tanggal_mulai IS NOT NULL
      AND b.tanggal_mulai <> '0000-00-00'
    ORDER BY b.tanggal_mulai ASC
";

$result = $koneksi->query($sql);

$dataWaspada = [];
$dataKritis = [];
$dataKadaluarsa = [];
$rekapPosisi = [];
$umurCache = [];

if ($result) {
    while ($row = $result->fetch_assoc()) {
        $tanggalMulaiString = $row['tanggal_mulai'] ?? '';
        if (empty($tanggalMulaiString) || $tanggalMulaiString === '0000-00-00') continue;

        $tanggalMulai = new DateTime($tanggalMulaiString);
        $row['tanggal_mulai_formatted'] = $tanggalMulai->format('d-m-Y');

        if (isset($umurCache[$tanggalMulaiString])) {
            $umurHari = $umurCache[$tanggalMulaiString];
        } else {
            $umurHari = getWorkingDays($tanggalMulaiString, $today, $hariLibur);
            $umurCache[$tanggalMulaiString] = $umurHari;
        }

        $waspada = (int)($row['waspada'] ?? 0);
        $kritis = (int)($row['kritis'] ?? 0);
        $jatuhTempo = (int)($row['jatuh_tempo'] ?? 0);

        if ($jatuhTempo <= 0) continue;

        $sisaHari = $jatuhTempo - $umurHari;

        if ($umurHari > $jatuhTempo) {
            $kategori = 'kadaluarsa';
        } elseif ($umurHari >= $kritis) {
            $kategori = 'kritis';
        } elseif ($umurHari >= $waspada) {
            $kategori = 'waspada';
        } else {
            continue;
        }

        $row['umur_hari'] = $umurHari;
        $row['sisa_hari'] = $sisaHari;
        $row['kategori'] = $kategori;

        $posisiNama = !empty($row['nama_posisi']) ? $row['nama_posisi'] : 'Tanpa Posisi';

        if (!isset($rekapPosisi[$posisiNama])) {
            $rekapPosisi[$posisiNama] = ['waspada' => 0, 'kritis' => 0, 'kadaluarsa' => 0];
        }

        if ($kategori === 'waspada') {
            $dataWaspada[] = $row;
            $rekapPosisi[$posisiNama]['waspada']++;
        } elseif ($kategori === 'kritis') {
            $dataKritis[] = $row;
            $rekapPosisi[$posisiNama]['kritis']++;
        } elseif ($kategori === 'kadaluarsa') {
            $dataKadaluarsa[] = $row;
            $rekapPosisi[$posisiNama]['kadaluarsa']++;
        }
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <style>
        table { border-collapse: collapse; width: 100%; margin-bottom: 20px; }
        th, td { border: 1px solid #333333; padding: 6px 10px; text-align: left; }
        th { background-color: #1e293b; color: #ffffff; font-weight: bold; }
        .bg-waspada { background-color: #fef3c7; }
        .bg-kritis { background-color: #fee2e2; }
        .bg-kadaluarsa { background-color: #f1f5f9; }
        .title { font-size: 16pt; font-weight: bold; margin-bottom: 10px; }
        .subtitle { font-size: 11pt; color: #555; margin-bottom: 20px; }
        .section-header { font-size: 13pt; font-weight: bold; padding: 8px; margin-top: 15px; }
    </style>
</head>
<body>

    <div class="title">LAPORAN MONITORING BERKAS RUTIN</div>
    <div class="subtitle">Tanggal Cetak: <?= date('d-m-Y H:i:s') ?></div>

    <!-- REKAP POSISI -->
    <h3>Ringkasan Berkas Berdasarkan Posisi</h3>
    <table>
        <thead>
            <tr>
                <th style="width: 40px;">No.</th>
                <th>Nama Posisi</th>
                <th style="text-align: center;">Waspada</th>
                <th style="text-align: center;">Kritis</th>
                <th style="text-align: center;">Kadaluarsa</th>
                <th style="text-align: center;">Total Berkas</th>
            </tr>
        </thead>
        <tbody>
            <?php 
            $noPos = 1;
            foreach ($rekapPosisi as $namaPosisi => $jumlah): 
                $totalPerPosisi = $jumlah['waspada'] + $jumlah['kritis'] + $jumlah['kadaluarsa'];
                if ($totalPerPosisi === 0) continue;
            ?>
            <tr>
                <td style="text-align: center;"><?= $noPos++ ?></td>
                <td><strong><?= htmlspecialchars($namaPosisi) ?></strong></td>
                <td style="text-align: center;"><?= $jumlah['waspada'] ?></td>
                <td style="text-align: center;"><?= $jumlah['kritis'] ?></td>
                <td style="text-align: center;"><?= $jumlah['kadaluarsa'] ?></td>
                <td style="text-align: center;"><strong><?= $totalPerPosisi ?></strong></td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>

    <br>

    <!-- DATA WASPADA -->
    <h3>BERKAS WASPADA (Total: <?= count($dataWaspada) ?>)</h3>
    <table>
        <thead>
            <tr style="background-color: #f59e0b; color: #ffffff;">
                <th>No.</th>
                <th>No. Berkas</th>
                <th>Nama Pemohon</th>
                <th>Layanan</th>
                <th>Posisi</th>
                <th>Tanggal Mulai</th>
                <th>Umur</th>
                <th>Sisa Hari</th>
                <th>PIC</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($dataWaspada)): ?>
                <tr><td colspan="9" style="text-align: center;">Tidak ada berkas dalam status waspada.</td></tr>
            <?php else: ?>
                <?php foreach ($dataWaspada as $no => $row): ?>
                <tr class="bg-waspada">
                    <td><?= $no + 1 ?></td>
                    <td>'<?= htmlspecialchars($row['no_berkas']) ?>/<?= htmlspecialchars($row['tahun']) ?></td>
                    <td><?= htmlspecialchars($row['nama_pemohon']) ?></td>
                    <td><?= htmlspecialchars($row['nama_layanan'] ?? '-') ?></td>
                    <td><?= htmlspecialchars($row['nama_posisi'] ?? '-') ?></td>
                    <td><?= htmlspecialchars($row['tanggal_mulai_formatted']) ?></td>
                    <td><?= $row['umur_hari'] ?> hari</td>
                    <td><?= $row['sisa_hari'] ?> hari</td>
                    <td><?= htmlspecialchars($row['nama_pic'] ?? '-') ?></td>
                </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>

    <br>

    <!-- DATA KRITIS -->
    <h3>BERKAS KRITIS (Total: <?= count($dataKritis) ?>)</h3>
    <table>
        <thead>
            <tr style="background-color: #ef4444; color: #ffffff;">
                <th>No.</th>
                <th>No. Berkas</th>
                <th>Nama Pemohon</th>
                <th>Layanan</th>
                <th>Posisi</th>
                <th>Tanggal Mulai</th>
                <th>Umur</th>
                <th>Sisa Hari</th>
                <th>PIC</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($dataKritis)): ?>
                <tr><td colspan="9" style="text-align: center;">Tidak ada berkas dalam status kritis.</td></tr>
            <?php else: ?>
                <?php foreach ($dataKritis as $no => $row): ?>
                <tr class="bg-kritis">
                    <td><?= $no + 1 ?></td>
                    <td>'<?= htmlspecialchars($row['no_berkas']) ?>/<?= htmlspecialchars($row['tahun']) ?></td>
                    <td><?= htmlspecialchars($row['nama_pemohon']) ?></td>
                    <td><?= htmlspecialchars($row['nama_layanan'] ?? '-') ?></td>
                    <td><?= htmlspecialchars($row['nama_posisi'] ?? '-') ?></td>
                    <td><?= htmlspecialchars($row['tanggal_mulai_formatted']) ?></td>
                    <td><?= $row['umur_hari'] ?> hari</td>
                    <td><?= $row['sisa_hari'] ?> hari</td>
                    <td><?= htmlspecialchars($row['nama_pic'] ?? '-') ?></td>
                </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>

    <br>

    <!-- DATA KADALUARSA -->
    <h3>BERKAS KADALUARSA / JATUH TEMPO (Total: <?= count($dataKadaluarsa) ?>)</h3>
    <table>
        <thead>
            <tr style="background-color: #1e293b; color: #ffffff;">
                <th>No.</th>
                <th>No. Berkas</th>
                <th>Nama Pemohon</th>
                <th>Layanan</th>
                <th>Posisi</th>
                <th>Tanggal Mulai</th>
                <th>Umur</th>
                <th>Terlambat</th>
                <th>PIC</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($dataKadaluarsa)): ?>
                <tr><td colspan="9" style="text-align: center;">Tidak ada berkas kadaluarsa.</td></tr>
            <?php else: ?>
                <?php foreach ($dataKadaluarsa as $no => $row): ?>
                <tr class="bg-kadaluarsa">
                    <td><?= $no + 1 ?></td>
                    <td>'<?= htmlspecialchars($row['no_berkas']) ?>/<?= htmlspecialchars($row['tahun']) ?></td>
                    <td><?= htmlspecialchars($row['nama_pemohon']) ?></td>
                    <td><?= htmlspecialchars($row['nama_layanan'] ?? '-') ?></td>
                    <td><?= htmlspecialchars($row['nama_posisi'] ?? '-') ?></td>
                    <td><?= htmlspecialchars($row['tanggal_mulai_formatted']) ?></td>
                    <td><?= $row['umur_hari'] ?> hari</td>
                    <td style="color: red; font-weight: bold;"><?= abs($row['sisa_hari']) ?> hari</td>
                    <td><?= htmlspecialchars($row['nama_pic'] ?? '-') ?></td>
                </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>

</body>
</html>