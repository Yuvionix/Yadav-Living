export type Property = 'society' | 'pg';
export type Field = { key: string; label: string; type?: string; required?: boolean; options?: string[] };
export const modules: Record<string, { title: string; description: string; audience: 'public' | 'private' | 'office'; properties?: Property[]; fields: Field[] }> = {
  announcements: { title: 'Announcements', description: 'The latest updates from your property team.', audience: 'public', fields: [{key:'title',label:'Title',required:true},{key:'body',label:'Announcement',type:'textarea',required:true}] },
  events: { title: 'Events', description: 'Keep up with life in your community.', audience: 'public', fields: [{key:'title',label:'Event',required:true},{key:'date',label:'Date',type:'date',required:true},{key:'location',label:'Location',required:true},{key:'body',label:'Details',type:'textarea'}] },
  services: { title: 'Home services', description: 'Property contacts for repairs and everyday help.', audience: 'public', properties:['society'], fields: [{key:'title',label:'Service',required:true},{key:'contact',label:'Contact',required:true},{key:'body',label:'Details',type:'textarea'}] },
  neighbourhood: { title: 'Neighbourhood', description: 'Useful places around your home.', audience: 'public', fields: [{key:'title',label:'Place',required:true},{key:'location',label:'Address',required:true},{key:'body',label:'Details',type:'textarea'}] },
  menu: { title: 'Mess menu', description: 'Your weekly dining plan.', audience: 'public', properties:['pg'], fields: [{key:'title',label:'Day',required:true,options:['Monday','Tuesday','Wednesday','Thursday','Friday','Saturday','Sunday']},{key:'breakfast',label:'Breakfast',required:true},{key:'lunch',label:'Lunch',required:true},{key:'dinner',label:'Dinner',required:true}] },
  complaints: { title: 'Complaints', description: 'Report an issue and follow its progress.', audience: 'private', fields: [{key:'title',label:'Subject',required:true},{key:'category',label:'Category',required:true,options:['Maintenance','Electricity','Water','Food','Other']},{key:'body',label:'Describe the issue',type:'textarea',required:true}] },
  kyc: { title: 'KYC documents', description: 'Submit your accommodation details and a supporting document for review.', audience: 'private', fields: [{key:'title',label:'Full name',required:true},{key:'phone',label:'Phone',type:'tel',required:true},{key:'address',label:'Permanent address',type:'textarea',required:true},{key:'unit',label:'Flat or room',required:true},{key:'residency',label:'Residency type',required:true,options:['Owner','Tenant','Student']}] },
  vacate: { title: 'Move-out requests', description: 'Let your property team know your planned departure.', audience: 'private', properties:['pg'], fields: [{key:'title',label:'Subject',required:true},{key:'date',label:'Departure date',type:'date',required:true},{key:'body',label:'Reason and forwarding details',type:'textarea',required:true}] },
  visitors: { title: 'Visitor registry', description: 'Record guest arrivals and departures.', audience: 'office', properties:['society'], fields: [{key:'title',label:'Visitor name',required:true},{key:'unit',label:'Flat to visit',required:true},{key:'phone',label:'Phone',type:'tel'},{key:'body',label:'Purpose of visit',required:true}] },
  bills: { title: 'Bills & payments', description: 'Review invoices, due dates, and payment records. Payments are recorded by the property team.', audience: 'private', fields: [{key:'title',label:'Invoice description',required:true},{key:'amount',label:'Amount (INR)',type:'number',required:true},{key:'date',label:'Due date',type:'date',required:true},{key:'body',label:'Payment instructions',type:'textarea'}] }
};
export function isManager(roles: string[], property: Property) { return roles.includes('admin') || (property === 'pg' && roles.includes('warden')); }
export function isOffice(roles: string[], property: Property) { return isManager(roles, property) || (property === 'society' && roles.includes('staff')); }
export function canCreate(kind: string, roles: string[], property: Property) {
 const m = modules[kind];
 if (!m || (m.properties && !m.properties.includes(property))) return false;
 return m.audience === 'public' || kind === 'bills' ? isManager(roles, property) : m.audience === 'office' ? isOffice(roles, property) : true;
}
export function canRead(kind: string, roles: string[], property: Property, userId: string, ownerId: string) {
 const m = modules[kind];
 if (!m || (m.properties && !m.properties.includes(property))) return false;
 return m.audience === 'public' || isManager(roles, property) || (m.audience === 'office' ? isOffice(roles, property) : ownerId === userId);
}
export function cleanFields(kind: string, input: unknown) {
 const m = modules[kind];
 if (!m || !input || typeof input !== 'object' || Array.isArray(input)) throw new Error('Invalid record.');
 const source = input as Record<string, unknown>, data: Record<string,string> = {};
 for (const f of m.fields) {
  const raw = source[f.key];
  const value = typeof raw === 'string' ? raw.trim() : '';
  if ((f.required && !value) || value.length > (f.type === 'textarea' ? 5000 : 300)) throw new Error(`Check ${f.label.toLowerCase()}.`);
  if (value && f.options && !f.options.includes(value)) throw new Error(`Choose a valid ${f.label.toLowerCase()}.`);
  if (value && f.type === 'date' && (!/^\d{4}-\d{2}-\d{2}$/.test(value) || new Date(value).toISOString().slice(0,10) !== value)) throw new Error('Enter a valid date.');
  if (value && f.type === 'number' && (!/^\d+(\.\d{1,2})?$/.test(value) || Number(value) <= 0 || Number(value) > 10000000)) throw new Error('Enter a valid positive amount.');
  data[f.key] = value;
 }
 return data;
}
export function statuses(kind: string) {
 return kind === 'complaints' ? ['Open','In progress','Resolved'] : kind === 'kyc' ? ['Pending','Approved','Rejected'] : kind === 'vacate' ? ['Pending','Approved','Rejected'] : kind === 'visitors' ? ['Checked in','Checked out'] : kind === 'bills' ? ['Due','Paid'] : ['Published'];
}
export function validProperty(value: unknown): Property { if (value !== 'pg' && value !== 'society') throw new Error('Choose a property.'); return value; }
