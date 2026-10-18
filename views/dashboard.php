<?php
$icons=[
 'peserta'=>'<svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="9" cy="8" r="3.2"/><path d="M3 20c0-3.3 2.7-6 6-6s6 2.7 6 6"/><circle cx="17" cy="9" r="2.4"/><path d="M16.5 14.2c2.6.2 4.5 2.4 4.5 5.3"/></svg>',
 'guru'=>'<svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M3 5.5 12 3l9 2.5-9 2.5z"/><path d="M7 9.2v4.3c0 1.5 2.2 2.7 5 2.7s5-1.2 5-2.7V9.2"/><path d="M21 6v6"/></svg>',
 'pengurus'=>'<svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="3" y="7" width="18" height="13" rx="2"/><path d="M9 7V5a1.5 1.5 0 0 1 1.5-1.5h3A1.5 1.5 0 0 1 15 5v2"/><path d="M3 13h18"/></svg>',
 'aset'=>'<svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m12 3 8 4.5v9L12 21l-8-4.5v-9z"/><path d="m4 7.5 8 4.5 8-4.5M12 12v9"/></svg>',
 'masuk'=>'<svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M12 19V5M6 11l6-6 6 6"/></svg>',
 'keluar'=>'<svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M12 5v14M6 13l6 6 6-6"/></svg>',
 'saldo'=>'<svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="3" y="6" width="18" height="13" rx="2.5"/><path d="M3 10h18"/><circle cx="16.5" cy="14.5" r="1.2"/></svg>',
];
$bulan=['','Jan','Feb','Mar','Apr','Mei','Jun','Jul','Agu','Sep','Okt','Nov','Des'];
$hari=['Minggu','Senin','Selasa','Rabu','Kamis','Jumat','Sabtu'];
$tgl=$hari[(int)date('w')].', '.date('j').' '.$bulan[(int)date('n')].' '.date('Y');
$stat=function(string $icon,string $tone,string $label,string $value,string $sub,string $link,string $linkLabel) use ($icons) {
 return '<section class="card stat"><span class="icon '.$tone.'">'.$icons[$icon].'</span><h3>'.esc($label).'</h3><div class="number">'.$value.'</div><p>'.$sub.'</p><a href="'.esc($link).'">'.esc($linkLabel).'</a></section>';
};
?><section class="hero"><div><p class="eyebrow">Assalamu'alaikum</p><h1>Dashboard</h1><p>Ringkasan administrasi Ma'had</p></div><div class="date"><?=esc($tgl)?></div></section>
<div class="grid"><?php
if(can_view('peserta')) echo $stat('peserta','i-blue','Jumlah Peserta',(string)(int)sql('SELECT COUNT(*) FROM participants')->fetchColumn(),'Aktif: '.(int)sql("SELECT COUNT(*) FROM participants WHERE status='aktif'")->fetchColumn(),'?page=peserta','Kelola peserta');
if(can_view('guru')) echo $stat('guru','i-green','Jumlah Guru',(string)(int)sql('SELECT COUNT(*) FROM teachers')->fetchColumn(),'Aktif: '.(int)sql("SELECT COUNT(*) FROM teachers WHERE status='aktif'")->fetchColumn(),'?page=guru','Kelola guru');
if(can_view('pengurus')) echo $stat('pengurus','i-slate','Jumlah Pengurus',(string)(int)sql('SELECT COUNT(*) FROM staff_members')->fetchColumn(),'Aktif: '.(int)sql("SELECT COUNT(*) FROM staff_members WHERE status='aktif'")->fetchColumn(),'?page=pengurus','Kelola pengurus');
if(can_view('aset')) echo $stat('aset','i-gold','Jumlah Aset',(string)(int)sql('SELECT COUNT(*) FROM assets')->fetchColumn(),'Nilai: '.esc(rupiah(sql('SELECT COALESCE(SUM(nilai_perolehan),0) FROM assets')->fetchColumn())),'?page=aset','Lihat inventaris');
?></div>
<?php if(can_view('keuangan')):
 $t=sql("SELECT COALESCE(SUM(CASE WHEN jenis='masuk' THEN nominal ELSE 0 END),0) masuk, COALESCE(SUM(CASE WHEN jenis='keluar' THEN nominal ELSE 0 END),0) keluar FROM transactions")->fetch();
 $saldo=(float)$t['masuk']-(float)$t['keluar'];
?><h2 class="sectiontitle">Keuangan</h2><div class="grid fin">
<section class="card stat"><span class="icon i-green"><?=$icons['masuk']?></span><h3>Total Pemasukan</h3><div class="number pos"><?=esc(rupiah($t['masuk']))?></div></section>
<section class="card stat"><span class="icon i-red"><?=$icons['keluar']?></span><h3>Total Pengeluaran</h3><div class="number neg"><?=esc(rupiah($t['keluar']))?></div></section>
<section class="card stat"><span class="icon i-blue"><?=$icons['saldo']?></span><h3>Saldo Tercatat</h3><div class="number <?=$saldo<0?'neg':''?>"><?=esc(rupiah($saldo))?></div></section>
</div>
<?php
 $months=[];for($i=5;$i>=0;$i--){$d=new DateTimeImmutable('first day of this month -'.$i.' month');$months[$d->format('Y-m')]=['label'=>$bulan[(int)$d->format('n')],'masuk'=>0.0,'keluar'=>0.0];}
 $q=sql("SELECT DATE_FORMAT(tanggal,'%Y-%m') ym, COALESCE(SUM(CASE WHEN jenis='masuk' THEN nominal ELSE 0 END),0) m, COALESCE(SUM(CASE WHEN jenis='keluar' THEN nominal ELSE 0 END),0) k FROM transactions WHERE tanggal >= ? GROUP BY ym",[array_key_first($months).'-01']);
 foreach($q as $r) if(isset($months[$r['ym']])){$months[$r['ym']]['masuk']=(float)$r['m'];$months[$r['ym']]['keluar']=(float)$r['k'];}
 $max=max(1.0,...array_map(fn($m)=>max($m['masuk'],$m['keluar']),array_values($months)));
?><section class="card"><h2>Arus Kas 6 Bulan Terakhir</h2><p class="legend"><span><i style="background:var(--in)"></i>Pemasukan</span><span><i style="background:#d77a7a"></i>Pengeluaran</span></p>
<div class="chart" role="img" aria-label="Grafik batang pemasukan dan pengeluaran enam bulan terakhir"><?php foreach($months as $m): ?><div class="m" title="<?=esc($m['label'].': masuk '.rupiah($m['masuk']).', keluar '.rupiah($m['keluar']))?>"><div class="bars"><span class="bi" style="height:<?=round($m['masuk']/$max*100,1)?>%"></span><span class="bo" style="height:<?=round($m['keluar']/$max*100,1)?>%"></span></div><small><?=esc($m['label'])?></small></div><?php endforeach; ?></div></section>
<?php endif; ?>
<?php if(can_view('laporan')): ?><p class="muted">Saldo merupakan selisih transaksi tercatat, belum merupakan rekonsiliasi kas dan bank.</p><a href="?page=laporan">Buka laporan keuangan</a><?php endif; ?>
