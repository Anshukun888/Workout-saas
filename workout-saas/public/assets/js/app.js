// public/assets/js/app.js
// Debounce helper
function debounce(fn, wait=150){ let t; return (...a)=>{ clearTimeout(t); t=setTimeout(()=>fn(...a), wait); }; }

// ================= Sets UI =================
function initSetsUI(){
  const container = document.getElementById('setsContainer');
  if (!container) return;
  container.innerHTML = '';
  addSetRow();
  document.getElementById('addSetBtn').addEventListener('click', addSetRow);
  document.getElementById('clearSetsBtn').addEventListener('click', ()=>{ container.innerHTML=''; addSetRow(); });
}

function addSetRow(reps='', weight=''){
  const container = document.getElementById('setsContainer');
  if (!container) return;
  const row = document.createElement('div');
  row.className = 'd-flex gap-2 align-items-center mb-2 set-row';
  row.innerHTML = `
    <input type="number" min="0" class="form-control form-control-sm reps" placeholder="Reps" value="${reps}">
    <input type="text" class="form-control form-control-sm weight" placeholder="Weight (e.g. 60 kg)" value="${weight}">
    <button type="button" class="btn btn-sm btn-outline-danger remove-set">X</button>
  `;
  row.querySelector('.remove-set').addEventListener('click', ()=> row.remove());
  container.appendChild(row);
}

function collectSets(){
  const rows = document.querySelectorAll('#setsContainer .set-row');
  const out = [];
  rows.forEach(r=>{
    const reps = parseInt(r.querySelector('.reps').value || 0, 10);
    const weight = (r.querySelector('.weight').value || '').trim();
    // push even if weight empty (user might track reps only)
    if (reps || weight) out.push({reps: reps||0, weight});
  });
  return out;
}

// ================= YouTube-like search (server-backed) =================
const titleInput = document.getElementById('titleInput');
const suggestionBox = document.getElementById('suggestions');
const typeSelect = document.getElementById('typeSelect');

async function fetchSuggestions(query){
  if (!query || query.length < 1) return [];
  try {
    const url = 'api/search_workout.php?q=' + encodeURIComponent(query) + '&limit=10';
    const res = await fetch(url);
    if (!res.ok) return [];
    const data = await res.json();
    return data;
  } catch (e) { console.error(e); return []; }
}

const showSuggestions = debounce(async function(e){
  const q = (e.target.value || '').trim();
  suggestionBox.innerHTML = '';
  if (!q) return;
  const items = await fetchSuggestions(q);
  if (!items || items.length === 0) return;
  items.forEach(it=>{
    const btn = document.createElement('button');
    btn.type = 'button';
    btn.className = 'list-group-item list-group-item-action';
    // highlight match (simple)
    const name = it.name;
    btn.innerHTML = `<strong>${name}</strong> <small class="text-muted"> — ${it.type||''}</small>`;
    btn.onclick = ()=>{
      fillSuggestion(name, it.type || '');
      suggestionBox.innerHTML = '';
    };
    suggestionBox.appendChild(btn);
  });
}, 120);

if (titleInput) {
  titleInput.addEventListener('input', showSuggestions);
  document.addEventListener('click', function(e){
    if (!titleInput.contains(e.target) && !suggestionBox.contains(e.target)) suggestionBox.innerHTML = '';
  });
}

// set type intelligently when selecting suggestion
function fillSuggestion(name, type){
  if (titleInput) titleInput.value = name;
  if (type && typeSelect) {
    let found = false;
    for (let i=0;i<typeSelect.options.length;i++){
      if (typeSelect.options[i].text.toLowerCase() === type.toLowerCase()){
        typeSelect.selectedIndex = i; found = true; break;
      }
    }
    if (!found){
      const opt = document.createElement('option'); opt.text = type; opt.value = type;
      typeSelect.add(opt); typeSelect.value = type;
    }
  }
}

// ================= Add workout (submit) =================
const addForm = document.getElementById('addForm');
if (addForm) {
  addForm.addEventListener('submit', async function(e){
    e.preventDefault();
    const btn = addForm.querySelector('button[type="submit"]');
    btn.disabled = true;

    const fd = new FormData(addForm);
    const sets = collectSets();
    fd.append('sets', JSON.stringify(sets));

    try {
      const res = await fetch('api/add_workout.php', { method: 'POST', body: fd });
      const data = await res.json();
      if (data.success) {
        showTempAlert('Workout added', 'success');
        document.getElementById('activeCount').textContent = data.active;
        document.getElementById('historyCount').textContent = data.history;
        addForm.reset();
        document.getElementById('setsContainer').innerHTML = '';
        addSetRow();
        reloadTrackList();
        loadHeatmap(); // refresh heatmap optionally
      } else {
        showTempAlert(data.message || 'Could not add workout', 'danger');
      }
    } catch (err) {
      console.error(err);
      showTempAlert('Network error', 'danger');
    } finally {
      btn.disabled = false;
    }
  });
}

// ================= Move / Remove (use your existing API paths) =================
async function moveWorkout(id){
  try {
    const res = await fetch('api/move_workout.php', { method:'POST', headers:{'Content-Type':'application/json'}, body: JSON.stringify({id}) });
    const data = await res.json();
    if (data.success) {
      const el = document.getElementById('workout-'+id); if (el) el.remove();
      document.getElementById('activeCount').textContent = data.active;
      document.getElementById('historyCount').textContent = data.history;
      reloadTrackList();
      loadHeatmap();
    }
  } catch (e) { console.error(e); }
}

