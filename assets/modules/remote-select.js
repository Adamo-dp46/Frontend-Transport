/**
 * remote-select.js
 * Composant vanilla JS de recherche distante — version ES6 module.
 * Compatible Turbo (turbo:load) et WeakMap registry.
 *
 * Usage dans Twig :
 *   <div data-remote-select
 *        data-resource="fournisseurs"
 *        data-name="fournisseur"
 *        data-placeholder="Rechercher un fournisseur..."
 *        data-value="{{ appro.fournisseur.id ?? '' }}"
 *        data-label="{{ appro.fournisseur.libelle ?? '' }}">
 *   </div>
 */

const DEBOUNCE_MS = 300
const registry    = new WeakMap()

// ── Debounce helper ───────────────────────────────────────────────────────────

function debounce(fn, delay) {
    let timer
    return (...args) => {
        clearTimeout(timer)
        timer = setTimeout(() => fn(...args), delay)
    }
}

// ── Création du composant ─────────────────────────────────────────────────────

function createRemoteSelect(container) {
    if (registry.has(container)) return

    const { resource, name, placeholder = 'Rechercher...', value: initValue = '', label: initLabel = '', limit = 10 } = container.dataset
    const required = container.hasAttribute('data-required')

    let selectedId    = initValue
    let selectedLabel = initLabel

    // ── DOM ───────────────────────────────────────────────────────────────────

    const hidden = Object.assign(document.createElement('input'), {
        type:  'hidden',
        name,
        value: initValue,
    })
    if (required) hidden.required = true

    const wrapper = Object.assign(document.createElement('div'), {
        className: 'relative',
    })

    const input = Object.assign(document.createElement('input'), {
        type:         'text',
        placeholder,
        value:        initLabel,
        autocomplete: 'off',
        className:    'flex h-9 w-full rounded-md border border-input bg-transparent px-3 py-1 text-sm shadow-sm focus-visible:outline-none focus-visible:ring-1 focus-visible:ring-ring pr-8',
    })

    const icon = Object.assign(document.createElement('span'), {
        innerHTML:  `<svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-muted-foreground" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                     </svg>`,
        className: 'absolute right-2.5 top-1/2 -translate-y-1/2 pointer-events-none',
    })

    const btnClear = Object.assign(document.createElement('button'), {
        type:      'button',
        innerHTML: '×',
        className: `absolute right-2 top-1/2 -translate-y-1/2 text-muted-foreground hover:text-foreground text-lg leading-none ${initValue ? '' : 'hidden'}`,
    })

    const dropdown = Object.assign(document.createElement('div'), {
        className: 'absolute z-50 w-full mt-1 bg-popover border border-border rounded-md shadow-lg overflow-hidden hidden',
    })

    const list = Object.assign(document.createElement('ul'), {
        className: 'max-h-48 overflow-y-auto py-1',
    })

    const statusMsg = Object.assign(document.createElement('div'), {
        className: 'px-3 py-2 text-xs text-muted-foreground hidden',
    })

    dropdown.append(list, statusMsg)
    wrapper.append(input, initValue ? btnClear : icon, dropdown)
    container.append(hidden, wrapper)

    // ── Helpers ───────────────────────────────────────────────────────────────

    const openDropdown  = () => dropdown.classList.remove('hidden')
    const closeDropdown = () => dropdown.classList.add('hidden')

    const showStatus = (msg) => {
        list.innerHTML        = ''
        statusMsg.textContent = msg
        statusMsg.classList.remove('hidden')
    }

    const hideStatus = () => statusMsg.classList.add('hidden')

    const selectItem = (id, label) => {
        selectedId = id
        selectedLabel = label
        hidden.value  = id
        input.value   = label
        icon.classList.add('hidden')
        btnClear.classList.remove('hidden')
        closeDropdown()
        container.dispatchEvent(new CustomEvent('remote-select:change', {
            detail: { id, label },
            bubbles: true,
        }))
    }

    const clearSelection = () => {
        selectedId = selectedLabel = hidden.value = input.value = ''
        btnClear.classList.add('hidden')
        icon.classList.remove('hidden')
        list.innerHTML = ''
        container.dispatchEvent(new CustomEvent('remote-select:change', {
            detail: { id: '', label: '' },
            bubbles: true,
        }))
    }

    const renderResults = (results) => {
        list.innerHTML = ''
        hideStatus()

        if (!results.length) {
            showStatus('Aucun résultat')
            return
        }

        results.forEach(({ id, label }) => {
            const li = Object.assign(document.createElement('li'), {
                textContent: label,
                className:   `px-3 py-2 text-sm cursor-pointer hover:bg-accent transition-colors ${id == selectedId ? 'bg-accent font-medium' : ''}`,
            })
            li.addEventListener('mousedown', (e) => {
                e.preventDefault()
                selectItem(id, label)
            })
            list.appendChild(li)
        })
    }

    // ── Fetch avec debounce ───────────────────────────────────────────────────

    const fetchResults = async (q) => {
        showStatus('Chargement...')
        openDropdown()
        try {
            const res = await fetch(`/search/${resource}?q=${encodeURIComponent(q)}&limit=${limit}`, {
                headers: { 'X-Requested-With': 'XMLHttpRequest' },
            })
            if (!res.ok) throw new Error(`HTTP ${res.status}`)
            renderResults(await res.json())
        } catch {
            showStatus('Erreur lors de la recherche')
        }
    }

    const debouncedFetch = debounce(fetchResults, DEBOUNCE_MS)

    // ── Événements ────────────────────────────────────────────────────────────

    input.addEventListener('input', ({ target }) => {
        const q = target.value.trim()
        if (q !== selectedLabel) {
            hidden.value = ''
            selectedId   = ''
        }
        if (!q) { closeDropdown(); return }
        debouncedFetch(q)
    })

    input.addEventListener('focus', ({ target }) => {
        if (!target.value.trim()) fetchResults('')
    })

    input.addEventListener('blur', () => {
        setTimeout(closeDropdown, 150)
        input.value = selectedId ? selectedLabel : ''
    })

    btnClear.addEventListener('click', clearSelection)

    // Fermer si clic en dehors
    document.addEventListener('click', ({ target }) => {
        if (!container.contains(target)) closeDropdown()
    })

    registry.set(container, { selectItem, clearSelection })
}

// ── Export public ─────────────────────────────────────────────────────────────

export function initRemoteSelects() {
    document.querySelectorAll('[data-remote-select]').forEach(createRemoteSelect)
}
