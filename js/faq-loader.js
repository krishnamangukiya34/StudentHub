/**
 * StudentHub Portal - FAQ JSON Fetch & Dynamic Accordion
 * Practical 6 & Practical 4 Integration
 */

document.addEventListener('DOMContentLoaded', () => {
    initFaqLoader();
});

function initFaqLoader() {
    const faqContainer = document.getElementById('faq-dynamic-container');
    if (!faqContainer) return;

    let allFaqs = [];
    const searchInput = document.getElementById('faq-search-input');
    const categoryContainer = document.getElementById('faq-category-pills');
    let activeCategory = 'All';
    let searchQuery = '';

    loadFaqs();

    async function loadFaqs() {
        const cached = localStorage.getItem('studenthub_cached_faqs');
        if (cached) {
            try {
                allFaqs = JSON.parse(cached);
                renderFaqs();
            } catch (e) {}
        }

        try {
            const jsonPath = window.location.pathname.includes('/pages/') 
                ? '../data/faqs.json' 
                : 'data/faqs.json';

            const res = await fetch(jsonPath);
            if (!res.ok) throw new Error('HTTP ' + res.status);
            const data = await res.json();
            allFaqs = data;
            localStorage.setItem('studenthub_cached_faqs', JSON.stringify(data));
            renderFaqs();
        } catch (e) {
            console.error('FAQ load error:', e);
        }
    }

    if (searchInput) {
        searchInput.addEventListener('input', (e) => {
            searchQuery = e.target.value.toLowerCase().trim();
            renderFaqs();
        });
    }

    if (categoryContainer) {
        categoryContainer.addEventListener('click', (e) => {
            const pill = e.target.closest('.category-pill');
            if (!pill) return;
            categoryContainer.querySelectorAll('.category-pill').forEach(p => p.classList.remove('active'));
            pill.classList.add('active');
            activeCategory = pill.dataset.category || 'All';
            renderFaqs();
        });
    }

    function renderFaqs() {
        const filtered = allFaqs.filter(faq => {
            const matchesCat = (activeCategory === 'All' || faq.category === activeCategory);
            const matchesQuery = searchQuery === '' || 
                faq.question.toLowerCase().includes(searchQuery) ||
                faq.answer.toLowerCase().includes(searchQuery);
            return matchesCat && matchesQuery;
        });

        faqContainer.innerHTML = '';
        if (filtered.length === 0) {
            faqContainer.innerHTML = `
                <div style="text-align: center; padding: 40px; background: var(--bg-card); border-radius: 12px; border: 1px solid var(--border-color);">
                    <p style="color: var(--text-secondary); margin: 0;">No matching FAQs found. Try asking in the <a href="forum.html">Community Forum</a>.</p>
                </div>
            `;
            return;
        }

        filtered.forEach((faq, idx) => {
            const details = document.createElement('details');
            details.className = 'faq-card';
            details.name = 'faq-accordion';
            if (idx === 0 && searchQuery === '') details.open = true;

            details.innerHTML = `
                <summary>
                    <span>${escapeHtml(faq.question)}</span>
                    <span style="font-size: 0.75rem; background: var(--bg-elevated); padding: 3px 8px; border-radius: 10px; color: var(--text-secondary); margin-right: 15px; font-weight: normal;">${escapeHtml(faq.category)}</span>
                </summary>
                <div class="faq-answer">
                    ${escapeHtml(faq.answer)}
                </div>
            `;

            faqContainer.appendChild(details);
        });
    }

    function escapeHtml(text) {
        if (!text) return '';
        const map = { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#039;' };
        return text.replace(/[&<>"']/g, m => map[m]);
    }
}