async function removeWorkout(id){
  try {
    const res = await fetch('api/remove_workout.php', { method:'POST', headers:{'Content-Type':'application/json'}, body: JSON.stringify({id}) });
    const data = await res.json();
    if (data.success) {
      const el1 = document.getElementById('workout-'+id); if (el1) el1.remove();
      const el2 = document.getElementById('log-'+id); if (el2) el2.remove();
      const ac = document.getElementById('activeCount'); if (ac) ac.textContent = data.active;
      await reloadTrackList();
      loadHeatmap();
    }
  } catch (e) { console.error(e); }
}

// helpers for Log/Track refresh
function prefillLogSets(){
  document.querySelectorAll('#log .list-group-item[id^="log-"]').forEach(el=>{
    const id = parseInt(el.id.replace('log-',''),10);
    const json = el.getAttribute('data-sets');
    const container = document.getElementById('setsContainer-'+id);
    if (!container) return;
    container.innerHTML = '';
    if (json) {
      try {
        const arr = JSON.parse(json);
        if (Array.isArray(arr) && arr.length){ arr.forEach(s=> addSetRowFor(id, s.reps||'', s.weight||'')); }
        else addSetRowFor(id);
      } catch { addSetRowFor(id); }
    } else {
      addSetRowFor(id);
    }
  });
}

function applyDayFilter(){
  const todaySel = document.getElementById('todayRoutineSelect');
  const idx = todaySel ? parseInt(todaySel.value||'0',10) : 0;
  document.querySelectorAll('#log .list-group-item[id^="log-"]').forEach(el=>{
    const rdi = parseInt(el.getAttribute('data-rdi')||'0',10); el.style.display = (!idx || !rdi || rdi === idx) ? '' : 'none';
  });
  document.querySelectorAll('#track .list-group-item[id^="workout-"]').forEach(el=>{
    const rdi = parseInt(el.getAttribute('data-rdi')||'0',10); el.style.display = (!idx || !rdi || rdi === idx) ? '' : 'none';
  });
}

// reload lists for Track and Log
async function reloadTrackList(){
  try {
    const resp = await fetch(location.pathname);
    const html = await resp.text();
    const div = document.createElement('div'); div.innerHTML = html;
    const newTrack = div.querySelector('#workoutList');
    if (newTrack && document.querySelector('#workoutList')) document.querySelector('#workoutList').innerHTML = newTrack.innerHTML;
    const newLog = div.querySelector('#logList');
    if (newLog && document.querySelector('#logList')) document.querySelector('#logList').innerHTML = newLog.innerHTML;
    // after replacing, re-initialize sets rows and filters
    prefillLogSets();
    applyDayFilter();
  } catch (e) { console.error(e); }
}

// ================= Heatmap rendering =================
async function loadHeatmap(){
  const rangeDays = document.getElementById('heatmapRange') ? document.getElementById('heatmapRange').value : '30';
  const routineOnly = '0';
  try {
    const res = await fetch('api/stats.php?range=' + encodeURIComponent(rangeDays) + '&routineOnly=' + routineOnly);
    const json = await res.json();
    if (!json.success) return;
    const data = json.data; // array of {type, cnt}
    // build a map of type->count
    const map = {};
    let max = 0;
    data.forEach(r => { map[r.type] = parseInt(r.cnt,10); if (map[r.type] > max) max = map[r.type]; });

    // list of svg ids and matching type names (keys in DB)
      const mapping = {
  'Chest': ['Chest'],
  'Back': ['Back'],
  'Legs': ['Legs'],
  'Quads': ['Quads_L', 'Quads_R'],
  'Hamstrings': ['Hamstrings_L', 'Hamstrings_R'],
  'Calves': ['Calves_L', 'Calves_R', 'Calves_Front_L', 'Calves_Front_R'],
  'Biceps': ['Biceps_Front_L', 'Biceps_Front_R'],
  'Forearms': ['Forearms_Front_L', 'Forearms_Front_R', 'Forearms_Back_L', 'Forearms_Back_R'],
  'Triceps': ['Triceps_Back_L', 'Triceps_Back_R'],
  'Shoulders': ['Shoulders_Front', 'Shoulders_Back'],
  'Abs': ['Abs'],
  'Neck': ['Neck_Front', 'Neck_Back'],
  'Glutes': ['Glutes'],
  'Full Body': ['Full_Body'],
  'Cardio': ['Cardio']
};

// color each part
Object.keys(mapping).forEach(typeName => {
  const cnt = map[typeName] || 0;
  const color = getHeatColor(cnt, max);
  mapping[typeName].forEach(id => {
    const el = document.getElementById(id);
    if (el) el.style.fill = color;
  });
});

  } catch (e) { console.error(e); }
}

// map count to color (light -> dark red)
function getHeatColor(count, max){
  if (!count || max === 0) return '#ffecec'; // very light
  const ratio = Math.min(1, count / max);
  // from light pink to dark red using HSL
  const hue = 0; // red
  const sat = 80; // percent
  const light = 85 - Math.round(ratio * 50); // 85 -> 35
  return `hsl(${hue} ${sat}% ${light}%)`;
}

// ================= small helpers =================
function showTempAlert(text, type='info'){
  const wrapper = document.createElement('div');
  wrapper.innerHTML = `<div class="alert alert-${type} alert-dismissible position-fixed top-0 end-0 m-3" role="alert" style="z-index:20000">
     ${text} <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
  </div>`;
  document.body.appendChild(wrapper);
  setTimeout(()=> wrapper.remove(), 3800);
}

