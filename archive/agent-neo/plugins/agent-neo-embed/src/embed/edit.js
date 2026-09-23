(function (blocks, blockEditor, components, element, i18n) {
  'use strict';

  var createElement = element.createElement;
  var Fragment = element.Fragment;
  var InspectorControls = blockEditor.InspectorControls;
  var useBlockProps = blockEditor.useBlockProps;
  var PanelBody = components.PanelBody;
  var SelectControl = components.SelectControl;
  var TextControl = components.TextControl;
  var TextareaControl = components.TextareaControl;
  var __ = i18n.__;

  blocks.registerBlockType('agent-neo/embed', {
    apiVersion: 3,
    title: __('AGENT NEO Embed', 'agent-neo-embed'),
    category: 'embed',
    icon: 'embed-generic',
    description: __('Renders AGENT NEO static or interactive isolated embeds.', 'agent-neo-embed'),
    attributes: {
      mode: {
        type: 'string',
        enum: ['static', 'interactive'],
        default: 'static'
      },
      embedUrl: {
        type: 'string',
        default: ''
      },
      title: {
        type: 'string',
        default: 'AGENT NEO embed'
      },
      payloadId: {
        type: 'string',
        default: ''
      },
      staticHtml: {
        type: 'string',
        default: ''
      },
      align: {
        type: 'string'
      }
    },
    supports: {
      align: ['wide', 'full'],
      customClassName: false,
      customCSS: false,
      html: false
    },
    edit: function Edit(props) {
      var attributes = props.attributes;
      var setAttributes = props.setAttributes;
      var blockProps = useBlockProps({
        className: 'agent-neo-embed-editor'
      });
      var mode = attributes.mode || 'static';

      return createElement(
        Fragment,
        null,
        createElement(
          InspectorControls,
          null,
          createElement(
            PanelBody,
            { title: __('Embed settings', 'agent-neo-embed'), initialOpen: true },
            createElement(SelectControl, {
              label: __('Mode', 'agent-neo-embed'),
              value: mode,
              options: [
                { label: __('Static', 'agent-neo-embed'), value: 'static' },
                { label: __('Interactive', 'agent-neo-embed'), value: 'interactive' }
              ],
              onChange: function (value) {
                setAttributes({ mode: value });
              }
            }),
            createElement(TextControl, {
              label: __('Title', 'agent-neo-embed'),
              value: attributes.title || '',
              onChange: function (value) {
                setAttributes({ title: value });
              }
            }),
            createElement(TextControl, {
              label: __('Payload ID / nonce', 'agent-neo-embed'),
              value: attributes.payloadId || '',
              onChange: function (value) {
                setAttributes({ payloadId: value });
              }
            }),
            mode === 'interactive'
              ? createElement(TextControl, {
                  label: __('Embed URL', 'agent-neo-embed'),
                  value: attributes.embedUrl || '',
                  onChange: function (value) {
                    setAttributes({ embedUrl: value });
                  }
                })
              : createElement(TextareaControl, {
                  label: __('Static HTML', 'agent-neo-embed'),
                  value: attributes.staticHtml || '',
                  rows: 8,
                  onChange: function (value) {
                    setAttributes({ staticHtml: value });
                  }
                })
          )
        ),
        createElement(
          'div',
          blockProps,
          createElement(
            'div',
            { className: 'agent-neo-embed-editor__summary' },
            createElement('strong', null, __('AGENT NEO Embed', 'agent-neo-embed')),
            createElement('span', null, mode === 'interactive' ? __('Interactive iframe', 'agent-neo-embed') : __('Static Shadow DOM', 'agent-neo-embed'))
          )
        )
      );
    },
    save: function Save() {
      return null;
    }
  });
})(window.wp.blocks, window.wp.blockEditor, window.wp.components, window.wp.element, window.wp.i18n);
