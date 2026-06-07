// ============================================================
//  app.js — ядро приложения v4.2 + KANBAN
// ============================================================

const API_URL = 'https://srm.itmag.site/api/api.php';
const API_KEY    = '12345';
const apiHeaders = { 'Content-Type': 'application/json', 'X-Api-Key': API_KEY };

/* ---------- CRM FRAMEWORK ---------- */
window.CRM = {
  _modules: {},

  registerModule(cfg) {
    this._modules[cfg.id] = cfg;
    this._injectPage(cfg);
    this._injectNav(cfg);
    console.log(`✅ Модуль "${cfg.name}" зарегистрирован`);
  },

  _injectPage(cfg) {
    if (document.getElementById('page-' + cfg.id)) return;
    const div       = document.createElement('div');
    div.className   = 'page';
    div.id          = 'page-' + cfg.id;
    div.innerHTML   = cfg.page || '';
    const main      = document.getElementById('mainContent');
    if (main) main.appendChild(div);
  },

  _injectNav(cfg) {
    if (document.getElementById('nav-' + cfg.id)) return;
    const btn       = document.createElement('button');
    btn.className   = 'nav-btn';
    btn.id          = 'nav-' + cfg.id;
    btn.innerHTML   = `<span>${cfg.icon}</span><span>${cfg.name}</span>`;
    btn.onclick     = () => showPage(cfg.id, btn);
    const section   = document.getElementById('modulesNavSection');
    if (section) section.appendChild(btn);
  },

  api(module, action, body = null, params = {}) {
    const qs  = new URLSearchParams({ module, action, key: API_KEY, ...params }).toString();
    const url = `${API_URL}?${qs}`;
    return fetch(url, {
      method:  body ? 'POST' : 'GET',
      headers: apiHeaders,
      body:    body ? JSON.stringify(body) : undefined,
    })
    .then(r => r.json())
    .catch(e => {
      console.warn(`CRM.api(${module}/${action}) error:`, e);
      return { ok: false, error: e.message };
    });
  }
};

/* ---------- beforeunload — ЗАЩИТА ---------- */
window.addEventListener('beforeunload', () => {
  if (dbCache && saveTimer && isLoaded) {
    clearTimeout(saveTimer);
    navigator.sendBeacon(
      `${API_URL}?action=db&key=${API_KEY}`,
      JSON.stringify(dbCache)
    );
  }
});

/* ---------- СЛОЙ ДАННЫХ ---------- */
let dbCache   = null;
let saveTimer = null;
let isSyncing = false;
let isLoaded  = false;

function initDBStructure() {
  return {
    orders:       [],
    finance:      [],
    clients:      [],
    notes:        [],
    warehouse:    [],
    calEvents:    [],
    chatHistory:  [],
    orderCounter: 1,
    salary:       { records: [], employees: [], shifts: [] },
    weborders:    [],
    debts:        [],
    checklists:   { templates: [], sessions: [] },
    printers:     [],
    templates:    [],
    reviews:      { list: [] },
    docs:         { documents: [], folders: [] },
    settings: {
      company:        '',
      inn:            '',
      ogrn:           '',
      address:        '',
      phone:          '',
      email:          '',
      website:        '',
      bankAcc:        '',
      bik:            '',
      bankName:       '',
      korAcc:         '',
      kpp:            '',
      receiptHeader:  'Спасибо за заказ! Ждём вас снова.',
      receiptFooter:  'Сохраняйте чек при получении заказа.',
      signatory:      '',
      signatoryTitle: 'Менеджер',
      vat:            '0',
      currency:       '₽',
      apiKey:         '',
      apiModel:       'deepseek-chat',
      modules:        {},
      tgToken:        '',
      tgBossId:       ''
    }
  };
}

function getDB() {
  if (!dbCache) dbCache = initDBStructure();
  return dbCache;
}

function saveDB(db) {
  dbCache = db;
  updateDBSize();

  try {
    localStorage.setItem('printcrm_backup', JSON.stringify({
      data: db,
      timestamp: Date.now()
    }));
  } catch(e) {}

  if (!isLoaded) {
    console.warn('⚠️ Сервер не отвечает, данные в localStorage');
    showSyncStatus('error');
    return;
  }

  clearTimeout(saveTimer);
  saveTimer = setTimeout(() => pushToServer(db), 800);
  showSyncStatus('saving');
}

async function pushToServer(db) {
  if (isSyncing) return;
  if (!isLoaded) return;

  isSyncing = true;
  showSyncStatus('saving');

  try {
    const res = await fetch(`${API_URL}?action=db&key=${API_KEY}`, {
      method:  'POST',
      headers: apiHeaders,
      body:    JSON.stringify(db)
    });

    if (!res.ok) throw new Error('HTTP ' + res.status);

    const raw = await res.text();
    console.log('💾 Сервер ответил на сохранение:', raw.slice(0, 100));

    showSyncStatus('ok');
  } catch (e) {
    showSyncStatus('error');
    console.warn('Ошибка сохранения на сервер:', e.message);
    notify('⚠️ Не удалось сохранить: ' + e.message, 'error');
  } finally {
    isSyncing = false;
  }
}

async function loadFromServer() {
  showSyncStatus('loading');

  try {
    const res = await fetch(`${API_URL}?action=db&key=${API_KEY}`, {
      headers: apiHeaders
    });

    if (res.ok) {
      const raw = await res.text();

      if (!raw || raw.trim() === 'null' || raw.trim() === '') {
        console.log('📭 База на сервере пуста — создаём новую');
        dbCache  = initDBStructure();
        isLoaded = true;
        showSyncStatus('ok');
        await pushToServer(dbCache);
        return true;
      }

      const jsonMatch = raw.match(/(\{[\s\S]*\}|$$[\s\S]*$$)/);
      if (!jsonMatch) {
        console.warn('⚠️ Сервер вернул не-JSON:', raw.slice(0, 200));
        throw new Error('Не JSON: ' + raw.slice(0, 100));
      }

      const json = JSON.parse(jsonMatch[0]);
      const data = json.data || json;

      if (data && typeof data === 'object' && !Array.isArray(data)) {
        const base = initDBStructure();
        dbCache  = { ...base, ...data };
        isLoaded = true;
        showSyncStatus('ok');
        localStorage.removeItem('printcrm_backup');
        console.log('✅ Загружено с сервера — orders:',
          dbCache.orders?.length, 'finance:', dbCache.finance?.length);
        return true;
      } else {
        console.warn('⚠️ Неверная структура данных:', typeof data, data);
        throw new Error('Неверная структура БД');
      }
    } else {
      throw new Error('HTTP ' + res.status);
    }

  } catch (e) {
    console.warn('Сервер недоступен:', e.message);
  }

  const backupRaw = localStorage.getItem('printcrm_backup');
  if (backupRaw) {
    try {
      const backup = JSON.parse(backupRaw);
      if (backup.data && backup.data.orders) {
        const base = initDBStructure();
        dbCache  = { ...base, ...backup.data };
        isLoaded = false;
        showSyncStatus('error');
        console.log('💾 Восстановлено из localStorage');
        notify('⚠️ Восстановлены данные из локальной копии', 'info');
        return false;
      }
    } catch(e) {
      console.warn('Ошибка чтения backup:', e);
    }
  }

  dbCache  = initDBStructure();
  isLoaded = false;
  showSyncStatus('error');
  return false;
}

function showSyncStatus(status) {
  const el  = document.getElementById('syncStatus');
  if (!el) return;
  const map = {
    loading: ['⟳ Загрузка...',      'var(--accent4)'],
    saving:  ['⟳ Сохранение...',    'var(--accent4)'],
    ok:      ['☁ Синхронизировано', 'var(--accent3)'],
    error:   ['⚠ Ошибка сервера',   'var(--danger)' ],
  };
  const [text, color] = map[status] || ['', ''];
  el.textContent = text;
  el.style.color = color;
}

function updateDBSize() {
  const el   = document.getElementById('dbSizeInfo');
  if (!el) return;
  const size = dbCache ? (JSON.stringify(dbCache).length / 1024).toFixed(1) : '0';
  el.textContent = `БД: ${size} KB`;
}

function clearOldLocalStorage() {
  const OLD_KEYS = ['printcrm_v2', 'printcrm_local_cache', 'printcrm_db', 'printcrm_v1'];
  let cleared = false;
  OLD_KEYS.forEach(k => {
    if (localStorage.getItem(k) !== null) {
      localStorage.removeItem(k);
      cleared = true;
    }
  });
  if (cleared) {
    console.log('🧹 Старый localStorage очищен');
    notify('🧹 Локальный кэш очищен', 'info');
  }
}

/* ---------- ФОНОВАЯ СИНХРОНИЗАЦИЯ ---------- */
setInterval(async () => {
  if (isSyncing || !isLoaded) return;
  try {
    const res = await fetch(`${API_URL}?action=db&key=${API_KEY}`, {
      headers: apiHeaders
    });
    if (!res.ok) return;
    const json = await res.json();
    const data = json.data || json;
    if (!data || typeof data !== 'object') return;

    const incoming = JSON.stringify(data);
    const current  = JSON.stringify(dbCache);

    if (incoming !== current) {
      const base = initDBStructure();
      dbCache = { ...base, ...data };
      showSyncStatus('ok');
      refreshDashboard();
      const activePage = document.querySelector('.page.active')?.id;
      if (activePage === 'page-orders')  renderKanban();
      if (activePage === 'page-finance') renderFinanceTable();
      notify('📡 Данные обновлены', 'info');
    }
  } catch {
    showSyncStatus('error');
  }
}, 30000);

/* ============================================================
   NAVIGATION
============================================================ */
function showPage(name, btn) {
  document.querySelectorAll('.page').forEach(p => p.classList.remove('active'));
  document.querySelectorAll('.nav-btn').forEach(b => b.classList.remove('active'));

  const page = document.getElementById('page-' + name);
  if (page) page.classList.add('active');
  if (btn)  btn.classList.add('active');

  const renderMap = {
    dashboard:  refreshDashboard,
    orders:     renderKanban,
    finance:    renderFinanceTable,
    stats:      renderStats,
    accounting: renderAccounting,
    clients:    renderClients,
    notes:      renderNotes,
    settings:   loadSettings,
    calendar:   renderCalendar,
  };

  if (renderMap[name]) {
    renderMap[name]();
    return;
  }

  /* ── ФИКС: правильное имя хранилища модулей ── */
  const mod = CRM.modules && CRM.modules[name];
  if (mod && typeof mod.render === 'function') {
    Promise.resolve()
      .then(() => mod.render())
      .catch(e => {
        console.error('Ошибка рендера модуля ' + name + ':', e);
        notify && notify('Ошибка модуля «' + name + '»', 'error');
      });
  }
}
/* ============================================================
   MODAL
============================================================ */
function openModal(id) {
  const m = document.getElementById(id);
  if (!m) return;
  m.classList.add('open');
  m.addEventListener('click', e => e.stopPropagation(), { once: false });

  if (id === 'orderModal')   initOrderModal();
  if (id === 'incomeModal')  { const el = document.getElementById('inc_date'); if (el) el.value = nowDTLocal(); }
  if (id === 'expenseModal') { const el = document.getElementById('exp_date'); if (el) el.value = nowDTLocal(); }
}

function closeModal(id) {
  const m = document.getElementById(id);
  if (m) m.classList.remove('open');
}

document.addEventListener('DOMContentLoaded', () => {
  document.querySelectorAll('.modal-overlay').forEach(o => {
    o.addEventListener('click', e => {
      if (e.target === o) o.classList.remove('open');
    });
  });
});

/* ============================================================
   TIME
============================================================ */
function nowDTLocal() {
  const n = new Date(), p = v => String(v).padStart(2, '0');
  return `${n.getFullYear()}-${p(n.getMonth()+1)}-${p(n.getDate())}T${p(n.getHours())}:${p(n.getMinutes())}`;
}

function updateClock() {
  const n      = new Date(), p = v => String(v).padStart(2, '0');
  const days   = ['Вс','Пн','Вт','Ср','Чт','Пт','Сб'];
  const months = ['янв','фев','мар','апр','май','июн','июл','авг','сен','окт','ноя','дек'];
  const tEl    = document.getElementById('clockTime');
  const dEl    = document.getElementById('clockDate');
  if (tEl) tEl.textContent = `${p(n.getHours())}:${p(n.getMinutes())}:${p(n.getSeconds())}`;
  if (dEl) dEl.textContent = `${days[n.getDay()]}, ${n.getDate()} ${months[n.getMonth()]}`;
}
setInterval(updateClock, 1000);
updateClock();

/* ============================================================
   NOTIFICATIONS
============================================================ */
function notify(msg, type = 'info') {
  const icons = { success: '✅', error: '❌', info: '💡' };
  const stack = document.getElementById('notifStack');
  if (!stack) return;
  const el     = document.createElement('div');
  el.className = `notification ${type}`;
  el.innerHTML = `<span>${icons[type] || 'ℹ'}</span><span>${msg}</span>`;
  stack.appendChild(el);
  setTimeout(() => {
    el.style.cssText += 'opacity:0;transform:translateX(20px);transition:all 0.3s;';
    setTimeout(() => el.remove(), 300);
  }, 3500);
}

/* ============================================================
   ORDER MODAL
============================================================ */
let currentOrderFiles = [];
let currentServiceTab = 'photo';
let editingOrderId    = null;