// initialize on DOM ready
document.addEventListener('DOMContentLoaded', function(){
  // initSetsUI(); // removed old add workout UI
  loadHeatmap();
  const rangeSelect = document.getElementById('heatmapRange');
  if (rangeSelect) rangeSelect.addEventListener('change', loadHeatmap);
  // routine-only toggle removed

  // Initialize the history chart and load stats
  initHistoryChart();
  loadStats();

  // Routine init
  initRoutineUI();

  // Profile init
  initProfileUI();

  // Filter Log and Track by selected 'today' routine day
  const todaySel = document.getElementById('todayRoutineSelect');
  if (todaySel) todaySel.addEventListener('change', applyDayFilter);
});
/* Stats & chart handling (same as previous) */
let historyChart = null;
async function loadStats(){
  const range = document.getElementById('rangeSelect') ? document.getElementById('rangeSelect').value : 'week';
  const res = await fetch('api/stats.php?range=' + encodeURIComponent(range));
  const json = await res.json();
  if (!json.success) return;
  const labels = json.data.map(r => r.type || 'Unknown');
  const counts = json.data.map(r => r.cnt);
  const container = document.getElementById('typeCounts');
  container.innerHTML = '';
  json.data.forEach(r => {
    const el = document.createElement('div');
    el.className = 'd-flex justify-content-between';
    el.innerHTML = `<div>${r.type}</div><div><strong>${r.cnt}</strong></div>`;
    container.appendChild(el);
  });
  updateChart(labels, counts);
}

function initHistoryChart(){
  const ctx = document.getElementById('historyChart');
  if (!ctx) return;
  historyChart = new Chart(ctx, {
    type: 'doughnut',
    data: { labels: [], datasets: [{ data: [], backgroundColor: [] }] },
    options: { plugins: { legend: { position: 'bottom' } } }
  });
  const rangeSelect = document.getElementById('rangeSelect');
  if (rangeSelect) rangeSelect.addEventListener('change', loadStats);
}

function updateChart(labels, data){
  if (!historyChart) return;
  const colors = labels.map((_, i) => `hsl(${(i*57)%360} 70% 50%)`);
  historyChart.data.labels = labels;
  historyChart.data.datasets[0].data = data;
  historyChart.data.datasets[0].backgroundColor = colors;
  historyChart.update();
}

// ================= Routine Builder =================
async function fetchRoutine(){
  const res = await fetch('api/routine_get.php');
  const json = await res.json();
  if (!json.success) return null;
  return json.routine;
}

