/* global wp, helixWTNavigation */
(function () {
 'use strict';
 helixWTNavigation.ref=Number(helixWTNavigation.ref);
 const el = wp.element.createElement;
 const Sidebar = wp.editor.PluginSidebar, MenuItem = wp.editor.PluginSidebarMoreMenuItem;
 const eventName = 'helix-wt-navigation-saved';
 const historySignal={direction:null,revision:0,listeners:new Set()};
 const observedHistoryActions=new WeakSet();
 // 公開registryのcore actionsを観測し、toolbar・shortcut・programmatic操作を同じ経路で扱う。
 const observeHistory=registry=>{
  const actions=registry.dispatch('core');
  if(observedHistoryActions.has(actions))return;
  for(const direction of ['undo','redo']){
   const original=actions[direction];
   actions[direction]=(...args)=>{
    historySignal.direction=direction;historySignal.revision++;
    const result=original(...args);
    queueMicrotask(()=>historySignal.listeners.forEach(listener=>listener(historySignal.revision)));
    return result;
   };
  }
  observedHistoryActions.add(actions);
 };
 let pendingSave = null;
 const save = ref => {
  if(pendingSave)return pendingSave.ref===ref ? pendingSave.promise : Promise.reject(new Error(helixWTNavigation.labels.text14));
  const promise=wp.apiFetch({path:'/helix-wt/v1/header-navigation',method:'POST',data:{ref}}).then(state => {
   helixWTNavigation.ref = Number(state.ref); helixWTNavigation.choices = state.choices;
   window.dispatchEvent(new CustomEvent(eventName)); return state;
  }).finally(()=>{pendingSave=null;});
  pendingSave={ref,promise};return promise;
 };
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
    el(wp.components.SelectControl,{label:helixWTNavigation.labels.text2,value:ref,disabled:busy,options:[{label:helixWTNavigation.labels.text3,value:0}].concat(helixWTNavigation.choices),onChange:value=>{setRef(Number(value));setMessage('');}}),
    el(wp.components.Button,{variant:'primary',disabled:busy,onClick:()=>{setBusy(true);save(ref).then(()=>{setError(false);setMessage(helixWTNavigation.labels.text4);}).catch(e=>{setError(true);setMessage(e.message);}).finally(()=>setBusy(false));}},helixWTNavigation.labels.text5),
    message&&el(wp.components.Notice,{status:error?'error':'success',isDismissible:false},message),
    el('p',null,helixWTNavigation.labels.text6)
   )));
 }
 wp.plugins.registerPlugin('helix-wt-header-navigation',{render:Panel,icon:'menu'});
 // 正規ヘッダー部品の構造で識別し、コピーされたCSSクラスには依存しない。
 const headerSlug = value => helixWTNavigation.headerParts.includes(value);
 const withReference = wp.compose.createHigherOrderComponent(BlockEdit => function (props) {
  const [ref,setRef] = wp.element.useState(helixWTNavigation.ref);
  const [error,setError] = wp.element.useState('');
 const [busy,setBusy] = wp.element.useState(false);
 const [touched,setTouched] = wp.element.useState(false);
  const [,setRevision] = wp.element.useState(0);
  const [,setHistoryRevision] = wp.element.useState(0);
  const registry=wp.data.useRegistry();observeHistory(registry);
  const origin = wp.element.useRef(null);
  const referenceHistory = wp.element.useRef(null);
  const expectedAttribute = wp.element.useRef(null);
  const sameValueExplicit = wp.element.useRef(false);
  const seenHistoryRevision = wp.element.useRef(0);
  const lastAttribute = wp.element.useRef(Number(props.attributes.ref || 0));
  const inHeader = wp.data.useSelect(select => {
   const blocks = select('core/block-editor'), editor = select('core/editor');
   if(blocks.getBlockParents(props.clientId).some(id => { const b=blocks.getBlock(id); return b?.name==='core/template-part' && headerSlug(b.attributes.slug); }))return true;
   return editor?.getCurrentPostType?.()==='wp_template_part' && (headerSlug(editor.getEditedPostAttribute?.('slug')) || headerSlug(String(editor.getCurrentPostId?.() || '').split('//').pop()));
  },[props.clientId]);
  wp.element.useEffect(()=>{const sync=()=>setRef(helixWTNavigation.ref);window.addEventListener(eventName,sync);return()=>window.removeEventListener(eventName,sync);},[]);
  wp.element.useEffect(()=>{const sync=revision=>setHistoryRevision(revision);historySignal.listeners.add(sync);return()=>historySignal.listeners.delete(sync);},[]);
  if(props.name!=='core/navigation'||!inHeader)return el(BlockEdit,props);
  const classes=(props.attributes.className||'').split(' ');
  const attributeRef=Number(props.attributes.ref || 0);
  if(touched&&attributeRef!==lastAttribute.current){
   const history=referenceHistory.current;
   const historyAction=historySignal.revision>seenHistoryRevision.current ? historySignal.direction : null;
   sameValueExplicit.current=false;
   if(expectedAttribute.current===attributeRef)expectedAttribute.current=null;
   else if(historyAction==='undo'&&history.index>0&&history.values[history.index-1]===attributeRef)history.index--;
   else if(historyAction==='redo'&&history.index+1<history.values.length&&history.values[history.index+1]===attributeRef)history.index++;
   else if(history.index>0&&history.values[history.index-1]===attributeRef&&(history.index+1>=history.values.length||history.values[history.index+1]!==attributeRef))history.index--;
   else if(history.index+1<history.values.length&&history.values[history.index+1]===attributeRef&&(history.index===0||history.values[history.index-1]!==attributeRef))history.index++;
   else{history.values=history.values.slice(0,history.index+1).concat(attributeRef);history.index=history.values.length-1;}
   seenHistoryRevision.current=historySignal.revision;
   lastAttribute.current=attributeRef;
  }
  else if(touched&&historySignal.revision>seenHistoryRevision.current)seenHistoryRevision.current=historySignal.revision;
  const target=touched ? (sameValueExplicit.current ? attributeRef : referenceHistory.current.index===0 ? origin.current.ref : referenceHistory.current.values[referenceHistory.current.index]) : ref;
  const pending=target!==ref;
  const label=id=>helixWTNavigation.choices.find(choice=>Number(choice.value)===id)?.label || helixWTNavigation.labels.text3;
  const change=attrs=>{
   if(Object.hasOwn(attrs,'ref')){
    if(!touched){origin.current={attribute:props.attributes.ref,ref};referenceHistory.current={values:[attributeRef],index:0};setTouched(true);}
    const history=referenceHistory.current;
    const next=Number(attrs.ref || 0);
    if(next===attributeRef)sameValueExplicit.current=true;
    else{
     sameValueExplicit.current=false;
     history.values=history.values.slice(0,history.index+1).concat(next);history.index++;
     expectedAttribute.current=next;
    }
    setRevision(value=>value+1);
   }
   props.setAttributes(attrs);
  };
  const navigation=target ? el(BlockEdit,Object.assign({},props,{attributes:Object.assign({},props.attributes,{ref:target}),setAttributes:change})) : el(wp.components.Notice,{status:'info',isDismissible:false},helixWTNavigation.labels.text7);
  const sp=classes.includes('wt-header__textnav');
  return el(wp.element.Fragment,null,
   el(wp.blockEditor.InspectorControls,{group:'list'},el(wp.components.PanelBody,{title:helixWTNavigation.labels.text0,initialOpen:true},
    sp&&el('p',null,helixWTNavigation.labels.text8),
    pending&&el('div',{className:'wt-header-navigation-pending'},
     el('p',null,helixWTNavigation.labels.text9),
     el('p',null,helixWTNavigation.labels.text12+': '+label(ref)+' → '+helixWTNavigation.labels.text13+': '+label(target)),
     el(wp.components.Button,{variant:'primary',disabled:busy,onClick:()=>{setBusy(true);save(target).then(()=>setError('')).catch(e=>setError(e.message)).finally(()=>setBusy(false));}},helixWTNavigation.labels.text10),
     el(wp.components.Button,{variant:'secondary',disabled:busy,onClick:()=>{change({ref});setError('');}},helixWTNavigation.labels.text11)),
    busy&&el(wp.components.Notice,{status:'info',isDismissible:false},helixWTNavigation.labels.text14),
    error&&el(wp.components.Notice,{status:'error',isDismissible:false},error))),
   sp ? el('div',{className:'wt-header-editor-sp-navigation',style:{display:'flex',flexDirection:'column',gap:'4px',border:'1px dashed #3858e9',padding:'8px',minWidth:'120px'}},el('span',{style:{fontSize:'11px',color:'#1e3a8a'}},helixWTNavigation.labels.text15),navigation) : navigation);
 },'withHelixHeaderNavigation');
 wp.hooks.addFilter('editor.BlockEdit','helix-wt/header-navigation',withReference);
}());
