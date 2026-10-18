import http.cookiejar, urllib.request, urllib.parse, urllib.error, re, csv, io
BASE='http://127.0.0.1:8080/'
def client():
 return urllib.request.build_opener(urllib.request.HTTPCookieProcessor(http.cookiejar.CookieJar()))
def request(c,page='login',data=None):
 url=BASE+'?'+page if '=' in page else BASE+'?page='+page
 try:
  r=c.open(url,urllib.parse.urlencode(data).encode() if data is not None else None)
  return r.status,r.read().decode('utf-8-sig')
 except urllib.error.HTTPError as e:return e.code,e.read().decode()
def token(s):return re.search(r'name="csrf" value="([^"]+)"',s).group(1)
def login(role):
 c=client();_,s=request(c);status,s=request(c,data={'csrf':token(s),'action':'login','username':role,'password':'Testing-Password-123'})
 assert status==200 and 'Keluar' in s,(role,status,s)
 return c
admin=login('admin')
jar=http.cookiejar.CookieJar();rc=urllib.request.build_opener(urllib.request.HTTPCookieProcessor(jar))
_,s=request(rc);assert 'name="ingat"' in s and 'Ingat username' in s
status,s=request(rc,data={'csrf':token(s),'action':'login','username':'admin','password':'Testing-Password-123','ingat':'1'})
assert status==200 and 'Keluar' in s and any(k.name=='ingat_username' and k.value=='admin' for k in jar)
jar2=http.cookiejar.CookieJar();rd=urllib.request.build_opener(urllib.request.HTTPCookieProcessor(jar2));_,s=request(rd)
status,s=request(rd,data={'csrf':token(s),'action':'login','username':'admin','password':'Testing-Password-123'})
assert status==200 and 'Keluar' in s and not any(k.name=='ingat_username' and k.value for k in jar2)
_,s=request(admin,'peserta')
assert request(admin,'peserta',{'csrf':'bad','action':'save_peserta'})[0]==419
_,s=request(admin,'peserta',{'csrf':token(s),'action':'save_peserta','nomor_induk':'001','nama':'<script>alert(1)</script>','angkatan':'2026','nama_wali':'Wali','status':'aktif'})
assert '&lt;script&gt;' in s and '<script>alert(1)</script>' not in s
_,s=request(admin,'page=peserta&edit=1')
_,s=request(admin,'peserta',{'csrf':token(s),'action':'save_peserta','id':'1','nomor_induk':'001','nama':'Santri Satu','angkatan':'2026','nama_wali':'Wali','status':'lulus'})
assert 'Santri Satu' in s and '<td>lulus</td>' in s
_,s=request(admin,'aset')
_,s=request(admin,'aset',{'csrf':token(s),'action':'save_aset','kode':'A001','nama':'Meja','kategori':'Perabot','lokasi':'Kelas','kondisi':'baik','nilai':'120000.50'})
assert 'Meja' in s and '120.000,50' in s
_,s=request(admin,'keuangan')
for date,kind,cat,value,description in [('2026-01-01','masuk','donasi','100.25','=test'),('2026-01-02','keluar','operasional','20.10','Biaya')]:
 _,s=request(admin,'keuangan',{'csrf':token(s),'action':'add_keuangan','tanggal':date,'jenis':kind,'kategori':cat,'nominal':value,'uraian':description})
