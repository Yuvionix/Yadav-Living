import { login, signup, logout, getUser, handleAuthCallback, requestPasswordRecovery, updateUser, acceptInvite } from '@netlify/identity';
import type { User } from '@netlify/identity';
import { modules, canCreate, isManager, isOffice, statuses } from '../../shared/model';
import type { Property, Field } from '../../shared/model';
import './portal.css';

type Profile = {user_id:string;full_name:string;email:string;phone:string;unit:string;property:Property};
type RecordRow = {id:string;owner_id:string;owner_name:string;data:Record<string,string>;status:string;response:string;created_at:string;document_id?:string};
type Room = {id:string;name:string;capacity:number;monthly_rent:string;occupied:number;bed?:number};
type Person = Profile & {room_id?:string;room_name?:string;bed?:number};
const app=document.querySelector<HTMLDivElement>('#app')!;
let user:User|null=null, profile:Profile|null=null;
let property:Property=new URLSearchParams(location.search).get('property')==='pg'?'pg':'society';
let view='dashboard', renderVersion=0;
let authMode=new URLSearchParams(location.search).get('mode')==='signup'?'signup':'login';
let inviteToken='';
const h=(value:unknown)=>String(value??'').replace(/[&<>"']/g,c=>({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c]!));
const money=(value:unknown)=>new Intl.NumberFormat('en-IN',{style:'currency',currency:'INR'}).format(Number(value||0));
const date=(value:string)=>new Date(value).toLocaleDateString('en-IN',{day:'numeric',month:'short',year:'numeric'});
const manager=()=>isManager(user?.roles||[],property);
const office=()=>isOffice(user?.roles||[],property);
const title=()=>property==='pg'?'Yadav Living PG':'Yadav Living Society';
function alertMessage(message:string,error=false) {
 const area=document.querySelector<HTMLElement>('#message');
 if(area){ area.textContent=message; area.className=error?'message error':'message success'; area.hidden=false; area.focus(); }
}
async function api<T>(action:string,method='GET',body?:unknown,kind?:string):Promise<T> {
 const response=await fetch(`/api/portal?action=${action}&property=${property}${kind?'&kind='+kind:''}`,{method,credentials:'same-origin',headers:body?{'Content-Type':'application/json'}:undefined,body:body?JSON.stringify(body):undefined});
 const result=await response.json();
 if(!response.ok) throw new Error(result.error||'Unable to complete the request.');
 return result;
}
function field(f:Field,value='') {
 const attrs=`name="${h(f.key)}" id="f-${h(f.key)}" ${f.required?'required':''}`;
 const element=f.options?`<select ${attrs}>${f.options.map(o=>`<option ${o===value?'selected':''}>${h(o)}</option>`).join('')}</select>`:f.type==='textarea'?`<textarea ${attrs} maxlength="5000" rows="4">${h(value)}</textarea>`:`<input ${attrs} type="${f.type||'text'}" value="${h(value)}" maxlength="${f.type==='tel'?30:300}" ${f.type==='number'?'min="0.01" max="10000000" step="0.01"':''}>`;
 return `<label class="field" for="f-${h(f.key)}"><span>${h(f.label)}${f.required?' <small>*</small>':''}</span>${element}</label>`;
}
function formData(form:HTMLFormElement) { return Object.fromEntries(new FormData(form).entries()) as Record<string,string>; }
async function submit(form:HTMLFormElement,fn:()=>Promise<void>) {
 const buttons=Array.from(form.querySelectorAll<HTMLButtonElement>('button')); buttons.forEach(b=>b.disabled=true);
 try {await fn();} catch(e) {alertMessage((e as Error).message,true);} finally {buttons.forEach(b=>b.disabled=false);}
}
function authScreen() {
 const signupMode=authMode==='signup', reset=authMode==='reset', recovery=authMode==='recovery', invite=authMode==='invite';
 const heading=signupMode?'Make yourself at home.':reset?'Choose a new password.':recovery?'Let’s get you back in.':invite?'Welcome to the team.':'Good to have you here.';
 app.innerHTML=`<main class="auth"><section class="auth-story"><a class="brand" href="/"><img src="/home/logo.svg" alt="">Yadav Living</a><div><span class="eyebrow">A PLACE TO BELONG · DEOGHAR</span><h1>A thoughtful home.<br>A connected community.</h1><p>One place for the everyday details of life at Yadav Living.</p><a href="/" class="text-link">Explore our properties ↗</a></div><span class="story-footer">Society living & student accommodation</span></section><section class="auth-panel"><a href="/" class="back">← Back to home</a><div class="auth-content"><span class="eyebrow">${h(title())}</span><h2>${heading}</h2><p>${signupMode?'Create your account to access your property portal.':recovery?'We send a link to your registered email.':reset||invite?'Use at least 10 characters for your password.':'Sign in to manage your day-to-day living.'}</p><div id="message" tabindex="-1" role="status" hidden></div><form id="auth-form">${signupMode?field({key:'name',label:'Full name',required:true}):''}${!reset&&!invite?field({key:'email',label:'Email address',type:'email',required:true}):''}${!recovery?`<label class="field"><span>Password</span><input type="password" name="password" required minlength="${signupMode||reset||invite?10:1}" autocomplete="${signupMode||reset||invite?'new-password':'current-password'}"></label>`:''}${signupMode?`<label class="field"><span>Your property</span><select name="property"><option value="society" ${property==='society'?'selected':''}>Yadav Living Society</option><option value="pg" ${property==='pg'?'selected':''}>Yadav Living PG</option></select></label>`:''}<button class="primary" type="submit">${signupMode?'Create account':reset?'Save new password':invite?'Accept invitation':recovery?'Send recovery email':'Sign in'} <span>↗</span></button></form><div class="auth-links">${!reset&&!invite?`<button data-auth="${signupMode?'login':'signup'}">${signupMode?'Already have an account? Sign in':'New here? Create an account'}</button>${!signupMode?'<button data-auth="recovery">Forgot password?</button>':''}`:''}</div><p class="fine">Administrator and staff access requires a role assigned by the property owner.</p></div></section></main>`;
 app.querySelectorAll<HTMLButtonElement>('[data-auth]').forEach(b=>b.onclick=()=>{authMode=b.dataset.auth!;authScreen();});
 const form=document.querySelector<HTMLFormElement>('#auth-form')!;
 form.onsubmit=e=>{e.preventDefault();void submit(form,async()=>{
  const data=formData(form);
  if(recovery){await requestPasswordRecovery(data.email);alertMessage('If the account exists, a recovery link is on its way. Check your inbox and spam folder.');return;}
  if(reset){await updateUser({password:data.password});await start();return;}
  if(invite){await acceptInvite(inviteToken,data.password);await start();return;}
  if(signupMode){property=data.property==='pg'?'pg':'society';const account=await signup(data.email,data.password,{full_name:data.name,property});if(!account.confirmedAt){alertMessage('Check your email to confirm your account, then sign in.');form.reset();return;}}
  else await login(data.email,data.password);
  await start();
 });};
}
function profileForm(onboarding=false) {
 return `<form id="profile-form" class="edit-form">${field({key:'full_name',label:'Full name',required:true},profile?.full_name||user?.name||'')}${field({key:'phone',label:'Phone',type:'tel'},profile?.phone||'')}${field({key:'unit',label:'Flat or room'},profile?.unit||'')}${onboarding?`<label class="field"><span>Property</span><select name="property"><option value="society" ${property==='society'?'selected':''}>Yadav Living Society</option><option value="pg" ${property==='pg'?'selected':''}>Yadav Living PG</option></select></label>`:''}<button class="primary">${onboarding?'Open my portal':'Save profile'}</button></form>`;
}
function wireProfile(onboarding=false) {
 const form=document.querySelector<HTMLFormElement>('#profile-form')!;
 form.onsubmit=e=>{e.preventDefault();void submit(form,async()=>{const b=formData(form);profile=await api<Profile>('profile','POST',{...b,property:b.property||property});if(onboarding){property=profile.property;await render();}else alertMessage('Profile saved.');});};
}
function onboarding() {
 app.innerHTML=`<main class="onboarding"><a class="brand" href="/"><img src="/home/logo.svg" alt="">Yadav Living</a><section class="panel"><span class="eyebrow">WELCOME HOME</span><h1>A few details to get started.</h1><p>Choose your property to open the right portal. Contact your administrator if you need to change it later.</p><div id="message" role="status" tabindex="-1" hidden></div>${profileForm(true)}<button class="quiet" id="logout">Sign out</button></section></main>`;
 wireProfile(true);document.querySelector<HTMLButtonElement>('#logout')!.onclick=()=>void signOut();
}
async function signOut(){try{await logout();user=null;profile=null;authMode='login';authScreen();}catch(e){alertMessage((e as Error).message,true);}}
function availableModules(){return Object.entries(modules).filter(([,m])=>(!m.properties||m.properties.includes(property))&&(m.audience!=='office'||office()));}
function shell() {
 app.innerHTML=`<div class="workspace"><aside class="sidebar"><a class="brand" href="/"><img src="/home/logo.svg" alt="">Yadav Living</a><div class="property-label"><span class="eyebrow">YOUR COMMUNITY</span><strong>${property==='pg'?'Student living':'Society living'}</strong><small>Deoghar, Jharkhand</small></div><nav aria-label="Portal sections"><button data-view="dashboard">Overview</button>${availableModules().map(([key,m])=>`<button data-view="${key}">${h(m.title)}</button>`).join('')} ${property==='pg'?'<button data-view="rooms">Rooms & beds</button>':''}${manager()?`<button data-view="people">${property==='pg'?'Students':'Residents'}</button>`:''}<button data-view="assistant">Community assistant</button><button data-view="location">Location</button><button data-view="profile">My profile</button></nav><a class="home-link" href="/">← Public website</a></aside><main class="workspace-main"><header class="topbar"><button id="menu-toggle" class="quiet" aria-label="Toggle navigation" aria-expanded="false">☰</button><span>${h(title())}</span><div class="user-menu"><span>${h(profile?.full_name)} <small>${manager()?'Administrator':office()?'Staff':property==='pg'?'Student':'Resident'}</small></span>${(user?.roles||[]).includes('admin')?`<button id="switch-property" class="quiet">Switch to ${property==='pg'?'Society':'PG'}</button>`:''}<button id="logout" class="quiet">Sign out</button></div></header><div class="page"><div id="message" tabindex="-1" role="status" hidden></div><section id="content" aria-live="polite"><p>Loading…</p></section></div><footer class="portal-footer">Yadav Living · A little more connected, every day.</footer></main></div>`;
 app.querySelectorAll<HTMLButtonElement>('[data-view]').forEach(b=>{b.classList.toggle('active',b.dataset.view===view);if(b.dataset.view===view)b.setAttribute('aria-current','page');b.onclick=()=>{view=b.dataset.view!;history.replaceState(null,'',`?property=${property}#${view}`);void render();};});
 document.querySelector<HTMLButtonElement>('#logout')!.onclick=()=>void signOut();
 const toggle=document.querySelector<HTMLButtonElement>('#menu-toggle')!;
 toggle.onclick=()=>{const open=app.classList.toggle('nav-open');toggle.setAttribute('aria-expanded',String(open));};
 const switcher=document.querySelector<HTMLButtonElement>('#switch-property');if(switcher)switcher.onclick=()=>{property=property==='pg'?'society':'pg';view='dashboard';void render();};
}
function heading(label:string,description:string){return `<div class="page-heading"><span class="eyebrow">${h(title())}</span><h1>${h(label)}</h1><p>${h(description)}</p></div>`;}
async function render() {
 if(!user){authScreen();return;}if(!profile){onboarding();return;}
 const version=++renderVersion;shell();const content=document.querySelector<HTMLElement>('#content')!;
 try {
  if(view==='dashboard') await dashboard(content,version);
  else if(view==='profile'){content.innerHTML=heading('My profile','Keep your contact and accommodation details up to date.')+`<section class="panel">${profileForm()}<p class="fine">${h(user.email)} · ${h(title())}</p></section>`;wireProfile();}
  else if(view==='rooms')await roomsPage(content,version);
  else if(view==='people')await peoplePage(content,version);
  else if(view==='assistant')assistantPage(content);
  else if(view==='location')locationPage(content);
  else if(modules[view]&&availableModules().some(([key])=>key===view))await recordsPage(content,version);
  else {view='dashboard';await dashboard(content,version);}
 }catch(e){if(version===renderVersion){content.innerHTML=`<section class="panel"><h1>Unable to load this section</h1><p>${h((e as Error).message)}</p><button class="primary" id="retry">Try again</button></section>`;document.querySelector<HTMLButtonElement>('#retry')!.onclick=()=>void render();}}
}
async function dashboard(content:HTMLElement,version:number){
 const [notices,bills,complaints]=await Promise.all([api<RecordRow[]>('records','GET',undefined,'announcements'),api<RecordRow[]>('records','GET',undefined,'bills'),api<RecordRow[]>('records','GET',undefined,'complaints')]);
 if(version!==renderVersion)return;
 const due=bills.filter(b=>b.status==='Due');
 content.innerHTML=heading(`Welcome home, ${profile!.full_name.split(' ')[0]}.`,'Your community, your updates, and the details that make everyday living easier.')+`<div class="stats"><article><span>Outstanding invoices</span><strong>${money(due.reduce((sum,b)=>sum+Number(b.data.amount),0))}</strong><small>${due.length} awaiting payment</small></article><article><span>Open complaints</span><strong>${complaints.filter(c=>c.status!=='Resolved').length}</strong><small>Track progress in Complaints</small></article><article><span>Community updates</span><strong>${notices.length}</strong><small>From your property team</small></article></div><section class="welcome-banner"><div><span class="eyebrow">LIFE, A LITTLE SIMPLER</span><h2>Everything you need.<br>Right where you live.</h2><p>${property==='pg'?'Your room, mess menu, payments, and student services.':'Your bills, community news, services, and resident support.'}</p></div><div class="banner-mark" aria-hidden="true">YL<span>${property==='pg'?'STUDENT LIVING':'SOCIETY LIVING'}</span></div></section><section class="panel"><div class="section-heading"><h2>Community noticeboard</h2><button class="quiet" id="open-notices">View all ↗</button></div>${notices.length?notices.slice(0,3).map(n=>`<article class="notice"><span class="eyebrow">${date(n.created_at)}</span><h3>${h(n.data.title)}</h3><p>${h(n.data.body)}</p></article>`).join(''):'<div class="empty"><span>✦</span><h3>A fresh start.</h3><p>Announcements from your property team appear here.</p></div>'}</section>`;
 document.querySelector<HTMLButtonElement>('#open-notices')!.onclick=()=>{view='announcements';void render();};
}
async function recordsPage(content:HTMLElement,version:number){
 const kind=view,m=modules[kind];
 const [records,people]=await Promise.all([api<RecordRow[]>('records','GET',undefined,kind),kind==='bills'&&manager()?api<Person[]>('people'):Promise.resolve([] as Person[])]);
 if(version!==renderVersion)return;
 const canAdd=canCreate(kind,user?.roles||[],property);
 const owner=kind==='bills'?`<label class="field"><span>Account</span><select name="owner_id" required><option value="">Choose ${property==='pg'?'student':'resident'}</option>${people.map(p=>`<option value="${h(p.user_id)}">${h(p.full_name)} — ${h(p.unit||p.email)}</option>`).join('')}</select></label>`:'';
 const create=canAdd?`<details class="panel compose"><summary>Add ${kind==='kyc'?'KYC submission':kind==='bills'?'invoice':kind==='menu'?'menu entry':'record'} <span>+</span></summary><form id="record-form" class="edit-form">${owner}${m.fields.map(f=>field(f)).join('')}<button class="primary">Save ${kind==='bills'?'invoice':'record'}</button></form>${kind==='kyc'?'<p class="fine">Save your details, then attach a supporting PDF, PNG, or JPEG to the pending submission below.</p>':''}</details>`:'';
 content.innerHTML=heading(m.title,m.description)+create+`<div class="records">${records.length?records.map(r=>recordCard(r,kind)).join(''):`<section class="panel empty"><span>✦</span><h2>No ${h(m.title.toLowerCase())} yet</h2><p>${canAdd?'Use the form above to add your first record.':'Records appear here when your property team adds them.'}</p></section>`}</div>`;
 const form=document.querySelector<HTMLFormElement>('#record-form');if(form)form.onsubmit=e=>{e.preventDefault();void submit(form,async()=>{const values=formData(form);await api('records','POST',{data:values,owner_id:values.owner_id},kind);await render();alertMessage('Record saved.');});};
 content.querySelectorAll<HTMLFormElement>('.status-form').forEach(form=>form.onsubmit=e=>{e.preventDefault();void submit(form,async()=>{await api('records','PATCH',{...formData(form),id:form.dataset.id},kind);await render();alertMessage('Record updated.');});});
 content.querySelectorAll<HTMLButtonElement>('[data-delete]').forEach(button=>button.onclick=()=>{if(!confirm('Delete this published record?'))return;button.disabled=true;void api('records','DELETE',{id:button.dataset.delete},kind).then(()=>render()).catch(e=>{button.disabled=false;alertMessage(e.message,true);});});
 content.querySelectorAll<HTMLFormElement>('.upload-form').forEach(form=>form.onsubmit=e=>{e.preventDefault();void submit(form,async()=>{const response=await fetch('/api/documents?id='+form.dataset.id,{method:'POST',body:new FormData(form),credentials:'same-origin'});const result=await response.json();if(!response.ok)throw new Error(result.error);await render();alertMessage('Document uploaded securely.');});});
 content.querySelectorAll<HTMLButtonElement>('[data-print]').forEach(button=>button.onclick=()=>{content.querySelectorAll('.record').forEach(el=>el.classList.toggle('print-hide',(el as HTMLElement).dataset.id!==button.dataset.print));window.print();content.querySelectorAll('.record').forEach(el=>el.classList.remove('print-hide'));});
}
function recordCard(r:RecordRow,kind:string){
 const m=modules[kind],editable=manager()||(kind==='visitors'&&office());
 return `<article class="panel record" data-id="${h(r.id)}"><div class="record-top"><span class="eyebrow">${h(r.owner_name)} · ${date(r.created_at)}</span><span class="badge ${['Paid','Approved','Resolved','Checked out'].includes(r.status)?'positive':''}">${h(r.status)}</span></div><h2>${h(r.data.title)}</h2><dl>${m.fields.filter(f=>f.key!=='title'&&r.data[f.key]).map(f=>`<div><dt>${h(f.label)}</dt><dd>${f.key==='amount'?money(r.data[f.key]):h(r.data[f.key])}</dd></div>`).join('')}</dl>${r.response?`<div class="response"><strong>Property team response</strong><p>${h(r.response)}</p></div>`:''}${kind==='kyc'?(r.document_id?`<a class="text-link" href="/api/documents?id=${h(r.id)}">Download supporting document ↓</a>`:r.owner_id===user?.id&&r.status==='Pending'?`<form class="upload-form" data-id="${h(r.id)}"><label class="field"><span>Supporting document (PDF, PNG, JPEG · max 4 MB)</span><input type="file" name="document" accept="application/pdf,image/png,image/jpeg" required></label><button class="secondary">Upload document</button></form>`:'<p class="fine">No supporting document attached.</p>'):''}${editable?`<form class="status-form" data-id="${h(r.id)}"><label class="field"><span>Status</span><select name="status">${statuses(kind).map(s=>`<option ${s===r.status?'selected':''}>${s}</option>`).join('')}</select></label><label class="field"><span>${kind==='bills'?'Payment reference / note':'Response / note'}</span><input name="response" maxlength="2000" value="${h(r.response)}"></label><button class="secondary">Update</button></form>`:''}<div class="record-actions">${kind==='bills'?`<button class="quiet" data-print="${h(r.id)}">Print invoice / receipt</button>`:''}${manager()&&m.audience==='public'?`<button class="danger" data-delete="${h(r.id)}">Delete record</button>`:''}</div></article>`;
}
async function peoplePage(content:HTMLElement,version:number){
 const people=await api<Person[]>('people');if(version!==renderVersion)return;
 content.innerHTML=heading(property==='pg'?'Student directory':'Resident directory','Registered accounts in this property. Private account details are visible only to administrators.')+`<section class="panel"><label class="field"><span>Search name, email, or accommodation</span><input type="search" id="people-search" placeholder="Start typing…"></label><div class="table-scroll"><table><thead><tr><th>Name</th><th>Email</th><th>Phone</th><th>Accommodation</th></tr></thead><tbody>${people.map(p=>`<tr><td>${h(p.full_name)}</td><td>${h(p.email)}</td><td>${h(p.phone||'—')}</td><td>${h(p.room_name?`${p.room_name} · Bed ${p.bed}`:p.unit||'Not assigned')}</td></tr>`).join('')}</tbody></table></div>${people.length?'':'<p>No registered accounts yet.</p>'}</section>`;
 document.querySelector<HTMLInputElement>('#people-search')!.oninput=e=>{const q=(e.target as HTMLInputElement).value.toLowerCase();content.querySelectorAll<HTMLTableRowElement>('tbody tr').forEach(row=>row.hidden=!row.textContent?.toLowerCase().includes(q));};
}
async function roomsPage(content:HTMLElement,version:number){
 const [rooms,people]=await Promise.all([api<Room[]>('rooms'),manager()?api<Person[]>('people'):Promise.resolve([] as Person[])]);if(version!==renderVersion)return;
 content.innerHTML=heading('Rooms & beds',manager()?'Manage room capacity and assign each student a unique bed.':'Your room assignment and monthly accommodation rent.')+(manager()?`<details class="panel compose"><summary>Add a room <span>+</span></summary><form id="room-form" class="edit-form">${field({key:'name',label:'Room name',required:true})}<label class="field"><span>Number of beds (1–20)</span><input name="capacity" type="number" min="1" max="20" required></label><label class="field"><span>Monthly rent (INR)</span><input name="monthly_rent" type="number" min="0" step="0.01" required></label><button class="primary">Add room</button></form></details><section class="panel"><h2>Assign or move a student</h2><form id="assignment-form" class="edit-form"><label class="field"><span>Student</span><select name="user_id" required><option value="">Choose a student</option>${people.map(p=>`<option value="${h(p.user_id)}">${h(p.full_name)}${p.room_name?' — '+h(p.room_name)+' / Bed '+p.bed:''}</option>`).join('')}</select></label><label class="field"><span>Room</span><select name="room_id" id="room-select" required><option value="">Choose a room</option>${rooms.map(r=>`<option value="${h(r.id)}">${h(r.name)} (${r.occupied}/${r.capacity} occupied)</option>`).join('')}</select></label><label class="field"><span>Bed number</span><input name="bed" id="bed-input" type="number" min="1" max="20" required></label><div class="form-actions"><button class="primary">Save assignment</button><button class="danger" type="button" id="release-bed">Release selected student's bed</button></div></form></section>`:'')+`<div class="room-grid">${rooms.length?rooms.map(r=>`<article class="panel"><span class="eyebrow">${manager()?`${r.occupied} / ${r.capacity} BEDS OCCUPIED`:'YOUR HOME'}</span><h2>${h(r.name)}</h2><p class="room-rent">${money(r.monthly_rent)}<small> / month</small></p>${r.bed?`<p>Bed ${r.bed}</p>`:''}</article>`).join(''):'<section class="panel empty"><h2>No room assigned yet</h2><p>Your property administrator manages room assignments.</p></section>'}</div>`;
 const form=document.querySelector<HTMLFormElement>('#room-form');if(form)form.onsubmit=e=>{e.preventDefault();void submit(form,async()=>{await api('rooms','POST',formData(form));await render();alertMessage('Room added.');});};
 const assignment=document.querySelector<HTMLFormElement>('#assignment-form');if(assignment){assignment.onsubmit=e=>{e.preventDefault();void submit(assignment,async()=>{await api('rooms','PUT',formData(assignment));await render();alertMessage('Room assignment saved.');});};document.querySelector<HTMLButtonElement>('#release-bed')!.onclick=()=>{const values=formData(assignment);if(!values.user_id){alertMessage('Choose a student first.',true);return;}void submit(assignment,async()=>{await api('rooms','DELETE',{user_id:values.user_id});await render();alertMessage('Bed released.');});};document.querySelector<HTMLSelectElement>('#room-select')!.onchange=e=>{const r=rooms.find(r=>r.id===(e.target as HTMLSelectElement).value);document.querySelector<HTMLInputElement>('#bed-input')!.max=String(r?.capacity||20);};}
}
function assistantPage(content:HTMLElement){
 content.innerHTML=heading('Your community guide','Ask about property locations and portal workflows. The guide does not access private account records.')+`<section class="panel assistant"><span class="guide-symbol" aria-hidden="true">✦</span><h2>A little guidance goes a long way.</h2><p>Try “How do I submit KYC?” or “Where is the property?”</p><form id="assistant-form"><label class="field"><span>Your question</span><textarea name="question" maxlength="800" rows="3" required placeholder="What would you like to know?"></textarea></label><p class="fine">Do not include personal, identity, contact, or payment information. Your question is sent to an AI provider.</p><button class="primary">Ask the guide ↗</button></form><div id="answer" aria-live="polite"></div></section>`;
 const form=document.querySelector<HTMLFormElement>('#assistant-form')!;form.onsubmit=e=>{e.preventDefault();void submit(form,async()=>{const answer=document.querySelector<HTMLElement>('#answer')!;answer.textContent='Finding guidance…';const response=await fetch('/api/assistant',{method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify({...formData(form),property}),credentials:'same-origin'});const result=await response.json();if(!response.ok){answer.textContent='';throw new Error(result.error);}answer.innerHTML=`<div class="response"><p>${h(result.answer)}</p>${result.sources.length?`<strong>From the property guide</strong><ul>${result.sources.map((s:{title:string;url:string})=>`<li><a href="${h(s.url)}">${h(s.title)}</a></li>`).join('')}</ul>`:''}</div>`;});};
}
function locationPage(content:HTMLElement){
 const address=property==='pg'?'Hirna Near Tv relay Center Near AS College Road Dewan baba Deoghar, Jharkhand 814112':'AS College Road, Satsang Nagar Near Atal Smriti Park Deoghar, Jharkhand 814112';
 content.innerHTML=heading('Find your way home','Our community in Deoghar, Jharkhand.')+`<section class="panel"><span class="eyebrow">${h(title())}</span><h2>${h(address)}</h2><a class="primary link-button" target="_blank" rel="noopener noreferrer" href="https://www.google.com/maps/search/?api=1&query=${encodeURIComponent(address)}">Open in Google Maps ↗</a></section>`;
}
async function start(){
 user=await getUser();if(!user){authScreen();return;}
 try{const result=await api<{profile:Profile|null}>('profile');profile=result.profile;if(profile&&!manager()&&!((user.roles||[]).includes('staff')&&property==='society'))property=profile.property;await render();}
 catch(e){app.innerHTML=`<main class="onboarding"><section class="panel"><h1>Unable to open your portal</h1><p>${h((e as Error).message)}</p><button id="retry" class="primary">Try again</button><button id="logout" class="quiet">Sign out</button></section></main>`;document.querySelector<HTMLButtonElement>('#retry')!.onclick=()=>void start();document.querySelector<HTMLButtonElement>('#logout')!.onclick=()=>void signOut();}
}
async function init(){
 try{
  const callback=await handleAuthCallback();
  if(callback?.type==='recovery'){authMode='reset';authScreen();return;}
  if(callback?.type==='invite'){inviteToken=callback.token || '';if(!inviteToken)throw new Error('The invitation link is invalid.');authMode='invite';authScreen();return;}
  if(location.hash&&!location.hash.includes('='))view=location.hash.slice(1)||'dashboard';
  await start();
 }catch(e){authScreen();alertMessage((e as Error).message,true);}
}
void init();
