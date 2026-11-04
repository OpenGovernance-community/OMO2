const homepageOrganizationMapRows = window.homepageMapConfig.rows;
const homepageMapToggle = document.getElementById(window.homepageMapConfig.toggleId || '');
const homepageMapPanel = homepageMapToggle
  ? document.getElementById(homepageMapToggle.getAttribute('aria-controls'))
  : null;
let homepageOrganizationMap = null;

function initHomepageOrganizationMap() {
  if (!Array.isArray(homepageOrganizationMapRows) || homepageOrganizationMapRows.length === 0) {
    return;
  }

  const mapElement = document.getElementById('homepageOrganizationMap');
  if (!mapElement || (homepageMapPanel && homepageMapPanel.hidden) || typeof window.L === 'undefined') {
    return;
  }
  if (homepageOrganizationMap) {
    homepageOrganizationMap.invalidateSize();
    return;
  }

  const defaultCenter = [46.8182, 8.2275];
  const map = L.map(mapElement, {
    scrollWheelZoom: false
  }).setView(defaultCenter, 7);
  homepageOrganizationMap = map;
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

    const name = String(organizationRow.name || 'Organisation');
    const logo = String(organizationRow.logo || '');
    const popup = document.createElement('div');
    popup.className = 'home-organization-map__popup';
    const header = document.createElement('div');
    header.className = 'home-organization-map__popup-header';
    const portrait = document.createElement(logo ? 'img' : 'span');
    if (logo) {
      portrait.className = 'home-organization-map__popup-logo';
      portrait.src = logo;
      portrait.alt = name;
    } else {
      portrait.className = 'home-organization-map__popup-placeholder';
      portrait.style.background = markerColor;
      portrait.textContent = name.trim().charAt(0).toUpperCase() || 'O';
    }
    const label = document.createElement('strong');
    label.textContent = name;
    header.append(portrait, label);
    popup.append(header);
    if (organizationRow.description) {
      const description = document.createElement('p');
      description.className = 'home-organization-map__popup-description';
      description.textContent = organizationRow.description;
      popup.append(description);
    }
    if (organizationRow.url) {
      const link = document.createElement('a');
      link.className = 'home-organization-map__popup-link';
      link.href = organizationRow.url;
      link.textContent = organizationRow.url;
      link.target = '_blank';
      link.rel = 'noopener noreferrer';
      popup.append(link);
    }
    marker.bindPopup(popup);
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

if (homepageMapToggle && homepageMapPanel) {
  homepageMapToggle.addEventListener('click', () => {
    homepageMapPanel.hidden = !homepageMapPanel.hidden;
    const expanded = !homepageMapPanel.hidden;
    homepageMapToggle.setAttribute('aria-expanded', String(expanded));
    homepageMapToggle.textContent = expanded ? homepageMapToggle.dataset.labelHide : homepageMapToggle.dataset.labelShow;
    if (expanded) initHomepageOrganizationMap();
  });
}

if (typeof window.commonWhenLeafletReady === 'function') {
  window.commonWhenLeafletReady(initHomepageOrganizationMap);
} else {
  window.addEventListener('load', initHomepageOrganizationMap);
}

const intro = document.getElementById('intro');
const textBlock = intro?.querySelector('.vertical');
const btn = document.getElementById('toggleText');

if (intro && textBlock && btn) {
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
}