function renderRoutine(routine){
  if (!routine) return;
  const nameEl = document.getElementById('routineName');
  const daysEl = document.getElementById('routineDays');
  const patternEl = document.getElementById('routinePattern');
  const startEl = document.getElementById('routineStart');
  const grid = document.getElementById('routineBuilder');
  const todaySel = document.getElementById('todayRoutineSelect');
  if (nameEl) nameEl.value = routine.name || 'My Routine';
  if (daysEl) daysEl.value = String(routine.days_per_week || 3);
  if (patternEl) patternEl.value = String(routine.pattern_length || routine.days_per_week || 3);
  if (startEl) startEl.value = String(routine.start_weekday || 1);
  if (!grid) return;
  grid.innerHTML = '';
  const daysCount = parseInt(daysEl.value,10);
  const byIndex = {};
  (routine.days||[]).forEach(d=>{ byIndex[d.day_index]=d; });
  // populate today selector
  if (todaySel) {
    todaySel.innerHTML='';
    for (let i=1;i<=daysCount;i++){
      const d = byIndex[i] || {day_index:i, name:`Day ${i}`};
      const opt = document.createElement('option');
      opt.value = String(i); opt.textContent = `${i}. ${d.name}`;
      todaySel.appendChild(opt);
    }
    if (routine.today_index) todaySel.value = String(routine.today_index);
  }
  for (let i=1;i<=daysCount;i++){
    const day = byIndex[i] || {day_index:i, name:`Day ${i}`, exercises:[]};
    const col = document.createElement('div');
    col.className = 'routine-day';
    col.innerHTML = `
      <div class="d-flex justify-content-between align-items-center mb-2">
        <div class="d-flex align-items-center gap-2 flex-grow-1">
          <input class="form-control form-control-sm routine-day-name" data-day-index="${i}" value="${day.name}" onclick="this.select()">
          <span class="badge bg-success selected-flag" style="display:none;">Selected</span>
        </div>
        <button class="btn btn-sm btn-outline-primary ms-2" data-add-ex="${i}">Add Exercise</button>
      </div>
      <div class="mb-2 position-relative">
        <input class="form-control form-control-sm day-search" data-day="${i}" placeholder="Search to add... (Enter to add)">
        <div class="list-group day-results" data-day="${i}" style="position:absolute; z-index:1000; width:100%; display:none;"></div>
      </div>
      <div class="routine-ex-list" data-day="${i}"></div>
    `;
    grid.appendChild(col);
    const list = col.querySelector('.routine-ex-list');
    (day.exercises||[]).forEach(ex => list.appendChild(renderExerciseRow(ex.title, ex.type || '', ex.default_sets || [])));
    // click to select the card
    col.addEventListener('click', (e)=>{
      // ignore clicks on inputs/buttons
      if (e.target.closest('input') || e.target.closest('button')) return;
      const sel = document.getElementById('todayRoutineSelect');
      if (sel) sel.value = String(i);
      // highlight selection
      document.querySelectorAll('.routine-day').forEach(x=> x.classList.remove('selected'));
      col.classList.add('selected');
      document.querySelectorAll('.routine-day .selected-flag').forEach(b=> b.style.display='none');
      const flag = col.querySelector('.selected-flag'); if (flag) flag.style.display='inline-block';
    });
    // attach per-day search handlers
    const ds = col.querySelector('.day-search');
    const dr = col.querySelector('.day-results');
    if (ds && dr) attachDaySearch(ds, dr, i, grid);
  }
  // apply initial selection highlight based on dropdown value, routine.today_index, or default to first day
  let initialIdx = todaySel ? parseInt(todaySel.value||'0',10) : (routine.today_index||1);
  if (!initialIdx || initialIdx < 1) initialIdx = 1; // ensure we have a valid index
  
  const cards = grid.querySelectorAll('.routine-day');
  if (cards.length > 0) {
    const card = cards[initialIdx-1];
    if (card){
      cards.forEach(x=> x.classList.remove('selected'));
      document.querySelectorAll('.routine-day .selected-flag').forEach(b=> b.style.display='none');
      card.classList.add('selected');
      const flag = card.querySelector('.selected-flag'); if (flag) flag.style.display='inline-block';
      
      // also update the dropdown to match the selected day
      if (todaySel) todaySel.value = String(initialIdx);
    }
  }
  // add handlers
  grid.querySelectorAll('[data-add-ex]').forEach(btn=>{
    btn.addEventListener('click', ()=>{
      const day = btn.getAttribute('data-add-ex');
      const list = grid.querySelector(`.routine-ex-list[data-day="${day}"]`);
      if (list) list.appendChild(renderExerciseRow('', '', []));
    });
  });

  // Global search to add to selected day
  const globalSearchInput = document.getElementById('routineSearchInput');
  const globalSearchResults = document.getElementById('routineSearchResults');
  if (globalSearchInput && globalSearchResults){
    const doGlobal = debounce(async ()=>{
      const q = (globalSearchInput.value||'').trim();
      if (!q) { globalSearchResults.style.display='none'; globalSearchResults.innerHTML=''; return; }
      try {
        const res = await fetch('api/search_workout.php?q='+encodeURIComponent(q)+'&limit=10&fallback=1');
        let items = []; if (res.ok) items = await res.json();
        globalSearchResults.innerHTML='';
        if (!items || items.length === 0){ globalSearchResults.style.display='none'; return; }
        items.forEach(it=>{
          const btn = document.createElement('button');
          btn.type='button'; btn.className='list-group-item list-group-item-action';
          btn.textContent = it.name + (it.type ? ' — '+it.type : '');
          btn.onclick = ()=>{
            const sel = document.getElementById('todayRoutineSelect');
            const idx = sel ? parseInt(sel.value,10) : 1;
            const list = grid.querySelector(`.routine-ex-list[data-day="${idx}"]`);
            if (list) list.appendChild(renderExerciseRow(it.name, it.type||'', []));
            globalSearchResults.style.display='none'; globalSearchResults.innerHTML=''; globalSearchInput.value='';
          };
          globalSearchResults.appendChild(btn);
        });
        globalSearchResults.style.display='block';
      } catch(e){ console.error(e); globalSearchResults.style.display='none'; }
    }, 120);
    globalSearchInput.addEventListener('input', doGlobal);
    document.addEventListener('click', (e)=>{ if (!globalSearchInput.contains(e.target) && !globalSearchResults.contains(e.target)) { globalSearchResults.style.display='none'; }});
  }
}

function renderExerciseRow(title, type, sets){
  const row = document.createElement('div');
  row.className = 'routine-ex-row';
  row.innerHTML = `
    <div class="d-flex gap-2 align-items-center mb-2 w-100">
      <div class="position-relative flex-grow-1">
        <input class="form-control form-control-sm ex-title" placeholder="Exercise name" autocomplete="off" value="${(title||'').replace(/"/g,'&quot;')}">
        <div class="list-group routine-suggestions" style="position:absolute; z-index: 1000; width:100%; display:none;"></div>
      </div>
      <input class="form-control form-control-sm ex-type" placeholder="Type (e.g., Chest)" value="${(type||'').replace(/"/g,'&quot;')}">
      <button class="btn btn-sm btn-outline-danger ex-del">Remove</button>
    </div>
  `;
  row.querySelector('.ex-del').addEventListener('click', ()=> row.remove());
  const titleInput = row.querySelector('.ex-title');
  if (titleInput) attachRoutineSuggestion(titleInput);
  return row;
}

function collectRoutineData(){
  const name = document.getElementById('routineName')?.value || 'My Routine';
  const days_per_week = parseInt(document.getElementById('routineDays')?.value || '3',10);
  const pattern_length = days_per_week; // pattern length equals days per week now
  const start_weekday = parseInt(document.getElementById('routineStart')?.value || '1',10);
  const grid = document.getElementById('routineBuilder');
  const days = [];
  if (grid){
    const cols = grid.querySelectorAll('.routine-day');
    let idx = 0;
    cols.forEach(col=>{
      idx++;
      const nameInput = col.querySelector('.routine-day-name');
      const exList = col.querySelectorAll('.routine-ex-row');
      const exercises = [];
      exList.forEach((row, i)=>{
        const t = row.querySelector('.ex-title').value.trim();
        const ty = row.querySelector('.ex-type').value.trim();
        if (t) exercises.push({ title: t, type: ty, default_sets: [] });
      });
      days.push({ day_index: idx, name: nameInput?.value || `Day ${idx}`, exercises });
    });
  }
  return { name, days_per_week, pattern_length, start_weekday, days };
}

async function saveRoutine(){
  const payload = collectRoutineData();
  const res = await fetch('api/routine_save.php', { method:'POST', headers:{'Content-Type':'application/json'}, body: JSON.stringify(payload) });
  const json = await res.json();
  if (json.success) showTempAlert('Routine saved', 'success'); else showTempAlert('Could not save routine', 'danger');
}

