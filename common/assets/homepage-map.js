const homepageOrganizationMapRows = window.homepageMapConfig.rows;

function initHomepageOrganizationMap() {
  if (!Array.isArray(homepageOrganizationMapRows) || homepageOrganizationMapRows.length === 0) {
    return;
  }

  const mapElement = document.getElementById('homepageOrganizationMap');
  if (!mapElement || typeof window.L === 'undefined') {
    return;
  }

  const defaultCenter = [46.8182, 8.2275];
  const map = L.map(mapElement, {
    scrollWheelZoom: false
  }).setView(defaultCenter, 7);
  const tileState = { layer: null, theme: null };
  if (typeof window.commonBindLeafletTheme === 'function') {
    window.commonBindLeafletTheme(map, tileState);
  }

  const bounds = [];
  homepageOrganizationMapRows.forEach((organizationRow) => {
    const latlong = organizationRow && organizationRow.latlong ? organizationRow.latlong : null;
    const lat = latlong ? Number(latlong.lat) : NaN;
    const lng = latlong ? Number(latlong.long) : NaN;
    if (!Number.isFinite(lat) || !Number.isFinite(lng)) {
      return;
    }

    const markerColor = String((organizationRow && organizationRow.color) || '#0b6e7a');
    const marker = L.circleMarker([lat, lng], {
      radius: 9,
      color: markerColor,
      weight: 2,
      fillColor: markerColor,
      fillOpacity: 0.72
    }).addTo(map);

    const safeName = String((organizationRow && organizationRow.name) || 'Organisation');
    const safeLogo = String((organizationRow && organizationRow.logo) || '');
    const safeColor = String((organizationRow && organizationRow.color) || '#0b6e7a');
    const safeInitial = safeName.trim() ? safeName.trim().charAt(0).toUpperCase() : 'O';
    const popupHtml = [
      '<div class="home-organization-map__popup">',
      safeLogo
        ? '<img class="home-organization-map__popup-logo" src="' + safeLogo.replace(/"/g, '&quot;') + '" alt="' + safeName.replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;') + '">'
        : '<span class="home-organization-map__popup-placeholder" style="background:' + safeColor.replace(/"/g, '&quot;') + ';">' + safeInitial.replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;') + '</span>',
      '<strong>' + safeName.replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;') + '</strong>',
      '</div>'
    ].join('');
    marker.bindPopup(popupHtml);
    bounds.push([lat, lng]);
  });

  if (bounds.length > 0) {
    map.fitBounds(bounds, {
      padding: [30, 30],
      maxZoom: 9
    });
  }

  window.setTimeout(() => map.invalidateSize(), 0);
  window.setTimeout(() => map.invalidateSize(), 250);
}

if (typeof window.commonWhenLeafletReady === 'function') {
  window.commonWhenLeafletReady(initHomepageOrganizationMap);
} else {
  window.addEventListener('load', initHomepageOrganizationMap);
}

const intro = document.getElementById('intro');
const textBlock = intro.querySelector('.vertical');
const btn = document.getElementById('toggleText');

let isExpanded = false;

// toggle
btn.addEventListener('click', () => {
  isExpanded = !isExpanded;

  intro.classList.toggle('expanded', isExpanded);

  btn.textContent = isExpanded
    ? "Réduire"
    : "Afficher la suite";
});

// check overflow sur le BON élément
function checkOverflow() {
  // force état fermé pour mesurer
  intro.classList.remove('expanded');

  const hasOverflow = textBlock.scrollHeight > textBlock.clientHeight + 2;

  btn.style.display = hasOverflow ? "inline-block" : "none";

  // restaure état
  if (isExpanded || !hasOverflow) {
    intro.classList.add('expanded');
  }
}

// debounce
function debounce(fn, delay) {
  let t;
  return () => {
    clearTimeout(t);
    t = setTimeout(fn, delay);
  };
}

const debouncedCheck = debounce(checkOverflow, 150);

// events
window.addEventListener('load', checkOverflow);
window.addEventListener('resize', debouncedCheck);

if (document.fonts) {
  document.fonts.ready.then(checkOverflow);
}
