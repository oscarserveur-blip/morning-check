// Gestion de la configuration simplifiée des templates

// Construction dynamique des sections depuis les catégories des clients
async function fetchClientCategories() {
    const checked = Array.from(document.querySelectorAll('input[name="client_ids[]"]:checked')).map(i => i.value);
    const container = document.getElementById('sections-container');
    container.innerHTML = '';
    if (checked.length === 0) {
        container.innerHTML = '<div class="col-12"><div class="alert alert-info mb-0">Sélectionnez un ou plusieurs clients ci-dessus pour charger leurs catégories.</div></div>';
        updateConfiguration();
        updatePreview();
        return;
    }
    try {
        const url = `/clients/categories?ids=${encodeURIComponent(checked.join(','))}`;
        const res = await fetch(url, { headers: { 'Accept': 'application/json' } });
        if (!res.ok) throw new Error('HTTP ' + res.status);
        const data = await res.json();
        const categories = Array.isArray(data.categories) ? data.categories : [];
        if (categories.length === 0) {
            container.innerHTML = '<div class="col-12"><div class="alert alert-warning mb-0">Aucune catégorie trouvée pour les clients sélectionnés.</div></div>';
        } else {
            // Grouper par client_id
            const byClient = categories.reduce((acc, c) => {
                (acc[c.client_id] = acc[c.client_id] || []).push(c);
                return acc;
            }, {});
            Object.keys(byClient).forEach(clientId => {
                const list = byClient[clientId];
                const col = document.createElement('div');
                col.className = 'col-12 col-md-6';
                const card = document.createElement('div');
                card.className = 'card border';
                const body = document.createElement('div');
                body.className = 'card-body';
                const title = document.createElement('h6');
                title.className = 'mb-3';
                title.textContent = `Client #${clientId}`;
                body.appendChild(title);
                list.forEach(cat => {
                    const div = document.createElement('div');
                    div.className = 'form-check mb-2';
                    const input = document.createElement('input');
                    input.type = 'checkbox';
                    input.className = 'form-check-input section-checkbox';
                    input.name = 'sections[]';
                    input.value = `cat:${cat.id}`;
                    input.id = `section_cat_${cat.id}`;
                    input.checked = true;
                    input.addEventListener('change', updateConfiguration);
                    const label = document.createElement('label');
                    label.className = 'form-check-label';
                    label.setAttribute('for', input.id);
                    label.textContent = cat.title;
                    div.appendChild(input);
                    div.appendChild(label);
                    body.appendChild(div);
                });
                card.appendChild(body);
                col.appendChild(card);
                container.appendChild(col);
            });
        }
    } catch (e) {
        container.innerHTML = '<div class="col-12"><div class="alert alert-danger mb-0">Erreur lors du chargement des catégories.</div></div>';
        console.error(e);
    }
    updateConfiguration();
    updatePreview();
}

// Gestion des options d'affichage
document.querySelectorAll('select[name="status_style"], select[name="date_format"], input[type="checkbox"]').forEach(function(element) {
    element.addEventListener('change', updateConfiguration);
});

// Mise à jour de la configuration JSON
function updateConfiguration() {
    const sections = [];
    const selectedSections = document.querySelectorAll('.section-checkbox:checked');
    selectedSections.forEach(function(checkbox) {
        const val = checkbox.value;
        if (val.startsWith('cat:')) {
            const categoryId = parseInt(val.split(':')[1], 10);
            if (!isNaN(categoryId)) {
                sections.push({ id: categoryId, type: 'category' });
            }
        }
    });

    const sectionConfig = {
        sections: sections
    };

    const config = {
        status_style: document.getElementById('status_style').value,
        date_format: document.getElementById('date_format').value,
        show_timestamp: document.getElementById('show_timestamp').checked,
        show_contact_info: document.getElementById('show_contact_info').checked
    };

    document.getElementById('section_config_hidden').value = JSON.stringify(sectionConfig);
    document.getElementById('config_hidden').value = JSON.stringify(config);
}

// Aperçu du template
function updatePreview() {
    const previewContent = document.getElementById('template-preview-content');
    const headerTitle = document.querySelector('input[name="header_title"]').value || 'Bulletin de Santé';
    const headerColor = document.querySelector('input[name="header_color"]').value || '#007bff';
    const footerText = document.querySelector('input[name="footer_text"]').value || 'Contact: support@example.com';
    const footerColor = document.querySelector('input[name="footer_color"]').value || '#6c757d';
    
    const selectedSections = document.querySelectorAll('.section-checkbox:checked');
    const dateFormat = document.getElementById('date_format').value;
    
    let dateText = '';
    switch(dateFormat) {
        case 'french':
            dateText = 'lundi 01/09/2025';
            break;
        case 'iso':
            dateText = '2025-09-01';
            break;
        case 'short':
            dateText = '01/09/2025';
            break;
    }

    let sectionsHtml = '';
    selectedSections.forEach(function(checkbox) {
        const val = checkbox.value;
        if (val.startsWith('cat:')) {
            const label = checkbox.nextElementSibling ? checkbox.nextElementSibling.textContent : 'Catégorie';
            sectionsHtml += `
                <div class="mb-3">
                    <h5>${label}</h5>
                    <div class="text-muted small">Services de la catégorie affichés ici dans le bulletin réel.</div>
                </div>
            `;
        }
    });

    previewContent.innerHTML = `
        <div style="border: 2px solid ${headerColor}; border-radius: 8px; padding: 20px;">
            <!-- Header -->
            <div class="text-center mb-4" style="background-color: ${headerColor}; color: white; padding: 15px; border-radius: 5px; margin: -20px -20px 20px -20px;">
                <h3 class="mb-0">${headerTitle}</h3>
                <small>${dateText}</small>
            </div>
            
            <!-- Sections -->
            ${sectionsHtml}
            
            <!-- Footer -->
            <div class="text-center mt-4 pt-3" style="border-top: 2px solid ${footerColor}; color: ${footerColor};">
                <small>${footerText}</small>
            </div>
        </div>
    `;
}

// Initialisation
document.addEventListener('DOMContentLoaded', function() {
    // Écoute la sélection des clients pour charger les catégories
    document.querySelectorAll('input[name="client_ids[]"]').forEach(cb => {
        cb.addEventListener('change', fetchClientCategories);
    });
    fetchClientCategories();
});