function initOrderModal() {
  const db  = getDB();

  // Если редактирование — не сбрасываем editingOrderId здесь,
  // он будет установлен из editOrder()
  if (!editingOrderId) {
    const num = 'ORD-' + String(db.orderCounter).padStart(5, '0');

    ['ord_num','ord_total','ord_prepay','ord_comment',
     'ord_client','ord_phone','ord_manager'].forEach(id => {
      const el = document.getElementById(id);
      if (el) el.value = '';
    });

    const ordNum      = document.getElementById('ord_num');
    const ordDate     = document.getElementById('ord_date');
    const ordDeadline = document.getElementById('ord_deadline');
    const ordDisp     = document.getElementById('ordTotalDisplay');

    if (ordNum)      ordNum.value      = num;
    if (ordDate)     ordDate.value     = nowDTLocal();
    if (ordDeadline) ordDeadline.value = '';
    if (ordDisp)     ordDisp.textContent = '0 ₽';

    currentOrderFiles = [];

    const filesList = document.getElementById('order_files_list');
    if (filesList) filesList.innerHTML = '';
    const fileInput = document.getElementById('order_files_input');
    if (fileInput) fileInput.value = '';

    document.querySelectorAll('.checkbox-item.checked').forEach(el => {
      el.classList.remove('checked');
      const dot   = el.querySelector('.checkbox-dot');
      const input = el.querySelector('input');
      if (dot)   dot.textContent = '';
      if (input) input.checked   = false;
    });

    switchServiceTab('photo', document.querySelector('.order-service-tab'));
  }

  const dl = document.getElementById('clientsList');
  if (dl) dl.innerHTML = db.clients.map(c => `<option value="${c.name}">`).join('');
}

function switchServiceTab(tabName, btn) {
  currentServiceTab = tabName;
  document.querySelectorAll('.order-service-tab').forEach(b => b.classList.remove('active'));
  if (btn) btn.classList.add('active');
  document.querySelectorAll('.service-tab-content').forEach(t => t.classList.remove('active'));
  const tab = document.getElementById('stab-' + tabName);
  if (tab) tab.classList.add('active');
}

function toggleCheck(label) {
  label.classList.toggle('checked');
  const dot   = label.querySelector('.checkbox-dot');
  const input = label.querySelector('input');
  const on    = label.classList.contains('checked');
  if (dot)   dot.textContent = on ? '✓' : '';
  if (input) input.checked   = on;
}

function selectSize(btn, type) {
  const matrix = btn.closest('.size-matrix') || document.getElementById('sizeMatrix-' + type);
  if (matrix) matrix.querySelectorAll('.size-btn').forEach(b => b.classList.remove('selected'));
  btn.classList.add('selected');
  if (type === 'banner') {
    const m = btn.textContent.trim().match(/([\d.]+)×([\d.]+)/);
    if (m) {
      const bw = document.getElementById('ban_w');
      const bh = document.getElementById('ban_h');
      if (bw) bw.value = m[1];
      if (bh) bh.value = m[2];
      calcBannerArea();
    }
  }
}

function calcBannerArea() {
  const w    = parseFloat(document.getElementById('ban_w')?.value)     || 0;
  const h    = parseFloat(document.getElementById('ban_h')?.value)     || 0;
  const p    = parseFloat(document.getElementById('ban_price')?.value) || 0;
  const q    = parseInt(document.getElementById('ban_qty')?.value)     || 1;
  const area = (w * h).toFixed(2);
  const aEl  = document.getElementById('ban_area');
  const tEl  = document.getElementById('ord_total');
  if (aEl) aEl.value = area;
  if (tEl) tEl.value = (area * p * q).toFixed(0);
  updateTotalDisplay();
}

function calcWideArea() {
  const w    = (parseFloat(document.getElementById('wide_w')?.value)    || 0) / 100;
  const h    = (parseFloat(document.getElementById('wide_h')?.value)    || 0) / 100;
  const p    = parseFloat(document.getElementById('wide_price')?.value) || 0;
  const area = (w * h).toFixed(4);
  const aEl  = document.getElementById('wide_area');
  const tEl  = document.getElementById('ord_total');
  if (aEl) aEl.value = parseFloat(area).toFixed(2);
  if (tEl) tEl.value = (parseFloat(area) * p).toFixed(0);
  updateTotalDisplay();
}

function calcTotal() {
  const fields = {
    photo:    ['photo_qty',  'photo_price'],
    copy:     ['copy_qty',   'copy_price'],
    design:   [null,         'des_price'],
    business: ['biz_qty',    'biz_price'],
    promo:    ['promo_qty',  'promo_price'],
    other:    ['other_qty',  'other_price'],
  };
  const pair = fields[currentServiceTab];
  if (!pair) return;
  const qty   = pair[0] ? (parseInt(document.getElementById(pair[0])?.value)   || 0) : 1;
  const price = parseFloat(document.getElementById(pair[1])?.value) || 0;
  const total = qty * price;
  if (total > 0) {
    const tEl = document.getElementById('ord_total');
    if (tEl) tEl.value = total.toFixed(0);
    updateTotalDisplay();
  }
}

function updateTotalDisplay() {
  const val = parseFloat(document.getElementById('ord_total')?.value) || 0;
  const cur = getDB().settings?.currency || '₽';
  const el  = document.getElementById('ordTotalDisplay');
  if (el) el.textContent = formatMoney(val, cur);
}

function autoResizeTextarea(el) {
  el.style.height = 'auto';
  el.style.height = Math.min(el.scrollHeight, 120) + 'px';
}

/* ============================================================
   РАБОТА С ФАЙЛАМИ В ЗАКАЗЕ
============================================================ */
function handleOrderFiles(files) {
  currentOrderFiles = [];
  for (let i = 0; i < files.length; i++) {
    currentOrderFiles.push(files[i]);
  }
  renderFileList(currentOrderFiles);
}

function renderFileList(files) {
  const container = document.getElementById('order_files_list');
  if (!container) return;

  if (!files.length) {
    container.innerHTML = '<span class="text-muted" style="font-size:0.7rem;">Файлы не выбраны</span>';
    return;
  }

  container.innerHTML = files.map((f, idx) => `
    <div style="background:var(--bg-dark);border-radius:6px;padding:4px 8px;
                display:flex;align-items:center;gap:6px;">
      <span>${f.type?.startsWith('image/') ? '🖼️' : '📄'}</span>
      <span style="font-size:0.7rem;flex:1;overflow:hidden;text-overflow:ellipsis;
                   white-space:nowrap;">${f.name}</span>
      <span style="font-size:0.6rem;color:var(--text-muted);">${formatSize(f.size)}</span>
      <button type="button" class="btn btn-danger btn-xs"
              onclick="removeOrderFile(${idx})">✕</button>
    </div>
  `).join('');
}

function removeOrderFile(idx) {
  currentOrderFiles.splice(idx, 1);
  renderFileList(currentOrderFiles);
  const fileInput = document.getElementById('order_files_input');
  if (fileInput) fileInput.value = '';
}

async function uploadOrderFiles(orderId) {
  const uploaded = [];
  for (const file of currentOrderFiles) {
    const formData = new FormData();
    formData.append('action', 'upload_chunk');
    formData.append('chunk', file);
    formData.append('fileName', file.name);
    formData.append('orderId', orderId);

    try {
      const res = await fetch('https://xn--47-6kca4bza.xn--p1ai/upload.php', {
        method: 'POST',
        body: formData
      });
      const data = await res.json();
      uploaded.push({
        name: file.name,
        size: file.size,
        type: file.type,
        url: data.url || `https://xn--47-6kca4bza.xn--p1ai/uploads/${orderId}_${file.name}`
      });
    } catch(e) {
      console.warn('Ошибка загрузки файла:', file.name, e);
      uploaded.push({
        name: file.name,
        size: file.size,
        type: file.type,
        url: ''
      });
    }
  }
  return uploaded;
}

/* ============================================================
   SAVE ORDER
============================================================ */
async function saveOrder() {
  const db     = getDB();
  const num    = document.getElementById('ord_num')?.value || '';
  const client = document.getElementById('ord_client')?.value.trim() || 'Без имени';
  const total  = parseFloat(document.getElementById('ord_total')?.value) || 0;

  const checkedItems = [];
  const activeTab    = document.querySelector('.service-tab-content.active');
  if (activeTab) {
    activeTab.querySelectorAll('.checkbox-item.checked').forEach(c => {
      checkedItems.push(c.textContent.trim().replace('✓', '').trim());
    });
  }
  const selSize = document.querySelector('.size-btn.selected');

  const orderId = editingOrderId || Date.now();

  let uploadedFiles = [];
  if (currentOrderFiles.length) {
    notify('⏳ Загрузка файлов...', 'info');
    uploadedFiles = await uploadOrderFiles(orderId);
  }

  // Сохраняем доп. поля по типу услуги
  const extraFields = {};
  if (currentServiceTab === 'photo') {
    extraFields.photo_size     = document.querySelector('#sizeMatrix-photo .size-btn.selected')?.textContent.trim() || '';
    extraFields.photo_qty      = document.getElementById('photo_qty')?.value      || '';
    extraFields.photo_material = document.getElementById('photo_material')?.value || '';
    extraFields.photo_price    = document.getElementById('photo_price')?.value    || '';
  }
  if (currentServiceTab === 'copy') {
    extraFields.copy_size  = document.querySelector('#stab-copy .size-btn.selected')?.textContent.trim() || '';
    extraFields.copy_qty   = document.getElementById('copy_qty')?.value   || '';
    extraFields.copy_sides = document.getElementById('copy_sides')?.value || '';
    extraFields.copy_price = document.getElementById('copy_price')?.value || '';
  }
  if (currentServiceTab === 'banner') {
    extraFields.ban_w     = document.getElementById('ban_w')?.value     || '';
    extraFields.ban_h     = document.getElementById('ban_h')?.value     || '';
    extraFields.ban_area  = document.getElementById('ban_area')?.value  || '';
    extraFields.ban_price = document.getElementById('ban_price')?.value || '';
    extraFields.ban_qty   = document.getElementById('ban_qty')?.value   || '';
  }
  if (currentServiceTab === 'wide') {
    extraFields.wide_w     = document.getElementById('wide_w')?.value     || '';
    extraFields.wide_h     = document.getElementById('wide_h')?.value     || '';
    extraFields.wide_area  = document.getElementById('wide_area')?.value  || '';
    extraFields.wide_price = document.getElementById('wide_price')?.value || '';
  }
  if (currentServiceTab === 'business') {
    extraFields.biz_qty   = document.getElementById('biz_qty')?.value   || '';
    extraFields.biz_size  = document.getElementById('biz_size')?.value  || '';
    extraFields.biz_price = document.getElementById('biz_price')?.value || '';
  }
  if (currentServiceTab === 'design') {
    extraFields.des_revisions = document.getElementById('des_revisions')?.value || '';
    extraFields.des_price     = document.getElementById('des_price')?.value     || '';
    extraFields.des_format    = document.getElementById('des_format')?.value    || '';
  }
  if (currentServiceTab === 'promo') {
    extraFields.promo_qty   = document.getElementById('promo_qty')?.value   || '';
    extraFields.promo_price = document.getElementById('promo_price')?.value || '';
  }
  if (currentServiceTab === 'other') {
    extraFields.other_desc  = document.getElementById('other_desc')?.value  || '';
    extraFields.other_qty   = document.getElementById('other_qty')?.value   || '';
    extraFields.other_price = document.getElementById('other_price')?.value || '';
  }

  const order = {
    id:           orderId,
    num,
    client,
    date:         document.getElementById('ord_date')?.value     || '',
    deadline:     document.getElementById('ord_deadline')?.value || '',
    phone:        document.getElementById('ord_phone')?.value    || '',
    manager:      document.getElementById('ord_manager')?.value  || '',
    service:      currentServiceTab,
    serviceLabel: getServiceLabel(currentServiceTab),
    size:         selSize ? selSize.textContent.trim() : '',
    checkedItems,
    bizcat:       document.getElementById('ord_bizcat')?.value   || '',
    status:       document.getElementById('ord_status')?.value   || 'new',
    payment:      document.getElementById('ord_payment')?.value  || 'Наличные',
    comment:      document.getElementById('ord_comment')?.value  || '',
    total,
    prepay:       parseFloat(document.getElementById('ord_prepay')?.value) || 0,
    files:        uploadedFiles,
    source:       'website',
    options:      checkedItems,
    ...extraFields
  };

  if (editingOrderId) {
    const idx = db.orders.findIndex(o => o.id === editingOrderId);
    if (idx !== -1) db.orders[idx] = order;
    notify('Заказ обновлён', 'success');
  } else {
    db.orders.unshift(order);
    db.orderCounter = (db.orderCounter || 1) + 1;
    notify('Заказ создан: ' + num, 'success');
    if (total > 0 && order.payment !== 'Предоплата') {
      db.finance.unshift({
        id:       Date.now() + 1,
        type:     'income',
        date:     order.date,
        category: order.serviceLabel,
        desc:     `Заказ ${num} — ${client}`,
        amount:   total,
        method:   order.payment,
        client,
      });
    }
  }

  editingOrderId = null;
  saveDB(db);
  closeModal('orderModal');
  renderKanban();
  refreshDashboard();
  updateOrdersBadge();
}

function getServiceLabel(tab) {
  return {
    photo:    'Фотопечать',
    copy:     'Копирование/Распечатка',
    banner:   'Баннерная печать',
    design:   'Дизайн',
    business: 'Бизнес-полиграфия',
    wide:     'Широкоформатная печать',
    promo:    'Сувенирная продукция',
    other:    'Прочее',
  }[tab] || tab;
}

/* ============================================================
   ВСПОМОГАТЕЛЬНЫЕ ФУНКЦИИ
============================================================ */
function escapeHtml(str) {
  if (!str) return '';
  return str.replace(/[&<>]/g, function(m) {
    if (m === '&') return '&amp;';
    if (m === '<') return '&lt;';
    if (m === '>') return '&gt;';
    return m;
  });
}

function formatSize(bytes) {
  if (!bytes) return '0 Б';
  if (bytes < 1024) return bytes + ' Б';
  if (bytes < 1048576) return (bytes / 1024).toFixed(1) + ' КБ';
  return (bytes / 1048576).toFixed(1) + ' МБ';
}

