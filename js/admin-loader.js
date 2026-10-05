/**
 * StudentHub Portal - Admin Portal Directory Loader
 * Practical 6 & Practical 2: Dynamic Table Rendering, Search, Course Filter & Pagination
 */

document.addEventListener('DOMContentLoaded', () => {
    initAdminDirectory();
});

function initAdminDirectory() {
    const tableBody = document.getElementById('admin-students-tbody');
    if (!tableBody) return;

    let students = [];
    let filteredStudents = [];
    let currentPage = 1;
    const itemsPerPage = 8;
    let searchQuery = '';
    let courseFilter = 'All';

    const searchInput = document.getElementById('admin-search');
    const courseSelect = document.getElementById('admin-course-filter');
    const paginationContainer = document.getElementById('admin-pagination');
    const totalCountEl = document.getElementById('metric-total-students');

    loadStudents();

    async function loadStudents() {
        try {
            const jsonPath = window.location.pathname.includes('/pages/') 
                ? '../data/students.json' 
                : 'data/students.json';

            const res = await fetch(jsonPath);
            if (!res.ok) throw new Error('HTTP ' + res.status);
            students = await res.json();
            if (totalCountEl) totalCountEl.textContent = students.length;
            applyFilters();
        } catch (e) {
            console.error('Failed to load students:', e);
        }
    }

    if (searchInput) {
        searchInput.addEventListener('input', (e) => {
            searchQuery = e.target.value.toLowerCase().trim();
            currentPage = 1;
            applyFilters();
        });
    }

    if (courseSelect) {
        courseSelect.addEventListener('change', (e) => {
            courseFilter = e.target.value;
            currentPage = 1;
            applyFilters();
        });
    }

    function applyFilters() {
        filteredStudents = students.filter(s => {
            const matchesCourse = (courseFilter === 'All' || s.course === courseFilter);
            const matchesSearch = searchQuery === '' ||
                s.name.toLowerCase().includes(searchQuery) ||
                s.rollNo.toLowerCase().includes(searchQuery) ||
                s.email.toLowerCase().includes(searchQuery) ||
                s.skills.some(skill => skill.toLowerCase().includes(searchQuery));
            return matchesCourse && matchesSearch;
        });

        renderTable();
        renderPagination();
    }

    function renderTable() {
        tableBody.innerHTML = '';
        if (filteredStudents.length === 0) {
            tableBody.innerHTML = `<tr><td colspan="7" style="text-align: center; padding: 30px; color: var(--text-secondary);">No student records match the search filter.</td></tr>`;
            return;
        }

        const start = (currentPage - 1) * itemsPerPage;
        const pageItems = filteredStudents.slice(start, start + itemsPerPage);

        pageItems.forEach(s => {
            const tr = document.createElement('tr');
            tr.innerHTML = `
                <td><code>${escapeHtml(s.rollNo)}</code></td>
                <td>
                    <div style="display: flex; align-items: center; gap: 8px;">
                        <span>${s.avatar || '👨‍🎓'}</span>
                        <strong style="color: var(--text-primary);">${escapeHtml(s.name)}</strong>
                    </div>
                </td>
                <td>${escapeHtml(s.course)} (${escapeHtml(s.year)})</td>
                <td>${escapeHtml(s.email)}</td>
                <td><strong>${s.cgpa}</strong></td>
                <td><span class="badge-status badge-active">${escapeHtml(s.status)}</span></td>
                <td>
                    <button class="action-btn-outline" style="padding: 4px 10px; font-size: 0.78rem;" onclick="alert('Viewing record for: ${escapeHtml(s.name)}')">View</button>
                </td>
            `;
            tableBody.appendChild(tr);
        });
    }

    function renderPagination() {
        if (!paginationContainer) return;
        paginationContainer.innerHTML = '';
        const totalPages = Math.ceil(filteredStudents.length / itemsPerPage);
        if (totalPages <= 1) return;

        for (let i = 1; i <= totalPages; i++) {
            const btn = document.createElement('button');
            btn.className = `page-btn ${i === currentPage ? 'active' : ''}`;
            btn.textContent = i;
            btn.addEventListener('click', () => {
                currentPage = i;
                renderTable();
                renderPagination();
            });
            paginationContainer.appendChild(btn);
        }
    }

    // CSV Export functionality
    const exportBtn = document.getElementById('btn-export-csv');
    if (exportBtn) {
        exportBtn.addEventListener('click', () => {
            let csv = "RollNo,Name,Course,Year,Email,CGPA,Status\n";
            students.forEach(s => {
                csv += `"${s.rollNo}","${s.name}","${s.course}","${s.year}","${s.email}",${s.cgpa},"${s.status}"\n`;
            });
            const blob = new Blob([csv], { type: 'text/csv' });
            const url = window.URL.createObjectURL(blob);
            const a = document.createElement('a');
            a.setAttribute('href', url);
            a.setAttribute('download', 'studentshub_directory.csv');
            a.click();
        });
    }

    function escapeHtml(text) {
        if (!text) return '';
        const map = { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#039;' };
        return String(text).replace(/[&<>"']/g, m => map[m]);
    }
}
