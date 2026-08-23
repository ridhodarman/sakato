const USERS={kepala:{p:'kepala',n:'Kepala Kantor',r:'Pimpinan',s:'ALL',i:'K'},php:{p:'php',n:'Kepala Seksi PHP',r:'Penetapan Hak & Pendaftaran',s:'Penetapan Hak & Pendaftaran',i:'P'},sp:{p:'sp',n:'Kepala Seksi SP',r:'Survei & Pemetaan',s:'Survei & Pemetaan',i:'S'},tu:{p:'tu',n:'Kepala Subbagian TU',r:'Tata Usaha',s:'Tata Usaha',i:'T'},pic:{p:'pic',n:'PIC Pelaksana',r:'Pelaksana',s:'PIC',i:'P'}};
const seed=[
{no:'AGM-26001',layanan:'Peralihan Hak',seksi:'Penetapan Hak & Pendaftaran',pemohon:'Pemohon A',pic:'Andi',umur:8,sla:30,progres:35,kendala:'-',esk:false},
{no:'AGM-26002',layanan:'Pemecahan Bidang',seksi:'Survei & Pemetaan',pemohon:'Pemohon B',pic:'Budi',umur:19,sla:25,progres:55,kendala:'Menunggu hasil ukur',esk:false},
{no:'AGM-26003',layanan:'Hak Tanggungan',seksi:'Penetapan Hak & Pendaftaran',pemohon:'Pemohon C',pic:'Citra',umur:22,sla:20,progres:65,kendala:'Perlu verifikasi dokumen',esk:true},
{no:'AGM-26004',layanan:'Pengukuran',seksi:'Survei & Pemetaan',pemohon:'Pemohon D',pic:'Budi',umur:15,sla:30,progres:70,kendala:'-',esk:false},
{no:'AGM-26005',layanan:'Pendaftaran Pertama Kali',seksi:'Penetapan Hak & Pendaftaran',pemohon:'Pemohon E',pic:'Dewi',umur:29,sla:30,progres:80,kendala:'Validasi data yuridis',esk:false},
{no:'AGM-26006',layanan:'Perubahan Hak',seksi:'Penetapan Hak & Pendaftaran',pemohon:'Pemohon F',pic:'Andi',umur:35,sla:30,progres:72,kendala:'Koordinasi lintas seksi',esk:true},
{no:'AGM-26007',layanan:'Peralihan Hak',seksi:'Penetapan Hak & Pendaftaran',pemohon:'Pemohon G',pic:'Citra',umur:12,sla:30,progres:100,kendala:'-',esk:false},
{no:'AGM-26008',layanan:'Pengukuran',seksi:'Survei & Pemetaan',pemohon:'Pemohon H',pic:'Dewi',umur:27,sla:30,progres:88,kendala:'Penjadwalan lapangan',esk:false},
{no:'AGM-26009',layanan:'Pemecahan Bidang',seksi:'Survei & Pemetaan',pemohon:'Pemohon I',pic:'Andi',umur:33,sla:30,progres:50,kendala:'Klarifikasi batas',esk:true},
{no:'AGM-26010',layanan:'Hak Tanggungan',seksi:'Penetapan Hak & Pendaftaran',pemohon:'Pemohon J',pic:'Budi',umur:6,sla:20,progres:30,kendala:'-',esk:false},
{no:'AGM-26011',layanan:'Administrasi Umum',seksi:'Tata Usaha',pemohon:'Internal',pic:'Rani',umur:9,sla:14,progres:60,kendala:'Menunggu paraf',esk:false},
{no:'AGM-26012',layanan:'Administrasi Umum',seksi:'Tata Usaha',pemohon:'Internal',pic:'Rani',umur:17,sla:14,progres:75,kendala:'Dokumen pendukung belum lengkap',esk:true}
];
const audit0=[{time:'08:10',text:'Sistem memuat monitoring harian.'},{time:'08:25',text:'PIC Budi memperbarui AGM-26002 menjadi 55%.'},{time:'08:40',text:'Early Warning menandai AGM-26003 sebagai Kritis.'},{time:'09:00',text:'AGM-26006 dieskalasikan untuk koordinasi lintas seksi.'}];
let data=JSON.parse(localStorage.getItem('sakato2data')||'null')||JSON.parse(JSON.stringify(seed)),audit=JSON.parse(localStorage.getItem('sakato2audit')||'null')||JSON.parse(JSON.stringify(audit0)),settings=JSON.parse(localStorage.getItem('sakato2set')||'null')||{warn:70,critical:100},me=null;
const $=id=>document.getElementById(id);function save(){localStorage.setItem('sakato2data',JSON.stringify(data));localStorage.setItem('sakato2audit',JSON.stringify(audit));localStorage.setItem('sakato2set',JSON.stringify(settings))}function msg(t){$('toast').textContent=t;$('toast').classList.remove('hidden');setTimeout(()=>$('toast').classList.add('hidden'),1800)}function log(t){audit.unshift({time:new Date().toLocaleTimeString('id-ID',{hour:'2-digit',minute:'2-digit'}),text:t});save()}
function st(x){if(+x.progres>=100)return'Selesai';let r=+x.umur/+x.sla*100;if(r>=settings.critical)return'Kritis';if(r>=settings.warn)return'Waspada';return'Aman'}function vd(){if(!me)return[];if(me.s==='ALL')return data;if(me.s==='PIC')return data.filter(x=>x.pic==='Andi');return data.filter(x=>x.seksi===me.s)}