function formatDate(str) {
  if (!str) return '—';
  try {
    const d = new Date(str);
    const base = d.toLocaleDateString('ru-RU',
      { day: '2-digit', month: '2-digit', year: 'numeric' });
    return str.includes('T')
      ? base + ' ' + d.toLocaleTimeString('ru-RU',
          { hour: '2-digit', minute: '2-digit' })
      : base;
  } catch {
    return str;
  }
}

function formatMoney(val, currency) {
  const cur = currency || (getDB().settings?.currency || '₽');
  return (parseFloat(val) || 0).toLocaleString('ru-RU') + ' ' + cur;
}

function getStatusBadge(status) {
  const map = {
    new:    '<span class="badge badge-new">Новый</span>',
    work:   '<span class="badge badge-work">В работе</span>',
    ready:  '<span class="badge badge-ready">Готов</span>',
    done:   '<span class="badge badge-done">Выдан</span>',
    cancel: '<span class="badge badge-cancel">Отменён</span>',
  };
  return map[status] || `<span class="badge">${status}</span>`;
}

/* ============================================================
   ФАЙЛЫ В ЗАКАЗЕ (просмотр)
============================================================ */
function renderOrderFilesList(files) {
  if (!files || !files.length) {
    return '<div class="text-muted" style="grid-column:1/-1;text-align:center;padding:20px;">📭 Файлы не приложены</div>';
  }

  return files.map((file) => {
    const isImage = file.type?.startsWith('image/') ||
      file.name?.match(/\.(jpg|jpeg|png|gif|webp)$/i);
    const fileUrl = file.url || '';

    return `
      <div style="background:var(--bg-dark);border-radius:8px;overflow:hidden;
                  border:1px solid var(--border);">
        ${isImage && fileUrl ? `
          <div style="aspect-ratio:1;background:#1a1a1a;display:flex;
                      align-items:center;justify-content:center;cursor:pointer;"
               onclick="window.open('${fileUrl}', '_blank')">
            <img src="${fileUrl}" style="max-width:100%;max-height:100%;object-fit:contain;"
                 onerror="this.style.display='none';
                          this.parentElement.innerHTML='<span style=\\'font-size:2rem;\\'>🖼️</span>'">
          </div>
        ` : `
          <div style="aspect-ratio:1;background:var(--bg-dark);display:flex;
                      align-items:center;justify-content:center;font-size:2rem;">
            ${isImage ? '🖼️' : '📄'}
          </div>
        `}
        <div style="padding:8px;font-size:0.7rem;">
          <div style="overflow:hidden;text-overflow:ellipsis;white-space:nowrap;"
               title="${escapeHtml(file.name)}">
            ${escapeHtml(file.name)}
          </div>
          <div class="text-muted" style="font-size:0.6rem;">${formatSize(file.size)}</div>
          <div style="display:flex;gap:4px;margin-top:6px;">
            ${fileUrl ? `
              <button class="btn btn-secondary btn-xs" style="flex:1;"
                      onclick="window.open('${fileUrl}', '_blank')">👁️</button>
              <button class="btn btn-primary btn-xs" style="flex:1;"
                      onclick="downloadFile('${fileUrl}', '${escapeHtml(file.name)}')">⬇️</button>
            ` : `<span class="text-muted" style="font-size:0.65rem;">Нет URL</span>`}
          </div>
        </div>
      </div>
    `;
  }).join('');
}

function downloadFile(url, filename) {
  const a = document.createElement('a');
  a.href = url;
  a.download = filename;
  a.target = '_blank';
  document.body.appendChild(a);
  a.click();
  document.body.removeChild(a);
}

async function downloadAllOrderFiles(orderId) {
  const db = getDB();
  const order = db.orders.find(o => o.id === orderId);
  if (!order || !order.files || !order.files.length) {
    notify('Нет файлов для скачивания', 'error');
    return;
  }

  notify('⏳ Создаю архив...', 'info');

  try {
    if (typeof JSZip === 'undefined') {
      await new Promise((resolve, reject) => {
        const script = document.createElement('script');
        script.src = 'https://cdnjs.cloudflare.com/ajax/libs/jszip/3.10.1/jszip.min.js';
        script.onload = resolve;
        script.onerror = () => reject(new Error('Не удалось загрузить JSZip'));
        document.head.appendChild(script);
      });
    }

    const zip = new JSZip();
    let loaded = 0;

    for (const file of order.files) {
      if (!file.url) continue;
      try {
        const response = await fetch(file.url);
        if (!response.ok) throw new Error(`HTTP ${response.status}`);
        const blob = await response.blob();
        zip.file(file.name, blob);
        loaded++;
      } catch (e) {
        console.warn(`Не удалось загрузить ${file.name}:`, e);
        zip.file(file.name + '.error.txt',
          `Не удалось загрузить файл: ${e.message}`);
      }
    }

    if (loaded === 0) {
      notify('❌ Не удалось загрузить ни одного файла', 'error');
      return;
    }

    const content = await zip.generateAsync({ type: 'blob' });
    const link = document.createElement('a');
    link.href = URL.createObjectURL(content);
    link.download = `order_${order.num}_files.zip`;
    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);
    URL.revokeObjectURL(link.href);

    notify(`✅ Архив создан: ${loaded} файлов`, 'success');
  } catch (e) {
    console.error('Ошибка создания архива:', e);
    notify('❌ Ошибка создания архива: ' + e.message, 'error');
  }
}

/* ============================================================
   МОДАЛКА ПРОСМОТРА ЗАКАЗА (legacy openOrderDetails)
============================================================ */
window.openOrderDetails = function(id) {
  const db = getDB();
  const order = db.orders.find(o => o.id === id);
  if (!order) { notify('Заказ не найден', 'error'); return; }

  const oldModal = document.getElementById('orderDetailsModal');
  if (oldModal) oldModal.remove();

  const filesHtml = renderOrderFilesList(order.files || []);

  const modalHtml = `
    <div class="modal-overlay" id="orderDetailsModal">
      <div class="modal modal-lg">
        <div class="modal-header">
          <div class="modal-title">📋 Заказ ${escapeHtml(order.num)}</div>
          <button class="modal-close" onclick="closeModal('orderDetailsModal')">✕</button>
        </div>
        <div style="padding:20px;">
          <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;margin-bottom:20px;">
            <div><span class="text-muted">Клиент:</span>
              <b>${escapeHtml(order.client)}</b></div>
            <div><span class="text-muted">Телефон:</span>
              <b>${escapeHtml(order.phone || '—')}</b></div>
            <div><span class="text-muted">Услуга:</span>
              <b>${escapeHtml(order.serviceLabel || '—')}</b></div>
            <div><span class="text-muted">Сумма:</span>
              <b class="text-green">${formatMoney(order.total)}</b></div>
            <div><span class="text-muted">Статус:</span>
              ${getStatusBadge(order.status)}</div>
            <div><span class="text-muted">Дата:</span>
              ${formatDate(order.date)}</div>
            ${order.deadline
              ? `<div><span class="text-muted">Срок:</span>
                  <b>${formatDate(order.deadline)}</b></div>`
              : ''}
          </div>
          <div class="section-label">📸 Файлы заказа (${order.files?.length || 0})</div>
          <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(140px,1fr));
                      gap:12px;margin-bottom:20px;">
            ${filesHtml}
          </div>
          ${order.comment ? `
            <div class="section-label">💬 Комментарий</div>
            <div style="background:var(--bg-dark);padding:12px;
                        border-radius:8px;margin-top:8px;">
              ${escapeHtml(order.comment)}
            </div>
          ` : ''}
          <div class="modal-footer">
            <button class="btn btn-secondary"
                    onclick="closeModal('orderDetailsModal')">Закрыть</button>
            ${order.files?.length ? `
              <button class="btn btn-primary"
                      onclick="downloadAllOrderFiles(${order.id})">
                📦 Скачать все (ZIP)
              </button>
            ` : ''}
            <button class="btn btn-success" onclick="printSingleOrder(${order.id})">
              🖨️ Печать
            </button>
          </div>
        </div>
      </div>
    </div>
  `;

  document.body.insertAdjacentHTML('beforeend', modalHtml);
  const newModal = document.getElementById('orderDetailsModal');
  if (newModal) {
    newModal.classList.add('open');
    newModal.addEventListener('click', e => {
      if (e.target === newModal) closeModal('orderDetailsModal');
    });
  }
};

/* ============================================================
   ██╗  ██╗ █████╗ ███╗  ██╗██████╗  █████╗ ███╗  ██╗
   ██║ ██╔╝██╔══██╗████╗ ██║██╔══██╗██╔══██╗████╗ ██║
   █████╔╝ ███████║██╔██╗██║██████╦╝███████║██╔██╗██║
   ██╔═██╗ ██╔══██║██║╚████║██╔══██╗██╔══██║██║╚████║
   ██║ ╚██╗██║  ██║██║ ╚███║██████╦╝██║  ██║██║ ╚███║
   ╚═╝  ╚═╝╚═╝  ╚═╝╚═╝  ╚══╝╚═════╝ ╚═╝  ╚═╝╚═╝  ╚══╝
============================================================ */

window.KB_STATUSES = ['new','work','ready','done','cancel'];
window.KB_SERVICE_LABELS = {
  photo:'📸 Фото', copy:'🖨️ Копи', banner:'🏳️ Баннер',
  design:'🎨 Дизайн', business:'💼 Бизнес',
  wide:'🖼️ Широкий', promo:'🎁 Сувенирка', other:'⚙️ Прочее'
};
window.KB_STATUS_LABELS = {
  new:'🆕 Новый',  work:'⚙️ В работе',
  ready:'✅ Готов', done:'📦 Выдан', cancel:'❌ Отменён'
};
window.draggedOrderId = null;

const KB_SERVICE_LABELS = {
  photo:    '📸 Фото',
  copy:     '🖨️ Копи',
  banner:   '🏳️ Баннер',
  design:   '🎨 Дизайн',
  business: '💼 Бизнес',
  wide:     '🖼️ Широкий',
  promo:    '🎁 Сувенирка',
  other:    '⚙️ Прочее'
};

const KB_STATUS_LABELS = {
  new:    '🆕 Новый',
  work:   '⚙️ В работе',
  ready:  '✅ Готов',
  done:   '📦 Выдан',
  cancel: '❌ Отменён'
};

let draggedOrderId = null;

/* ---------- RENDER KANBAN ---------- */
function renderKanban() {
  const search = (document.getElementById('orderSearch')?.value || '').toLowerCase();
  const svcF   = document.getElementById('orderServiceFilter')?.value || '';

  let orders = (getDB().orders || []).filter(o => {
    const matchSearch = !search ||
      (o.num     || '').toLowerCase().includes(search) ||
      (o.client  || '').toLowerCase().includes(search) ||
      (o.comment || '').toLowerCase().includes(search);
    const matchSvc = !svcF || o.service === svcF;
    return matchSearch && matchSvc;
  });

  // Сброс колонок
  KB_STATUSES.forEach(st => {
    const col = document.getElementById('kbCol_' + st);
    if (col) col.innerHTML = '';
  });

  const counts = { new: 0, work: 0, ready: 0, done: 0, cancel: 0 };
  let   total  = 0;

  // Сортировка: новые сверху
  orders = [...orders].sort((a, b) =>
    new Date(b.date || 0) - new Date(a.date || 0)
  );

  orders.forEach(order => {
    const st  = order.status || 'new';
    const col = document.getElementById('kbCol_' + st);
    if (!col) return;
    counts[st] = (counts[st] || 0) + 1;
    total += Number(order.total) || 0;
    col.appendChild(buildKanbanCard(order));
  });

  // Empty states
  KB_STATUSES.forEach(st => {
    const col = document.getElementById('kbCol_' + st);
    if (col && col.children.length === 0) {
      col.innerHTML = '<div class="kanban-empty">Нет заказов</div>';
    }
  });

  // Badges / stat pills
  KB_STATUSES.forEach(st => {
    const badge = document.getElementById('kbBadge_' + st);
    if (badge) badge.textContent = counts[st] || 0;
    const pill = document.getElementById('kbCount_' + st);
    if (pill) pill.textContent = counts[st] || 0;
  });

  const totalEl = document.getElementById('kbTotalSum');
  if (totalEl) totalEl.textContent = formatMoney(total);

  updateOrdersBadge();
}

