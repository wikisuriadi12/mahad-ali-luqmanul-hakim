<?php if (!in_array($_SESSION['role'],['admin','pimpinan','bendahara'],true)){http_response_code(403);exit('Akses ditolak.');}
$canWrite=in_array($_SESSION['role'],['admin','bendahara'],true);
$editId=$canWrite?(int)filter_var(query('edit'),FILTER_VALIDATE_INT,['options'=>['min_range'=>1,'default'=>0]]):0;
$baruId=(int)($_SESSION['transaksi_baru']??0);unset($_SESSION['transaksi_baru']);
$katOptions=function(string $jenis,string $pilih): string {
 $o='';foreach(['masuk'=>'Pemasukan','keluar'=>'Pengeluaran'] as $j=>$label){
  $o.='<optgroup label="'.esc($label).'">';
  foreach(categories($j) as $k) $o.='<option value="'.esc($k).'"'.($j===$jenis&&$k===$pilih?' selected':'').'>'.esc(str_replace('_',' ',$k)).'</option>';
  $o.='</optgroup>';
 }
 return $o;
};
?><h1>Keuangan</h1><p><a href="?page=laporan">Laporan periode, saldo, dan unduh CSV</a></p>
<?php if($canWrite): ?><section class="card"><h2>Tambah Transaksi</h2><form method="post"><input type="hidden" name="csrf" value="<?=esc(csrf())?>"><div class="formgrid"><label>Tanggal<input type="date" name="tanggal" value="<?=date('Y-m-d')?>" required></label><label>Jenis<select name="jenis" id="jenis" onchange="categories()"><option value="masuk">Pemasukan</option><option value="keluar">Pengeluaran</option></select></label><label>Kategori<select name="kategori" id="kategori" onchange="lainnya()"></select></label><label id="lain-wrap" hidden>Kategori lainnya (ketik sendiri)<input name="kategori_lain" id="kategori_lain" maxlength="100" placeholder="Contoh: Zakat fitrah"></label><label>Nominal (Rp)<input type="number" name="nominal" min=".01" step=".01" required></label><label>Uraian<input name="uraian" maxlength="255" required></label></div><button name="action" value="add_keuangan">Simpan Transaksi</button></form></section><?php endif; ?>
<section class="card"><h2>200 Transaksi Terbaru</h2><p class="muted"><?=$canWrite?'Transaksi baru langsung tampil di tabel ini. Gunakan Edit atau Hapus untuk memperbaiki data; setiap perubahan dan penghapusan dicatat (siapa, kapan, dan isi sebelumnya).':'Daftar transaksi tercatat.'?></p><div class="tablewrap"><table><thead><tr><th>No</th><th>Tanggal</th><th>Jenis</th><th>Kategori</th><th>Uraian</th><th>Nominal</th><th>Status</th><?php if($canWrite): ?><th>Aksi</th><?php endif; ?></tr></thead><tbody>
<?php foreach(db()->query('SELECT t.id,t.tanggal,t.jenis,t.kategori,'.kolom_kategori_lain('t').',t.uraian,t.nominal,c1.id AS dikoreksi,c1.alasan AS alasan,c2.transaction_id AS pembalik_atas,c3.transaction_id AS pengganti_atas FROM transactions t LEFT JOIN transaction_corrections c1 ON c1.transaction_id=t.id LEFT JOIN transaction_corrections c2 ON c2.reversal_id=t.id LEFT JOIN transaction_corrections c3 ON c3.replacement_id=t.id ORDER BY t.tanggal DESC,t.id DESC LIMIT 200') as $r):
 $editing=$canWrite&&$editId===(int)$r['id'];
?><tr id="baris-<?=esc($r['id'])?>"<?=(int)$r['id']===$baruId?' class="baru"':''?>>
<td><?=esc($r['id'])?></td>
<?php if($editing): ?>
<td><input type="date" name="tanggal" form="f-edit" value="<?=esc($r['tanggal'])?>" required></td>
<td><select name="jenis" form="f-edit"><option value="masuk"<?=$r['jenis']==='masuk'?' selected':''?>>Pemasukan</option><option value="keluar"<?=$r['jenis']==='keluar'?' selected':''?>>Pengeluaran</option></select></td>
<td><select name="kategori" form="f-edit"><?=$katOptions($r['jenis'],$r['kategori'])?></select><input name="kategori_lain" form="f-edit" maxlength="100" placeholder="Jika lainnya, ketik kategori" value="<?=esc($r['kategori_lain']??'')?>"></td>
<td><input name="uraian" form="f-edit" maxlength="255" value="<?=esc($r['uraian'])?>" required></td>
<td><input type="number" name="nominal" form="f-edit" min=".01" step=".01" value="<?=esc($r['nominal'])?>" required></td>
<?php else: ?>
<td><?=esc($r['tanggal'])?></td><td><?=esc($r['jenis'])?></td><td><?=esc(label_kategori($r))?></td><td><?=esc($r['uraian'])?></td><td><?=rupiah($r['nominal'])?></td>
<?php endif; ?>
<td><?php if($r['dikoreksi']!==null): ?>Dikoreksi<br><span class="muted"><?=esc($r['alasan'])?></span><?php elseif($r['pembalik_atas']!==null): ?>Pembalik atas #<?=esc($r['pembalik_atas'])?><?php endif; ?><?php if($r['pengganti_atas']!==null): ?><span class="muted">Pengganti #<?=esc($r['pengganti_atas'])?></span><?php endif; ?></td>
<?php if($canWrite): ?><td><div class="aksi"><?php if($editing): ?><form method="post" id="f-edit"><input type="hidden" name="csrf" value="<?=esc(csrf())?>"><input type="hidden" name="id" value="<?=esc($r['id'])?>"><button class="mini" name="action" value="edit_keuangan">Simpan</button></form><a class="btnlink" href="?page=keuangan">Batal</a><?php else: ?><a class="btnlink" href="?page=keuangan&amp;edit=<?=esc($r['id'])?>#baris-<?=esc($r['id'])?>">Edit</a><?php if($r['dikoreksi']===null&&$r['pembalik_atas']===null&&$r['pengganti_atas']===null): ?><form method="post" onsubmit="return confirm('Hapus transaksi no. <?=esc($r['id'])?>? Tindakan ini dicatat dan tidak dapat dibatalkan.')"><input type="hidden" name="csrf" value="<?=esc(csrf())?>"><input type="hidden" name="id" value="<?=esc($r['id'])?>"><button class="mini danger" name="action" value="hapus_keuangan">Hapus</button></form><?php endif; ?><?php endif; ?></div></td><?php endif; ?>
</tr><?php endforeach; ?></tbody></table></div></section>
<script>function categories(){const type=document.getElementById('jenis').value;const values=type==='masuk'?['infaq','usaha','iuran','donasi','lainnya']:['operasional','listrik_air','konsumsi','pendidikan','pemeliharaan','lainnya'];document.getElementById('kategori').replaceChildren(...values.map(x=>new Option(x.replaceAll('_',' '),x)));if(typeof lainnya==='function')lainnya();}function lainnya(){const k=document.getElementById('kategori'),w=document.getElementById('lain-wrap'),i=document.getElementById('kategori_lain');if(!k||!w)return;const on=k.value==='lainnya';w.hidden=!on;i.required=on;if(!on)i.value='';}if(document.getElementById('jenis')){categories();lainnya();}(function(){var t=document.querySelector('tr.baru')||document.querySelector('#f-edit');if(t){(t.closest?t.closest('tr'):t).scrollIntoView({block:'center'})}})();</script>
