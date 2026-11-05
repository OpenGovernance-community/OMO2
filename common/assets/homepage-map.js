const homepageOrganizationMapRows = window.homepageMapConfig.rows;
const homepageMapToggle = document.getElementById(window.homepageMapConfig.toggleId || '');
const homepageMapPanel = homepageMapToggle
  ? document.getElementById(homepageMapToggle.getAttribute('aria-controls'))
  : null;
let homepageOrganizationMap = null;
const homepageMapLocale = new URLSearchParams(window.location.search).get('lang') || 'fr';
let homepageScoreFormatter;
try {
  homepageScoreFormatter = new Intl.NumberFormat(homepageMapLocale, { minimumFractionDigits: 1, maximumFractionDigits: 1 });
} catch {
  homepageScoreFormatter = new Intl.NumberFormat('fr', { minimumFractionDigits: 1, maximumFractionDigits: 1 });
}
const homepageMapTexts = { current: 'Etat actuel', desired: 'Ambition', maturity: 'Maturit\u00e9 organisationnelle', open: 'Voir le graphique et sa legende', close: 'Fermer', cluster: '{count} organisations : cliquer pour zoomer' };
const homepageMapTextsReady = fetch('/common/jstranslation/homepage_map.php?v=20261104-clusters&lang=' + encodeURIComponent(homepageMapLocale))
  .then((response) => response.ok ? response.json() : {})
  .then((texts) => Object.keys(homepageMapTexts).forEach((key) => {
    if (typeof texts[key] === 'string' && texts[key]) homepageMapTexts[key] = texts[key];
  }))
  .catch(() => {});
function openHomepageMaturityChart(trigger, organizationRow) {
  window.commonOpenIframePopup({
    url: trigger.href,
    title: homepageMapTexts.maturity + ' - ' + organizationRow.name,
    closeLabel: homepageMapTexts.close,
    trigger
  });
}

async function initHomepageOrganizationMap() {
  if (!Array.isArray(homepageOrganizationMapRows) || homepageOrganizationMapRows.length === 0) {
    return;
  }

  const mapElement = document.getElementById('homepageOrganizationMap');
  if (!mapElement || (homepageMapPanel && homepageMapPanel.hidden) || typeof window.L === 'undefined') {
    return;
  }
  await homepageMapTextsReady;
  if (homepageMapPanel && homepageMapPanel.hidden) return;
  if (homepageOrganizationMap) {
    homepageOrganizationMap.invalidateSize();
    return;
  }

  const defaultCenter = [46.8182, 8.2275];
  const map = L.map(mapElement, {
    scrollWheelZoom: false,
    maxZoom: 20
  }).setView(defaultCenter, 7);
  homepageOrganizationMap = map;
  const tileState = { layer: null, theme: null };
  if (typeof window.commonBindLeafletTheme === 'function') {
    window.commonBindLeafletTheme(map, tileState);
  }
  const organizationMarkers = typeof L.markerClusterGroup === 'function'
    ? L.markerClusterGroup({
      maxClusterRadius: 60,
      showCoverageOnHover: false,
      zoomToBoundsOnClick: true,
      spiderfyOnMaxZoom: true,
      iconCreateFunction: (cluster) => {
        const count = cluster.getChildCount();
        const label = homepageMapTexts.cluster.replace('{count}', String(count));
        const icon = document.createElement('span');
        icon.textContent = String(count);
        icon.setAttribute('aria-label', label);
        cluster.options.title = label;
        return L.divIcon({ html: icon, className: 'home-organization-map__cluster', iconSize: [44, 44] });
      }
    }).addTo(map)
    : L.layerGroup().addTo(map);

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
    }).addTo(organizationMarkers);

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
    const maturity = organizationRow.maturity;
    if (maturity && Number.isFinite(maturity.todayAverage) && Number.isFinite(maturity.tomorrowAverage)) {
      const scores = document.createElement('a');
      scores.className = 'home-organization-map__maturity';
      scores.href = '/survey/organization.php?id=' + encodeURIComponent(organizationRow.id) + '&chart=1&lang=' + encodeURIComponent(homepageMapLocale);
      scores.title = homepageMapTexts.open;
      const maturityTitle = document.createElement('span');
      maturityTitle.className = 'home-organization-map__maturity-title';
      maturityTitle.textContent = homepageMapTexts.maturity;
      scores.append(maturityTitle);
      [ ['current', maturity.todayAverage], ['desired', maturity.tomorrowAverage] ].forEach(([key, value], index) => {
        if (index) {
          const arrow = document.createElement('span');
          arrow.textContent = '\u2192';
          arrow.setAttribute('aria-hidden', 'true');
          scores.append(arrow);
        }
        const score = document.createElement('span');
        const caption = document.createElement('span');
        caption.textContent = homepageMapTexts[key];
        const number = document.createElement('strong');
        number.textContent = homepageScoreFormatter.format(value);
        score.append(caption, number);
        scores.append(score);
      });
      scores.addEventListener('click', (event) => {
        if (event.ctrlKey || event.metaKey || event.shiftKey || event.altKey || event.button !== 0 || typeof HTMLDialogElement === 'undefined') return;
        event.preventDefault();
        openHomepageMaturityChart(scores, organizationRow);
      });
      popup.append(scores);
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