/* ---------- BUILD KANBAN CARD ---------- */
function buildKanbanCard(order) {
  const card = document.createElement('div');
  card.className = 'kb-card';
  card.setAttribute('draggable', 'true');
  card.setAttribute('data-id', order.id);
  card.setAttribute('data-status', order.status || 'new');

  // Deadline badge
  let deadlineBadge = '';
  if (order.deadline) {
    const diff = new Date(order.deadline) - new Date();
    const h    = diff / 3600000;
    if (h < 0)
      deadlineBadge = `<span class="kb-deadline-badge kb-deadline-over">⚠ Просрочен</span>`;
    else if (h < 24)
      deadlineBadge = `<span class="kb-deadline-badge kb-deadline-warning">⏰ ${Math.ceil(h)}ч</span>`;
    else
      deadlineBadge = `<span class="kb-deadline-badge kb-deadline-ok">📅 ${Math.ceil(h / 24)}д</span>`;
  }

  const svc    = order.service || 'other';
  const svcLbl = KB_SERVICE_LABELS[svc] || svc;

  card.innerHTML = `
    <!-- Кнопки действий -->
    <div class="kb-card-actions">
      <button class="kb-action-btn" title="Открыть"
        onclick="openOrderDetail(event,${order.id})">👁</button>
      <button class="kb-action-btn" title="Сменить статус"
        onclick="toggleKbStatusMenu(event,${order.id})">⇄</button>
      <button class="kb-action-btn" title="Редактировать"
        onclick="editOrderKb(event,${order.id})">✏</button>
    </div>

    <!-- Quick status menu -->
    <div class="kb-status-menu" id="kbMenu_${order.id}">
      ${KB_STATUSES.map(st => `
        <div class="kb-status-opt"
             onclick="changeKbOrderStatus(${order.id},'${st}',event)">
          ${KB_STATUS_LABELS[st]}
        </div>
      `).join('')}
    </div>

    <!-- Header -->
    <div class="kb-card-head">
      <span class="kb-card-num">${escapeHtml(order.num || '#—')}</span>
      <span class="kb-card-service kb-svc-${svc}">${svcLbl}</span>
    </div>

    <!-- Client -->
    <div class="kb-card-client">
      ${escapeHtml(order.client || '👤 Анонимный клиент')}
    </div>

    <!-- Desc -->
    <div class="kb-card-desc">${buildKbOrderDesc(order)}</div>

    <!-- Footer -->
    <div class="kb-card-foot">
      <div class="kb-card-price">${formatMoney(order.total)}</div>
      <div class="kb-card-meta">
        <span class="kb-card-date">
          <svg width="10" height="10" fill="none" stroke="currentColor"
               stroke-width="2" viewBox="0 0 24 24">
            <circle cx="12" cy="12" r="10"/>
            <polyline points="12 6 12 12 16 14"/>
          </svg>
          ${fmtDateShort(order.date)}
        </span>
        ${deadlineBadge}
      </div>
    </div>
  `;

  /* Drag events */
  card.addEventListener('dragstart', e => {
    draggedOrderId = order.id;
    setTimeout(() => card.classList.add('dragging'), 0);
    e.dataTransfer.effectAllowed = 'move';
    e.dataTransfer.setData('text/plain', String(order.id));
  });
  card.addEventListener('dragend', () => {
    card.classList.remove('dragging');
    draggedOrderId = null;
  });

  /* Click → open detail */
  card.addEventListener('click', e => {
    if (e.target.closest('.kb-card-actions') ||
        e.target.closest('.kb-status-menu')) return;
    openOrderDetail(e, order.id);
  });

  return card;
}

/* ---------- DRAG & DROP ---------- */
function highlightDrop(el) {
  el.classList.add('drag-over');
}
function unhighlightDrop(el) {
  el.classList.remove('drag-over');
}
function dropCard(event, newStatus) {
  event.preventDefault();
  unhighlightDrop(event.currentTarget);
  const id = event.dataTransfer.getData('text/plain') || draggedOrderId;
  if (!id) return;
  const db    = getDB();
  const order = db.orders.find(o => String(o.id) === String(id));
  if (!order || order.status === newStatus) return;
  order.status = newStatus;
  saveDB(db);
  renderKanban();
  notify(`Заказ ${order.num} → ${KB_STATUS_LABELS[newStatus]}`, 'success');
}

/* ---------- QUICK STATUS MENU ---------- */
function toggleKbStatusMenu(e, id) {
  e.stopPropagation();
  document.querySelectorAll('.kb-status-menu.open').forEach(m => {
    if (m.id !== 'kbMenu_' + id) m.classList.remove('open');
  });
  const menu = document.getElementById('kbMenu_' + id);
  if (menu) menu.classList.toggle('open');
}

function changeKbOrderStatus(id, newStatus, e) {
  if (e) e.stopPropagation();
  document.querySelectorAll('.kb-status-menu.open')
    .forEach(m => m.classList.remove('open'));
  const db    = getDB();
  const order = db.orders.find(o => String(o.id) === String(id));
  if (!order) return;
  order.status = newStatus;
  saveDB(db);
  renderKanban();
  // Если деталь открыта — обновляем её
  const overlay = document.getElementById('orderDetailOverlay');
  if (overlay?.classList.contains('open')) {
    openOrderDetail(null, id);
  }
  notify(`Заказ ${order.num} → ${KB_STATUS_LABELS[newStatus]}`, 'success');
}

// Закрытие меню при клике вне
document.addEventListener('click', () => {
  document.querySelectorAll('.kb-status-menu.open')
    .forEach(m => m.classList.remove('open'));
});

/* ---------- EDIT ORDER FROM KANBAN ---------- */
function editOrderKb(e, id) {
  if (e) e.stopPropagation();
  editOrder(Number(id));
}

/* ---------- ORDER DETAIL MODAL ---------- */
function openOrderDetail(e, id) {
  if (e) e.stopPropagation();
  const db    = getDB();
  const order = db.orders.find(o => String(o.id) === String(id));
  if (!order) return;

  // Создаём оверлей если нет
  let overlay = document.getElementById('orderDetailOverlay');
  if (!overlay) {
    overlay = document.createElement('div');
    overlay.className = 'order-detail-overlay';
    overlay.id = 'orderDetailOverlay';
    overlay.innerHTML = '<div class="order-detail-modal" id="orderDetailModal"></div>';
    overlay.addEventListener('click', ev => {
      if (ev.target === overlay) closeOrderDetail();
    });
    document.body.appendChild(overlay);
  }

  const modal  = document.getElementById('orderDetailModal');
  const svc    = order.service || 'other';
  const svcLbl = KB_SERVICE_LABELS[svc] || svc;
  const st     = order.status || 'new';

  const iconBg = {
    new:    'linear-gradient(135deg,#6366f1,#a78bfa)',
    work:   'linear-gradient(135deg,#f59e0b,#fbbf24)',
    ready:  'linear-gradient(135deg,#10b981,#34d399)',
    done:   'linear-gradient(135deg,#06b6d4,#22d3ee)',
    cancel: 'linear-gradient(135deg,#ef4444,#f87171)',
  }[st] || '';

  const svcIcon = {
    photo: '📸', copy: '🖨️', banner: '🏳️', design: '🎨',
    business: '💼', wide: '🖼️', promo: '🎁', other: '⚙️'
  }[svc] || '📋';

  // Progress steps
  const stepsHtml = KB_STATUSES.map(s => `
    <div class="od-status-step ${s === st ? 'active-' + s : ''}"
         onclick="changeKbOrderStatus(${order.id},'${s}',null);
                  setTimeout(()=>openOrderDetail(null,${order.id}),200)">
      ${KB_STATUS_LABELS[s]}
    </div>
  `).join('');

  // Параметры заказа
  const params = buildKbOrderParams(order);
  const chips  = params.map(p => `<span class="od-chip">${escapeHtml(p)}</span>`).join('');

  modal.innerHTML = `
    <!-- HEADER -->
    <div class="od-header">
      <div class="od-icon-wrap" style="background:${iconBg};">${svcIcon}</div>
      <div class="od-titles">
        <div class="od-order-num">Заказ ${escapeHtml(order.num || '#—')}</div>
        <div class="od-client-name">
          ${escapeHtml(order.client || 'Анонимный клиент')}
        </div>
        <div class="od-sub">
          ${svcLbl} &bull; ${formatDate(order.date)}
        </div>
      </div>
      <button class="od-close" onclick="closeOrderDetail()">✕</button>
    </div>

    <!-- BODY -->
    <div class="od-body">

      <!-- STATUS STEPPER -->
      <div class="od-section-title">Статус заказа</div>
      <div class="od-status-bar">${stepsHtml}</div>

      <!-- KEY INFO -->
      <div class="od-grid-3">
        <div class="od-info-block">
          <div class="od-info-label">💰 Сумма</div>
          <div class="od-info-val price">${formatMoney(order.total)}</div>
        </div>
        <div class="od-info-block">
          <div class="od-info-label">💵 Предоплата</div>
          <div class="od-info-val" style="color:var(--accent3);">
            ${order.prepay ? formatMoney(order.prepay) : '—'}
          </div>
        </div>
        <div class="od-info-block">
          <div class="od-info-label">💳 Оплата</div>
          <div class="od-info-val">${escapeHtml(order.payment || '—')}</div>
        </div>
      </div>

      <div class="od-grid">
        <div class="od-info-block">
          <div class="od-info-label">📞 Телефон</div>
          <div class="od-info-val phone">${escapeHtml(order.phone || '—')}</div>
        </div>
        <div class="od-info-block">
          <div class="od-info-label">👔 Менеджер</div>
          <div class="od-info-val">${escapeHtml(order.manager || '—')}</div>
        </div>
        <div class="od-info-block">
          <div class="od-info-label">🕐 Принят</div>
          <div class="od-info-val">${formatDate(order.date)}</div>
        </div>
        <div class="od-info-block">
          <div class="od-info-label">⏰ Дедлайн</div>
          <div class="od-info-val"
               style="color:${getDeadlineColor(order.deadline)};">
            ${order.deadline ? formatDate(order.deadline) : '—'}
          </div>
        </div>
      </div>

      <!-- PARAMS CHIPS -->
      ${chips ? `
        <div class="od-section-title">Параметры заказа</div>
        <div class="od-params-chips">${chips}</div>
      ` : ''}

      <!-- FILES -->
      ${order.files?.length ? `
        <div class="od-section-title">📎 Файлы (${order.files.length})</div>
        <div style="display:grid;
                    grid-template-columns:repeat(auto-fill,minmax(110px,1fr));
                    gap:8px;margin-bottom:14px;">
          ${renderOrderFilesList(order.files)}
        </div>
      ` : ''}

      <!-- COMMENT -->
      ${order.comment ? `
        <div class="od-comment-box">
          <strong>💬 Комментарий:</strong><br>
          ${escapeHtml(order.comment)}
        </div>
      ` : ''}

    </div>

    <!-- FOOTER ACTIONS -->
    <div class="od-actions">
      <button class="od-btn od-btn-edit"
              onclick="editOrderKb(null,${order.id});closeOrderDetail();">
        ✏️ Редактировать
      </button>
      <button class="od-btn od-btn-print"
              onclick="printSingleOrder(${order.id})">
        🖨️ Чек клиенту
      </button>
      ${st !== 'done' ? `
        <button class="od-btn od-btn-done"
                onclick="changeKbOrderStatus(${order.id},'done',null);
                         closeOrderDetail();">
          ✅ Выдать заказ
        </button>
      ` : ''}
      ${st !== 'work' ? `
        <button class="od-btn od-btn-edit"
                style="background:rgba(245,158,11,0.2);
                       border-color:rgba(245,158,11,0.4);color:#fbbf24;"
                onclick="changeKbOrderStatus(${order.id},'work',null);
                         closeOrderDetail();">
          ⚙️ В работу
        </button>
      ` : ''}
      <button class="od-btn od-btn-delete"
              onclick="deleteOrderKb(${order.id})">
        🗑️ Удалить
      </button>
    </div>
  `;

  requestAnimationFrame(() => overlay.classList.add('open'));
}

function closeOrderDetail() {
  const overlay = document.getElementById('orderDetailOverlay');
  if (!overlay) return;
  overlay.classList.remove('open');
}

function deleteOrderKb(id) {
  if (!confirm('Удалить заказ?')) return;
  const db  = getDB();
  db.orders = db.orders.filter(o => String(o.id) !== String(id));
  saveDB(db);
  closeOrderDetail();
  renderKanban();
  refreshDashboard();
  notify('Заказ удалён', 'error');
}

/* ---------- KANBAN HELPERS ---------- */
function buildKbOrderDesc(order) {
  const parts = [];
  const svc = order.service;
  if (svc === 'photo'  && order.photo_size) parts.push(order.photo_size);
  if (svc === 'copy'   && order.copy_size)  parts.push(order.copy_size);
  if (svc === 'banner' && order.ban_w)      parts.push(`${order.ban_w}×${order.ban_h}м`);
  if (svc === 'wide'   && order.wide_w)     parts.push(`${order.wide_w}×${order.wide_h}см`);
  if (svc === 'other'  && order.other_desc) parts.push(order.other_desc);
  if (order.bizcat)  parts.push(order.bizcat);
  if (order.comment) parts.push(order.comment.substring(0, 60));
  return escapeHtml(parts.join(' · ') || '—');
}

function buildKbOrderParams(order) {
  const p   = [];
  const svc = order.service;
  if (svc === 'photo') {
    if (order.photo_size)     p.push('📐 ' + order.photo_size);
    if (order.photo_qty)      p.push('×' + order.photo_qty + ' шт');
    if (order.photo_material) p.push(order.photo_material);
  }
  if (svc === 'copy') {
    if (order.copy_size)  p.push(order.copy_size);
    if (order.copy_qty)   p.push(order.copy_qty + ' листов');
    if (order.copy_sides) p.push(order.copy_sides);
  }
  if (svc === 'banner') {
    if (order.ban_w) p.push(`${order.ban_w}×${order.ban_h} м`);
    if (order.ban_qty > 1) p.push(order.ban_qty + ' шт');
  }
  if (svc === 'business') {
    if (order.biz_qty)  p.push(order.biz_qty + ' шт');
    if (order.biz_size) p.push(order.biz_size);
  }
  if (order.bizcat)  p.push('🏷️ ' + order.bizcat);
  if (order.payment) p.push('💳 ' + order.payment);
  if (order.options && order.options.length > 0)
    order.options.forEach(opt => p.push(opt));
  return p;
}

function getDeadlineColor(deadline) {
  if (!deadline) return 'var(--text-muted)';
  const diff = new Date(deadline) - new Date();
  if (diff < 0)        return '#f87171';
  if (diff < 86400000) return '#fbbf24';
  return '#34d399';
}

function fmtDateShort(iso) {
  if (!iso) return '—';
  const d = new Date(iso);
  return d.toLocaleString('ru-RU', {
    day: '2-digit', month: '2-digit',
    hour: '2-digit', minute: '2-digit'
  });
}

/* ============================================================
   RENDER ORDERS TABLE (legacy — вызываем канбан)
============================================================ */
function renderOrdersTable() {
  renderKanban();
}

