<?php
$bulan=['','Jan','Feb','Mar','Apr','Mei','Jun','Jul','Agu','Sep','Okt','Nov','Des'];
$hari=['Minggu','Senin','Selasa','Rabu','Kamis','Jumat','Sabtu'];
$bulanPanjang=['','Januari','Februari','Maret','April','Mei','Juni','Juli','Agustus','September','Oktober','November','Desember'];
$tgl=$hari[(int)date('w')].', '.date('j').' '.$bulanPanjang[(int)date('n')].' '.date('Y');
$hitung=function(string $table,string $kolom='status'): array {
 $r=[];foreach(sql("SELECT $kolom k, COUNT(*) n FROM $table GROUP BY $kolom") as $x) $r[$x['k']]=(int)$x['n'];
 return $r;
};
$persen=fn(int $a,int $b): float => $b>0?round($a/$b*100,1):0.0;
$gabung=function(array $bagian): string { $n=count($bagian); if($n<2) return implode('',$bagian); return implode(', ',array_slice($bagian,0,$n-1)).' dan '.$bagian[$n-1]; };

$ps=$gu=$pe=$as=null;$totalPs=$totalGu=$totalPe=$totalAs=0;$nilaiAset=0.0;
if(can_view('peserta')){$ps=$hitung('participants');$totalPs=array_sum($ps);}
if(can_view('guru')){$gu=$hitung('teachers');$totalGu=array_sum($gu);}
if(can_view('pengurus')){$pe=$hitung('staff_members');$totalPe=array_sum($pe);}
if(can_view('aset')){$as=$hitung('assets','kondisi');$totalAs=array_sum($as);$nilaiAset=(float)sql('SELECT COALESCE(SUM(nilai_perolehan),0) FROM assets')->fetchColumn();}

$keu=can_view('keuangan');
if($keu){
 $t=sql("SELECT COALESCE(SUM(CASE WHEN jenis='masuk' THEN nominal ELSE 0 END),0) masuk, COALESCE(SUM(CASE WHEN jenis='keluar' THEN nominal ELSE 0 END),0) keluar FROM transactions")->fetch();
 $saldo=(float)$t['masuk']-(float)$t['keluar'];
 $months=[];for($i=5;$i>=0;$i--){$d=new DateTimeImmutable('first day of this month -'.$i.' month');$months[$d->format('Y-m')]=['label'=>$bulan[(int)$d->format('n')],'masuk'=>0.0,'keluar'=>0.0];}
 $awal=array_key_first($months).'-01';
 $q=sql("SELECT DATE_FORMAT(tanggal,'%Y-%m') ym, COALESCE(SUM(CASE WHEN jenis='masuk' THEN nominal ELSE 0 END),0) m, COALESCE(SUM(CASE WHEN jenis='keluar' THEN nominal ELSE 0 END),0) k FROM transactions WHERE tanggal >= ? GROUP BY ym",[$awal]);
 foreach($q as $r) if(isset($months[$r['ym']])){$months[$r['ym']]['masuk']=(float)$r['m'];$months[$r['ym']]['keluar']=(float)$r['k'];}
 $max=max(1.0,...array_map(fn($m)=>max($m['masuk'],$m['keluar']),array_values($months)));
 // Saldo berjalan per bulan untuk grafik garis kecil.
 $sebelum=(float)sql("SELECT COALESCE(SUM(CASE WHEN jenis='masuk' THEN nominal ELSE -nominal END),0) FROM transactions WHERE tanggal < ?",[$awal])->fetchColumn();
 $titik=[];$run=$sebelum;foreach($months as $m){$run+=$m['masuk']-$m['keluar'];$titik[]=$run;}
 $lo=min($titik);$hi=max($titik);$rentang=($hi-$lo)>0?($hi-$lo):1.0;
 $pts=[];foreach($titik as $i=>$v){$pts[]=[round($i/(count($titik)-1)*260,1),round(52-(($v-$lo)/$rentang)*42,1)];}
 $garis='M'.implode(' L',array_map(fn($p)=>$p[0].' '.$p[1],$pts));
 $area=$garis.' L260 58 L0 58Z';
 $akhir=end($months);$selisihBulan=$akhir['masuk']-$akhir['keluar'];
 $terbaru=sql("SELECT tanggal,jenis,kategori,uraian,nominal FROM transactions ORDER BY tanggal DESC,id DESC LIMIT 5")->fetchAll();
}