function save(){localStorage.setItem('sakato2data',JSON.stringify(data));localStorage.setItem('sakato2audit',JSON.stringify(audit));localStorage.setItem('sakato2set',JSON.stringify(settings))}
function msg(t){let e=$('toast');if(e){e.textContent=t;e.classList.remove('hidden');setTimeout(()=>e.classList.add('hidden'),1800)}}
function log(t){audit.unshift({time:new Date().toLocaleTimeString('id-ID',{hour:'2-digit',minute:'2-digit'}),text:t});save()}
function st(x){if(+x.progres>=100)return'Selesai';let r=+x.umur/+x.sla*100;if(r>=settings.critical)return'Kritis';if(r>=settings.warn)return'Waspada';return'Aman'}
function vd(){if(!me)return[];if(me.s==='ALL')return data;if(me.s==='PIC')return data.filter(x=>x.pic==='Andi');return data.filter(x=>x.seksi===me.s)}
function initUser(){
  const k=sessionStorage.getItem('sakato_user')||'kepala';
  me=USERS[k]||USERS.kepala;
  if($('avatar')) $('avatar').textContent=me.i;
  if($('uname')) $('uname').textContent=me.n;
  if($('urole')) $('urole').textContent=me.r;
  if($('date')) $('date').textContent=new Date().toLocaleDateString('id-ID',{day:'2-digit',month:'short',year:'numeric'});
}
function logout(){
  log((me?.n||'Pengguna')+' keluar dari sistem.');
  sessionStorage.removeItem('sakato_user');
  location.href='index.php';
}

