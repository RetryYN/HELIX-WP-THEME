/* global wp, helixSitePageEditor */
(function () {
  'use strict';
  const h = wp.element.createElement;
  const definitions = window.helixSitePageEditor?.pages || [];
  const render = wp.serverSideRender.ServerSideRender || wp.serverSideRender.default || wp.serverSideRender;
  wp.blocks.registerBlockType('helix-wt/site-page', {
    edit: function Edit({ attributes, setAttributes }) {
      const blockProps = wp.blockEditor.useBlockProps({ className: 'wt-site-page-editor' });
      const post = wp.data.useSelect(select => ({
        id: select('core/editor').getCurrentPostId(),
        type: select('core/editor').getCurrentPostType(),
        template: select('core/editor').getEditedPostAttribute('template'),
      }), []);
      const current = definitions.find(page => page.value === attributes.pageKey);
      return h('div', blockProps,
        h('div', { className: 'wt-site-page-editor__controls' },
          h(wp.components.SelectControl, {
            label: 'ページの種類', value: attributes.pageKey,
            options: definitions, onChange: pageKey => setAttributes({ pageKey }),
            help: '共通の事業者情報と、選んだ種類の内容を表示します。',
          }),
          post.type === 'page' && post.template !== 'page-site-guide' ? h(wp.components.Button, {
            variant: 'secondary', onClick: () => wp.data.dispatch('core/editor').editPost({ template: 'page-site-guide' }),
          }, 'ページ全体用テンプレートを適用') : h('p', null, 'ページ全体用テンプレートを使用中'),
          !current ? h(wp.components.Notice, { status: 'warning', isDismissible: false }, 'この種類の設定を取得できません。登録済みの種類を選び直してください。') : null
        ),
        current ? h('div', { className: 'wt-site-page-editor__preview', 'aria-label': 'ページのプレビュー' },
          h(wp.components.Disabled, null, h(render, { block: 'helix-wt/site-page', attributes, urlQueryArgs: { post_id: post.id } }))
        ) : null
      );
    },
    save: () => null,
  });
}());