_,s=request(admin,'keuangan',{'csrf':token(s),'action':'add_keuangan','tanggal':'2026-02-30','jenis':'masuk','kategori':'donasi','nominal':'5','uraian':'BAD DATE'})
assert 'Tanggal, jenis, atau kategori tidak valid' in s and '<td>BAD DATE</td>' not in s
_,s=request(admin,'page=laporan&dari=2026-01-01&sampai=2026-01-31')
assert '80,15' in s and '2 transaksi' in s
status,s=request(admin,'page=laporan&dari=2026-01-01&sampai=2026-01-31&export=csv')
rows=list(csv.reader(io.StringIO(s)))
assert status==200 and len(rows)==3 and rows[1][3]=="'=test"
_,s=request(admin,'keuangan')
_,s=request(admin,'keuangan',{'csrf':token(s),'action':'koreksi_keuangan','id':'1','alasan':'Salah nominal','tanggal_koreksi':'2026-03-01','ganti':'1','tanggal':'2026-01-01','jenis':'masuk','kategori':'donasi','nominal':'150.00','uraian':'Donasi benar'})
assert 'Berhasil disimpan' in s and 'Dikoreksi' in s and 'Salah nominal' in s and 'KOREKSI #1' in s
_,s=request(admin,'page=laporan&dari=2026-01-01&sampai=2026-03-31')
assert '129,90' in s and '4 transaksi' in s
_,s=request(admin,'keuangan',{'csrf':token(s),'action':'koreksi_keuangan','id':'1','alasan':'Ulang','tanggal_koreksi':'2026-03-02'})
assert 'sudah dikoreksi' in s
_,s=request(admin,'keuangan',{'csrf':token(s),'action':'koreksi_keuangan','id':'3','alasan':'Koreksi pembalik','tanggal_koreksi':'2026-03-02'})
assert 'sudah dikoreksi atau merupakan transaksi pembalik' in s
_,s=request(admin,'keuangan',{'csrf':token(s),'action':'koreksi_keuangan','id':'2','alasan':'','tanggal_koreksi':'2026-03-02'})
assert 'kosong atau terlalu panjang' in s
_,s=request(admin,'keuangan',{'csrf':token(s),'action':'koreksi_keuangan','id':'2','alasan':'Salah catat','tanggal_koreksi':'2026-02-30'})
assert 'tidak valid' in s
_,s=request(admin,'keuangan',{'csrf':token(s),'action':'koreksi_keuangan','id':'999','alasan':'Tidak ada','tanggal_koreksi':'2026-03-02'})
assert 'Transaksi tidak ditemukan' in s
pimp=login('pimpinan');_,s=request(pimp,'keuangan')
assert 'Simpan Koreksi' not in s and request(pimp,'keuangan',{'csrf':token(s),'action':'koreksi_keuangan','id':'2','alasan':'x','tanggal_koreksi':'2026-03-02'})[0]==403
for mod,code,extra in (('guru','G-001',{'bidang_ajar':'Fiqih','no_hp':'0812'}),('pengurus','P-001',{'jabatan':'Sekretaris','no_hp':'0813'})):
 _,s=request(admin,mod)
 assert request(admin,mod,{'csrf':'bad','action':'save_'+mod})[0]==419
 data={'action':'save_'+mod,'kode':code,'nama':'<b>Orang Satu</b>','status':'aktif',**extra}
 _,s=request(admin,mod,{'csrf':token(s),**data})
 assert 'Berhasil disimpan' in s and '&lt;b&gt;Orang Satu' in s and '<b>Orang Satu' not in s,mod
 _,s=request(admin,mod,{'csrf':token(s),**data})
 assert 'sudah digunakan' in s,mod
 _,s=request(admin,mod,{'csrf':token(s),**{**data,'kode':code+'X','status':'bukan-status'}})
 assert 'Status tidak valid' in s,mod
 _,s=request(admin,'page=%s&edit=1'%mod)
 assert 'value="%s"'%code in s,mod
 _,s=request(admin,mod,{'csrf':token(s),'id':'1',**{**data,'nama':'Orang Dua'}})
 assert 'Orang Dua' in s and 'Berhasil disimpan' in s,mod
 _,s=request(admin,mod,{'csrf':token(s),'id':'999',**data})
 assert 'Data tidak ditemukan' in s,mod
_,s=request(admin,'keuangan')
_,s=request(admin,'keuangan',{'csrf':token(s),'action':'add_keuangan','tanggal':'2027-06-01','jenis':'masuk','kategori':'lainnya','kategori_lain':'<i>Zakat fitrah</i>','nominal':'0.01','uraian':'Kategori manual'})
assert 'lainnya: &lt;i&gt;Zakat fitrah&lt;/i&gt;' in s and '<i>Zakat fitrah</i>' not in s
_,s=request(admin,'keuangan',{'csrf':token(s),'action':'add_keuangan','tanggal':'2027-06-01','jenis':'masuk','kategori':'infaq','kategori_lain':'Diabaikan','nominal':'0.01','uraian':'Kategori baku'})
assert 'Diabaikan' not in s
_,s=request(admin,'keuangan',{'csrf':token(s),'action':'add_keuangan','tanggal':'2027-06-01','jenis':'masuk','kategori':'lainnya','kategori_lain':'x'*101,'nominal':'0.01','uraian':'Terlalu panjang'})
assert 'Terlalu panjang</td>' not in s
_,s=request(admin,'dashboard')
assert 'Kelola guru' in s and 'Kelola pengurus' in s
for mod in ('guru','pengurus'):
 _,s=request(pimp,mod);assert request(pimp,mod)[0]==200 and 'Simpan' not in s
 assert request(pimp,mod,{'csrf':token(s),'action':'save_'+mod})[0]==403
 assert request(login('peserta'),mod)[0]==403
for role,allowed,forbidden in [('peserta','peserta','keuangan'),('aset','aset','peserta'),('bendahara','keuangan','aset'),('pimpinan','laporan','pengguna')]:
 c=login(role);assert request(c,allowed)[0]==200;assert request(c,forbidden)[0]==403
 _,s=request(c,'dashboard')
 if role in ['peserta','aset']:assert 'Saldo tercatat' not in s
 if role=='pimpinan':assert request(c,'peserta',{'csrf':token(s),'action':'save_peserta'})[0]==403
other_admin=login('admin')
_,s=request(admin,'akun')
_,s=request(admin,'akun',{'csrf':token(s),'action':'change_password','password_lama':'Testing-Password-123','password_baru':'Changed-Password-123','konfirmasi':'Changed-Password-123'})
assert 'Berhasil disimpan' in s
assert 'Masuk ke Sistem' in request(other_admin,'dashboard')[1]
print('PASS: data guru dan pengurus, koreksi transaksi (pembalik, pengganti, tolak ganda), login, CSRF, XSS escaping, participant edits, asset creation, money, dates, reports, CSV, roles, password/session revocation')