// removed generateWeek (legacy)

function initRoutineUI(){
  const saveBtn = document.getElementById('saveRoutineBtn');
  if (saveBtn) saveBtn.addEventListener('click', saveRoutine);
  const startBtn = document.getElementById('startDayBtn');
  if (startBtn) startBtn.addEventListener('click', startSelectedDay);
  const startBtn2 = document.getElementById('startDayBtn2');
  if (startBtn2) startBtn2.addEventListener('click', startSelectedDay);
  // routine analysis toggle removed
  const daysEl = document.getElementById('routineDays');
  if (daysEl) daysEl.addEventListener('change', ()=>{
    const current = collectRoutineData();
    current.days_per_week = parseInt(daysEl.value,10);
    renderRoutine(current);
  });
  // Fetch and render
  fetchRoutine().then(renderRoutine);
  // Preload existing set rows in Log tab from embedded 'data-sets'
  document.querySelectorAll('#log .list-group-item[id^="log-"]').forEach(el=>{
    const id = parseInt(el.id.replace('log-',''),10);
    const json = el.getAttribute('data-sets');
    const container = document.getElementById('setsContainer-'+id);
    if (!container) return;
    container.innerHTML = '';
    if (json) {
      try {
        const arr = JSON.parse(json);
        if (Array.isArray(arr) && arr.length){ arr.forEach(s=> addSetRowFor(id, s.reps||'', s.weight||'')); }
        else addSetRowFor(id);
      } catch { addSetRowFor(id); }
    } else {
      addSetRowFor(id);
    }
  });
}

async function startSelectedDay(){
  const todaySel = document.getElementById('todayRoutineSelect');
  const idx = todaySel ? parseInt(todaySel.value,10) : 1;
  try {
    const fd = new FormData(); fd.append('day_index', String(idx));
    const res = await fetch('api/start_day.php', { method:'POST', body: fd });
    const json = await res.json();
    if (json.success) {
      await reloadTrackList();
      // Switch to Log tab
      document.getElementById('log-tab')?.click();
      // Apply filter show
      const evt = new Event('change'); todaySel.dispatchEvent(evt);
    }
  } catch (e) { console.error(e); }
}

// Suggestions for routine builder inputs
function attachRoutineSuggestion(input){
  const box = input.parentElement.querySelector('.routine-suggestions');
  const typeInput = input.closest('.routine-ex-row').querySelector('.ex-type');
  const hide = ()=>{ if (box) { box.style.display='none'; box.innerHTML=''; } };
  const show = (items)=>{
    if (!box) return;
    box.innerHTML='';
    if (!items || items.length === 0) { hide(); return; }
    items.forEach(it=>{
      const btn = document.createElement('button');
      btn.type = 'button';
      btn.className = 'list-group-item list-group-item-action';
      btn.textContent = it.name + (it.type ? ' — '+it.type : '');
      btn.onclick = ()=>{ input.value = it.name; if (typeInput && it.type) typeInput.value = it.type; hide(); };
      box.appendChild(btn);
    });
    box.style.display = 'block';
  };
  const doSearch = debounce(async ()=>{
    const q = (input.value || '').trim();
    if (!q) { hide(); return; }
    try {
      const res = await fetch('api/search_workout.php?q='+encodeURIComponent(q)+'&limit=10&fallback=1');
      let items = [];
      if (res.ok) items = await res.json();
      if ((!items || items.length===0) && window.workouts && Array.isArray(window.workouts)){
        const lower = q.toLowerCase();
        const pref = window.workouts.filter(n=> n.toLowerCase().startsWith(lower)).slice(0,10).map(n=>({name:n, type: inferType(n)}));
        const contains = window.workouts.filter(n=> !n.toLowerCase().startsWith(lower) && n.toLowerCase().includes(lower)).slice(0,10 - pref.length).map(n=>({name:n, type: inferType(n)}));
        items = pref.concat(contains);
      }
      show(items);
    } catch(e){ console.error(e); hide(); }
  }, 120);
  input.addEventListener('input', doSearch);
  document.addEventListener('click', (e)=>{ if (!input.contains(e.target) && !box.contains(e.target)) hide(); });
}

// Per-day search box logic
function attachDaySearch(input, resultsBox, dayIndex, grid){
  const hide = ()=>{ resultsBox.style.display='none'; resultsBox.innerHTML=''; };
  const addToDay = (name, type)=>{
    const list = grid.querySelector(`.routine-ex-list[data-day="${dayIndex}"]`);
    if (list) list.appendChild(renderExerciseRow(name, type||'', []));
  };
  const doSearch = debounce(async ()=>{
    const q = (input.value||'').trim();
    if (!q) { hide(); return; }
    try {
      const res = await fetch('api/search_workout.php?q='+encodeURIComponent(q)+'&limit=10&fallback=1');
      let items = []; if (res.ok) items = await res.json();
      // merge with local list (like your example) for instant suggestions
      if (window.workouts && Array.isArray(window.workouts)){
        const lower = q.toLowerCase();
        const localPref = window.workouts.filter(n=> n.toLowerCase().startsWith(lower)).slice(0,10).map(n=>({name:n, type: inferType(n)}));
        const have = new Set((items||[]).map(i=> (i.name||'').toLowerCase()));
        localPref.forEach(it=>{ const k=(it.name||'').toLowerCase(); if (!have.has(k)) items.push(it); });
      }
      resultsBox.innerHTML='';
      if (!items || items.length===0){ hide(); return; }
      items.forEach(it=>{
        const btn = document.createElement('button');
        btn.type='button'; btn.className='list-group-item list-group-item-action';
        btn.textContent = it.name; // show name like your example
        btn.onclick = ()=>{ addToDay(it.name, it.type); hide(); input.value=''; };
        resultsBox.appendChild(btn);
      });
      resultsBox.style.display='block';
    } catch(e){ console.error(e); hide(); }
  }, 120);
  input.addEventListener('input', doSearch);
  input.addEventListener('keydown', (e)=>{
    if (e.key === 'Enter'){
      e.preventDefault();
      const first = resultsBox.querySelector('.list-group-item');
      if (first){ first.click(); } else {
        // no suggestion; add with inferred type
        addToDay(input.value.trim(), inferType(input.value)); input.value=''; hide();
      }
    }
  });
  document.addEventListener('click', (e)=>{ if (!input.contains(e.target) && !resultsBox.contains(e.target)) hide(); });
}

