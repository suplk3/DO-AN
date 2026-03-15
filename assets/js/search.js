/**
 * TTVH Cinemas — Search
 * Tính năng:
 *   - Debounce 300ms để không spam server
 *   - AJAX gọi search_api.php, trả kết quả dropdown
 *   - Highlight từ khóa trong tên phim
 *   - Điều hướng bằng phím ↑ ↓ Enter Escape
 *   - Filter DOM (ẩn/hiện .movie-card) song song với AJAX
 *   - Cache kết quả để không gọi lại cùng keyword
 */
(function () {
  /* ── Cấu hình ── */
  const API_PATH   = '../user/search_api.php';   // chỉnh nếu cấu trúc thư mục khác
  const IMG_PATH   = '../assets/images/';
  const DEBOUNCE   = 300;   // ms chờ sau khi ngừng gõ
  const MIN_CHARS  = 1;     // ký tự tối thiểu để search

  /* ── DOM elements ── */
  const wrap      = document.getElementById('searchWrap');
  const input     = document.getElementById('searchInput');
  const dropdown  = document.getElementById('searchDropdown');
  const spinner   = wrap?.querySelector('.search-spinner');

  if (!wrap || !input || !dropdown) return; // không có search trên trang này

  /* ── State ── */
  let debounceTimer = null;
  let currentQuery  = '';
  let focusedIndex  = -1;
  let cache         = {};       // { query: results[] }

  /* ── Helpers ── */
  function escapeRe(s) {
    return s.replace(/[.*+?^${}()|[\]\\]/g, '\\$&');
  }

  function highlight(text, query) {
    if (!query) return escapeHtml(text);
    const re = new RegExp('(' + escapeRe(query) + ')', 'gi');
    return escapeHtml(text).replace(re, '<mark>$1</mark>');
  }

  function escapeHtml(s) {
    return s.replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
  }

  function fmtDate(d) {
    if (!d) return '';
    const parts = d.split('-');
    return parts[2] + '/' + parts[1] + '/' + parts[0];
  }

  /* ── Filter các .movie-card trên trang hiện tại (DOM filter) ── */
  function filterCards(query) {
    const cards = document.querySelectorAll('.movie-card');
    if (!cards.length) return;

    const q = query.toLowerCase().trim();
    let visibleCount = 0;

    cards.forEach(card => {
      const title   = (card.querySelector('.movie-title')?.textContent || '').toLowerCase();
      const genre   = (card.querySelector('.genre')?.textContent || '').toLowerCase();
      const visible = q === '' || title.includes(q) || genre.includes(q);

      card.style.display = visible ? '' : 'none';
      if (visible) visibleCount++;
    });

    // Hiện/ẩn thông báo "không tìm thấy"
    let noResult = document.getElementById('searchNoResult');
    if (q && visibleCount === 0) {
      if (!noResult) {
        noResult = document.createElement('div');
        noResult.id = 'searchNoResult';
        noResult.className = 'no-movies';
        noResult.innerHTML = '<div style="font-size:32px;margin-bottom:8px;opacity:.5">🔍</div>'
          + '<div style="font-size:16px;font-weight:700;color:#e2e8f0;margin-bottom:4px">Không tìm thấy phim</div>'
          + '<div style="font-size:13px;color:#64748b">Thử từ khóa khác hoặc xem toàn bộ phim</div>';
        document.querySelector('.movies')?.appendChild(noResult);
      }
      noResult.style.display = '';
    } else if (noResult) {
      noResult.style.display = 'none';
    }
  }

  /* ── Render dropdown ── */
  function renderDropdown(results, query) {
    dropdown.innerHTML = '';

    if (!results.length) {
      dropdown.innerHTML = `
        <div class="search-empty">
          <div class="search-empty-icon">🎬</div>
          Không tìm thấy phim nào với từ khóa "<strong>${escapeHtml(query)}</strong>"
        </div>`;
      openDropdown();
      return;
    }

    const header = document.createElement('div');
    header.className = 'search-dropdown-header';
    header.textContent = `${results.length} kết quả`;
    dropdown.appendChild(header);

    results.forEach((movie, i) => {
      const a = document.createElement('a');
      a.href = `chi_tiet_phim.php?id=${movie.id}`;
      a.className = 'search-result-item';
      a.dataset.index = i;

      const badge = movie.is_upcoming
        ? `<span class="search-result-badge upcoming">Sắp chiếu</span>`
        : `<span class="search-result-badge showing">Đang chiếu</span>`;

      const dur = movie.thoi_luong ? `<span class="search-result-duration">⏱ ${escapeHtml(movie.thoi_luong)} phút</span>` : '';
      const genre = movie.the_loai ? `<span class="search-result-genre">${escapeHtml(movie.the_loai)}</span>` : '';

      a.innerHTML = `
        <img class="search-result-poster"
             src="${IMG_PATH}${escapeHtml(movie.poster)}"
             alt="${escapeHtml(movie.ten_phim)}"
             onerror="this.style.background='#1e293b';this.removeAttribute('src')">
        <div class="search-result-info">
          <div class="search-result-title">${highlight(movie.ten_phim, query)}</div>
          <div class="search-result-meta">
            ${genre}${dur}${badge}
          </div>
        </div>`;

      dropdown.appendChild(a);
    });

    // "Xem tất cả" link — chỉ hiện nếu có thể có nhiều hơn
    if (results.length >= 5) {
      const all = document.createElement('a');
      all.href  = `index.php?q=${encodeURIComponent(query)}`;
      all.className = 'search-view-all';
      all.textContent = 'Xem tất cả kết quả →';
      dropdown.appendChild(all);
    }

    openDropdown();
    focusedIndex = -1;
  }

  /* ── Open / close ── */
  function openDropdown() {
    dropdown.classList.add('open');
  }
  function closeDropdown() {
    dropdown.classList.remove('open');
    focusedIndex = -1;
  }

  /* ── Keyboard navigation ── */
  function getItems() {
    return dropdown.querySelectorAll('.search-result-item');
  }

  function setFocus(idx) {
    const items = getItems();
    items.forEach(el => el.classList.remove('focused'));
    if (idx >= 0 && idx < items.length) {
      items[idx].classList.add('focused');
      items[idx].scrollIntoView({ block: 'nearest' });
    }
    focusedIndex = idx;
  }

  input.addEventListener('keydown', e => {
    const items = getItems();
    if (!dropdown.classList.contains('open')) return;

    if (e.key === 'ArrowDown') {
      e.preventDefault();
      setFocus(Math.min(focusedIndex + 1, items.length - 1));
    } else if (e.key === 'ArrowUp') {
      e.preventDefault();
      setFocus(Math.max(focusedIndex - 1, 0));
    } else if (e.key === 'Enter' && focusedIndex >= 0) {
      e.preventDefault();
      items[focusedIndex]?.click();
    } else if (e.key === 'Escape') {
      closeDropdown();
      input.blur();
    }
  });

  /* ── Fetch với cache ── */
  async function fetchResults(query) {
    if (cache[query]) return cache[query];

    wrap.classList.add('loading');
    try {
      const url = `${API_PATH}?q=${encodeURIComponent(query)}`;
      const res = await fetch(url);
      const data = await res.json();
      cache[query] = data.results || [];
      return cache[query];
    } catch (err) {
      console.error('Search error:', err);
      return [];
    } finally {
      wrap.classList.remove('loading');
    }
  }

  /* ── Main input handler ── */
  input.addEventListener('input', () => {
    const query = input.value.trim();
    currentQuery = query;

    // DOM filter luôn chạy ngay (không debounce)
    filterCards(query);

    clearTimeout(debounceTimer);

    if (query.length < MIN_CHARS) {
      closeDropdown();
      return;
    }

    debounceTimer = setTimeout(async () => {
      if (input.value.trim() !== query) return; // đã thay đổi
      const results = await fetchResults(query);
      if (input.value.trim() === query) {
        renderDropdown(results, query);
      }
    }, DEBOUNCE);
  });

  /* ── Focus: mở lại nếu có query ── */
  input.addEventListener('focus', () => {
    if (input.value.trim().length >= MIN_CHARS && dropdown.innerHTML) {
      openDropdown();
    }
  });

  /* ── Click ngoài: đóng dropdown ── */
  document.addEventListener('click', e => {
    if (!wrap.contains(e.target)) closeDropdown();
  });

  /* ── Nếu URL có ?q=... thì auto fill ── */
  const urlQ = new URLSearchParams(location.search).get('q');
  if (urlQ) {
    input.value = urlQ;
    filterCards(urlQ);
  }

})();