// Kalimat ringkasan pada banner.
$ringkas=[];
if($ps!==null) $ringkas[]=($ps['aktif']??0).' peserta aktif';
if($gu!==null) $ringkas[]=($gu['aktif']??0).' guru mengajar';
if($keu) $ringkas[]='saldo tercatat '.($selisihBulan>=0?'bertambah ':'berkurang ').rupiah_ringkas(abs($selisihBulan)).' bulan ini';
$kalimat=$ringkas?'Ini kondisi Ma\'had hari ini: '.$gabung($ringkas).'.':'Ringkasan administrasi Ma\'had.';

$bagi=function(int $jumlah): array { return $jumlah>=2?['c7','c5']:['c12']; };
$kartu=[];
if($ps!==null){
 $sub=[($ps['aktif']??0).' aktif'];if(($ps['nonaktif']??0)>0)$sub[]=$ps['nonaktif'].' nonaktif';if(($ps['lulus']??0)>0)$sub[]=$ps['lulus'].' lulus';
 $kartu[]=['t1','peserta','Peserta',$totalPs,implode(', ',$sub),$persen($ps['aktif']??0,$totalPs),'','?page=peserta','Kelola peserta'];
}
if($gu!==null){
 $sub=[($gu['aktif']??0).' aktif'];if(($gu['cuti']??0)>0)$sub[]=$gu['cuti'].' cuti';if(($gu['nonaktif']??0)>0)$sub[]=$gu['nonaktif'].' nonaktif';
 $kartu[]=['t2','guru','Guru',$totalGu,implode(', ',$sub),$persen($gu['aktif']??0,$totalGu),'','?page=guru','Kelola guru'];
}
if($pe!==null){
 $sub=[($pe['aktif']??0).' aktif'];if(($pe['nonaktif']??0)>0)$sub[]=$pe['nonaktif'].' nonaktif';
 $kartu[]=['t3','pengurus','Pengurus',$totalPe,implode(', ',$sub),$persen($pe['aktif']??0,$totalPe),'','?page=pengurus','Kelola pengurus'];
}
if($as!==null){
 $kartu[]=['t4','aset','Aset',$totalAs,'Nilai perolehan '.rupiah_ringkas($nilaiAset),$persen($as['baik']??0,$totalAs),'gold','?page=aset','Lihat inventaris'];
}
$lulus=$ps['lulus']??0;$nonaktif=$ps['nonaktif']??0;$aktif=$ps['aktif']??0;
$pA=$persen($aktif,$totalPs);$pL=$persen($lulus,$totalPs);
$donut=$totalPs>0?'conic-gradient(var(--ok) 0 '.$pA.'%,#d9b55f '.$pA.'% '.($pA+$pL).'%,#8da3ae '.($pA+$pL).'% 100%)':'#e6edf0';
$baik=$as['baik']??0;$ringan=$as['rusak_ringan']??0;$berat=$as['rusak_berat']??0;
$baris1=array_values(array_filter([$keu,$ps!==null]));
$baris2=array_values(array_filter([$keu,$as!==null]));
$k1=$bagi(count($baris1));$k2=$bagi(count($baris2));
?><div class="top"><h1>Dashboard</h1><span class="date"><?=esc($tgl)?></span></div>
<div class="layout">
<section class="card hero<?=$keu?'':' full'?>">
 <svg class="arch" viewBox="0 0 230 260" fill="none" aria-hidden="true"><path d="M8 260V118C8 66 68 36 115 4c47 32 107 62 107 114v142" stroke="#b08d45" stroke-width="10"/><path d="M42 260V124c0-36 42-58 73-82 31 24 73 46 73 82v136" stroke="#ffffff" stroke-opacity=".28" stroke-width="8"/><path d="M76 260V132c0-22 22-36 39-52 17 16 39 30 39 52v128" stroke="#ffffff" stroke-opacity=".16" stroke-width="7"/></svg>
 <div><h2>Assalamu'alaikum, <?=esc($_SESSION['username'])?></h2><p><?=esc($kalimat)?></p></div>
 <div class="acts"><?php if(can_write('peserta')): ?><a class="btn gold" href="?page=peserta"><?=icon('plus')?>Tambah peserta</a><?php endif; ?><?php if(can_write('bendahara')&&can_view('keuangan')): ?><a class="btn" href="?page=keuangan"><?=icon('plus')?>Catat transaksi</a><?php endif; ?><?php if(can_view('laporan')): ?><a class="btn" href="?page=laporan"><?=icon('laporan')?>Buka laporan</a><?php endif; ?></div>