function dashboard(){let d=vd(),ss=d.map(st),a=ss.filter(x=>x!=='Selesai').length,w=ss.filter(x=>x==='Waspada').length,k=ss.filter(x=>x==='Kritis').length,s=ss.filter(x=>x==='Selesai').length,ot=d.length?Math.round(ss.filter(x=>['Aman','Selesai'].includes(x)).length/d.length*100):0;$('ka').textContent=a;$('kw').textContent=w;$('kk').textContent=k;$('ks').textContent=s;$('kot').textContent=ot+'%';$('notifCount').textContent=w+k;let c={Aman:0,Waspada:0,Kritis:0,Selesai:0};ss.forEach(x=>c[x]++);$('risk').innerHTML=Object.entries(c).map(([n,v])=>{let p=d.length?Math.round(v/d.length*100):0;return`<div class="metric"><b>${n}</b><span>${v} berkas (${p}%)</span></div><div class="progress"><div style="width:${p}%"></div></div>`}).join('');let p=d.filter(x=>st(x)==='Kritis').sort((a,b)=>b.umur/b.sla-a.umur/a.sla).slice(0,5);$('priority').innerHTML=p.length?p.map(x=>`<div class="li"><div><b>${x.no}</b><div class="muted">${x.layanan} • PIC ${x.pic}</div></div><span class="priority">Prioritas</span></div>`).join(''):'<div class="muted">Tidak ada berkas kritis.</div>';let secs=[...new Set(d.map(x=>x.seksi))];$('seksiperf').innerHTML=secs.map(s=>{let z=d.filter(x=>x.seksi===s),avg=Math.round(z.reduce((q,x)=>q+(+x.progres),0)/(z.length||1)),cr=z.filter(x=>st(x)==='Kritis').length;return`<div class="metric"><b>${s}</b><span>${avg}% • ${cr} kritis</span></div><div class="progress"><div style="width:${avg}%"></div></div>`}).join('');$('activity').innerHTML=audit.slice(0,4).map(x=>`<div class="timeitem"><div class="tm">${x.time}</div><div class="tb"><b>${x.text}</b></div></div>`).join('');drawTrend()}
function filters(){let vals=[...new Set(vd().map(x=>x.seksi))];$('fk').innerHTML='<option value="">Semua Seksi</option>'+vals.map(x=>`<option>${x}</option>`).join('')}
function table(){let q=$('search').value.toLowerCase(),fs=$('fs').value,fk=$('fk').value;let r=vd().filter(x=>(!q||Object.values(x).join(' ').toLowerCase().includes(q))&&(!fs||st(x)===fs)&&(!fk||x.seksi===fk));$('tbody').innerHTML=r.map(x=>`<tr><td><b>${x.no}</b></td><td>${x.layanan}</td><td>${x.seksi}</td><td>${x.pemohon}</td><td>${x.pic}</td><td>${x.umur} hari</td><td>${x.sla} hari</td><td>${x.progres}%</td><td><span class="status ${st(x)}">${st(x)}</span></td><td>${x.kendala||'-'}</td><td><button class="mini" onclick="upd('${x.no}')">Update</button> <button class="mini" onclick="esc('${x.no}')">${x.esk?'Tutup Esk.':'Eskalasi'}</button></td></tr>`).join('')}
function warnings(){let a=vd().filter(x=>['Waspada','Kritis'].includes(st(x))).sort((a,b)=>b.umur/b.sla-a.umur/a.sla);$('alerts').innerHTML=a.length?a.map(x=>{let s=st(x),r=Math.round(x.umur/x.sla*100);return`<div class="alert ${s==='Kritis'?'critical':''}"><div class="alerthead"><b>${s}: ${x.no} — ${x.layanan}</b><span class="status ${s}">${r}% SLA</span></div><div class="muted" style="margin-top:5px">${x.seksi} • PIC ${x.pic} • ${x.umur}/${x.sla} hari • Progres ${x.progres}%</div><div style="font-size:10px;margin-top:5px">Kendala: ${x.kendala||'-'}</div><div style="margin-top:7px"><button class="mini" onclick="upd('${x.no}')">Tindak Lanjut</button> <button class="mini" onclick="esc('${x.no}')">${x.esk?'Tutup Eskalasi':'Eskalasi ke Pimpinan'}</button></div></div>`}).join(''):'<div class="muted">Tidak ada peringatan aktif.</div>'}
function escalation(){let a=vd().filter(x=>x.esk);$('esclist').innerHTML=a.length?a.map(x=>`<div class="escalation"><div><b>${x.no} — ${x.layanan}</b><div class="muted">${x.seksi} • PIC ${x.pic} • ${st(x)} • ${x.kendala||'-'}</div></div><div><button class="btn success" onclick="upd('${x.no}')">Tindak Lanjut</button> <button class="btn light" onclick="esc('${x.no}')">Tutup</button></div></div>`).join(''):'<div class="muted">Belum ada berkas yang dieskalasikan.</div>'}
function pics(){let m={};vd().forEach(x=>(m[x.pic]??=[]).push(x));$('piclist').innerHTML=Object.entries(m).map(([p,a])=>{let av=Math.round(a.reduce((s,x)=>s+(+x.progres),0)/a.length),dn=a.filter(x=>st(x)==='Selesai').length,cr=a.filter(x=>st(x)==='Kritis').length;return`<div class="li"><div><b>${p}</b><div class="muted">${a.length} berkas • ${dn} selesai • ${cr} kritis</div></div><div style="min-width:170px"><div class="muted">Rata-rata ${av}%</div><div class="progress"><div style="width:${av}%"></div></div></div></div>`}).join('')}
function quick(){let a=vd().filter(x=>st(x)!=='Selesai').sort((a,b)=>b.umur/b.sla-a.umur/a.sla).slice(0,7);$('quick').innerHTML=a.map(x=>`<div class="li"><div><b>${x.no}</b><div class="muted">${x.layanan} • ${x.progres}% • ${st(x)}</div></div><button class="mini" onclick="upd('${x.no}')">Update</button></div>`).join('')}
function audits(){$('auditlist').innerHTML=audit.map(x=>`<div class="timeitem"><div class="tm">${x.time}</div><div class="tb"><b>${x.text}</b></div></div>`).join('')}
function upd(no){let x=data.find(y=>y.no===no);if(!x)return;$('modal').innerHTML=`<div class="modalbg"><div class="modal"><div class="modalhead"><h3>Update ${x.no}</h3><button class="x" onclick="closeM()">✕</button></div><div class="formgrid"><div class="field"><label>Progres (%)</label><input id="mp" type="number" min="0" max="100" value="${x.progres}"></div><div class="field"><label>Umur (hari)</label><input id="mu" type="number" min="0" value="${x.umur}"></div><div class="field full"><label>Kendala</label><textarea id="mk" rows="3">${x.kendala||''}</textarea></div><div class="field full"><label>Tindak Lanjut</label><textarea id="ma" rows="3" placeholder="Koordinasi / verifikasi / penjadwalan / penyelesaian"></textarea></div><div class="full"><button class="btn primary" onclick="saveUpd('${x.no}')">Simpan Update</button></div></div></div></div>`;$('modal').classList.remove('hidden')}
function closeM(){$('modal').classList.add('hidden');$('modal').innerHTML=''}
function saveUpd(no){let x=data.find(y=>y.no===no);x.progres=Math.max(0,Math.min(100,+$('mp').value));x.umur=Math.max(0,+$('mu').value);x.kendala=$('mk').value.trim()||'-';let a=$('ma').value.trim();log(me.n+' memperbarui '+no+' menjadi '+x.progres+'%'+(a?' — '+a:'')+'.');save();closeM();render();msg('Update tersimpan.')}
function esc(no){let x=data.find(y=>y.no===no);x.esk=!x.esk;log(me.n+' '+(x.esk?'melakukan':'menutup')+' eskalasi '+no+'.');save();render();msg(x.esk?'Berkas dieskalasikan.':'Eskalasi ditutup.')}
function drawTrend(){let c=$('trend'),ctx=c.getContext('2d'),w=c.clientWidth,h=c.clientHeight,d=window.devicePixelRatio||1;c.width=w*d;c.height=h*d;ctx.scale(d,d);ctx.clearRect(0,0,w,h);let labs=['M1','M2','M3','M4','M5','M6'],ser={Aman:[12,14,15,16,17,18],Waspada:[7,6,6,5,4,3],Kritis:[5,5,4,4,3,2],Selesai:[2,4,6,8,10,12]},col={Aman:'#2e6fb3',Waspada:'#ce8a00',Kritis:'#c53d3d',Selesai:'#16835d'};ctx.strokeStyle='#dce4ed';for(let i=0;i<5;i++){let y=20+i*(h-45)/4;ctx.beginPath();ctx.moveTo(30,y);ctx.lineTo(w-10,y);ctx.stroke()}ctx.font='9px Segoe UI';ctx.fillStyle='#6b7280';labs.forEach((m,i)=>ctx.fillText(m,30+i*(w-45)/(labs.length-1)-5,h-7));Object.entries(ser).forEach(([n,v])=>{ctx.strokeStyle=col[n];ctx.lineWidth=2;ctx.beginPath();v.forEach((q,i)=>{let x=30+i*(w-45)/(v.length-1),y=h-27-(q/20)*(h-52);i?ctx.lineTo(x,y):ctx.moveTo(x,y)});ctx.stroke()})}
function drawPic(){let m={};vd().forEach(x=>m[x.pic]=(m[x.pic]||0)+1);let n=Object.keys(m),v=Object.values(m),c=$('picchart'),ctx=c.getContext('2d'),w=c.clientWidth,h=c.clientHeight,d=window.devicePixelRatio||1;c.width=w*d;c.height=h*d;ctx.scale(d,d);ctx.clearRect(0,0,w,h);let mx=Math.max(1,...v);ctx.font='9px Segoe UI';n.forEach((a,i)=>{let slot=(w-40)/n.length,bw=slot*.55,x=22+i*slot+slot*.2,bh=v[i]/mx*(h-50),y=h-25-bh;ctx.fillStyle='#2e6fb3';ctx.fillRect(x,y,bw,bh);ctx.fillStyle='#5d6878';ctx.fillText(a,x,h-7);ctx.fillText(v[i],x+bw/2-2,y-5)})}

