/** G-E1 scoped banner gate. Measurements come from the rendered viewport, not source strings. */
export function evaluateBannerZone(snapshot) {
  return {
    'G-E1:area-cap': Number.isFinite(snapshot.areaRatio) && Number.isFinite(snapshot.areaCap) && snapshot.areaCap > 0 && snapshot.areaCap <= 1 && snapshot.areaRatio <= snapshot.areaCap,
    'G-E1:initial-modal': snapshot.openPromotionalModals === 0 || (snapshot.face === 'lp' && snapshot.lpException === true),
    'G-E1:stack-order': snapshot.layers.every((layer, i, list) => i === 0 || list[i - 1].bottom <= layer.top + 1) && snapshot.layers.every((x, i, xs) => ['consent', 'menu', 'share'].includes(x.kind) && (i === 0 || ['consent', 'menu', 'share'].indexOf(xs[i-1].kind) < ['consent', 'menu', 'share'].indexOf(x.kind))),
    'G-E1:cta-clear': snapshot.layers.every(layer => snapshot.cta.bottom <= layer.top || snapshot.cta.top >= layer.bottom),
  };
}