/* ---------- Edit / Delete / Status (legacy совместимость) ---------- */
function editOrder(id) {
  const db    = getDB();
  const order = db.orders.find(o => o.id === id);
  if (!order) return;

  editingOrderId = id;
  openModal('orderModal');

  setTimeout(() => {
    const set = (elId, val) => {
      const e = document.getElementById(elId);
      if (e) e.value = val;
    };
    set('ord_num',      order.num);
    set('ord_date',     order.date);
    set('ord_deadline', order.deadline || '');
    set('ord_client',   order.client);
    set('ord_phone',    order.phone   || '');
    set('ord_manager',  order.manager || '');
    set('ord_status',   order.status);
    set('ord_payment',  order.payment || 'Наличные');
    set('ord_comment',  order.comment || '');
    set('ord_total',    order.total);
    set('ord_prepay',   order.prepay  || 0);
    updateTotalDisplay();

    const btn = document.querySelector(
      `.order-service-tab[onclick*="'${order.service}'"]`
    );
    if (btn) switchServiceTab(order.service, btn);

    currentOrderFiles = [];
    renderFileList([]);
  }, 100);
}

function deleteOrder(id) {
  if (!confirm('Удалить заказ?')) return;
  const db  = getDB();
  db.orders = db.orders.filter(o => o.id !== id);
  saveDB(db);
  renderKanban();
  refreshDashboard();
  notify('Заказ удалён', 'error');
}

function changeOrderStatus(id) {
  const db    = getDB();
  const order = db.orders.find(o => o.id === id);
  if (!order) return;
  const list   = ['new', 'work', 'ready', 'done', 'cancel'];
  order.status = list[(list.indexOf(order.status) + 1) % list.length];
  saveDB(db);
  renderKanban();
  notify('Статус: ' + order.status, 'info');
}

function updateOrdersBadge() {
  const db     = getDB();
  const active = (db.orders || []).filter(
    o => o.status === 'new' || o.status === 'work'
  ).length;
  const badge  = document.getElementById('ordersNavBadge');
  if (!badge) return;
  badge.textContent   = active;
  badge.style.display = active > 0 ? '' : 'none';
}

/* ============================================================
   FINANCE
============================================================ */
function saveIncome() {
  const amount = parseFloat(document.getElementById('inc_amount')?.value);
  if (!amount || amount <= 0) { notify('Укажите сумму', 'error'); return; }
  const db = getDB();
  db.finance.unshift({
    id:       Date.now(),
    type:     'income',
    date:     document.getElementById('inc_date')?.value   || nowDTLocal(),
    category: document.getElementById('inc_cat')?.value    || '',
    desc:     document.getElementById('inc_desc')?.value   || '',
    amount,
    method:   document.getElementById('inc_method')?.value || '',
    client:   document.getElementById('inc_client')?.value || '',
  });
  saveDB(db);
  closeModal('incomeModal');
  notify('Доход: ' + formatMoney(amount), 'success');
  refreshDashboard();
  if (document.getElementById('page-finance')?.classList.contains('active'))
    renderFinanceTable();
}

function saveExpense() {
  const amount = parseFloat(document.getElementById('exp_amount')?.value);
  if (!amount || amount <= 0) { notify('Укажите сумму', 'error'); return; }
  const db = getDB();
  db.finance.unshift({
    id:       Date.now(),
    type:     'expense',
    date:     document.getElementById('exp_date')?.value   || nowDTLocal(),
    category: document.getElementById('exp_cat')?.value    || '',
    desc:     document.getElementById('exp_desc')?.value   || '',
    amount,
    method:   document.getElementById('exp_method')?.value || '',
  });
  saveDB(db);
  closeModal('expenseModal');
  notify('Расход: ' + formatMoney(amount), 'error');
  refreshDashboard();
  if (document.getElementById('page-finance')?.classList.contains('active'))
    renderFinanceTable();
}

function renderFinanceTable() {
  const db     = getDB();
  const search = (document.getElementById('finSearch')?.value || '').toLowerCase();
  const type   = document.getElementById('finTypeFilter')?.value || '';
  const now    = new Date();

  let items = [...(db.finance || [])];
  if (search) items = items.filter(i =>
    (i.desc     || '').toLowerCase().includes(search) ||
    (i.category || '').toLowerCase().includes(search)
  );
  if (type) items = items.filter(i => i.type === type);

  const monthItems = (db.finance || []).filter(i => {
    const d = new Date(i.date);
    return d.getMonth() === now.getMonth() &&
           d.getFullYear() === now.getFullYear();
  });
  const income  = monthItems.filter(i => i.type === 'income')
    .reduce((a, b) => a + b.amount, 0);
  const expense = monthItems.filter(i => i.type === 'expense')
    .reduce((a, b) => a + b.amount, 0);

  const s = (id, v) => {
    const e = document.getElementById(id);
    if (e) e.textContent = v;
  };
  s('finIncomeTotal',  formatMoney(income));
  s('finExpenseTotal', formatMoney(expense));
  s('finProfitTotal',  formatMoney(income - expense));

  const tbody = document.getElementById('financeTableBody');
  if (!tbody) return;

  if (!items.length) {
    tbody.innerHTML = '<tr><td colspan="7"><div class="empty-state">' +
      '<div class="icon">💰</div><div class="title">Операций нет</div>' +
      '</div></td></tr>';
    return;
  }

  tbody.innerHTML = items.map(i => `
    <tr>
      <td style="white-space:nowrap;">${formatDate(i.date)}</td>
      <td><span class="badge ${i.type === 'income' ? 'badge-income' : 'badge-expense'}">
        ${i.type === 'income' ? '↑ Доход' : '↓ Расход'}</span></td>
      <td>${i.category || '—'}</td>
      <td>${i.desc     || '—'}</td>
      <td style="font-weight:700;
          color:${i.type === 'income' ? 'var(--accent3)' : 'var(--danger)'};">
        ${i.type === 'income' ? '+' : '−'}${formatMoney(i.amount)}
      </td>
      <td>${i.method   || '—'}</td>
      <td><button class="btn btn-danger btn-xs"
                  onclick="deleteFinance(${i.id})">🗑️</button></td>
    </tr>
  `).join('');
}

function deleteFinance(id) {
  if (!confirm('Удалить запись?')) return;
  const db   = getDB();
  db.finance = db.finance.filter(f => f.id !== id);
  saveDB(db);
  renderFinanceTable();
  refreshDashboard();
}

/* ============================================================
   CLIENTS
============================================================ */
function saveClient() {
  const name = document.getElementById('cli_name')?.value.trim();
  if (!name) { notify('Введите имя клиента', 'error'); return; }
  const db = getDB();
  db.clients.unshift({
    id:       Date.now(),
    name,
    type:     document.getElementById('cli_type')?.value     || '',
    phone:    document.getElementById('cli_phone')?.value    || '',
    email:    document.getElementById('cli_email')?.value    || '',
    bizcat:   document.getElementById('cli_bizcat')?.value   || '',
    address:  document.getElementById('cli_address')?.value  || '',
    inn:      document.getElementById('cli_inn')?.value      || '',
    discount: parseInt(document.getElementById('cli_discount')?.value) || 0,
    notes:    document.getElementById('cli_notes')?.value    || '',
    created:  new Date().toISOString(),
  });
  saveDB(db);
  closeModal('clientModal');
  renderClients();
  notify('Клиент добавлен: ' + name, 'success');
}

function renderClients() {
  const db     = getDB();
  const search = (document.getElementById('clientSearch')?.value || '').toLowerCase();
  let clients  = [...(db.clients || [])];
  if (search) clients = clients.filter(c =>
    (c.name  || '').toLowerCase().includes(search) ||
    (c.phone || '').includes(search) ||
    (c.email || '').toLowerCase().includes(search)
  );

  const grid = document.getElementById('clientsGrid');
  if (!grid) return;

  if (!clients.length) {
    grid.innerHTML = '<div class="empty-state card" style="grid-column:1/-1;">' +
      '<div class="icon">👥</div><div class="title">Клиентов нет</div></div>';
    return;
  }

  const orderCount = name => (db.orders || []).filter(o => o.client === name).length;
  const totalSpent = name => (db.orders || [])
    .filter(o => o.client === name && o.status === 'done')
    .reduce((a, b) => a + (b.total || 0), 0);

  grid.innerHTML = clients.map(c => `
    <div class="card">
      <div style="display:flex;gap:10px;margin-bottom:12px;">
        <div class="client-avatar">${(c.name || '?').charAt(0).toUpperCase()}</div>
        <div style="flex:1;min-width:0;">
          <div style="font-weight:700;font-size:0.9rem;overflow:hidden;
                      text-overflow:ellipsis;white-space:nowrap;">
            ${escapeHtml(c.name)}
          </div>
          <div class="text-xs text-muted">${c.type || ''}</div>
        </div>
        ${c.discount > 0 ? `
          <span style="background:rgba(245,158,11,0.2);color:var(--accent4);
                       border-radius:6px;padding:2px 6px;font-size:0.7rem;font-weight:700;">
            −${c.discount}%
          </span>` : ''}
      </div>
      ${c.phone  ? `<div class="text-sm" style="margin-bottom:4px;">📞 ${escapeHtml(c.phone)}</div>` : ''}
      ${c.email  ? `<div class="text-xs text-muted" style="margin-bottom:4px;">✉️ ${escapeHtml(c.email)}</div>` : ''}
      ${c.bizcat ? `<div class="text-xs text-muted" style="margin-bottom:8px;">🏷️ ${escapeHtml(c.bizcat)}</div>` : ''}
      <div style="display:flex;gap:10px;padding-top:10px;border-top:1px solid var(--border);">
        <div style="flex:1;text-align:center;">
          <div style="font-size:1.1rem;font-weight:800;color:var(--accent2);">
            ${orderCount(c.name)}
          </div>
          <div class="text-xs text-muted">заказов</div>
        </div>
        <div style="flex:1;text-align:center;">
          <div style="font-size:0.85rem;font-weight:700;color:var(--accent3);">
            ${formatMoney(totalSpent(c.name))}
          </div>
          <div class="text-xs text-muted">потрачено</div>
        </div>
      </div>
      <div style="display:flex;gap:6px;margin-top:10px;">
        <button class="btn btn-danger btn-xs" style="flex:1;"
                onclick="deleteClient(${c.id})">🗑️ Удалить</button>
        <button class="btn btn-primary btn-xs" style="flex:1;"
                onclick="newOrderForClient('${escapeHtml(c.name)}',
                         '${escapeHtml(c.phone || '')}')">
          + Заказ
        </button>
      </div>
    </div>
  `).join('');

  const sc = document.getElementById('statClients');
  if (sc) sc.textContent = db.clients.length;
}

function deleteClient(id) {
  if (!confirm('Удалить клиента?')) return;
  const db   = getDB();
  db.clients = db.clients.filter(c => c.id !== id);
  saveDB(db);
  renderClients();
  notify('Клиент удалён', 'error');
}

function newOrderForClient(name, phone) {
  openModal('orderModal');
  setTimeout(() => {
    const cn = document.getElementById('ord_client');
    const cp = document.getElementById('ord_phone');
    if (cn) cn.value = name;
    if (cp) cp.value = phone;
  }, 100);
}

/* ============================================================
   NOTES
============================================================ */
function saveNote() {
  const title = document.getElementById('note_title')?.value.trim() || '';
  const body  = document.getElementById('note_body')?.value.trim()  || '';
  if (!title && !body) { notify('Введите текст заметки', 'error'); return; }
  const db = getDB();
  db.notes.unshift({
    id:       Date.now(),
    title:    title || 'Без заголовка',
    body,
    priority: document.getElementById('note_priority')?.value || 'normal',
    shift:    document.getElementById('note_shift')?.value    || '',
    created:  new Date().toISOString(),
  });
  saveDB(db);
  closeModal('noteModal');
  renderNotes();
  updateNotesBadge();
  notify('Заметка сохранена', 'success');
}

function renderNotes() {
  const db   = getDB();
  const grid = document.getElementById('notesGrid');
  if (!grid) return;

  if (!(db.notes || []).length) {
    grid.innerHTML = '<div class="empty-state card" style="grid-column:1/-1;">' +
      '<div class="icon">📝</div><div class="title">Заметок нет</div></div>';
    return;
  }

  const labels = {
    normal:    'Обычная',
    info:      'Информация',
    important: '⚠️ Важная',
    urgent:    '🚨 Срочно!'
  };
  const colors = {
    normal:    'var(--text-muted)',
    info:      'var(--accent2)',
    important: 'var(--accent4)',
    urgent:    'var(--danger)'
  };

  grid.innerHTML = db.notes.map(n => `
    <div class="note-card ${n.priority}">
      <div style="display:flex;justify-content:space-between;
                  align-items:flex-start;gap:8px;margin-bottom:6px;">
        <div class="note-title">${escapeHtml(n.title)}</div>
        <span style="font-size:0.68rem;padding:2px 6px;border-radius:4px;
                     background:var(--bg-dark);
                     color:${colors[n.priority] || 'var(--text-muted)'};
                     font-weight:700;">
          ${labels[n.priority] || n.priority}
        </span>
      </div>
      <div class="note-body">${escapeHtml(n.body || '')}</div>
      <div class="note-meta">
        <span>🕐 ${formatDate(n.created)}</span>
        ${n.shift ? `<span>👤 ${escapeHtml(n.shift)}</span>` : ''}
        <button class="btn btn-danger btn-xs" style="margin-left:auto;"
                onclick="deleteNote(${n.id})">🗑️</button>
      </div>
    </div>
  `).join('');
}

function deleteNote(id) {
  if (!confirm('Удалить заметку?')) return;
  const db = getDB();
  db.notes = db.notes.filter(n => n.id !== id);
  saveDB(db);
  renderNotes();
  updateNotesBadge();
}

