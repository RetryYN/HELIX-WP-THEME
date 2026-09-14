/* global wp, helixWTNavigation */
(function () {
 'use strict';
 const el = wp.element.createElement;
 const Sidebar = wp.editor.PluginSidebar, MenuItem = wp.editor.PluginSidebarMoreMenuItem;
 const eventName = 'helix-wt-navigation-saved';
 const save = ref => wp.apiFetch({path:'/helix-wt/v1/header-navigation',method:'POST',data:{ref}}).then(state => {
  helixWTNavigation.ref = state.ref; helixWTNavigation.choices = state.choices;
  window.dispatchEvent(new CustomEvent(eventName)); return state;
 });
 function Panel() {
  const [ref,setRef] = wp.element.useState(helixWTNavigation.ref);
  const [message,setMessage] = wp.element.useState('');
  const [error,setError] = wp.element.useState(false);
  const [busy,setBusy] = wp.element.useState(false);
  wp.element.useEffect(() => { const sync=()=>setRef(helixWTNavigation.ref);window.addEventListener(eventName,sync);return()=>window.removeEventListener(eventName,sync); },[]);
  return el(wp.element.Fragment,null,
   el(MenuItem,{target:'header-navigation'},helixWTNavigation.labels.text0),
   el(Sidebar,{name:'header-navigation',title:helixWTNavigation.labels.text0},el('div',{style:{padding:'16px'}},
    el('p',null,helixWTNavigation.labels.text1),
    el(wp.components.SelectControl,{label:helixWTNavigation.labels.text2,value:ref,options:[{label:helixWTNavigation.labels.text3,value:0}].concat(helixWTNavigation.choices),onChange:value=>{setRef(Number(value));setMessage('');}}),
    el(wp.components.Button,{variant:'primary',disabled:busy,onClick:()=>{setBusy(true);save(ref).then(()=>{setError(false);setMessage(helixWTNavigation.labels.text4);}).catch(e=>{setError(true);setMessage(e.message);}).finally(()=>setBusy(false));}},helixWTNavigation.labels.text5),
    message&&el(wp.components.Notice,{status:error?'error':'success',isDismissible:false},message),
    el('p',null,helixWTNavigation.labels.text6)
   )));
 }
 wp.plugins.registerPlugin('helix-wt-header-navigation',{render:Panel,icon:'menu'});
 // 共通管理するナビだけ、編集canvasも保存済みの共通参照を表示する。
 const withReference = wp.compose.createHigherOrderComponent(BlockEdit => function (props) {
  const [ref,setRef] = wp.element.useState(helixWTNavigation.ref);
  const [error,setError] = wp.element.useState('');
  wp.element.useEffect(()=>{const sync=()=>setRef(helixWTNavigation.ref);window.addEventListener(eventName,sync);return()=>window.removeEventListener(eventName,sync);},[]);
  if(props.name!=='core/navigation'||!(props.attributes.className||'').split(' ').includes('wt-header-navigation'))return el(BlockEdit,props);
  if(!ref)return el(wp.components.Notice,{status:'info',isDismissible:false},helixWTNavigation.labels.text7);
  return el(wp.element.Fragment,null,error&&el(wp.components.Notice,{status:'error',isDismissible:false},error),el(BlockEdit,Object.assign({},props,{attributes:Object.assign({},props.attributes,{ref}),setAttributes:attrs=>{
   if(Object.hasOwn(attrs,'ref')) { save(attrs.ref).then(()=>setError('')).catch(e=>setError(e.message)); }
   else props.setAttributes(attrs);
  }})));
 },'withHelixHeaderNavigation');
 wp.hooks.addFilter('editor.BlockEdit','helix-wt/header-navigation',withReference);
}());
