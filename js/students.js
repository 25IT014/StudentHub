/**
 * StudentHub Portal - Student Management & Directory Module (Practicals 6, 11, 13)
 * Features:
 * - Fetch API integration for students.json
 * - Real-time student search and filtering by department & year
 * - Dynamic pagination & sorting
 * - Student detail modal
 * - Export to CSV (Practical 11 Intermediate Extension)
 * - Client-side interactive CRUD mockups
 */

document.addEventListener('DOMContentLoaded', () => {
  const tableContainer = document.getElementById('studentsTableBody');
  if (tableContainer) {
    initStudentsModule();
  }
});

let allStudents = [];
let filteredStudents = [];
let currentStudentPage = 1;
const studentsPerPage = 8;

async function initStudentsModule() {
  const searchInput = document.getElementById('studentSearchInput');
  const deptFilter = document.getElementById('studentDeptFilter');
  const yearFilter = document.getElementById('studentYearFilter');
  const exportBtn = document.getElementById('exportCsvBtn');

  await loadStudentsData();

  if (searchInput) {
    let timer;
    searchInput.addEventListener('input', () => {
      clearTimeout(timer);
      timer = setTimeout(() => applyStudentFilters(), 300);
    });
  }

  if (deptFilter) deptFilter.addEventListener('change', applyStudentFilters);
  if (yearFilter) yearFilter.addEventListener('change', applyStudentFilters);
  if (exportBtn) exportBtn.addEventListener('click', exportStudentsToCSV);
}

/**
 * Fetch students from JSON file
 */
async function loadStudentsData() {
  const tbody = document.getElementById('studentsTableBody');
  if (!tbody) return;

  tbody.innerHTML = `
    <tr>
      <td colspan="7" style="text-align: center; padding: 2.5rem; color: var(--text-muted);">
        Loading student records...
      </td>
    </tr>
  `;

  try {
    const res = await fetch('../data/students.json');
    if (!res.ok) throw new Error(`HTTP error ${res.status}`);
    const data = await res.json();
    allStudents = data;
    localStorage.setItem('studenthub_students_cache', JSON.stringify(data));
    filteredStudents = [...allStudents];
    renderStudentsTable();
  } catch (error) {
    console.warn('Loading students from cache...', error);
    const cached = localStorage.getItem('studenthub_students_cache');
    if (cached) {
      allStudents = JSON.parse(cached);
      filteredStudents = [...allStudents];
      renderStudentsTable();
    } else {
      tbody.innerHTML = `
        <tr>
          <td colspan="7" style="text-align: center; padding: 2.5rem; color: var(--danger);">
            Failed to load student records from students.json.
          </td>
        </tr>
      `;
    }
  }
}

function applyStudentFilters() {
  const q = (document.getElementById('studentSearchInput')?.value || '').toLowerCase().trim();
  const dept = document.getElementById('studentDeptFilter')?.value || 'all';
  const year = document.getElementById('studentYearFilter')?.value || 'all';

  filteredStudents = allStudents.filter(st => {
    const matchSearch =
      st.name.toLowerCase().includes(q) ||
      st.student_id.toLowerCase().includes(q) ||
      st.email.toLowerCase().includes(q) ||
      st.department.toLowerCase().includes(q);

    const matchDept = dept === 'all' || st.department.toLowerCase().includes(dept.toLowerCase());
    const matchYear = year === 'all' || st.year.toLowerCase().includes(year.toLowerCase());

    return matchSearch && matchDept && matchYear;
  });

  currentStudentPage = 1;
  renderStudentsTable();
}

function renderStudentsTable() {
  const tbody = document.getElementById('studentsTableBody');
  const countBadge = document.getElementById('studentCountBadge');
  const paginationContainer = document.getElementById('studentsPagination');

  if (!tbody) return;

  if (countBadge) {
    countBadge.textContent = `${filteredStudents.length} Students`;
  }

  if (filteredStudents.length === 0) {
    tbody.innerHTML = `
      <tr>
        <td colspan="7" style="text-align: center; padding: 2.5rem; color: var(--text-muted);">
          No students found matching your search criteria.
        </td>
      </tr>
    `;
    if (paginationContainer) paginationContainer.innerHTML = '';
    return;
  }

  const start = (currentStudentPage - 1) * studentsPerPage;
  const end = start + studentsPerPage;
  const pageItems = filteredStudents.slice(start, end);

  tbody.innerHTML = pageItems.map(st => `
    <tr>
      <td><strong>${st.student_id}</strong></td>
      <td>
        <div style="font-weight: 600; color: var(--text-main);">${escapeHTML(st.name)}</div>
        <div style="font-size: 0.8rem; color: var(--text-muted);">${st.email}</div>
      </td>
      <td>${st.department}</td>
      <td><span class="badge badge-primary">${st.course}</span></td>
      <td>${st.year}</td>
      <td><span class="badge badge-success">${st.gpa ? st.gpa.toFixed(2) : '8.50'}</span></td>
      <td>
        <button class="btn btn-secondary btn-sm" onclick="viewStudentModal(${st.id})">View</button>
      </td>
    </tr>
  `).join('');

  renderStudentPagination();
}