function updateNotesBadge() {
  const db     = getDB();
  const urgent = (db.notes || []).filter(n =>
    n.priority === 'urgent' || n.priority === 'important'
  ).length;
  const badge  = document.getElementById('notesNavBadge');
  if (badge) badge.style.display = urgent > 0 ? '' : 'none';
}

/* ============================================================
   DASHBOARD
============================================================ */
function refreshDashboard() {
  const db    = getDB();
  const now   = new Date();
  const today = now.toDateString();

  const monthFilter = arr => (arr || []).filter(i => {
    const d = new Date(i.date);
    return d.getMonth() === now.getMonth() &&
           d.getFullYear() === now.getFullYear();
  });
  const todayFilter = arr => (arr || []).filter(i =>
    new Date(i.date).toDateString() === today
  );

  const ordersToday  = (db.orders || []).filter(o =>
    new Date(o.date).toDateString() === today
  ).length;
  const finIncome    = (db.finance || []).filter(f => f.type === 'income');
  const finExpense   = (db.finance || []).filter(f => f.type === 'expense');
  const incomeMonth  = monthFilter(finIncome) .reduce((a, b) => a + (b.amount || 0), 0);
  const expenseMonth = monthFilter(finExpense).reduce((a, b) => a + (b.amount || 0), 0);
  const incomeToday  = todayFilter(finIncome) .reduce((a, b) => a + (b.amount || 0), 0);
  const expenseToday = todayFilter(finExpense).reduce((a, b) => a + (b.amount || 0), 0);
  const profit       = incomeMonth - expenseMonth;

  const s = (id, v) => {
    const e = document.getElementById(id);
    if (e) e.textContent = v;
  };
  s('kpiOrdersToday',  ordersToday);
  s('kpiIncomeMonth',  formatMoney(incomeMonth));
  s('kpiExpenseMonth', formatMoney(expenseMonth));
  s('kpiProfitMonth',  formatMoney(profit));
  s('kpiIncomeToday',  'сегодня: ' + formatMoney(incomeToday));
  s('kpiExpenseToday', 'сегодня: ' + formatMoney(expenseToday));
  s('kpiProfitStatus', profit >= 0 ? '📈 Прибыльно' : '📉 Убыток');

  const ro = document.getElementById('dashRecentOrders');
  if (ro) {
    const recent = (db.orders || []).slice(0, 5);
    ro.innerHTML = recent.length
      ? '<div style="display:flex;flex-direction:column;gap:6px;">' +
        recent.map(o => `
          <div style="display:flex;align-items:center;gap:10px;padding:8px;
                      background:var(--bg-dark);border-radius:8px;cursor:pointer;"
               onclick="openOrderDetail(null,${o.id})">
            <div style="min-width:80px;font-weight:700;font-size:0.78rem;
                        color:var(--accent2);">${o.num}</div>
            <div style="flex:1;min-width:0;">
              <div style="font-size:0.82rem;font-weight:600;overflow:hidden;
                          text-overflow:ellipsis;white-space:nowrap;">
                ${escapeHtml(o.client)}
              </div>
              <div style="font-size:0.7rem;color:var(--text-muted);">
                ${o.serviceLabel || ''}
              </div>
            </div>
            <div style="font-weight:700;color:var(--accent3);font-size:0.82rem;">
              ${formatMoney(o.total)}
            </div>
            ${getStatusBadge(o.status)}
          </div>
        `).join('') + '</div>'
      : '<div class="empty-state"><div class="icon">📋</div>' +
        '<div class="title">Заказов пока нет</div></div>';
  }

  updateOrdersBadge();
  updateNotesBadge();
  updateDBSize();
  refreshDashboardExtended();
}

/* ============================================================
   DASHBOARD EXTENDED
============================================================ */
function refreshDashboardExtended() {
  const db       = getDB();
  const now      = new Date();
  const todayStr = now.toDateString();
  const cur      = db.settings?.currency || '₽';

  const days   = ['Вс','Пн','Вт','Ср','Чт','Пт','Сб'];
  const months = ['января','февраля','марта','апреля','мая','июня',
                  'июля','августа','сентября','октября','ноября','декабря'];
  const dateEl = document.getElementById('dashTodayDate');
  if (dateEl) dateEl.textContent =
    `${days[now.getDay()]}, ${now.getDate()} ${months[now.getMonth()]}`;

  const todayFin = (db.finance || []).filter(f =>
    new Date(f.date).toDateString() === todayStr
  );
  const todayInc = todayFin.filter(f => f.type === 'income');
  const todayExp = todayFin.filter(f => f.type === 'expense');
  const sumInc   = todayInc.reduce((a, b) => a + (b.amount || 0), 0);
  const sumExp   = todayExp.reduce((a, b) => a + (b.amount || 0), 0);
  const profit   = sumInc - sumExp;

  const todayOrders = (db.orders || []).filter(o =>
    new Date(o.date).toDateString() === todayStr
  );

  const s = (id, v) => {
    const e = document.getElementById(id);
    if (e) e.textContent = v;
  };
  s('dashTodayIncome',  formatMoney(sumInc, cur));
  s('dashTodayExpense', formatMoney(sumExp, cur));
  s('dashTodayProfit',  formatMoney(profit, cur));

  // Прогресс-бар
  const barEl   = document.getElementById('dashTodayBar');
  const ratioEl = document.getElementById('dashTodayRatio');
  if (barEl && ratioEl) {
    const total = sumInc + sumExp;
    const pct   = total > 0 ? Math.round((sumInc / total) * 100) : 0;
    barEl.style.width = pct + '%';
    barEl.style.background = pct >= 60
      ? 'linear-gradient(to right,var(--accent3),var(--accent2))'
      : pct >= 40
        ? 'linear-gradient(to right,var(--accent4),var(--accent2))'
        : 'linear-gradient(to right,var(--danger),var(--accent4))';
    ratioEl.textContent = total > 0 ? `${pct}% доход` : '—';
  }

  // Последние 4 операции
  const rfEl = document.getElementById('dashRecentFinance');
  if (rfEl) {
    const last4 = [...(db.finance || [])].slice(0, 4);
    rfEl.innerHTML = last4.length
      ? last4.map(f => `
          <div style="display:flex;align-items:center;gap:8px;padding:5px 8px;
                      background:var(--bg-dark);border-radius:7px;">
            <span style="font-size:0.85rem;">
              ${f.type === 'income' ? '💚' : '🔴'}
            </span>
            <div style="flex:1;min-width:0;">
              <div style="font-size:0.75rem;font-weight:600;overflow:hidden;
                          text-overflow:ellipsis;white-space:nowrap;">
                ${escapeHtml(f.category || f.desc || '—')}
              </div>
              <div style="font-size:0.65rem;color:var(--text-muted);">
                ${formatDate(f.date)}
              </div>
            </div>
            <div style="font-size:0.8rem;font-weight:700;white-space:nowrap;
                        color:${f.type === 'income' ? 'var(--accent3)' : 'var(--danger)'};">
              ${f.type === 'income' ? '+' : '−'}${formatMoney(f.amount, cur)}
            </div>
          </div>
        `).join('')
      : '<div style="text-align:center;padding:12px;color:var(--text-muted);font-size:0.78rem;">Операций пока нет</div>';
  }

  // График по часам
  const hourlyEl = document.getElementById('dashHourlyChart');
  if (hourlyEl) {
    const hours = Array(24).fill(0);
    todayInc.forEach(f => {
      const h = new Date(f.date).getHours();
      hours[h] += f.amount || 0;
    });
    const STEP = 2;
    const bars = [];
    for (let h = 0; h < 24; h += STEP) {
      const val = hours[h] + (hours[h + 1] || 0);
      bars.push({ h, val });
    }
    const maxBar = Math.max(...bars.map(b => b.val), 1);
    const nowH   = now.getHours();

    hourlyEl.innerHTML = bars.map(({ h, val }) => {
      const pct    = Math.max(4, Math.round((val / maxBar) * 100));
      const isNow  = h <= nowH && nowH < h + STEP;
      const hasData = val > 0;
      return `
        <div style="flex:1;display:flex;flex-direction:column;
                    align-items:center;gap:2px;cursor:default;"
             title="${h}:00–${h + STEP}:00 • ${formatMoney(val, cur)}">
          ${hasData
            ? `<div style="font-size:0.55rem;color:var(--accent3);
                           font-weight:700;line-height:1;">
                ${val >= 1000 ? Math.round(val / 1000) + 'к' : Math.round(val)}
               </div>`
            : '<div style="height:10px;"></div>'}
          <div style="width:100%;border-radius:3px 3px 0 0;
            height:${pct}%;min-height:3px;transition:height 0.5s;
            opacity:${hasData ? '1' : '0.3'};
            background:${isNow
              ? 'linear-gradient(to top,var(--accent),var(--accent2))'
              : hasData
                ? 'linear-gradient(to top,var(--accent3),rgba(16,185,129,0.5))'
                : 'var(--border)'};">
          </div>
        </div>`;
    }).join('');
  }

  // Топ категорий дохода сегодня
  const topEl = document.getElementById('dashTopIncome');
  if (topEl) {
    const cats = {};
    todayInc.forEach(f => {
      cats[f.category || 'Прочее'] =
        (cats[f.category || 'Прочее'] || 0) + (f.amount || 0);
    });
    const sorted = Object.entries(cats).sort((a, b) => b[1] - a[1]).slice(0, 5);
    const maxV   = sorted[0]?.[1] || 1;
    const colors = ['#10b981','#06b6d4','#7c3aed','#f59e0b','#ef4444'];

    topEl.innerHTML = sorted.length
      ? sorted.map(([cat, val], i) => `
          <div>
            <div style="display:flex;justify-content:space-between;
                        font-size:0.72rem;margin-bottom:3px;">
              <span style="overflow:hidden;text-overflow:ellipsis;white-space:nowrap;
                           max-width:120px;">${escapeHtml(cat)}</span>
              <span style="font-weight:700;color:${colors[i % colors.length]};
                           white-space:nowrap;">
                ${formatMoney(val, cur)}
              </span>
            </div>
            <div style="height:4px;background:var(--border);
                        border-radius:2px;overflow:hidden;">
              <div style="height:100%;width:${Math.round((val / maxV) * 100)}%;
                          background:${colors[i % colors.length]};
                          border-radius:2px;transition:width 0.6s;">
              </div>
            </div>
          </div>
        `).join('')
      : '<div style="color:var(--text-muted);font-size:0.72rem;text-align:center;padding:16px 0;">Нет доходов сегодня</div>';
  }

  // Итог дня
  const emojiEl = document.getElementById('dashDayEmoji');
  const labelEl = document.getElementById('dashDayLabel');
  if (emojiEl && labelEl) {
    if (sumInc === 0 && sumExp === 0) {
      emojiEl.textContent = '😴'; labelEl.textContent = 'День не начат';
      labelEl.style.color = 'var(--text-muted)';
    } else if (profit > 5000) {
      emojiEl.textContent = '🤑'; labelEl.textContent = 'Отличный день!';
      labelEl.style.color = 'var(--accent3)';
    } else if (profit > 1000) {
      emojiEl.textContent = '😊'; labelEl.textContent = 'Хороший день';
      labelEl.style.color = 'var(--accent3)';
    } else if (profit > 0) {
      emojiEl.textContent = '🙂'; labelEl.textContent = 'Небольшой плюс';
      labelEl.style.color = 'var(--accent2)';
    } else if (profit === 0 && sumInc > 0) {
      emojiEl.textContent = '😐'; labelEl.textContent = 'В ноль';
      labelEl.style.color = 'var(--accent4)';
    } else {
      emojiEl.textContent = '😟'; labelEl.textContent = 'Расходы > доходов';
      labelEl.style.color = 'var(--danger)';
    }
  }

  // Мини-статы
  const avgCheck = todayOrders.length
    ? Math.round(todayOrders.reduce((a, b) => a + (b.total || 0), 0) / todayOrders.length)
    : 0;
  s('dashTodayOpsCount',    todayFin.length);
  s('dashTodayOrdersCount', todayOrders.length);
  s('dashTodayAvgCheck',    formatMoney(avgCheck, cur));
}

/* ============================================================
   STATS
============================================================ */
function renderStats() {
  const db     = getDB();
  const period = document.getElementById('statsPeriod')?.value || 'month';
  const now    = new Date();
  let orders   = db.orders || [];

  if (period === 'month') orders = orders.filter(o => {
    const d = new Date(o.date);
    return d.getMonth() === now.getMonth() &&
           d.getFullYear() === now.getFullYear();
  });
  if (period === 'week') {
    const weekAgo = new Date(now - 7 * 86400000);
    orders = orders.filter(o => new Date(o.date) >= weekAgo);
  }

  const s = (id, v) => {
    const e = document.getElementById(id);
    if (e) e.textContent = v;
  };
  s('statTotalOrders', orders.length);
  s('statDoneOrders',  orders.filter(o => o.status === 'done').length);
  s('statClients',     (db.clients || []).length);
  s('statAvgCheck',    formatMoney(
    orders.length
      ? Math.round(orders.reduce((a, b) => a + (b.total || 0), 0) / orders.length)
      : 0
  ));

  const byService = {};
  orders.forEach(o => {
    byService[o.serviceLabel || 'Прочее'] =
      (byService[o.serviceLabel || 'Прочее'] || 0) + 1;
  });
  renderBarChart('statsByService', byService, '#7c3aed');

  const byBiz = {};
  orders.forEach(o => {
    const k = o.bizcat || 'Не указано';
    byBiz[k] = (byBiz[k] || 0) + 1;
  });
  renderBarChart('statsByCategory', byBiz, '#06b6d4');
  renderServiceBars(orders);
}

