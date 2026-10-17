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
for role,allowed,forbidden in [('peserta','peserta','keuangan'),('aset','aset','peserta'),('bendahara','keuangan','aset'),('pimpinan','laporan','pengguna')]:
 c=login(role);assert request(c,allowed)[0]==200;assert request(c,forbidden)[0]==403
 _,s=request(c,'dashboard')
 if role in ['peserta','aset']:assert 'Total Pemasukan' not in s
 if role=='pimpinan':assert request(c,'peserta',{'csrf':token(s),'action':'save_peserta'})[0]==403
other_admin=login('admin')
_,s=request(admin,'akun')
_,s=request(admin,'akun',{'csrf':token(s),'action':'change_password','password_lama':'Testing-Password-123','password_baru':'Changed-Password-123','konfirmasi':'Changed-Password-123'})
assert 'Berhasil disimpan' in s
assert 'Masuk ke Sistem' in request(other_admin,'dashboard')[1]
print('PASS: login, CSRF, XSS escaping, participant edits, asset creation, money, dates, reports, CSV, roles, password/session revocation')
