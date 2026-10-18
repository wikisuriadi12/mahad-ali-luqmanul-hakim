<?php
$menu=[
 ''=>['dashboard'=>'Dashboard'],
 'Data'=>['peserta'=>'Data Peserta','guru'=>'Data Guru','pengurus'=>'Data Pengurus','aset'=>'Aset'],
 'Keuangan'=>['keuangan'=>'Keuangan','laporan'=>'Laporan'],
 'Pengaturan'=>['pengguna'=>'Pengguna','akun'=>'Akun'],
];
$iconKey=['dashboard'=>'home'];
$tables=['peserta'=>'participants','guru'=>'teachers','pengurus'=>'staff_members','aset'=>'assets'];
$counts=[];
foreach($tables as $k=>$t){
 if(!can_view($k)) continue;
 try{$counts[$k]=(int)sql('SELECT COUNT(*) FROM '.$t)->fetchColumn();}catch(Throwable $e){}
}
$short=['dashboard'=>'Beranda','peserta'=>'Peserta','guru'=>'Guru','keuangan'=>'Keuangan','aset'=>'Aset','pengurus'=>'Pengurus','laporan'=>'Laporan'];
$bottom=[];
foreach($short as $k=>$label){ if(can_view($k)&&count($bottom)<4) $bottom[$k]=$label; }
$username=(string)($_SESSION['username']??'');
$initial=function_exists('mb_substr')?mb_strtoupper(mb_substr($username,0,1)):strtoupper(substr($username,0,1));
?><!doctype html><html lang="id"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><meta name="theme-color" content="#17394a"><title>SIM Ma'had Aly</title><link rel="stylesheet" href="assets/style.css?v=<?=(int)@filemtime(dirname(__DIR__).'/assets/style.css')?>"><link rel="icon" type="image/png" href="assets/favicon.png"></head><body>
<div class="app">
<aside class="side" id="menu-samping" aria-label="Menu utama">
 <a class="logo" href="?page=dashboard"><img src="assets/logo-mahad-aly.png" alt="Ma'had Aly Luqmanul Hakim" width="190" height="83"></a>
 <nav class="nav"><?php foreach($menu as $group=>$items):
  $visible=array_filter(array_keys($items),'can_view');
  if(!$visible) continue;
  if($group!==''): ?><div class="grp"><?=esc($group)?></div><?php endif;
  foreach($visible as $key): ?><a <?=$page===$key?'class="on" aria-current="page"':''?> href="?page=<?=esc($key)?>"><?=icon($iconKey[$key]??$key)?><?=esc($items[$key])?><?php if(isset($counts[$key])): ?><span class="cnt"><?=$counts[$key]?></span><?php endif; ?></a><?php endforeach;
 endforeach; ?></nav>
 <div class="me"><span class="av"><?=esc($initial)?></span><div><b><?=esc($username)?></b><small><?=esc(ucfirst((string)($_SESSION['role']??'')))?></small></div><form method="post"><?php token(); ?><button name="action" value="logout" class="logout">Keluar</button></form></div>
</aside>
<div class="side-backdrop" data-tutup></div>
<div class="content">
<div class="mtop"><a href="?page=dashboard"><img src="assets/logo-mahad-aly.png" alt="Ma'had Aly Luqmanul Hakim" width="105" height="46"></a><a class="av" href="?page=akun" aria-label="Akun"><?=esc($initial)?></a></div>
<main class="main"><?php if(isset($_SESSION['flash'])): ?><div class="notice" role="status"><?=esc($_SESSION['flash'])?></div><?php unset($_SESSION['flash']);endif; ?><?php try {require __DIR__.'/'.$page.'.php';} catch(Throwable $e) {error_log($e->getMessage());echo '<p class="error">Data belum dapat ditampilkan. Periksa konfigurasi database dan impor tabel sistem.</p>';} ?></main>
<footer>SIM Ma'had Aly Luqmanul Hakim</footer>
</div></div>
<nav class="bnav" aria-label="Menu ponsel"><?php foreach($bottom as $k=>$label): ?><a <?=$page===$k?'class="on" aria-current="page"':''?> href="?page=<?=esc($k)?>"><span><?=icon($iconKey[$k]??$k)?></span><?=esc($label)?></a><?php endforeach; ?><button type="button" id="buka-menu" aria-controls="menu-samping" aria-expanded="false"><span><?=icon('menu')?></span>Menu</button></nav>
<script>
(function(){var b=document.body,t=document.getElementById("buka-menu");function set(o){b.classList.toggle("menu-open",o);t.setAttribute("aria-expanded",o?"true":"false")}
t.addEventListener("click",function(){set(!b.classList.contains("menu-open"))});
document.querySelector("[data-tutup]").addEventListener("click",function(){set(false)});
document.addEventListener("keydown",function(e){if(e.key==="Escape")set(false)});
document.querySelectorAll(".tablewrap table").forEach(function(t){var h=[].map.call(t.querySelectorAll("thead th"),function(x){return x.textContent.trim()});t.querySelectorAll("tbody tr").forEach(function(r){[].forEach.call(r.children,function(c,i){if(h[i]&&!c.hasAttribute("colspan"))c.setAttribute("data-label",h[i])})})})})();
</script></body></html>
