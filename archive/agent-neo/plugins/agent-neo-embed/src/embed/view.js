(function () {
  'use strict';

  var MESSAGE_TYPES = {
    READY: 'ane-embed:ready',
    HEIGHT: 'ane-embed:height'
  };
  var MIN_HEIGHT = 80;
  var MAX_HEIGHT = 1200;

  function resolveResetCssUrl(host) {
    if (host.dataset.resetCssUrl) {
      return host.dataset.resetCssUrl;
    }

    if (document.currentScript && document.currentScript.src) {
      return new URL('../../assets/embed-reset.css', document.currentScript.src).href;
    }

    return '';
  }

  function supportsDeclarativeShadowDom() {
    return typeof HTMLTemplateElement !== 'undefined' &&
      Object.prototype.hasOwnProperty.call(HTMLTemplateElement.prototype, 'shadowRootMode');
  }

  function initStaticEmbed(host) {
    if (host.dataset.agentNeoStaticReady === 'true' || host.shadowRoot) {
      return;
    }

    if (supportsDeclarativeShadowDom()) {
      return;
    }

    var template = host.querySelector('template[shadowrootmode="open"], template[data-agent-neo-static-html]');

    if (!template || typeof host.attachShadow !== 'function') {
      return;
    }

    var shadowRoot = host.attachShadow({ mode: 'open' });
    var resetCssUrl = resolveResetCssUrl(host);
    var hasResetLink = template.content &&
      template.content.querySelector('link[rel="stylesheet"]');

    if (resetCssUrl && !hasResetLink) {
      var resetLink = document.createElement('link');
      resetLink.rel = 'stylesheet';
      resetLink.href = resetCssUrl;
      shadowRoot.appendChild(resetLink);
    }

    shadowRoot.appendChild(template.content.cloneNode(true));

    host.dataset.agentNeoStaticReady = 'true';
  }

  function clampHeight(height) {
    var parsed = Number(height);

    if (!Number.isFinite(parsed)) {
      return null;
    }

    return Math.max(MIN_HEIGHT, Math.min(MAX_HEIGHT, Math.round(parsed)));
  }

  function findMatchingFrame(event) {
    var frames = document.querySelectorAll('iframe[data-agent-neo-iframe="true"]');

    for (var index = 0; index < frames.length; index += 1) {
      if (event.source === frames[index].contentWindow) {
        return frames[index];
      }
    }

    return null;
  }

  function handleEmbedMessage(event) {
    var frame = findMatchingFrame(event);

    if (!frame) {
      return;
    }

    var data = event.data || {};
    var expectedNonce = frame.dataset.agentNeoNonce || '';

    if (!expectedNonce || data.nonce !== expectedNonce) {
      return;
    }

    if (data.type !== MESSAGE_TYPES.READY && data.type !== MESSAGE_TYPES.HEIGHT) {
      return;
    }

    if (data.type === MESSAGE_TYPES.READY) {
      frame.dataset.agentNeoReady = 'true';
      return;
    }

    var nextHeight = clampHeight(data.height);

    if (nextHeight !== null) {
      frame.height = String(nextHeight);
      frame.style.height = nextHeight + 'px';
    }
  }

  function init() {
    var staticHosts = document.querySelectorAll('[data-agent-neo-embed][data-mode="static"]');

    for (var index = 0; index < staticHosts.length; index += 1) {
      initStaticEmbed(staticHosts[index]);
    }
  }

  window.addEventListener('message', handleEmbedMessage);

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', init);
  } else {
    init();
  }
})();
