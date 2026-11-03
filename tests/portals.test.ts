import test from 'node:test';
import assert from 'node:assert/strict';
import {canCreate,validateData,privileged,modules} from '../shared/modules.ts';
import api from '../netlify/functions/api.mts';
import document from '../netlify/functions/document.mts';
test('signups cannot gain administrator powers from a property choice',()=>{
 assert.equal(privileged([]),false);
 for(const kind of ['bills','payments','rooms','announcements'])assert.equal(canCreate(kind,[]),false);
 assert.equal(canCreate('kyc',[]),true);assert.equal(canCreate('complaints',[]),true);
});
test('gate staff cannot issue bills or publish announcements',()=>{
 assert.equal(canCreate('visitors',['staff']),true);
 assert.equal(canCreate('bills',['staff']),false);
 assert.equal(canCreate('announcements',['staff']),false);
});
test('administrators can manage every migrated module',()=>{
 for(const kind of Object.keys(modules))assert.equal(canCreate(kind,['admin']),true);
});
test('billing validation rejects negative, invalid and oversized amounts',()=>{
 for(const amount of ['-1','NaN','10000001','1.123'])assert.throws(()=>validateData('bills',{title:'Invoice',date:'2026-11-03',amount,content:'Monthly charges'}));
 assert.equal(validateData('bills',{title:'Invoice',date:'2026-11-03',amount:'4200.50',content:'Monthly charges'}).amount,'4200.50');
});
test('record inputs cannot overwrite private storage pointers or ownership',()=>{
 const data=validateData('kyc',{title:'Resident',address:'Flat',residency:'Tenant',content:'Details',fileKey:'someone-else/file',ownerId:'another-user'});
 assert.equal(data.fileKey,undefined);assert.equal(data.ownerId,undefined);
});
test('required fields fail with a validation response status',()=>{
 assert.throws(()=>validateData('complaints',{title:'Issue',content:''}),{status:400});
});
test('private API denies anonymous requests before querying the database',async()=>{
 const r=await api(new Request('https://example.net/api/records/bills'));assert.equal(r.status,401);
});
test('private documents are unavailable to anonymous users',async()=>{
 const r=await document(new Request('https://example.net/.netlify/functions/document?id=00000000-0000-0000-0000-000000000000'));assert.equal(r.status,401);
});
test('cross-site mutations are rejected before any data mutation',async()=>{
 const r=await api(new Request('https://example.net/api/records/bills',{method:'POST',headers:{Origin:'https://untrusted.example','Content-Type':'application/json'},body:'{}'}));assert.equal(r.status,403);
});