</section>
<?php if($keu): ?>
<section class="card saldo">
 <div class="lbl"><?=icon('keuangan')?>Saldo tercatat</div>
 <div class="big<?=$saldo<0?' neg':''?>"><?=esc(rupiah_bulat($saldo))?></div><p class="note">Selisih transaksi tercatat, belum rekonsiliasi kas dan bank.</p>
 <svg class="spark" viewBox="0 0 260 58" preserveAspectRatio="none" role="img" aria-label="Tren saldo 6 bulan terakhir"><defs><linearGradient id="g" x1="0" y1="0" x2="0" y2="1"><stop offset="0" stop-color="#b08d45" stop-opacity=".35"/><stop offset="1" stop-color="#b08d45" stop-opacity="0"/></linearGradient></defs><path d="<?=esc($area)?>" fill="url(#g)"/><path d="<?=esc($garis)?>" stroke="#b08d45" stroke-width="3" fill="none" stroke-linecap="round" stroke-linejoin="round" vector-effect="non-scaling-stroke"/></svg>
 <div class="io"><div><small>Pemasukan</small><b class="in"><?=esc(rupiah_ringkas($t['masuk'],true))?></b></div><div><small>Pengeluaran</small><b class="out"><?=esc(rupiah_ringkas($t['keluar'],true))?></b></div></div>
</section>
<?php endif; ?>
<?php if($kartu): ?><div class="statrow"><?php foreach($kartu as [$tone,$ic,$label,$jumlah,$sub,$pct,$warna,$link,$linkLabel]): ?>
 <section class="card stat"><span class="ar <?=esc($tone)?>"><?=icon($ic)?></span><span class="k"><?=esc($label)?></span><span class="v"><?=(int)$jumlah?></span><span class="d"><?=esc($sub)?></span><div class="bar"><i style="width:<?=$pct?>%<?=$warna==='gold'?';background:var(--gold)':''?>"></i></div><a href="<?=esc($link)?>"><?=esc($linkLabel)?></a></section>
<?php endforeach; ?></div><?php endif; ?>

<?php $i=0; if($keu): ?>
<section class="card cash <?=$k1[$i++]?>"><h2>Arus kas enam bulan terakhir</h2><p class="sub">Pemasukan dan pengeluaran per bulan</p>
 <p class="legend"><span><i style="background:var(--ok)"></i>Pemasukan</span><span><i style="background:#dd8e8b"></i>Pengeluaran</span></p>
 <div class="chart" role="img" aria-label="Grafik batang pemasukan dan pengeluaran enam bulan terakhir"><?php foreach($months as $m): ?><div class="m" title="<?=esc($m['label'].': masuk '.rupiah($m['masuk']).', keluar '.rupiah($m['keluar']))?>"><span class="a" style="height:<?=round($m['masuk']/$max*100,1)?>%"></span><span class="b" style="height:<?=round($m['keluar']/$max*100,1)?>%"></span></div><?php endforeach; ?></div>
 <div class="months"><?php foreach($months as $m): ?><small><?=esc($m['label'])?></small><?php endforeach; ?></div>
