import {test,expect} from '@playwright/test';
test('public homepage and portal navigation load without application errors',async({page})=>{
 const errors:string[]=[];page.on('pageerror',e=>errors.push(e.message));
 await page.goto('http://localhost:8889/');
 await expect(page).toHaveTitle(/Yadav Living/);
 await page.getByRole('link',{name:'Resident Portal',exact:true}).click();
 await expect(page.getByRole('heading',{name:'Welcome home'})).toBeVisible();
 await page.getByRole('button',{name:'Create account',exact:true}).click();
 await expect(page.getByLabel('Your full name')).toBeVisible();
 await page.getByRole('button',{name:'Student PG',exact:true}).click();
 await expect(page.getByText('Student accommodation',{exact:true})).toBeVisible();
 await page.getByRole('button',{name:'Back to sign in'}).click();
 await page.getByRole('button',{name:'Forgot password'}).click();
 await expect(page.getByRole('heading',{name:'Reset your password'})).toBeVisible();
 expect(errors).toEqual([]);
});
test('mobile homepage and sign in fit the viewport',async({page})=>{
 await page.setViewportSize({width:390,height:844});
 await page.goto('http://localhost:8889/');
 await expect(page.getByRole('link',{name:'Student Sign Up',exact:true})).toBeVisible();
 await page.getByRole('link',{name:'Student Sign Up',exact:true}).click();
 await expect(page.getByRole('heading',{name:'Create your account'})).toBeVisible();
 expect(await page.evaluate(()=>document.documentElement.scrollWidth<=window.innerWidth)).toBeTruthy();
});
test('legacy portal and public page links redirect to working pages',async({page})=>{
 await page.goto('http://localhost:8889/HMS-2/home/home.php');await expect(page).toHaveURL('http://localhost:8889/');
 await page.goto('http://localhost:8889/HMS-2/pg/login.php');
 await expect(page.getByText('Student accommodation',{exact:true})).toBeVisible();
 await expect(page.getByRole('heading',{name:'Welcome home'})).toBeVisible();
});
