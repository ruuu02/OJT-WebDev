// ── Dropdown ──────────────────────────────────────────────────────────
function toggleDropdown() {
    document.getElementById('dropdownMenu').classList.toggle('open');
    document.getElementById('chevron').classList.toggle('open');
}
document.addEventListener('click', function(e) {
    if (!e.target.closest('.dropdown')) {
        document.getElementById('dropdownMenu').classList.remove('open');
        document.getElementById('chevron').classList.remove('open');
    }
});

// ── Left panel row selection ──────────────────────────────────────────
let selectedData = null;

function selectRow(el, category, name, id) {
    document.querySelectorAll('.section-row').forEach(r => r.classList.remove('active'));
    el.classList.add('active');

    selectedData = { category, name, id };

    document.getElementById('detailCategory').textContent = category.replace(/_/g,' ').toUpperCase();
    document.getElementById('detailTitle').textContent = name;
    document.getElementById('detailCard').classList.remove('hidden');

    populateTable(category, name, id);
}

function populateTable(category, name, id) {
    const tbody = document.getElementById('tableBody');
    tbody.innerHTML = `
        <tr>
            <td><input type="checkbox" class="row-check"></td>
            <td class="font-medium">${name}</td>
            <td>${category.charAt(0).toUpperCase() + category.slice(1)}</td>
            <td><span class="bg-green-100 text-green-700 text-xs px-2 py-0.5 rounded-full">Active</span></td>
            <td class="text-gray-400 text-xs">—</td>
            <td class="text-gray-400 text-xs">ID: ${id}</td>
        </tr>
    `;
}

function toggleAll(master) {
    document.querySelectorAll('.row-check').forEach(cb => cb.checked = master.checked);
}

function handleAction() {
    const action = document.getElementById('actionSelect').value;
    if (!action) { alert('Please select an action first.'); return; }
    const checked = document.querySelectorAll('.row-check:checked');
    if (!checked.length) { alert('No items selected.'); return; }
    alert(`Action "${action}" would apply to ${checked.length} item(s). (Wire to backend when DB is ready)`);
}

// ── Upload Modal ──────────────────────────────────────────────────────
function openModal(event, label) {
    event.stopPropagation();
    document.getElementById('modalLabel').textContent = label || '—';
    document.getElementById('uploadModal').classList.add('open');
    document.getElementById('previewArea').classList.add('hidden');
    document.getElementById('fileInput').value = '';
}

function closeModal() {
    document.getElementById('uploadModal').classList.remove('open');
}

function closeModalOnOverlay(event) {
    if (event.target === document.getElementById('uploadModal')) closeModal();
}

function previewFile(event) {
    const file = event.target.files[0];
    if (!file) return;
    const reader = new FileReader();
    reader.onload = e => {
        document.getElementById('previewImg').src = e.target.result;
        document.getElementById('previewName').textContent = file.name;
        document.getElementById('previewArea').classList.remove('hidden');
        document.getElementById('dropZone').classList.add('hidden');
    };
    reader.readAsDataURL(file);
}

function handleDragOver(e) {
    e.preventDefault();
    e.currentTarget.classList.add('border-blue-400', 'bg-blue-50');
}

function handleDrop(e) {
    e.preventDefault();
    const file = e.dataTransfer.files[0];
    if (file) {
        const dt = new DataTransfer();
        dt.items.add(file);
        document.getElementById('fileInput').files = dt.files;
        previewFile({ target: { files: [file] } });
    }
}

function submitUpload() {
    const file = document.getElementById('fileInput').files[0];
    if (!file) { alert('Please select a file first.'); return; }
    alert(`"${file.name}" ready to upload. Connect to backend route when database is set up.`);
    closeModal();
    document.getElementById('dropZone').classList.remove('hidden');
}