function renderBarChart(containerId, data, color) {
  const el = document.getElementById(containerId);
  if (!el) return;
  const entries = Object.entries(data).sort((a, b) => b[1] - a[1]).slice(0, 8);
  if (!entries.length) {
    el.innerHTML = '<div class="text-muted text-sm" style="padding:16px;">Нет данных</div>';
    return;
  }
  const max = Math.max(...entries.map(e => e[1]));
  el.innerHTML = entries.map(([k, v]) => `
    <div style="margin-bottom:10px;">
      <div style="display:flex;justify-content:space-between;
                  font-size:0.78rem;margin-bottom:4px;">
        <span style="overflow:hidden;text-overflow:ellipsis;white-space:nowrap;
                     max-width:200px;">${escapeHtml(k)}</span>
        <span style="font-weight:700;color:${color};">${v}</span>
      </div>
      <div class="progress-bar">
        <div class="progress-fill"
             style="width:${max ? ((v / max) * 100).toFixed(0) : 0}%;
                    background:${color};">
        </div>
      </div>
    </div>
  `).join('');
}

function renderServiceBars(orders) {
  const el = document.getElementById('statsServiceBars');
  if (!el) return;
  const data = {};
  orders.forEach(o => {
    data[o.serviceLabel || 'Прочее'] =
      (data[o.serviceLabel || 'Прочее'] || 0) + 1;
  });
  const entries = Object.entries(data).sort((a, b) => b[1] - a[1]).slice(0, 8);
  if (!entries.length) {
    el.innerHTML = '<div class="text-muted text-sm">Нет данных</div>';
    return;
  }
  const max    = Math.max(...entries.map(e => e[1]));
  const colors = ['#7c3aed','#06b6d4','#10b981','#f59e0b',
                  '#ef4444','#8b5cf6','#0ea5e9','#14b8a6'];
  el.innerHTML =
    '<div style="display:flex;align-items:flex-end;gap:12px;height:120px;padding-top:10px;">' +
    entries.map(([k, v], i) => `
      <div style="flex:1;display:flex;flex-direction:column;align-items:center;gap:4px;">
        <div style="font-size:0.75rem;font-weight:700;
                    color:${colors[i % colors.length]};">${v}</div>
        <div style="width:100%;border-radius:4px 4px 0 0;
                    background:${colors[i % colors.length]};
                    height:${max ? Math.max(8, (v / max) * 90) : 8}px;
                    transition:height 0.5s;">
        </div>
        <div style="font-size:0.62rem;color:var(--text-muted);text-align:center;
                    max-width:60px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;"
             title="${escapeHtml(k)}">
          ${escapeHtml(k).split(' ')[0]}
        </div>
      </div>
    `).join('') + '</div>';
}

/* ============================================================
   ACCOUNTING
============================================================ */
function renderAccounting() {
  const db     = getDB();
  const months = {};

  (db.finance || []).forEach(f => {
    const d   = new Date(f.date);
    const key = `${d.getFullYear()}-${String(d.getMonth() + 1).padStart(2, '0')}`;
    if (!months[key]) months[key] = { income: 0, expense: 0 };
    if (f.type === 'income') months[key].income  += (f.amount || 0);
    else                     months[key].expense += (f.amount || 0);
  });

  const ordersByMonth = {};
  (db.orders || []).forEach(o => {
    const d   = new Date(o.date);
    const key = `${d.getFullYear()}-${String(d.getMonth() + 1).padStart(2, '0')}`;
    ordersByMonth[key] = (ordersByMonth[key] || 0) + 1;
  });

  const MONTHS = ['Январь','Февраль','Март','Апрель','Май','Июнь',
                  'Июль','Август','Сентябрь','Октябрь','Ноябрь','Декабрь'];
  const tbody  = document.getElementById('accountingTable');
  if (!tbody) return;
  const arr    = Object.entries(months).sort((a, b) => b[0].localeCompare(a[0]));

  tbody.innerHTML = !arr.length
    ? '<tr><td colspan="6"><div class="empty-state">' +
      '<div class="icon">📊</div><div class="title">Нет данных</div></div></td></tr>'
    : arr.map(([k, v]) => {
        const profit = v.income - v.expense;
        const margin = v.income > 0
          ? ((profit / v.income) * 100).toFixed(1) : '0';
        const [yr, mn] = k.split('-');
        return `<tr>
          <td style="font-weight:700;">
            ${MONTHS[parseInt(mn) - 1]} ${yr}
          </td>
          <td style="color:var(--accent3);font-weight:700;">
            ${formatMoney(v.income)}
          </td>
          <td style="color:var(--danger);font-weight:700;">
            ${formatMoney(v.expense)}
          </td>
          <td style="color:${profit >= 0 ? 'var(--accent2)' : 'var(--danger)'};
                     font-weight:700;">
            ${formatMoney(profit)}
          </td>
          <td>${ordersByMonth[k] || 0}</td>
          <td>
            <div style="display:flex;align-items:center;gap:6px;">
              <div class="progress-bar" style="flex:1;">
                <div class="progress-fill"
                     style="width:${Math.min(100, Math.max(0, parseFloat(margin)))}%;">
                </div>
              </div>
              <span style="font-size:0.78rem;font-weight:700;
                color:${parseFloat(margin) >= 0 ? 'var(--accent3)' : 'var(--danger)'};">
                ${margin}%
              </span>
            </div>
          </td>
        </tr>`;
      }).join('');

  const expByCat = {};
  (db.finance || []).filter(f => f.type === 'expense').forEach(f => {
    expByCat[f.category || 'Прочее'] =
      (expByCat[f.category || 'Прочее'] || 0) + (f.amount || 0);
  });
  renderBarChart('expenseByCategory', expByCat, '#ef4444');

  const incByCat = {};
  (db.finance || []).filter(f => f.type === 'income').forEach(f => {
    incByCat[f.category || 'Прочее'] =
      (incByCat[f.category || 'Прочее'] || 0) + (f.amount || 0);
  });
  renderBarChart('incomeByCategory', incByCat, '#10b981');
}

/* ============================================================
   SETTINGS
============================================================ */
function loadSettings() {
  const s    = getDB().settings || {};
  const flds = {
    setCompany:'company',           setInn:'inn',
    setOgrn:'ogrn',                 setAddress:'address',
    setPhone:'phone',               setEmail:'email',
    setWebsite:'website',           setBankAcc:'bankAcc',
    setBik:'bik',                   setBankName:'bankName',
    setKorAcc:'korAcc',             setKpp:'kpp',
    setReceiptHeader:'receiptHeader', setReceiptFooter:'receiptFooter',
    setSignatory:'signatory',       setSignatoryTitle:'signatoryTitle',
    setVat:'vat',                   setCurrency:'currency',
    setApiKey:'apiKey',             setApiModel:'apiModel',
  };
  Object.entries(flds).forEach(([id, key]) => {
    const el = document.getElementById(id);
    if (el) el.value = s[key] || '';
  });
  const tgT = document.getElementById('set_tgToken');
  const tgB = document.getElementById('set_tgBossId');
  if (tgT) tgT.value = s.tgToken  || '';
  if (tgB) tgB.value = s.tgBossId || '';
  renderModulesGrid();
}

function saveSettings() {
  const db   = getDB();
  const flds = {
    setCompany:'company',           setInn:'inn',
    setOgrn:'ogrn',                 setAddress:'address',
    setPhone:'phone',               setEmail:'email',
    setWebsite:'website',           setBankAcc:'bankAcc',
    setBik:'bik',                   setBankName:'bankName',
    setKorAcc:'korAcc',             setKpp:'kpp',
    setReceiptHeader:'receiptHeader', setReceiptFooter:'receiptFooter',
    setSignatory:'signatory',       setSignatoryTitle:'signatoryTitle',
    setVat:'vat',                   setCurrency:'currency',
    setApiKey:'apiKey',             setApiModel:'apiModel',
  };
  Object.entries(flds).forEach(([id, key]) => {
    const el = document.getElementById(id);
    if (el) db.settings[key] = el.value;
  });
  const tgT = document.getElementById('set_tgToken');
  const tgB = document.getElementById('set_tgBossId');
  if (tgT) db.settings.tgToken  = tgT.value;
  if (tgB) db.settings.tgBossId = tgB.value;
  saveDB(db);
  notify('Настройки сохранены!', 'success');
}

function renderModulesGrid() {
  const grid = document.getElementById('modulesGrid');
  if (!grid) return;
  const mods = Object.values(CRM._modules);
  if (!mods.length) {
    grid.innerHTML = '<div class="text-muted text-sm">Нет подключённых модулей</div>';
    return;
  }
  grid.innerHTML = mods.map(m => `
    <div class="card" style="display:flex;align-items:center;gap:12px;padding:12px;">
      <span style="font-size:1.5rem;">${m.icon}</span>
      <div style="flex:1;">
        <div style="font-weight:700;">${m.name}</div>
        <div class="text-xs text-muted">${m.id}</div>
      </div>
      <span style="width:10px;height:10px;border-radius:50%;
                   background:var(--accent3);flex-shrink:0;" title="Активен"></span>
    </div>
  `).join('');
}

async function testApiKey() {
  const key = document.getElementById('setApiKey')?.value;
  if (!key) { notify('Введите API ключ', 'error'); return; }
  notify('Проверяю ключ...', 'info');
  try {
    const res = await callDeepSeekAPI('Ответь одним словом: готов.', key,
      document.getElementById('setApiModel')?.value);
    if (res) notify('API ключ работает! ✅', 'success');
  } catch (e) { notify('Ошибка: ' + e.message, 'error'); }
}

function renderWarehouse() {
  const mod = CRM._modules['warehouse'];
  if (mod?.render) { mod.render(); return; }
  _renderStub('page-warehouse', '📦', 'Склад', 'Модуль склада не подключён');
}

function renderCalendar() {
  const mod = CRM._modules['calendar'];
  if (mod?.render) { mod.render(); return; }
  _renderStub('page-calendar', '📅', 'Календарь', 'Модуль календаря не подключён');
}

function _renderStub(pageId, icon, title, desc) {
  const p = document.getElementById(pageId);
  if (!p) return;
  p.innerHTML = `<div class="empty-state" style="padding-top:80px;">
    <div class="icon">${icon}</div>
    <div class="title">${title}</div>
    <div class="desc">${desc}</div>
  </div>`;
}

function exportDB() {
  const db   = getDB();
  const blob = new Blob([JSON.stringify(db, null, 2)], { type: 'application/json' });
  const url  = URL.createObjectURL(blob);
  const a    = document.createElement('a');
  a.href     = url;
  a.download = `printcrm_${new Date().toISOString().slice(0, 10)}.json`;
  a.click();
  URL.revokeObjectURL(url);
  notify('База экспортирована', 'success');
}

function importDB() {
  document.getElementById('importFile')?.click();
}

function loadImportFile(e) {
  const file = e.target.files[0];
  if (!file) return;
  const reader = new FileReader();
  reader.onload = ev => {
    try {
      const data = JSON.parse(ev.target.result);
      if (!confirm('Загрузить базу? Текущие данные будут заменены.')) return;
      saveDB(data);
      notify('База загружена', 'success');
      refreshDashboard();
    } catch { notify('Ошибка: неверный файл', 'error'); }
  };
  reader.readAsText(file);
}

function clearDB() {
  if (!confirm('УДАЛИТЬ ВСЕ ДАННЫЕ? Необратимо!')) return;
  if (!confirm('Вы точно уверены?')) return;
  dbCache  = initDBStructure();
  isLoaded = true;
  pushToServer(dbCache);
  notify('База очищена', 'error');
  refreshDashboard();
}