function renderStudentPagination() {
  const pagination = document.getElementById('studentsPagination');
  if (!pagination) return;

  const totalPages = Math.ceil(filteredStudents.length / studentsPerPage);
  if (totalPages <= 1) {
    pagination.innerHTML = '';
    return;
  }

  let html = `
    <button class="page-btn" ${currentStudentPage === 1 ? 'disabled' : ''} onclick="goToStudentPage(${currentStudentPage - 1})">« Prev</button>
  `;

  for (let i = 1; i <= totalPages; i++) {
    html += `
      <button class="page-btn ${i === currentStudentPage ? 'active' : ''}" onclick="goToStudentPage(${i})">${i}</button>
    `;
  }

  html += `
    <button class="page-btn" ${currentStudentPage === totalPages ? 'disabled' : ''} onclick="goToStudentPage(${currentStudentPage + 1})">Next »</button>
  `;

  pagination.innerHTML = html;
}

window.goToStudentPage = function(p) {
  currentStudentPage = p;
  renderStudentsTable();
};

window.viewStudentModal = function(id) {
  const st = allStudents.find(s => s.id === id);
  if (!st) return;

  const modalBody = document.getElementById('studentModalBody');
  const modalTitle = document.getElementById('studentModalTitle');

  if (modalTitle) modalTitle.textContent = `${st.name} (${st.student_id})`;
  if (modalBody) {
    modalBody.innerHTML = `
      <div style="display: flex; flex-direction: column; gap: 0.85rem;">
        <div style="padding: 1rem; background: var(--bg-surface-elevated); border-radius: var(--radius-md);">
          <p style="margin: 0;"><strong>Student ID:</strong> ${st.student_id}</p>
          <p style="margin: 0.3rem 0 0 0;"><strong>Full Name:</strong> ${st.name}</p>
          <p style="margin: 0.3rem 0 0 0;"><strong>Email:</strong> ${st.email}</p>
          <p style="margin: 0.3rem 0 0 0;"><strong>Mobile:</strong> ${st.phone || 'N/A'}</p>
        </div>
        <div style="padding: 1rem; background: var(--bg-surface-elevated); border-radius: var(--radius-md);">
          <p style="margin: 0;"><strong>Department:</strong> ${st.department}</p>
          <p style="margin: 0.3rem 0 0 0;"><strong>Course:</strong> ${st.course}</p>
          <p style="margin: 0.3rem 0 0 0;"><strong>Academic Year:</strong> ${st.year}</p>
          <p style="margin: 0.3rem 0 0 0;"><strong>Semester:</strong> ${st.semester || '5th'}</p>
          <p style="margin: 0.3rem 0 0 0;"><strong>Cumulative GPA:</strong> ${st.gpa}</p>
          <p style="margin: 0.3rem 0 0 0;"><strong>Status:</strong> <span class="badge badge-success">${st.status}</span></p>
        </div>
      </div>
    `;
  }

  if (window.openModal) window.openModal('studentDetailModal');
};

/**
 * Export Student List to CSV (Practical 11 Intermediate Extension)
 */
function exportStudentsToCSV() {
  if (!allStudents || allStudents.length === 0) {
    if (window.showToast) window.showToast('No students to export.', 'error');
    return;
  }

  const headers = ['ID', 'StudentID', 'Name', 'Email', 'Phone', 'Department', 'Course', 'Year', 'GPA', 'Status'];
  const rows = allStudents.map(s => [
    s.id,
    `"${s.student_id}"`,
    `"${s.name}"`,
    `"${s.email}"`,
    `"${s.phone || ''}"`,
    `"${s.department}"`,
    `"${s.course}"`,
    `"${s.year}"`,
    s.gpa,
    s.status
  ]);

  const csvContent = 'data:text/csv;charset=utf-8,' +
    [headers.join(','), ...rows.map(e => e.join(','))].join('\n');

  const encodedUri = encodeURI(csvContent);
  const link = document.createElement('a');
  link.setAttribute('href', encodedUri);
  link.setAttribute('download', `StudentHub_Directory_${new Date().toISOString().slice(0, 10)}.csv`);
  document.body.appendChild(link);
  link.click();
  document.body.removeChild(link);

  if (window.showToast) {
    window.showToast('📁 Exported student directory to CSV successfully!', 'success');
  }
}

function escapeHTML(str) {
  if (!str) return '';
  return str.replace(/[&<>'"]/g, tag => ({
    '&': '&amp;',
    '<': '&lt;',
    '>': '&gt;',
    "'": '&#39;',
    '"': '&quot;'
  }[tag] || tag));
}