function inferType(name) {
  const n = name.toLowerCase();
  const escapeRegExp = (s) => s.replace(/[.*+?^${}()|[\]\\]/g, '\\$&');

  const map = {
    Neck: [
      "Neck Curl","Neck Flexion","Neck Extension","Neck Lateral Flexion",
      "Neck Rotation","Isometric Neck Hold","Neck Harness Extension",
      "Neck Harness Flexion","Shrugs with Dumbbells","Barbell Shrugs",
      "Cable Shrugs","Upright Row","Farmer’s Walk with Shrugs","Kettlebell Shrugs"
    ],
    Shoulders: [
      "Overhead Press","Dumbbell Shoulder Press","Barbell Shoulder Press",
      "Arnold Press","Lateral Raise","Front Raise","Rear Delt Fly",
      "Cable Face Pull","Cuban Press","Snatch Grip High Pull",
      "Handstand Push-ups","Pike Push-ups","Z-Press","Dumbbell Reverse Fly",
      "Plate Raises","Kettlebell Overhead Press","Landmine Press",
      "Single-arm Dumbbell Press","Cable Lateral Raise","Incline Lateral Raise",
      "Reverse Pec Deck Fly","Dumbbell Cuban Rotation","Landmine 180 Press",
      "Dumbbell Y-Press","Kettlebell Bottoms-Up Press","Shoulder Tap Push-ups",
      "Wall Slides"
    ],
      Chest: [
      "bench press","incline bench press","decline bench press","dumbbell press",
      "incline dumbbell press","decline dumbbell press","chest press","pec deck",
      "chest fly","cable crossover","cable fly","push-up","incline push-up",
      "decline push-up","diamond push-up","floor press","medicine ball push-up",
      "dumbbell fly","incline fly","floor dumbbell fly","single-arm dumbbell press"
    ],
    Back: [
      "Pull-ups","Chin-ups","Inverted Row","Bent Over Row (Barbell)",
      "Dumbbell Row","T-Bar Row","Cable Row","Lat Pulldown","Face Pulls",
      "Straight Arm Pulldown","Renegade Row","Meadows Row","Seal Row",
      "Kettlebell Row","Back Extension","Scapular Pull-ups",
      "Single-arm Cable Row","Resistance Band Pull-apart",
      "Lat Pullover with Dumbbell","Kettlebell Renegade Row","Cable High Row",
      "Standing T-Bar Row","Meadows Row (Barbell)","Chest Supported Row",
      "Wide-grip Pull-up","Close-grip Pulldown","Deadlift to Shrug",
      "Reverse Grip Bent Over Row","Single-arm Inverted Row","TRX Row",
      "Scapular Retraction Holds","Deadlift with Dumbbells",
      "Deadlift (Conventional)","Romanian Deadlift","Sumo Deadlift",
      "Good Mornings","Hyperextensions","Kettlebell Swings","Rack Pulls",
      "Single-Leg Deadlift","Back Extensions on Stability Ball",
      "Suitcase Deadlift","Kettlebell Deadlift","Single-leg Back Extension",
      "Stability Ball Back Extension","Glute Ham Raises","Bird Dog",
      "Supermans","Floor Bridges"
    ],
    Biceps: [
      "Barbell Curl","Dumbbell Curl","Hammer Curl","Concentration Curl",
      "Preacher Curl","Zottman Curl","Cable Curl","Incline Dumbbell Curl",
      "Spider Curl","Chin-up Hold","Resistance Band Curl",
      "Cross-body Hammer Curl","21s (Curl variations)",
      "Cross-body Dumbbell Curl","Cable Rope Hammer Curl",
      "Dumbbell Drag Curl","Barbell Reverse Curl",
      "Zottman Curl with Dumbbells","Resistance Band Curl with Hold",
      "Incline Inner-biceps Curl","Spider Curl on Incline Bench",
      "Concentration Curl with Pause","Preacher Curl with EZ Bar"
    ],
    Triceps: [
      "Tricep Dips","Tricep Pushdown","Overhead Tricep Extension",
      "Skull Crushers","Close-Grip Bench Press","Kickbacks",
      "Cable Tricep Extension","One-Arm Overhead Extension","Bench Dips",
      "Diamond Push-ups","Dumbbell Floor Press","Rope Pushdown",
      "Dumbbell Tate Press","One-arm Cable Overhead Extension",
      "Close-Grip Push-ups","Dumbbell Kickbacks with Twist",
      "EZ Bar Skull Crushers","Overhead Rope Extension",
      "Dumbbell Overhead Extension",
      "Bodyweight Tricep Extension (inverted push-up)"
    ],
    Forearms: [
      "Wrist Curls","Reverse Wrist Curls","Farmer’s Walk","Plate Pinch",
      "Towel Pull-ups","Dead Hangs","Rice Bucket Training",
      "Fingertip Push-ups","Wrist Roller Exercise","Hammer Curls",
      "Kettlebell Holds","Plate Wrist Rotations","Reverse Dumbbell Curl",
      "Fingertip Dead Hang","Towel Grip Rows","Rice Finger Pinches",
      "Fat Grip Farmer’s Carry","Wrist Roller with Weight",
      "Plate Pinch Walk","Kettlebell Finger Holds","Wrist Flexion Stretch"
    ],
    Abs: [
      "Crunches","Sit-ups","Plank","Side Plank","Russian Twist",
      "Leg Raises","Hanging Leg Raises","Bicycle Crunches","Toe Touches",
      "Flutter Kicks","V-Ups","Ab Wheel Rollout","Mountain Climbers",
      "Cable Woodchopper","Medicine Ball Slams","Side Bends","Dead Bug",
      "Windshield Wipers","L-Sit Hold","Hanging Knee Tucks","Dragon Flag",
      "Hanging Windshield Wipers","Cable Russian Twist","Weighted Plank",
      "Medicine Ball Russian Twist","Stability Ball Pike",
      "TRX Mountain Climbers","Side Plank with Leg Raise",
      "Lying Leg Circles","Hollow Body Hold",
      "Hanging Oblique Knee Raises","V-Sit Hold",
      "Cable Anti-Rotation Press","Plank to Push-up","Spiderman Plank",
      "Plank Jacks"
    ],
    Quads: [
      "Squats (Back)","Front Squat","Bulgarian Split Squat",
      "Lunges (Forward)","Walking Lunges","Reverse Lunges","Step-ups",
      "Leg Press","Leg Extension","Wall Sit","Jump Squats","Box Jumps",
      "Pistol Squat","Sled Push","Hack Squat Machine","Goblet Squat",
      "Step-back Lunges","Jumping Lunges","Curtsy Lunges",
      "Kettlebell Goblet Squat","Sled Drag","Barbell Walking Lunges",
      "Box Step-down","Reverse Nordic Curl","Wall Ball Squats",
      "Dumbbell Split Squat","Front Rack Squat","Step-up to Reverse Lunge",
      "Jumping Step-ups","Sled Sprints","Band Resisted Squats"
    ],
    Hamstrings: [
      "Romanian Deadlift","Good Mornings","Leg Curl Machine (Seated)",
      "Leg Curl Machine (Lying)","Glute-Ham Raises","Single-Leg Deadlift",
      "Kettlebell Swings","Nordic Hamstring Curl","Elevated Glute Bridge",
      "Cable Pull-Through","Dumbbell Glute Kickback",
      "Frog Pumps with Resistance Band","Glute Bridge March",
      "Side-lying Hip Abduction","Banded Clamshell","Cable Glute Kickback",
      "Barbell Hip Thrust","Sled Backward Drag"
    ],
    Glutes: [
      "Hip Thrust","Glute Bridge","Cable Kickbacks","Step-ups",
      "Bulgarian Split Squat","Sumo Deadlift","Kettlebell Swing",
      "Frog Pumps","Clamshells","Fire Hydrants","Donkey Kicks",
      "Lateral Band Walks"
    ],
    Calves: [
      "Standing Calf Raises","Seated Calf Raises","Donkey Calf Raises",
      "Jump Rope","Box Jumps","Sprinting","Farmer’s Walk on Toes",
      "Single-leg Calf Raise","Jump Rope Double Unders",
      "Box Jump Calf Focus","Seated Dumbbell Calf Raise",
      "Explosive Calf Jumps","Jump Squat Calf Raise",
      "Weighted Standing Calf Raise","Donkey Calf Raise on Machine"
    ],
    Cardio: [
      "Running (Steady State)","Sprinting","Jogging","Jump Rope",
      "Stair Climber","Rowing Machine","Battle Ropes",
      "Medicine Ball Slams","Agility Ladder Drills","Cone Drills",
      "Sandbag Carry","Tire Flip","Bear Hug Carry","Shadowboxing",
      "Swimming","Cycling","Elliptical Machine","Burpees","Mountain Climbers",
      "Box Jumps","Hill Sprints","Shuttle Runs","Sled Push Sprint",
      "Jump Rope Intervals","Tabata Burpees","Battle Rope Waves",
      "Medicine Ball Chest Pass","Agility Ladder Quick Feet",
      "Box Drill Sprints","Rowing Intervals","Swimming Sprints",
      "Kettlebell Complex","Shadowboxing with Weights",
      "Jump Rope Criss-Cross","Sprint Intervals on Treadmill"
    ],
    Gymnastics: [
      "Muscle-ups","Front Lever","Back Lever","Planche","Human Flag",
      "Wall Walk","Handstand Push-ups","L-Sit","Wall Sit","Jump Tuck",
      "Skin the Cat","Ring Rows","Ring Dips","Rope Climbing",
      "Ring Muscle-ups","Assisted Front Lever",
      "Advanced Planche Progressions","Wall Handstand Hold",
      "Skin the Cat on Rings","One-arm Push-up","L-Sit to Handstand",
      "Back Lever Progression","Human Flag Holds","Handstand Walk",
      "Ring Support Hold","Planche Lean"
    ],
    Mobility: [
      "Yoga Sun Salutation","Downward Dog","Cat-Cow Stretch","Cobra Stretch",
      "Pigeon Pose","Child’s Pose","Hip Flexor Stretch","Hamstring Stretch",
      "Quad Stretch","Shoulder Dislocates","Thoracic Spine Rotations",
      "Wrist Mobility Drills","Standing Forward Fold","Half Pigeon Pose",
      "Downward Dog to Cobra Flow","Seated Spinal Twist",
      "Shoulder Opener with Strap","Hip Circles",
      "Cat-Cow with Breath","Dynamic Hamstring Stretch",
      "Ankle Mobility Drills","Thoracic Bridge","Foam Rolling Quads",
      "Foam Rolling IT Band","Foam Rolling Calves","Foam Rolling Glutes",
      "Wrist Extension Stretch","Prone Thoracic Rotation"
    ]
  };

  for (const [category, list] of Object.entries(map)) {
    const regex = new RegExp(
      list
        .map(str => escapeRegExp(str.toLowerCase()))
        .join('|')
    );
    if (regex.test(n)) return category;
  }

  return '';
}

