import './bootstrap';
import * as bootstrap from 'bootstrap';

window.bootstrap = bootstrap;

const $ = (s, root = document) => root.querySelector(s);
const $$ = (s, root = document) => Array.from(root.querySelectorAll(s));
const csrf = () => document.querySelector('meta[name="csrf-token"]')?.content;

/* ------------------------------------------------------------------ */
/* Toast notifications                                                  */
/* ------------------------------------------------------------------ */
export function toast(message, type = 'success') {
    const holder = $('#toast-holder');
    if (!holder) return;
    const el = document.createElement('div');
    el.className = `toast toast-syro align-items-center ${type === 'error' ? 'error' : ''}`;
    el.setAttribute('role', 'status');
    el.innerHTML = `<div class="d-flex"><div class="toast-body"></div>
        <button type="button" class="btn-close me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button></div>`;
    el.querySelector('.toast-body').textContent = message;
    holder.appendChild(el);
    const t = new bootstrap.Toast(el, { delay: 3500 });
    el.addEventListener('hidden.bs.toast', () => el.remove());
    t.show();
}
window.syroToast = toast;

/* ------------------------------------------------------------------ */
/* JSON fetch helper (handles Laravel validation + auth redirects)       */
/* ------------------------------------------------------------------ */
async function send(url, method = 'POST', body = null) {
    const res = await fetch(url, {
        method,
        headers: {
            'X-CSRF-TOKEN': csrf(),
            'X-Requested-With': 'XMLHttpRequest',
            Accept: 'application/json',
            ...(body && !(body instanceof FormData) ? { 'Content-Type': 'application/json' } : {}),
        },
        body: body ? (body instanceof FormData ? body : JSON.stringify(body)) : null,
    });

    if (res.status === 401) {
        window.location.href = '/login';
        throw new Error('auth');
    }
    const data = await res.json().catch(() => ({}));
    if (!res.ok) {
        const first = data.errors ? Object.values(data.errors)[0][0] : data.message || 'Something went wrong. Please try again.';
        throw new Error(first);
    }
    return data;
}

function setBadge(selector, count) {
    $$(selector).forEach((b) => { b.textContent = count; b.dataset.count = count; });
}

/* ------------------------------------------------------------------ */
/* Add to cart (any form with data-add-to-cart)                          */
/* ------------------------------------------------------------------ */
document.addEventListener('submit', async (e) => {
    const form = e.target.closest('form[data-add-to-cart]');
    if (!form) return;
    e.preventDefault();

    const btn = form.querySelector('[type="submit"]');
    const label = btn?.innerHTML;
    if (btn) { btn.disabled = true; btn.innerHTML = '<span class="spinner-border spinner-border-sm"></span>'; }

    try {
        const data = await send(form.action, 'POST', new FormData(form));
        setBadge('[data-cart-count]', data.count);
        toast(data.message);
        if (form.dataset.redirect) window.location.href = form.dataset.redirect;
    } catch (err) {
        if (err.message !== 'auth') toast(err.message, 'error');
    } finally {
        if (btn) { btn.disabled = false; btn.innerHTML = label; }
    }
});

/* ------------------------------------------------------------------ */
/* Wishlist toggle                                                       */
/* ------------------------------------------------------------------ */
document.addEventListener('click', async (e) => {
    const btn = e.target.closest('[data-wishlist]');
    if (!btn) return;
    e.preventDefault();
    if (btn.dataset.auth !== '1') { window.location.href = '/login'; return; }

    try {
        const data = await send(btn.dataset.wishlist);
        btn.classList.toggle('active', data.added);
        const icon = btn.querySelector('i');
        if (icon) icon.className = data.added ? 'bi bi-heart-fill' : 'bi bi-heart';
        setBadge('[data-wishlist-count]', data.count);
        toast(data.message);
        if (!data.added && btn.dataset.removeCard) btn.closest('.col')?.remove();
    } catch (err) {
        if (err.message !== 'auth') toast(err.message, 'error');
    }
});

/* ------------------------------------------------------------------ */
/* Quantity steppers                                                     */
/* ------------------------------------------------------------------ */
document.addEventListener('click', (e) => {
    const b = e.target.closest('[data-qty]');
    if (!b) return;
    const input = b.closest('.qty-input').querySelector('input');
    const min = parseInt(input.min || '1', 10);
    const max = parseInt(input.max || '99', 10);
    const next = Math.min(max, Math.max(min, (parseInt(input.value, 10) || min) + parseInt(b.dataset.qty, 10)));
    if (next !== parseInt(input.value, 10)) {
        input.value = next;
        input.dispatchEvent(new Event('change', { bubbles: true }));
    }
});
// Cart page: auto-submit quantity changes.
document.addEventListener('change', (e) => {
    if (e.target.matches('[data-autosubmit]')) e.target.form.requestSubmit();
});

