<!doctype html><html lang="id"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Login - Ma'had Aly</title><link rel="icon" type="image/png" href="assets/favicon.png"><style>
:root{--navy:#184153;--navy-d:#0f2d3a;--gold:#ae8b49;--gold-l:#e9dcc0;--bg:#f5f2ea;--line:#d8dde0;--ink:#1d3440;--muted:#5d727d}
*{box-sizing:border-box}
html,body{height:100%}
body{margin:0;min-height:100vh;font-family:"Segoe UI",system-ui,-apple-system,Roboto,Arial,sans-serif;color:var(--ink);background:var(--bg);display:grid;place-items:center;padding:24px;position:relative;overflow-x:hidden}
.deco{position:fixed;inset:0;pointer-events:none;z-index:0}
.deco svg{position:absolute;width:520px;height:auto;opacity:.055}
.deco .a{left:-140px;bottom:-120px}.deco .b{right:-160px;top:-140px;transform:rotate(180deg)}
main{position:relative;z-index:1;width:100%;max-width:440px}
.card{background:#fff;border-radius:22px;padding:34px 36px 30px;box-shadow:0 1px 2px rgba(24,65,83,.06),0 18px 50px rgba(24,65,83,.12);position:relative;overflow:hidden}
.card:before{content:"";position:absolute;top:0;left:0;right:0;height:5px;background:linear-gradient(90deg,var(--navy) 0 38%,var(--gold) 38% 62%,var(--navy) 62% 100%)}
.logo{display:block;width:100%;max-width:330px;height:auto;margin:6px auto 4px}
.head{text-align:center;margin:14px 0 22px}
.head h1{margin:0;font-size:1.2rem;font-weight:700;color:var(--navy);letter-spacing:.2px}
.head p{margin:4px 0 0;color:var(--muted);font-size:.92rem}
label.f{display:block;font-size:.86rem;font-weight:600;color:var(--navy);margin:0 0 6px}
.field{margin-bottom:16px}
input[type=text],input[type=password]{width:100%;height:48px;border:1.5px solid var(--line);border-radius:12px;padding:0 14px;font:inherit;font-size:1rem;color:var(--ink);background:#fbfcfc;transition:border-color .15s,box-shadow .15s}
input:focus{outline:none;border-color:var(--navy);box-shadow:0 0 0 4px rgba(24,65,83,.12);background:#fff}
.pw{position:relative}.pw input{padding-right:52px}
.eye{position:absolute;right:4px;top:50%;transform:translateY(-50%);width:44px;height:44px;display:grid;place-items:center;border:0;background:transparent;color:var(--navy);cursor:pointer;border-radius:10px}
.eye:focus-visible{outline:2px solid var(--gold);outline-offset:1px}
.eye svg{width:22px;height:22px}.eye .slash{display:none}.eye[aria-pressed=true] .slash{display:block}
.ingat{display:flex;align-items:center;gap:10px;margin:4px 0 22px;font-size:.92rem;color:var(--ink);cursor:pointer;user-select:none}
.ingat input{appearance:none;width:20px;height:20px;border:1.5px solid #9fb0b8;border-radius:6px;margin:0;display:grid;place-items:center;background:#fff;cursor:pointer;flex:none}
.ingat input:checked{background:var(--navy);border-color:var(--navy)}
.ingat input:checked:after{content:"";width:5px;height:10px;border:solid #fff;border-width:0 2.2px 2.2px 0;transform:rotate(45deg) translate(-1px,-1px)}
.ingat input:focus-visible{outline:2px solid var(--gold);outline-offset:2px}
.btn{width:100%;height:50px;border:0;border-radius:12px;background:linear-gradient(180deg,#1d4b60,var(--navy));color:#fff;font:inherit;font-weight:700;font-size:1.02rem;letter-spacing:.3px;cursor:pointer;box-shadow:0 6px 16px rgba(24,65,83,.28);transition:transform .08s,box-shadow .15s}
.btn:hover{box-shadow:0 8px 20px rgba(24,65,83,.36)}.btn:active{transform:translateY(1px)}
.btn:focus-visible{outline:3px solid var(--gold);outline-offset:2px}
.err{background:#fdeaea;color:#8d2323;border-radius:10px;padding:11px 14px;font-size:.9rem;margin:0 0 16px;display:none}
.show-err .err{display:block}
.foot{text-align:center;color:var(--muted);font-size:.8rem;margin-top:18px}
.foot b{color:var(--gold);font-weight:700}
@media(max-width:480px){body{padding:14px;align-items:start;padding-top:28px}.card{padding:26px 22px 24px;border-radius:18px}}
</style></head><body>
<div class="deco" aria-hidden="true">
<svg class="a" viewBox="0 0 300 400" fill="none" stroke="#184153" stroke-width="10"><path d="M20 400V190C20 110 110 60 150 12c40 48 130 98 130 178v210"/><path d="M60 400V200c0-55 62-92 90-128 28 36 90 73 90 128v200"/></svg>
<svg class="b" viewBox="0 0 300 400" fill="none" stroke="#ae8b49" stroke-width="10"><path d="M20 400V190C20 110 110 60 150 12c40 48 130 98 130 178v210"/><path d="M60 400V200c0-55 62-92 90-128 28 36 90 73 90 128v200"/></svg>
</div>
<main><section class="card">
<img class="logo" alt="Logo Ma'had Aly Luqmanul Hakim" src="assets/logo-mahad-aly.png" width="800" height="350">
<div class="head"><h1>Masuk ke Sistem</h1><p>Sistem Informasi Manajemen</p></div>
<?php if (!empty($_SESSION['flash'])): ?><p class="err" role="alert" style="display:block"><?=esc($_SESSION['flash'])?></p><?php unset($_SESSION['flash']); endif; ?>
<?php $ingat=isset($_COOKIE['ingat_username'])&&is_string($_COOKIE['ingat_username'])&&preg_match('/^[A-Za-z0-9_.-]{3,80}$/D',$_COOKIE['ingat_username'])?$_COOKIE['ingat_username']:''; ?>
<form method="post"><input type="hidden" name="csrf" value="<?=esc(csrf())?>">
<div class="field"><label class="f" for="username">Username</label><input id="username" name="username" type="text" autocomplete="username" value="<?=esc($ingat)?>" required></div>
<div class="field"><label class="f" for="password">Password</label><div class="pw"><input id="password" name="password" type="password" autocomplete="current-password" required><button type="button" class="eye" aria-label="Tampilkan password" title="Tampilkan password" aria-controls="password" aria-pressed="false" onclick="const p=document.getElementById('password');const show=p.type==='password';p.type=show?'text':'password';this.setAttribute('aria-pressed',String(show));this.setAttribute('aria-label',show?'Sembunyikan password':'Tampilkan password');this.title=this.getAttribute('aria-label')"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M2 12s3.5-7 10-7 10 7 10 7-3.5 7-10 7S2 12 2 12Z"/><circle cx="12" cy="12" r="3"/><path class="slash" d="m3 3 18 18"/></svg></button></div></div>
<label class="ingat"><input type="checkbox" name="ingat" value="1"<?=$ingat!==''?' checked':''?>> Ingat username saya di perangkat ini</label>
<button class="btn" name="action" value="login">Masuk</button>
</form>
<p class="foot">&copy; Ma'had Aly Luqmanul Hakim</p>
</section></main>
</body></html>