// Inline sets logging for active workouts
function addSetRowFor(id, reps='', weight=''){
  const container = document.getElementById('setsContainer-'+id);
  if (!container) return;
  const row = document.createElement('div');
  row.className = 'd-flex gap-2 align-items-center mb-2 set-row';
  row.innerHTML = `
    <input type="number" min="0" class="form-control form-control-sm reps" placeholder="Reps" value="${reps}">
    <input type="text" class="form-control form-control-sm weight" placeholder="Weight (e.g. 60 kg)" value="${weight}">
    <button type="button" class="btn btn-sm btn-outline-danger remove-set">X</button>
  `;
  row.querySelector('.remove-set').addEventListener('click', ()=> row.remove());
  container.appendChild(row);
}
function clearSetsFor(id){
  const container = document.getElementById('setsContainer-'+id);
  if (container) container.innerHTML='';
}
function collectSetsFor(id){
  const rows = document.querySelectorAll('#setsContainer-'+id+' .set-row');
  const out = [];
  rows.forEach(r=>{
    const reps = parseInt(r.querySelector('.reps').value || 0, 10);
    const weight = (r.querySelector('.weight').value || '').trim();
    if (reps || weight) out.push({reps: reps||0, weight});
  });
  return out;
}
async function completeWorkout(id){
  const duration = document.getElementById('dur-'+id)?.value || '';
  const notes = document.getElementById('notes-'+id)?.value || '';
  const sets = collectSetsFor(id);
  try {
    const res = await fetch('api/complete_workout.php', { method:'POST', headers:{'Content-Type':'application/json'}, body: JSON.stringify({ id, duration, notes, sets }) });
    const data = await res.json();
    if (data.success) {
      const el1 = document.getElementById('workout-'+id); if (el1) el1.remove();
      const el2 = document.getElementById('log-'+id); if (el2) el2.remove();
      const ac = document.getElementById('activeCount'); if (ac) ac.textContent = data.active;
      const hc = document.getElementById('historyCount'); if (hc) hc.textContent = data.history;
      loadHeatmap();
      loadStats();
    }
  } catch (e) { console.error(e); }
}

