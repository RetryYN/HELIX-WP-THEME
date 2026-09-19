import{readFileSync,writeFileSync}from'node:fs';import{execFileSync}from'node:child_process';
const path='docs/research/2026-09-10-current-theme-quality/fixtures.json';
const wp=code=>JSON.parse(execFileSync('docker',['exec','helix-content-wp','php','-r',"require '/var/www/html/wp-load.php';"+code],{encoding:'utf8'}));
if(process.argv[2]==='create'){
 const rows=[];
 try{
  for(const [face,type,template,content]of [['article','post','','<!-- wp:pattern {"slug":"helix-wt/article-kit"} /-->'],['lp','page','page-lp',''],['event','page','page-event',''],['form','page','page-canvas','<!-- wp:helix-wt/form /-->']]){
   const slug='quality-audit-'+face;
   const row=wp(`if(get_page_by_path('${slug}',OBJECT,'${type}'))throw new Exception('fixture collision');$id=wp_insert_post(['post_type'=>'${type}','post_name'=>'${slug}','post_title'=>'品質比較用の一時ページ','post_status'=>'publish','post_content'=>'${content}'],true);if(is_wp_error($id))throw new Exception($id->get_error_message());if('${template}')update_post_meta($id,'_wp_page_template','${template}');echo wp_json_encode(['id'=>$id,'slug'=>'${slug}','url'=>get_permalink($id),'created'=>true,'priorSlugAbsent'=>true]);`);
   rows.push(row);writeFileSync(path,JSON.stringify(rows,null,2)+'\n');
  }
 }catch(error){console.error('Creation incomplete; run fixtures.mjs cleanup for recorded IDs.');throw error;}
}else if(process.argv[2]==='cleanup'){
 const rows=JSON.parse(readFileSync(path,'utf8'));const errors=[];
 for(const row of rows){try{Object.assign(row,wp(`$p=get_post(${row.id});if($p&&$p->post_name!=='${row.slug}')throw new Exception('fixture identity mismatch');$deleted=$p?wp_delete_post(${row.id},true):true;echo wp_json_encode(['deleted'=>(bool)$deleted,'absent'=>get_post(${row.id})===null]);`));}catch(error){errors.push(row.id);}writeFileSync(path,JSON.stringify(rows,null,2)+'\n');}
 if(errors.length)throw new Error('Fixture cleanup failed: '+errors.join(','));
}else throw new Error('Use create or cleanup');