/* ------------------------------------------------------------------ */
/* Newsletter (AJAX)                                                     */
/* ------------------------------------------------------------------ */
document.addEventListener('submit', async (e) => {
    const form = e.target.closest('form[data-newsletter]');
    if (!form) return;
    e.preventDefault();
    try {
        const data = await send(form.action, 'POST', new FormData(form));
        toast(data.message);
        form.reset();
    } catch (err) { toast(err.message, 'error'); }
});

/* ------------------------------------------------------------------ */
/* Header: scroll shadow + search overlay with live suggestions          */
/* ------------------------------------------------------------------ */
const header = $('.site-header');
if (header) {
    const onScroll = () => header.classList.toggle('is-scrolled', window.scrollY > 10);
    window.addEventListener('scroll', onScroll, { passive: true });
    onScroll();
}

const overlay = $('#search-overlay');
if (overlay) {
    const input = $('.search-input', overlay);
    const results = $('.search-results', overlay);
    const open = () => { overlay.classList.add('open'); setTimeout(() => input.focus(), 50); };
    const close = () => overlay.classList.remove('open');
    $$('[data-search-open]').forEach((b) => b.addEventListener('click', open));
    $$('[data-search-close]').forEach((b) => b.addEventListener('click', close));
    document.addEventListener('keydown', (e) => { if (e.key === 'Escape') close(); });

    let timer;
    input.addEventListener('input', () => {
        clearTimeout(timer);
        const q = input.value.trim();
        if (q.length < 2) { results.innerHTML = ''; return; }
        timer = setTimeout(async () => {
            const res = await fetch(`${overlay.dataset.suggest}?q=${encodeURIComponent(q)}`, { headers: { Accept: 'application/json' } });
            const items = await res.json();
            results.innerHTML = '';
            if (!items.length) {
                results.innerHTML = '<p class="text-muted py-3 mb-0">No products found. Press Enter to search all.</p>';
                return;
            }
            items.forEach((p) => {
                const a = document.createElement('a');
                a.href = p.url;
                a.innerHTML = '<img alt=""><span class="flex-grow-1"></span><strong></strong>';
                a.querySelector('img').src = p.image;
                a.querySelector('span').textContent = p.name;
                a.querySelector('strong').textContent = p.price;
                results.appendChild(a);
            });
        }, 220);
    });
}

/* ------------------------------------------------------------------ */
/* Shop: AJAX filtering                                                  */
/* ------------------------------------------------------------------ */
const filterForm = $('#shop-filters');
const grid = $('#product-grid');
if (filterForm && grid) {
    const load = async (url, push = true) => {
        grid.classList.add('loading');
        try {
            const res = await fetch(url, { headers: { 'X-Requested-With': 'XMLHttpRequest', Accept: 'application/json' } });
            const data = await res.json();
            grid.innerHTML = data.html;
            $$('[data-result-count]').forEach((el) => { el.textContent = data.total; });
            if (push) history.pushState({}, '', url);
            grid.scrollIntoView({ behavior: 'smooth', block: 'start' });
        } catch {
            window.location.href = url;
        } finally {
            grid.classList.remove('loading');
        }
    };

    const buildUrl = () => {
        const params = new URLSearchParams();
        new FormData(filterForm).forEach((v, k) => { if (v !== '') params.append(k, v); });
        const sort = $('#sort-select');
        if (sort && sort.value !== 'newest') params.set('sort', sort.value);
        const qs = params.toString();
        return filterForm.action + (qs ? `?${qs}` : '');
    };

    let debounce;
    filterForm.addEventListener('change', () => { clearTimeout(debounce); debounce = setTimeout(() => load(buildUrl()), 250); });
    filterForm.addEventListener('submit', (e) => { e.preventDefault(); load(buildUrl()); });
    $('#sort-select')?.addEventListener('change', () => load(buildUrl()));

    // Pagination links inside the grid.
    grid.addEventListener('click', (e) => {
        const a = e.target.closest('.pagination a');
        if (!a) return;
        e.preventDefault();
        load(a.href);
    });
    window.addEventListener('popstate', () => load(window.location.href, false));
}

/* ------------------------------------------------------------------ */
/* Product page: gallery + variant picker                                */
/* ------------------------------------------------------------------ */
const gallery = $('[data-gallery]');
if (gallery) {
    const main = $('.gallery-main', gallery);
    const mainImg = $('img', main);
    $$('.gallery-thumbs button', gallery).forEach((b) => b.addEventListener('click', () => {
        $$('.gallery-thumbs button', gallery).forEach((x) => x.classList.remove('active'));
        b.classList.add('active');
        mainImg.src = b.dataset.src;
        main.classList.remove('zoomed');
    }));
    main.addEventListener('click', () => main.classList.toggle('zoomed'));
    main.addEventListener('mousemove', (e) => {
        if (!main.classList.contains('zoomed')) return;
        const r = main.getBoundingClientRect();
        mainImg.style.transformOrigin = `${((e.clientX - r.left) / r.width) * 100}% ${((e.clientY - r.top) / r.height) * 100}%`;
    });
}

