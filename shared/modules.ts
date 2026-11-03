export type Module = {label:string; fields:Record<string,string>; access:'public'|'private'|'staff'; create:'admin'|'self'|'staff'; statuses:string[]};
export const modules:Record<string,Module>={
 announcements:{label:'Announcements',fields:{title:'Title',content:'Notice'},access:'public',create:'admin',statuses:['published','archived']},
 events:{label:'Events',fields:{title:'Event',date:'Date',location:'Location',content:'Details'},access:'public',create:'admin',statuses:['scheduled','completed','cancelled']},
 services:{label:'Home services',fields:{title:'Service',contact:'Contact',content:'Details'},access:'public',create:'admin',statuses:['available','unavailable']},
 neighbourhood:{label:'Neighbourhood',fields:{title:'Place',location:'Address',content:'Details'},access:'public',create:'admin',statuses:['published','archived']},
 complaints:{label:'Complaints',fields:{title:'Subject',content:'Describe the issue'},access:'private',create:'self',statuses:['pending','in_progress','resolved']},
 vacate:{label:'Move-out notices',fields:{date:'Planned move-out date',content:'Reason / notes'},access:'private',create:'self',statuses:['pending','approved','declined']},
 bills:{label:'Bills & receipts',fields:{title:'Invoice number',date:'Due date',amount:'Amount (INR)',content:'Charges and instructions'},access:'private',create:'admin',statuses:['pending','paid','cancelled']},
 payments:{label:'Payment records',fields:{title:'Receipt / reference',date:'Payment date',amount:'Amount (INR)',content:'Payment details'},access:'private',create:'admin',statuses:['recorded','void']},
 rooms:{label:'Rooms & beds',fields:{title:'Unique room / bed label',floor:'Floor',amount:'Monthly fee (INR)',content:'Room details'},access:'public',create:'admin',statuses:['vacant','maintenance']},
 mess:{label:'Mess menu',fields:{title:'Day / meal',content:'Menu'},access:'public',create:'admin',statuses:['published','archived']},
 visitors:{label:'Visitor register',fields:{title:'Visitor name',destination:'Flat / room',purpose:'Purpose',phone:'Phone'},access:'staff',create:'staff',statuses:['checked_in','checked_out']},
 kyc:{label:'KYC verification',fields:{title:'Legal name',address:'Address',residency:'Owner / tenant / student',content:'Additional information'},access:'private',create:'self',statuses:['pending','approved','rejected']}
};
export function privileged(roles:string[]){return roles.includes('admin');}
export function canCreate(kind:string,roles:string[]){const m=modules[kind];return !!m&&(privileged(roles)||m.create==='self'||(m.create==='staff'&&roles.includes('staff')));}
export function validateData(kind:string,input:unknown){
 const m=modules[kind];if(!m||!input||typeof input!=='object')throw Object.assign(new Error('Invalid record.'),{status:400});
 const result:Record<string,string>={};
 for(const key of Object.keys(m.fields)){const v=(input as Record<string,unknown>)[key];if(typeof v!=='string'||!v.trim()||v.length>6000)throw Object.assign(new Error(`Please complete ${m.fields[key]}.`),{status:400});result[key]=v.trim();}
 if(result.amount&&(!/^\d+(\.\d{1,2})?$/.test(result.amount)||Number(result.amount)>10000000))throw Object.assign(new Error('Enter a valid amount.'),{status:400});
 if(result.date&&(!/^\d{4}-\d{2}-\d{2}$/.test(result.date)||!Number.isFinite(Date.parse(result.date))||new Date(result.date).toISOString().slice(0,10)!==result.date))throw Object.assign(new Error('Enter a valid date.'),{status:400});
 return result;
}
