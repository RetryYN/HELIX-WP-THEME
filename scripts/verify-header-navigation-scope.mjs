import { contentLab } from './lib/content-lab-env.mjs';
import fs from 'node:fs';import {execFileSync} from 'node:child_process';import {createHash} from 'node:crypto';
const out='docs/research/2026-09-14-header-navigation-editing';fs.mkdirSync(out,{recursive:true});
const php=code=>execFileSync('docker',['exec',contentLab.wpContainer,'php','-r',`require '/var/www/html/wp-load.php'; ${code}`],{encoding:'utf8'}).trim();
if(php('echo get_option("blogname");')!=='HELIX Content Lab')throw Error('dedicated lab required');
const rows=[];const check=(name,pass)=>rows.push({name,pass:!!pass});
const data=JSON.parse(php(`$original=get_option('theme_mods_helix-wt',null);$ids=[];$out=[];
try {
 foreach(['shared'=>'共通の案内','independent'=>'独立した案内'] as $name=>$label){$id=wp_insert_post(wp_slash(['post_type'=>'wp_navigation','post_status'=>'publish','post_title'=>$label,'post_content'=>'<!-- wp:navigation-link '.wp_json_encode(['label'=>$label,'url'=>'/learn/']).' /-->']));$ids[$name]=$id;}
 set_theme_mod('wt_content_navigation_ref',$ids['shared']);
 foreach(['plain'=>'','copied'=>'wt-header-navigation','sp-copied'=>'wt-header__textnav wt-header-navigation'] as $marker=>$class){
  $nav='<!-- wp:navigation '.wp_json_encode(['ref'=>$ids['independent'],'className'=>$class]).' /-->';
  foreach(['body'=>'main','footer'=>'footer'] as $scope=>$tag){$content='<!-- wp:group '.wp_json_encode(['tagName'=>$tag]).' --><'.$tag.' class="wp-block-group">'.$nav.'</'.$tag.'><!-- /wp:group -->';$html=do_blocks($content);$out[$scope.':'.$marker]=['independent'=>strpos($html,'独立した案内')!==false,'shared'=>strpos($html,'共通の案内')!==false];}
 }
 foreach(['header','header-nav','header-cta','header-announce','header-center','header-two-rows','header-overlay','header-tel','header-band'] as $slug){$html=do_blocks('<!-- wp:template-part '.wp_json_encode(['slug'=>$slug,'theme'=>'helix-wt']).' /-->');$out[$slug]=['shared'=>strpos($html,'共通の案内')!==false];}
 foreach(['footer-navigation-scope','headerish-navigation-scope','header-copied-body'] as $slug){
  if(get_page_by_path($slug,OBJECT,'wp_template_part'))throw new Exception('scope fixture collision');
  $id=wp_insert_post(wp_slash(['post_type'=>'wp_template_part','post_status'=>'publish','post_name'=>$slug,'post_title'=>$slug,'post_content'=>'<!-- wp:navigation '.wp_json_encode(['ref'=>$ids['independent'],'className'=>'wt-header-navigation']).' /-->']));$ids[$slug]=$id;wp_set_object_terms($id,'helix-wt','wp_theme');
  $html=do_blocks('<!-- wp:template-part '.wp_json_encode(['slug'=>$slug,'theme'=>'helix-wt']).' /-->');$out['outside-part:'.$slug]=['independent'=>strpos($html,'独立した案内')!==false,'shared'=>strpos($html,'共通の案内')!==false];
 }
 $out['after-header-scope']=strpos(do_blocks('<!-- wp:navigation '.wp_json_encode(['ref'=>$ids['independent'],'className'=>'wt-header-navigation']).' /-->'),'独立した案内')!==false;
} finally {foreach($ids as $id)wp_delete_post($id,true);if(null===$original)delete_option('theme_mods_helix-wt');else update_option('theme_mods_helix-wt',$original);$out['cleanup']=get_option('theme_mods_helix-wt',null)===$original;}
echo wp_json_encode($out);`));
for(const [name,row]of Object.entries(data)){if(name==='cleanup'||name==='after-header-scope')check(name,row);else if(name.startsWith('header'))check(name+':shared',row.shared);else{check(name+':independent',row.independent);check(name+':not-rebound',!row.shared);}}
const sources=['inc/content-navigation.php','assets/js/header-navigation-editor.js','inc/header-navigation-settings.php'].map(f=>'docs/research/2026-09-05-design-prototype-03/theme/helix-wt/'+f);sources.push('scripts/verify-header-navigation-scope.mjs');
const sourceDigests=Object.fromEntries(sources.map(f=>[f,createHash('sha256').update(fs.readFileSync(f)).digest('hex')]));
const completed=rows.every(r=>r.pass);fs.writeFileSync(out+'/scope.json',JSON.stringify({completed,conditionCount:18,assertionCount:rows.length,sourceDigests,rows},null,2)+'\n');console.log(rows.length,rows.filter(r=>!r.pass));if(!completed)process.exitCode=1;
