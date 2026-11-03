import { test, expect, type Page } from '@playwright/test';

async function signedIn(page:Page, property='society',roles:string[]=[]){
 const user={id:'test-account',email:'reviewer@example.invalid',name:'Test Resident',roles};
 await page.route('**/node_modules/.vite/deps/@netlify_identity.js*',route=>route.fulfill({contentType:'text/javascript',body:`export const getUser=async()=>(${JSON.stringify(user)});export const handleAuthCallback=async()=>null;export const logout=async()=>{};export const login=async()=>{};export const signup=async()=>{};export const requestPasswordRecovery=async()=>{};export const updateUser=async()=>{};export const acceptInvite=async()=>{};`}));
 await page.route('**/api/portal?**',async route=>{
  const url=new URL(route.request().url()), action=url.searchParams.get('action');
  const data=action==='profile'?{user,profile:{user_id:user.id,full_name:'Test Resident',email:user.email,property,unit:'A-101',phone:''}}:[];
  await route.fulfill({json:data});
 });
}
test('public homepage keeps property cards and routes every local asset',async({page})=>{
 const missing:string[]=[],errors:string[]=[];
 page.on('response',r=>{if(r.url().startsWith('http://localhost:8889')&&r.status()>=400)missing.push(r.url());});
 page.on('pageerror',e=>errors.push(e.message));
 await page.goto('/');
 await expect(page).toHaveTitle(/Yadav Living/);
 await expect(page.locator('#card-society')).toBeVisible();
 await expect(page.locator('#card-pg')).toBeVisible();
 await page.getByRole('link',{name:'Resident Portal',exact:true}).click();
 await expect(page.getByRole('heading',{name:'Good to have you here.'})).toBeVisible();
 expect(missing).toEqual([]);expect(errors).toEqual([]);
});
test('student signup and recovery screens work',async({page})=>{
 await page.goto('/portal/?property=pg&mode=signup');
 await expect(page.getByRole('heading',{name:'Make yourself at home.'})).toBeVisible();
 await expect(page.locator('select[name=property]')).toHaveValue('pg');
 await page.getByRole('button',{name:'Already have an account? Sign in'}).click();
 await page.getByRole('button',{name:'Forgot password?'}).click();
 await expect(page.getByRole('button',{name:'Send recovery email'})).toBeVisible();
});
test('private APIs reject unauthenticated requests',async({request})=>{
 for(const path of ['/api/portal','/api/documents?id=00000000-0000-0000-0000-000000000000','/api/assistant'])expect((await request.get(path)).status()).toBe(401);
});
test('legacy PHP URLs redirect into matching portal sections',async({request})=>{
 const r=await request.get('/HMS-2/pg/student/complaints.php',{maxRedirects:0});
 expect(r.status()).toBe(302);expect(r.headers().location).toContain('property=pg#complaints');
 const signup=await request.get('/HMS-2/pg/register.php',{maxRedirects:0});expect(signup.headers().location).toContain('mode=signup');
});
test('resident dashboard opens and complaints save through the API',async({page})=>{
 await signedIn(page);
 let saved:unknown;
 await page.route('**/api/portal?**kind=complaints',async route=>{
  if(route.request().method()==='POST'){saved=route.request().postDataJSON();await route.fulfill({json:{id:'new-complaint'},status:201});}
  else await route.fulfill({json:[]});
 });
 await page.goto('/portal/');
 await expect(page.getByRole('heading',{name:'Welcome home, Test.'})).toBeVisible();
 await expect(page.getByRole('button',{name:'Visitor registry',exact:true})).toHaveCount(0);
 await page.getByRole('button',{name:'Complaints',exact:true}).click();
 await page.locator('summary').click();
 await page.getByLabel('Subject').fill('Water tap');
 await page.getByLabel('Describe the issue').fill('Kitchen tap needs repair.');
 await page.getByRole('button',{name:'Save record',exact:true}).click();
 await expect(page.getByRole('status').filter({hasText:'Record saved.'})).toBeVisible();
 expect(saved).toMatchObject({data:{title:'Water tap',body:'Kitchen tap needs repair.'}});
});
test('admin can issue invoices to a selected resident',async({page})=>{
 await signedIn(page,'society',['admin']);let saved:unknown;
 await page.route('**/api/portal?action=people*',r=>r.fulfill({json:[{user_id:'resident-2',full_name:'Example Resident',email:'example@example.invalid',unit:'B-2'}]}));
 await page.route('**/api/portal?**kind=bills',async route=>{if(route.request().method()==='POST'){saved=route.request().postDataJSON();await route.fulfill({json:{id:'new-invoice'},status:201});}else await route.fulfill({json:[]});});
 await page.goto('/portal/#bills');
 await page.locator('summary').click();
 await page.getByLabel('Account',{exact:true}).selectOption('resident-2');
 await page.getByLabel('Invoice description').fill('November maintenance');
 await page.getByLabel('Amount (INR)').fill('1250');
 await page.getByLabel('Due date').fill('2026-11-10');
 await page.getByRole('button',{name:'Save invoice'}).click();
 await expect(page.getByRole('status').filter({hasText:'Record saved.'})).toBeVisible();
 expect(saved).toMatchObject({owner_id:'resident-2',data:{amount:'1250'}});
});
test('PG portal shows dining and room sections without society-only services',async({page})=>{
 await signedIn(page,'pg');await page.goto('/portal/?property=pg');
 await expect(page.getByRole('button',{name:'Mess menu',exact:true})).toBeVisible();
 await expect(page.getByRole('button',{name:'Home services',exact:true})).toHaveCount(0);
 await page.getByRole('button',{name:'Rooms & beds',exact:true}).click();
 await expect(page.getByRole('heading',{name:'No room assigned yet'})).toBeVisible();
});
test('mobile portal navigation works without horizontal overflow',async({page})=>{
 await page.setViewportSize({width:390,height:844});await signedIn(page);await page.goto('/portal/');
 await expect(page.getByRole('heading',{name:'Welcome home, Test.'})).toBeVisible();
 await page.getByRole('button',{name:'Toggle navigation'}).click();
 await page.getByRole('button',{name:'My profile',exact:true}).click();
 await expect(page.getByRole('heading',{name:'My profile'})).toBeVisible();
 expect(await page.evaluate(()=>document.documentElement.scrollWidth<=window.innerWidth)).toBe(true);
});
