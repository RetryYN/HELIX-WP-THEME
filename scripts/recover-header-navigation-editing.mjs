import fs from 'node:fs';import path from 'node:path';import os from 'node:os';import {execFileSync} from 'node:child_process';
const file=path.join(os.tmpdir(),'helix-header-editing-restore.json');
if(!fs.existsSync(file)){console.log('No pending header editing recovery.');process.exit(0);}
const state=JSON.parse(fs.readFileSync(file));
const php=code=>execFileSync('docker',['exec','helix-content-wp','php','-r',`require '/var/www/html/wp-load.php'; ${code}`],{encoding:'utf8'}).trim();
if(php('echo get_option("blogname");')!=='HELIX Content Lab')throw Error('dedicated lab required');
const encoded=Buffer.from(JSON.stringify(state)).toString('base64');
php(`$state=json_decode(base64_decode('${encoded}'),true);$variants=['header','header-nav','header-cta','header-announce','header-center','header-two-rows','header-overlay','header-tel','header-band'];
foreach(get_posts(['post_type'=>'wp_template_part','post_status'=>'any','numberposts'=>-1]) as $p){
 if(!in_array($p->post_name,$variants,true))continue;
 $old=null;foreach($state['originals'] as $candidate)if($candidate['ID']===$p->ID)$old=$candidate;
 if($old)wp_update_post(wp_slash($old));
 elseif(in_array($p->ID,$state['createdPartIds']??[],true)||strpos($p->post_content,'検証ヘッダー')!==false)wp_delete_post($p->ID,true);
 else throw new Exception('Unowned template part: recovery stopped');
}
foreach($state['created']??[] as $id){$p=get_post($id);if(!$p)continue;if(strpos($p->post_name,'header-editor-')!==0&&$p->post_name!=='footer-header-editor-proof')throw new Exception('Fixture identity mismatch');wp_delete_post($id,true);}
if(!empty($state['lowId'])){$u=get_user_by('id',$state['lowId']);if($u){if($u->user_login!=='header-low-fixture')throw new Exception('User identity mismatch');require_once ABSPATH.'wp-admin/includes/user.php';wp_delete_user($u->ID);}}
if(null===$state['mods'])delete_option('theme_mods_helix-wt');else update_option('theme_mods_helix-wt',$state['mods']);
if(get_option('theme_mods_helix-wt',null)!==$state['mods'])throw new Exception('Settings restore mismatch');
`);
fs.unlinkSync(file);console.log('Header editing fixtures and complete settings snapshot restored.');