// override loadStats to respect routineOnly toggle
const _origLoadStats = loadStats;
loadStats = async function(){
  const range = document.getElementById('rangeSelect') ? document.getElementById('rangeSelect').value : 'week';
  const routineOnly = document.getElementById('routineOnlyToggle')?.checked ? '1' : '0';
  const res = await fetch('api/stats.php?range=' + encodeURIComponent(range) + '&routineOnly=' + routineOnly);
  const json = await res.json();
  if (!json.success) return;
  const labels = json.data.map(r => r.type || 'Unknown');
  const counts = json.data.map(r => r.cnt);
  const container = document.getElementById('typeCounts');
  if (container) {
    container.innerHTML = '';
    json.data.forEach(r => {
      const el = document.createElement('div');
      el.className = 'd-flex justify-content-between';
      el.innerHTML = `<div>${r.type}</div><div><strong>${r.cnt}</strong></div>`;
      container.appendChild(el);
    });
  }
  updateChart(labels, counts);
}

// ================= Profile (update/delete) =================
function initProfileUI(){
  const save = document.getElementById('saveProfileBtn');
  if (save) save.addEventListener('click', async ()=>{
    const form = document.getElementById('profileForm');
    const name = form?.querySelector('input[name="name"]').value || '';
    const password = form?.querySelector('input[name="password"]').value || '';
    const password2 = form?.querySelector('input[name="password2"]').value || '';
    try {
      const res = await fetch('api/profile.php', { method:'POST', headers:{'Content-Type':'application/json'}, body: JSON.stringify({ action:'update', name, password, password2 }) });
      const json = await res.json();
      if (json.success) showTempAlert('Profile updated', 'success'); else showTempAlert(json.message || 'Update failed', 'danger');
    } catch (e) { console.error(e); showTempAlert('Network error', 'danger'); }
  });

  const del = document.getElementById('deleteAccountBtn');
  if (del) del.addEventListener('click', async ()=>{
    if (!confirm('Delete your account permanently? This cannot be undone.')) return;
    if (!confirm('Are you absolutely sure? All data will be removed.')) return;
    try {
      const res = await fetch('api/profile.php', { method:'POST', headers:{'Content-Type':'application/json'}, body: JSON.stringify({ action:'delete' }) });
      const json = await res.json();
      if (json.success) { showTempAlert('Account deleted', 'success'); setTimeout(()=>{ window.location.href = 'index.php'; }, 800); }
      else showTempAlert(json.message || 'Delete failed', 'danger');
    } catch (e) { console.error(e); showTempAlert('Network error', 'danger'); }
  });
}