const picker = $('[data-variant-picker]');
if (picker && JSON.parse(picker.dataset.variants || '[]').length) {
    const variants = JSON.parse(picker.dataset.variants || '[]');
    const state = { size: null, color: null };
    const hasSizes = $$('[data-size]', picker).length > 0;
    const hasColors = $$('[data-color]', picker).length > 0;
    const variantInput = $('input[name="variant_id"]', picker);
    const submit = $('[type="submit"]', picker);
    const stockEl = $('[data-stock-label]');
    const priceEl = $('[data-price-label]');
    const qty = $('input[name="quantity"]', picker);

    const match = () => variants.find((v) => (!hasSizes || v.size === state.size) && (!hasColors || v.color === state.color));

    const refresh = () => {
        // Grey out sizes that don't exist for the chosen colour (and vice versa).
        $$('[data-size]', picker).forEach((b) => {
            const ok = variants.some((v) => v.size === b.dataset.size && v.stock > 0 && (!state.color || v.color === state.color));
            b.classList.toggle('unavailable', !ok);
        });
        if (state.color) $('[data-color-name]').textContent = state.color;
        if (state.size) $('[data-size-name]') && ($('[data-size-name]').textContent = state.size);

        const v = match();
        variantInput.value = v ? v.id : '';
        if (v) {
            priceEl && (priceEl.innerHTML = v.price_formatted);
            stockEl.textContent = v.stock > 0 ? (v.stock <= 5 ? `Only ${v.stock} left` : 'In stock') : 'Out of stock';
            stockEl.className = v.stock > 0 ? (v.stock <= 5 ? 'text-warning' : 'text-success') : 'text-danger';
            qty.max = Math.max(1, v.stock);
            if (parseInt(qty.value, 10) > v.stock) qty.value = Math.max(1, v.stock);
            submit.disabled = v.stock < 1;
            submit.textContent = v.stock < 1 ? 'Sold out' : 'Add to bag';
        } else {
            submit.disabled = true;
            submit.textContent = hasSizes && !state.size ? 'Select a size' : 'Select an option';
            stockEl.textContent = '';
        }
    };

    picker.addEventListener('click', (e) => {
        const s = e.target.closest('[data-size]');
        const c = e.target.closest('[data-color]');
        if (s) {
            state.size = s.dataset.size;
            $$('[data-size]', picker).forEach((x) => x.classList.toggle('active', x === s));
        }
        if (c) {
            state.color = c.dataset.color;
            $$('[data-color]', picker).forEach((x) => x.classList.toggle('active', x === c));
        }
        if (s || c) refresh();
    });

    // Preselect a single colour / size automatically.
    if ($$('[data-color]', picker).length === 1) $('[data-color]', picker).click();
    if ($$('[data-size]', picker).length === 1) $('[data-size]', picker).click();
    refresh();
}

/* ------------------------------------------------------------------ */
/* Checkout: live shipping + payment method panels                      */
/* ------------------------------------------------------------------ */
const checkout = $('#checkout-form');
if (checkout) {
    const rates = JSON.parse(checkout.dataset.rates);
    const subtotal = parseFloat(checkout.dataset.subtotal);
    const discount = parseFloat(checkout.dataset.discount);
    const threshold = parseFloat(checkout.dataset.threshold);
    const fmt = (n) => 'BDT ' + n.toLocaleString('en-US', { maximumFractionDigits: 2 });

    const update = () => {
        const zone = $('input[name="shipping_zone"]:checked', checkout)?.value || 'inside_dhaka';
        const afterDiscount = subtotal - discount;
        const ship = threshold > 0 && afterDiscount >= threshold ? 0 : rates[zone];
        $('[data-shipping]').textContent = ship === 0 ? 'Free' : fmt(ship);
        $('[data-total]').textContent = fmt(afterDiscount + ship);

        const method = $('input[name="payment_method"]:checked', checkout)?.value;
        $$('[data-payment-panel]').forEach((p) => p.classList.toggle('d-none', p.dataset.paymentPanel !== method));
    };
    checkout.addEventListener('change', update);
    update();
    checkout.addEventListener('submit', () => {
        const b = $('[type="submit"]', checkout);
        b.disabled = true;
        b.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Placing order…';
    });
}

/* ------------------------------------------------------------------ */
/* Scroll reveal                                                          */
/* ------------------------------------------------------------------ */
if ('IntersectionObserver' in window) {
    const io = new IntersectionObserver((entries) => entries.forEach((en) => {
        if (en.isIntersecting) { en.target.classList.add('is-visible'); io.unobserve(en.target); }
    }), { threshold: 0.12 });
    $$('.reveal').forEach((el) => io.observe(el));
} else {
    $$('.reveal').forEach((el) => el.classList.add('is-visible'));
}

/* Flash messages rendered by the server */
$$('[data-flash]').forEach((el) => toast(el.dataset.flash, el.dataset.type));
