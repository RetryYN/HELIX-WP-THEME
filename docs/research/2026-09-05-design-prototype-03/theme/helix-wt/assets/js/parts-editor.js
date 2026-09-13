/* global wp, helixWTParts */
(function () {
  'use strict';
  const el = wp.element.createElement;
  const __ = wp.i18n.__;
  // サーバー登録と同じ属性をクライアント登録にも明示する。
  wp.hooks.addFilter('blocks.registerBlockType', 'helix-wt/parts-declaration', function (settings, name) {
    if (name !== 'core/template-part') return settings;
    return Object.assign({}, settings, {attributes: Object.assign({}, settings.attributes, {wtPartsDeclaration: {type: 'object'}})});
  });
  const options = helixWTParts.map(function (slug) { return {label: slug, value: slug}; });
  const withParts = wp.compose.createHigherOrderComponent(function (BlockEdit) {
    return function (props) {
      if (props.name !== 'core/template-part') return el(BlockEdit, props);
      const declaration = props.attributes.wtPartsDeclaration;
      const enabled = declaration !== undefined;
      const valid = enabled && declaration && Object.keys(declaration).length === 3 && ['common', 'pc', 'sp'].every(function (key) {
        return Object.prototype.hasOwnProperty.call(declaration, key) && ((key !== 'common' && declaration[key] === null) || helixWTParts.includes(declaration[key]));
      });
      const set = function (key, value) {
        props.setAttributes({wtPartsDeclaration: Object.assign({}, declaration, {[key]: value === '__inherit__' ? null : value})});
      };
      return el(wp.element.Fragment, null, el(BlockEdit, props), props.isSelected && el(wp.blockEditor.InspectorControls, null,
        el(wp.components.PanelBody, {title: __('端末別パーツ', 'helix-wt'), initialOpen: true},
          el(wp.components.ToggleControl, {label: __('共通と端末別の参照を設定', 'helix-wt'), checked: enabled, onChange: function (on) {
            props.setAttributes({wtPartsDeclaration: on ? {common: helixWTParts.includes(props.attributes.slug) ? props.attributes.slug : helixWTParts[0], pc: null, sp: null} : undefined});
          }}),
          enabled && ['common', 'pc', 'sp'].map(function (key) {
            return el(wp.components.SelectControl, {key: key, label: {common: __('共通パーツ', 'helix-wt'), pc: __('PCの差分', 'helix-wt'), sp: __('SPの差分', 'helix-wt')}[key],
              value: declaration && declaration[key] === null ? '__inherit__' : (declaration && declaration[key]) || '',
              options: key === 'common' ? options : [{label: __('共通を継承', 'helix-wt'), value: '__inherit__'}].concat(options), onChange: function (value) {set(key, value);}});
          }),
          enabled && el(wp.components.Notice, {status: valid ? 'info' : 'error', isDismissible: false}, valid ? __('公開面では選択した端末のパーツだけを表示します。編集キャンバスは元のパーツを表示します。', 'helix-wt') : __('宣言が不正です。保存は拒否されます。参照を選び直してください。', 'helix-wt'))
        )));
    };
  }, 'withHelixPartsDeclaration');
  wp.hooks.addFilter('editor.BlockEdit', 'helix-wt/parts-inspector', withParts);
}());
