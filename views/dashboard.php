<?php
$peserta=(int)db()->query('SELECT COUNT(*) FROM participants')->fetchColumn();
$aset=(int)db()->query('SELECT COUNT(*) FROM assets')->fetchColumn();
$rows=db()->query('SELECT jenis, SUM(nominal) total FROM transactions GROUP BY jenis')->fetchAll();
$masuk=0;$keluar=0;
foreach($rows as $row){ if($row['jenis']==='masuk')$masuk=(float)$row['total'];else $keluar=(float)$row['total']; }
?><h1>Dashboard</h1><div class="grid"><section class="card"><h3>Jumlah Peserta</h3><div class="number"><?=$peserta?></div></section><section class="card"><h3>Jumlah Aset</h3><div class="number"><?=$aset?></div></section><section class="card"><h3>Total Pemasukan</h3><div class="number"><?=rupiah($masuk)?></div></section><section class="card"><h3>Total Pengeluaran</h3><div class="number"><?=rupiah($keluar)?></div></section><section class="card"><h3>Saldo Tercatat</h3><div class="number"><?=rupiah($masuk-$keluar)?></div></section></div><p class="muted">Saldo merupakan selisih transaksi yang tercatat, belum merupakan rekonsiliasi kas dan bank.</p>