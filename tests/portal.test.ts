import test from 'node:test';
import assert from 'node:assert/strict';
import {canRead,canCreate,isManager,cleanFields,validProperty,statuses} from '../shared/model.ts';
import {authenticate,HttpError} from '../netlify/lib/auth.ts';

test('members can only see their own private records',()=>{
 for(const kind of ['bills','kyc','complaints','vacate']){
  const property=kind==='vacate'?'pg':'society';
  assert.equal(canRead(kind,[],property,'alice','alice'),true);
  assert.equal(canRead(kind,[],property,'alice','bob'),false);
 }
});
test('staff access is confined to society visitor operations',()=>{
 assert.equal(canRead('visitors',['staff'],'society','staff','resident'),true);
 assert.equal(canCreate('visitors',['staff'],'society'),true);
 assert.equal(canRead('kyc',['staff'],'society','staff','resident'),false);
 assert.equal(canCreate('bills',['staff'],'society'),false);
 assert.equal(canCreate('visitors',['staff'],'pg'),false);
});
test('warden permissions never grant society administration',()=>{
 assert.equal(isManager(['warden'],'pg'),true);
 assert.equal(isManager(['warden'],'society'),false);
 assert.equal(canRead('kyc',['warden'],'society','warden','resident'),false);
 assert.equal(canCreate('announcements',['warden'],'society'),false);
});
test('public accounts cannot issue invoices or publish notices',()=>{
 for(const kind of ['announcements','events','bills','menu','services'])assert.equal(canCreate(kind,[],'society'),false);
 assert.equal(canCreate('complaints',[],'pg'),true);
 assert.equal(canCreate('announcements',['admin'],'pg'),true);
});
test('input validation rejects invalid amounts and strips privileged fields',()=>{
 const base={title:' Maintenance ',amount:'100.50',date:'2026-11-03',body:'Pay at reception',owner_id:'someone',status:'Paid',roles:['admin']};
 assert.deepEqual(cleanFields('bills',base),{title:'Maintenance',amount:'100.50',date:'2026-11-03',body:'Pay at reception'});
 for(const amount of ['-1','0','Infinity','1e6','10000001','2.345'])assert.throws(()=>cleanFields('bills',{...base,amount}));
 for(const date of ['2026-02-30','tomorrow','2026-13-01'])assert.throws(()=>cleanFields('bills',{...base,date}));
});
test('required fields, enums, size limits, and property values are validated',()=>{
 assert.throws(()=>cleanFields('complaints',{title:'',category:'Other',body:'Test'}));
 assert.throws(()=>cleanFields('complaints',{title:'Test',category:'Admin',body:'Test'}));
 assert.throws(()=>cleanFields('complaints',{title:'Test',category:'Other',body:'x'.repeat(5001)}));
 assert.throws(()=>validProperty('admin'));
 assert.deepEqual(statuses('bills'),['Due','Paid']);
});
test('cross-origin writes and requests without an origin fail before authentication',async()=>{
 for(const origin of [null,'https://untrusted.example']){
  const headers=new Headers();if(origin)headers.set('origin',origin);
  await assert.rejects(()=>authenticate(new Request('https://yadavliving.netlify.app/api/portal',{method:'POST',headers})),(e:unknown)=>e instanceof HttpError&&e.status===403);
 }
});