/* ============================================================
   PRINT
============================================================ */
function printOrderForm(forWhom) {
  const db      = getDB();
  const s       = db.settings || {};
  const num     = document.getElementById('ord_num')?.value     || '';
  const client  = document.getElementById('ord_client')?.value  || 'Без имени';
  const phone   = document.getElementById('ord_phone')?.value   || '';
  const manager = document.getElementById('ord_manager')?.value || '';
  const date    = document.getElementById('ord_date')?.value    || '';
  const deadline= document.getElementById('ord_deadline')?.value|| '';
  const total   = document.getElementById('ord_total')?.value   || '0';
  const prepay  = document.getElementById('ord_prepay')?.value  || '0';
  const comment = document.getElementById('ord_comment')?.value || '';
  const payment = document.getElementById('ord_payment')?.value || '';
  const service = getServiceLabel(currentServiceTab);
  const selSize = document.querySelector('.size-btn.selected');
  const size    = selSize ? selSize.textContent.trim() : '';
  const isMan   = forWhom === 'manager';
  const remain  = (parseFloat(total) - parseFloat(prepay)).toFixed(0);

  const checkedItems = [];
  document.querySelector('.service-tab-content.active')
    ?.querySelectorAll('.checkbox-item.checked')
    .forEach(c => checkedItems.push(c.textContent.trim().replace('✓', '').trim()));

  const html = `<!DOCTYPE html><html><head><meta charset="UTF-8"><style>
    body{font-family:Arial,sans-serif;font-size:12px;color:#000;padding:20px;}
    .wrap{max-width:800px;margin:0 auto;}
    .hdr{border-bottom:3px solid #333;padding-bottom:12px;margin-bottom:16px;
         display:flex;justify-content:space-between;}
    .co{font-size:20px;font-weight:bold;}
    .det{font-size:10px;color:#555;line-height:1.6;}
    .ord{font-size:18px;font-weight:bold;text-align:center;margin:12px 0;
         background:#f0f0f0;padding:8px;border-radius:4px;}
    table{width:100%;border-collapse:collapse;margin-bottom:12px;}
    td,th{border:1px solid #ccc;padding:6px 10px;font-size:11px;}
    th{background:#eee;font-weight:bold;text-align:left;}
    .total{font-size:16px;font-weight:bold;background:#e8f5e9;}
    .ftr{border-top:1px solid #ccc;margin-top:20px;padding-top:12px;
         font-size:10px;color:#555;}
    .sigs{display:flex;justify-content:space-between;margin-top:30px;}
    .sig{text-align:center;width:200px;border-top:1px solid #333;
         padding-top:4px;font-size:10px;}
    .chip{display:inline-block;border:1px solid #333;border-radius:3px;
          padding:2px 6px;margin:2px;font-size:10px;}
  </style></head><body><div class="wrap">
  <div class="hdr">
    <div>
      <div class="co">${escapeHtml(s.company || 'Фотокопицентр')}</div>
      <div class="det">
        ${escapeHtml(s.address || '')}
        <br>${s.phone ? 'Тел: ' + escapeHtml(s.phone) : ''}
        ${s.email ? ' • ' + escapeHtml(s.email) : ''}
        <br>${s.inn ? 'ИНН: ' + escapeHtml(s.inn) : ''}
        ${s.ogrn ? ' • ОГРН: ' + escapeHtml(s.ogrn) : ''}
      </div>
    </div>
    <div style="text-align:right;font-size:10px;color:#555;">
      <b>${isMan ? 'БЛАНК МЕНЕДЖЕРА' : 'КВИТАНЦИЯ КЛИЕНТА'}</b>
      <br>${new Date().toLocaleString('ru')}
    </div>
  </div>
  <div class="ord">ЗАКАЗ № ${escapeHtml(num)}</div>
  <table>
    <tr>
      <th width="140">Дата приёма</th><td>${formatDate(date)}</td>
      <th width="140">Срок выдачи</th><td>${deadline ? formatDate(deadline) : '—'}</td>
    </tr>
    <tr>
      <th>Клиент</th><td>${escapeHtml(client)}</td>
      <th>Телефон</th><td>${escapeHtml(phone)}</td>
    </tr>
    <tr>
      <th>Вид услуги</th><td>${escapeHtml(service)}</td>
      <th>Формат/Размер</th><td>${escapeHtml(size) || '—'}</td>
    </tr>
    <tr>
      <th>Менеджер</th><td>${escapeHtml(manager)}</td>
      <th>Оплата</th><td>${escapeHtml(payment)}</td>
    </tr>
  </table>
  ${checkedItems.length
    ? `<div style="margin-bottom:12px;"><b>Параметры:</b><br>
       ${checkedItems.map(i =>
         `<span class="chip">✓ ${escapeHtml(i)}</span>`
       ).join(' ')}</div>`
    : ''}
  ${comment
    ? `<div style="margin-bottom:12px;border:1px solid #ccc;padding:8px;
                   border-radius:4px;">
         <b>Комментарий:</b> ${escapeHtml(comment)}
       </div>`
    : ''}
  <table>
    <tr class="total">
      <td colspan="2" style="text-align:right;"><b>ИТОГО:</b></td>
      <td style="font-size:18px;font-weight:bold;">
        ${formatMoney(parseFloat(total))} ${s.currency || '₽'}
      </td>
    </tr>
    ${parseFloat(prepay) > 0 ? `
      <tr>
        <td colspan="2" style="text-align:right;">Предоплата:</td>
        <td>${formatMoney(parseFloat(prepay))} ${s.currency || '₽'}</td>
      </tr>
      <tr>
        <td colspan="2" style="text-align:right;"><b>Остаток:</b></td>
        <td style="font-weight:bold;">
          ${formatMoney(parseFloat(remain))} ${s.currency || '₽'}
        </td>
      </tr>
    ` : ''}
  </table>
  <div class="ftr">
    ${escapeHtml(s.receiptHeader || '')}
    <div class="sigs">
      <div class="sig">
        ${escapeHtml(s.signatoryTitle || 'Менеджер')}:
        ${escapeHtml(s.signatory || '_______________')}
      </div>
      <div class="sig">Клиент: ${escapeHtml(client)}</div>
    </div>
    <div style="margin-top:12px;">${escapeHtml(s.receiptFooter || '')}</div>
  </div>
  </div><script>window.onload=()=>window.print();<\/script></body></html>`;

  const win = window.open('', '_blank');
  if (win) { win.document.write(html); win.document.close(); }
}

function printSingleOrder(id) {
  const db = getDB();
  const o  = db.orders.find(x => x.id === id || String(x.id) === String(id));
  if (!o) return;
  const s  = db.settings || {};

  const html = `<!DOCTYPE html><html><head><meta charset="UTF-8"><style>
    body{font-family:Arial,sans-serif;font-size:12px;padding:20px;}
    .t{font-size:18px;font-weight:bold;margin-bottom:16px;text-align:center;
       border-bottom:2px solid #333;padding-bottom:8px;}
    table{width:100%;border-collapse:collapse;margin-bottom:12px;}
    td,th{border:1px solid #ccc;padding:6px 10px;}
    th{background:#eee;font-weight:bold;}
    .tot{font-size:16px;font-weight:bold;background:#e8f5e9;}
    .sigs{display:flex;justify-content:space-between;margin-top:30px;}
    .sig{border-top:1px solid #000;min-width:150px;padding-top:4px;
         text-align:center;font-size:11px;}
  </style></head><body>
  <div class="t">
    ${escapeHtml(s.company || 'Фотокопицентр')} — Заказ ${escapeHtml(o.num)}
  </div>
  <table>
    <tr>
      <th>Клиент</th><td>${escapeHtml(o.client)}</td>
      <th>Телефон</th><td>${escapeHtml(o.phone || '—')}</td>
    </tr>
    <tr>
      <th>Дата</th><td>${formatDate(o.date)}</td>
      <th>Срок</th><td>${o.deadline ? formatDate(o.deadline) : '—'}</td>
    </tr>
    <tr>
      <th>Услуга</th><td>${escapeHtml(o.serviceLabel)}</td>
      <th>Размер</th><td>${escapeHtml(o.size || '—')}</td>
    </tr>
    <tr>
      <th>Параметры</th>
      <td colspan="3">${escapeHtml((o.checkedItems || []).join(', ') || '—')}</td>
    </tr>
    <tr>
      <th>Статус</th><td>${escapeHtml(o.status)}</td>
      <th>Оплата</th><td>${escapeHtml(o.payment)}</td>
    </tr>
    <tr>
      <th>Комментарий</th><td colspan="3">${escapeHtml(o.comment || '—')}</td>
    </tr>
    <tr class="tot">
      <td colspan="2"><b>ИТОГО:</b></td>
      <td colspan="2" style="font-size:16px;">
        ${formatMoney(o.total)} ${s.currency || '₽'}
      </td>
    </tr>
  </table>
  <p>${escapeHtml(s.receiptFooter || '')}</p>
  <div class="sigs">
    <div class="sig">Менеджер: ${escapeHtml(o.manager || '')}</div>
    <div class="sig">Подпись клиента</div>
  </div>
  <script>window.onload=()=>window.print();<\/script></body></html>`;

  const win = window.open('', '_blank');
  if (win) { win.document.write(html); win.document.close(); }
}

/* ============================================================
   AI CHAT
============================================================ */
let chatContext = [];

const SYSTEM_PROMPT =
  `Ты Валера — эксперт-гений в типографии, фотокопицентре, экономике и полиграфическом бизнесе.
Помогаешь менеджеру на смене. Характер: весёлый, с юмором, но профессионал.
Знаешь всё о форматах, ценообразовании, материалах, технологиях.
Анализируешь данные о заказах и финансах. Отвечай развёрнуто. Язык — русский.
Данные системы:`;

async function callDeepSeekAPI(message, apiKey, model) {
  const db  = getDB();
  const key = apiKey || db.settings?.apiKey;
  const mdl = model  || db.settings?.apiModel || 'deepseek-chat';
  if (!key) throw new Error('Не указан API ключ. Перейдите в Настройки → DeepSeek API');

  const now  = new Date();
  const mfin = (db.finance || []).filter(f => {
    const d = new Date(f.date);
    return d.getMonth() === now.getMonth() &&
           d.getFullYear() === now.getFullYear();
  });
  const incM = mfin.filter(f => f.type === 'income')
    .reduce((a, b) => a + (b.amount || 0), 0);
  const expM = mfin.filter(f => f.type === 'expense')
    .reduce((a, b) => a + (b.amount || 0), 0);

  const sysMsg = `${SYSTEM_PROMPT}
Дата: ${now.toLocaleDateString('ru')}.
Заказов: ${(db.orders || []).length}, активных: ${
  (db.orders || []).filter(o => o.status === 'new' || o.status === 'work').length
}.
Доходы/месяц: ${incM}₽, расходы: ${expM}₽, прибыль: ${incM - expM}₽.
Клиентов: ${(db.clients || []).length}.
Компания: ${db.settings?.company || 'не указана'}.`;

  const messages = [
    { role: 'system',    content: sysMsg },
    ...chatContext.slice(-20),
    { role: 'user',      content: message },
  ];

  const res = await fetch('https://api.deepseek.com/chat/completions', {
    method:  'POST',
    headers: {
      'Content-Type':  'application/json',
      'Authorization': `Bearer ${key}`
    },
    body: JSON.stringify({
      model: mdl, messages, max_tokens: 2048, temperature: 0.8
    }),
  });

  if (!res.ok) {
    const err = await res.json().catch(() => ({}));
    throw new Error(err.error?.message || `HTTP ${res.status}`);
  }
  const data = await res.json();
  return data.choices[0].message.content;
}

function appendChatMsg(role, text) {
  const container = document.getElementById('chatMessages');
  if (!container) return;
  const now  = new Date();
  const time = `${String(now.getHours()).padStart(2, '0')}:${String(now.getMinutes()).padStart(2, '0')}`;
  const div  = document.createElement('div');
  div.className = `chat-msg ${role}`;
  const fmt  = t => t
    .replace(/\n/g, '<br>')
    .replace(/\*\*(.*?)\*\*/g, '<b>$1</b>')
    .replace(/\*(.*?)\*/g, '<i>$1</i>');
  div.innerHTML = role === 'ai'
    ? `<div class="chat-avatar">🤖</div>
       <div>
         <div class="chat-bubble">${fmt(text)}</div>
         <div class="chat-time">${time}</div>
       </div>`
    : `<div>
         <div class="chat-bubble">${fmt(text)}</div>
         <div class="chat-time" style="text-align:right;">${time}</div>
       </div>
       <div class="chat-avatar">👤</div>`;
  container.appendChild(div);
  container.scrollTop = container.scrollHeight;
}

function showTyping() {
  const container = document.getElementById('chatMessages');
  if (!container) return;
  const div = document.createElement('div');
  div.className = 'chat-msg ai';
  div.id = 'typingIndicator';
  div.innerHTML =
    `<div class="chat-avatar">🤖</div>
     <div class="chat-bubble" style="padding:14px 18px;">
       <div class="typing-indicator">
         <div class="typing-dot"></div>
         <div class="typing-dot"></div>
         <div class="typing-dot"></div>
       </div>
     </div>`;
  container.appendChild(div);
  container.scrollTop = container.scrollHeight;
}

function hideTyping() {
  document.getElementById('typingIndicator')?.remove();
}

async function sendChatMessage() {
  const input = document.getElementById('chatInput');
  const text  = input?.value.trim();
  if (!text) return;
  input.value = '';
  if (input.style) input.style.height = 'auto';
  appendChatMsg('user', text);
  chatContext.push({ role: 'user', content: text });
  showTyping();
  try {
    const reply = await callDeepSeekAPI(text);
    hideTyping();
    appendChatMsg('ai', reply);
    chatContext.push({ role: 'assistant', content: reply });
    const db = getDB();
    db.chatHistory = chatContext.slice(-50);
    saveDB(db);
  } catch (e) {
    hideTyping();
    appendChatMsg('ai',
      `⚠️ Ошибка: ${e.message}\n\nПроверьте API ключ в Настройках.`);
  }
}

function sendQuickChat(text) {
  const input = document.getElementById('chatInput');
  if (input) input.value = text;
  sendChatMessage();
}

/* ============================================================
   INIT
============================================================ */
async function init() {
  clearOldLocalStorage();

  showSyncStatus('loading');
  const serverOk = await loadFromServer();

  if (serverOk) {
    console.log('✅ Загружено с сервера — orders:',
      getDB().orders?.length, 'finance:', getDB().finance?.length);
    refreshDashboard();
    updateOrdersBadge();
    updateNotesBadge();
    updateDBSize();
  } else {
    dbCache  = initDBStructure();
    isLoaded = false;
    notify('❌ Сервер недоступен. Данные не загружены.', 'error');
    refreshDashboard();
  }

  const db = getDB();
  if (db.chatHistory?.length) {
    chatContext = db.chatHistory;
    const container = document.getElementById('chatMessages');
    if (container) {
      container.innerHTML = '';
      chatContext.forEach(m => {
        if (m.role === 'user' || m.role === 'assistant')
          appendChatMsg(m.role === 'assistant' ? 'ai' : 'user', m.content);
      });
    }
  } else {
    setTimeout(() => appendChatMsg('ai',
      `🎉 Привет! Я **Валера** — ваш эксперт по типографии!\n\n` +
      `Знаю всё о форматах, материалах, ценообразовании 😄\n\n` +
      `Добавьте API ключ DeepSeek в **Настройках** и начнём!`
    ), 400);
  }

  const fileInput = document.getElementById('order_files_input');
  if (fileInput) {
    fileInput.onchange = e => {
      const files = Array.from(e.target.files);
      handleOrderFiles(files);
    };
  }

  const mods     = window.CRM?._modules || {};
  const modCount = Object.keys(mods).length;
  console.log(
    `✅ Система запущена | Модулей: ${modCount} | Сервер: ${serverOk ? 'OK' : 'недоступен'}`
  );
}

init();