function render(){
  if($('warn')) $('warn').value=settings.warn;
  if($('critical')) $('critical').value=settings.critical;
  if($('fk')) filters();
  if($('dashboard')) dashboard();
  if($('tbody')) table();
  if($('alerts')) warnings();
  if($('esclist')) escalation();
  if($('piclist')) pics();
  if($('quick')) quick();
  if($('auditlist')) audits();
  if($('trend')) drawTrend();
  if($('picchart')) setTimeout(drawPic,30);
}
window.addEventListener('resize',()=>{if($('trend'))drawTrend();if($('picchart'))drawPic();});

document.addEventListener('DOMContentLoaded',()=>{
  initUser();
  if($('search')) $('search').oninput=table;
  if($('fs')) $('fs').onchange=table;
  if($('fk')) $('fk').onchange=table;
  if($('form')) $('form').onsubmit=e=>{
    e.preventDefault();
    let x=Object.fromEntries(new FormData($('form')).entries());
    x.sla=+x.sla;x.umur=+x.umur;x.progres=+x.progres;x.esk=false;
    if(data.some(y=>y.no===x.no)){msg('Nomor berkas sudah ada.');return}
    data.push(x);log(me.n+' menambahkan '+x.no+'.');$('form').reset();render();msg('Berkas berhasil ditambahkan.');
  };
  if($('saveSet')) $('saveSet').onclick=()=>{
    settings={warn:+$('warn').value,critical:+$('critical').value};
    log(me.n+' mengubah parameter EWS menjadi '+settings.warn+'% / '+settings.critical+'%.');
    save();render();msg('Parameter tersimpan.');
  };
  if($('reset')) $('reset').onclick=()=>{
    if(confirm('Reset data prototype ke data demo?')){
      data=JSON.parse(JSON.stringify(seed));audit=JSON.parse(JSON.stringify(audit0));settings={warn:70,critical:100};
      save();render();msg('Data demo direset.');
    }
  };
  if($('csv')) $('csv').onclick=()=>{
    let h=['No Berkas','Layanan','Seksi','Pemohon','PIC','Umur','SLA','Progres','Status','Kendala','Eskalasi'],
      r=vd().map(x=>[x.no,x.layanan,x.seksi,x.pemohon,x.pic,x.umur,x.sla,x.progres,st(x),x.kendala,x.esk?'Ya':'Tidak']),
      c=[h,...r].map(z=>z.map(v=>'"'+String(v??'').replace(/"/g,'""')+'"').join(',')).join('\n'),
      b=new Blob([c],{type:'text/csv'}),a=document.createElement('a');
    a.href=URL.createObjectURL(b);a.download='SAKATO_V2_data.csv';a.click();
    log(me.n+' mengekspor data CSV.');audits();
  };
});