</section>
<?php endif; ?>
<?php if($ps!==null): ?>
<section class="card comp <?=$k1[$i++]?>"><h2>Status peserta</h2><p class="sub">Dari <?=$totalPs?> peserta terdaftar</p>
 <div class="donutrow"><div class="donut" style="background:<?=esc($donut)?>" role="img" aria-label="<?=esc(round($pA).' persen peserta aktif')?>"><b><?=round($pA)?>%<small>aktif</small></b></div>
 <ul class="dl"><li><i style="background:var(--ok)"></i><span>Aktif</span><b><?=$aktif?></b></li><li><i style="background:#d9b55f"></i><span>Lulus</span><b><?=$lulus?></b></li><li><i style="background:#8da3ae"></i><span>Nonaktif</span><b><?=$nonaktif?></b></li></ul></div>
 <?php if($nonaktif>0): ?><div class="tip"><?=$nonaktif?> peserta nonaktif. Periksa datanya bila ada yang perlu diaktifkan kembali.</div><?php endif; ?>
</section>
<?php endif; ?>

<?php $i=0; if($keu): ?>
<section class="card trx <?=$k2[$i++]?>"><h2>Transaksi terbaru</h2><p class="sub">Lima catatan terakhir</p>
 <?php if($terbaru): ?><ul class="list"><?php foreach($terbaru as $x): $masuk=$x['jenis']==='masuk'; $tt=strtotime($x['tanggal']); ?>
  <li><span class="ti <?=$masuk?'in':'out'?>"><?=icon($masuk?'plus':'minus')?></span><div class="t"><b><?=esc($x['uraian'])?></b><small><?=esc(ucfirst(str_replace('_',' ',$x['kategori'])).', '.date('j',$tt).' '.$bulan[(int)date('n',$tt)])?></small></div><span class="amt <?=$masuk?'in':'out'?>"><?=$masuk?'+':'-'?> <?=esc(rupiah_bulat($x['nominal']))?></span></li>
 <?php endforeach; ?></ul><?php else: ?><p class="muted">Belum ada transaksi. Catat transaksi pertama di menu Keuangan.</p><?php endif; ?>
 <?php if(can_view('laporan')): ?><a class="more" href="?page=laporan">Lihat semua transaksi</a><?php endif; ?>
</section>
<?php endif; ?>
<?php if($as!==null): ?>
<section class="card kond <?=$k2[$i++]?>"><h2>Kondisi aset</h2><p class="sub">Dari <?=$totalAs?> aset tercatat</p>
 <div class="stack" role="img" aria-label="<?=esc("$baik baik, $ringan rusak ringan, $berat rusak berat")?>"><i style="width:<?=$persen($baik,$totalAs)?>%;background:var(--ok)"></i><i style="width:<?=$persen($ringan,$totalAs)?>%;background:#d9b55f"></i><i style="width:<?=$persen($berat,$totalAs)?>%;background:var(--bad)"></i></div>
 <div class="kl"><div><i style="background:var(--ok)"></i><span>Baik</span><b><?=$baik?></b></div><div><i style="background:#d9b55f"></i><span>Rusak ringan</span><b><?=$ringan?></b></div><div><i style="background:var(--bad)"></i><span>Rusak berat</span><b><?=$berat?></b></div></div>
 <?php if($ringan+$berat>0): ?><div class="tip"><?=$ringan+$berat?> aset perlu perawatan atau penggantian.</div><?php endif; ?>
</section>
<?php endif; ?>
</div>
