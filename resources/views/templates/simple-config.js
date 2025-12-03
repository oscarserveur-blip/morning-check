// Gestion de la configuration simplifiée des templates

document.addEventListener('DOMContentLoaded', function() {
    // Fonction pour mettre à jour la configuration JSON
    function updateConfig() {
        const config = {
            ok_color: document.getElementById('ok_color').value.replace('#', ''),
            nok_color: document.getElementById('nok_color').value.replace('#', ''),
            status_style: document.getElementById('status_style').value,
            date_format: document.getElementById('date_format').value,
            show_timestamp: document.getElementById('show_timestamp').checked,
            show_contact_info: document.getElementById('show_contact_info').checked,
            header_color: document.getElementById('header_color').value.replace('#', ''),
            footer_color: document.getElementById('footer_color').value.replace('#', '')
        };

        document.getElementById('config_hidden').value = JSON.stringify(config);
    }

    // Fonction pour mettre à jour la configuration des sections
    function updateSectionConfig() {
        const sections = [];
        document.querySelectorAll('.section-item').forEach((section, index) => {
            sections.push({
                name: section.querySelector('input[name="section_name"]').value,
                color: section.querySelector('input[name="section_color"]').value.replace('#', ''),
                order: index
            });
        });

        document.getElementById('section_config_hidden').value = JSON.stringify({ sections });
    }

    // Écouteurs d'événements pour tous les champs de couleur et configuration
    const colorInputs = ['ok_color', 'nok_color', 'header_color', 'footer_color'];
    const configInputs = ['status_style', 'date_format', 'show_timestamp', 'show_contact_info'];
    
    [...colorInputs, ...configInputs].forEach(id => {
        const element = document.getElementById(id);
        if (element) {
            element.addEventListener('input', function() {
                updateConfig();
                updatePreview();
            });
            element.addEventListener('change', function() {
                updateConfig();
                updatePreview();
            });
        }
    });

    // Écouter les changements de section
    document.querySelectorAll('.section-item').forEach(section => {
        const colorInput = section.querySelector('input[name="section_color"]');
        if (colorInput) {
            colorInput.addEventListener('input', function() {
                updateSectionConfig();
                updatePreview();
            });
        }
    });

    // Initialisation des configurations
    updateConfig();
    updateSectionConfig();
    updatePreview();
});

// Fonction pour mettre à jour l'aperçu avec les nouvelles couleurs
function updatePreview() {
    const previewContent = document.getElementById('template-preview-content');
    if (!previewContent) return;

    const headerTitle = document.querySelector('input[name="header_title"]').value || 'Bulletin de Santé';
    const headerColor = document.getElementById('header_color').value;
    const footerText = document.querySelector('input[name="footer_text"]').value || 'Contact: support@example.com';
    const footerColor = document.getElementById('footer_color').value;
    const okColor = document.getElementById('ok_color').value;
    const nokColor = document.getElementById('nok_color').value;

    // ... Reste du code updatePreview existant ...
    
    // Mise à jour des couleurs des badges
    document.querySelectorAll('.badge-ok').forEach(badge => {
        badge.style.backgroundColor = okColor;
    });
    document.querySelectorAll('.badge-nok').forEach(badge => {
        badge.style.backgroundColor = nokColor;
    });
}

// Gestion de la section personnalisée
document.getElementById('section_custom').addEventListener('change', function() {
    const details = document.getElementById('custom-section-details');
    if (this.checked) {
        details.style.display = 'block';
    } else {
        details.style.display = 'none';
    }
    updateConfiguration();
});

// Gestion des sections
document.querySelectorAll('.section-checkbox').forEach(function(checkbox) {
    checkbox.addEventListener('change', updateConfiguration);
});

// Gestion des options d'affichage
document.querySelectorAll('select[name="status_style"], select[name="date_format"], input[type="checkbox"]').forEach(function(element) {
    element.addEventListener('change', updateConfiguration);
});

// Mise à jour de la configuration JSON
function updateConfiguration() {
    const sections = [];
    const selectedSections = document.querySelectorAll('.section-checkbox:checked');
    
    selectedSections.forEach(function(checkbox) {
        if (checkbox.value === 'custom') {
            const name = document.querySelector('input[name="custom_section_name"]').value;
            const description = document.querySelector('textarea[name="custom_section_description"]').value;
            if (name) {
                sections.push({
                    name: name,
                    description: description,
                    type: 'custom'
                });
            }
        } else {
            sections.push({
                name: checkbox.value,
                type: 'predefined'
            });
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
        if (checkbox.value === 'applications') {
            sectionsHtml += `
                <div class="mb-4">
                    <h5>📱 Applications</h5>
                    <div class="row">
                        <div class="col-md-6">
                            <div class="d-flex justify-content-between align-items-center mb-2">
                                <span>GLPI</span>
                                <span class="badge bg-success">OK</span>
                            </div>
                            <div class="d-flex justify-content-between align-items-center mb-2">
                                <span>GMAO</span>
                                <span class="badge bg-success">OK</span>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="d-flex justify-content-between align-items-center mb-2">
                                <span>CITYLINX</span>
                                <span class="badge bg-success">OK</span>
                            </div>
                            <div class="d-flex justify-content-between align-items-center mb-2">
                                <span>HYPERVISEUR</span>
                                <span class="badge bg-success">OK</span>
                            </div>
                        </div>
                    </div>
                </div>
            `;
        } else if (checkbox.value === 'informatique') {
            sectionsHtml += `
                <div class="mb-4">
                    <h5>💻 Informatique</h5>
                    <div class="row">
                        <div class="col-md-6">
                            <div class="d-flex justify-content-between align-items-center mb-2">
                                <span>Systancia</span>
                                <span class="badge bg-success">OK</span>
                            </div>
                            <div class="d-flex justify-content-between align-items-center mb-2">
                                <span>Keycloak</span>
                                <span class="badge bg-success">OK</span>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="d-flex justify-content-between align-items-center mb-2">
                                <span>Trustbuilder</span>
                                <span class="badge bg-success">OK</span>
                            </div>
                            <div class="d-flex justify-content-between align-items-center mb-2">
                                <span>SharePoint</span>
                                <span class="badge bg-success">OK</span>
                            </div>
                        </div>
                    </div>
                </div>
            `;
        } else if (checkbox.value === 'reseaux') {
            sectionsHtml += `
                <div class="mb-4">
                    <h5>🌐 Réseaux et Sites Distants</h5>
                    <div class="row">
                        <div class="col-md-6">
                            <div class="d-flex justify-content-between align-items-center mb-2">
                                <span>www.google.fr</span>
                                <span class="badge bg-success">OK</span>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="d-flex justify-content-between align-items-center mb-2">
                                <span>login.microsoftonline.com</span>
                                <span class="badge bg-success">OK</span>
                            </div>
                        </div>
                    </div>
                </div>
            `;
        } else if (checkbox.value === 'infrastructure') {
            sectionsHtml += `
                <div class="mb-4">
                    <h5>🏗️ Infrastructure</h5>
                    <div class="row">
                        <div class="col-md-6">
                            <div class="d-flex justify-content-between align-items-center mb-2">
                                <span>PowerProtect Datamanager</span>
                                <span class="badge bg-success">OK</span>
                            </div>
                            <div class="d-flex justify-content-between align-items-center mb-2">
                                <span>Active Directory</span>
                                <span class="badge bg-success">OK</span>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="d-flex justify-content-between align-items-center mb-2">
                                <span>VMware Vsphere</span>
                                <span class="badge bg-success">OK</span>
                            </div>
                            <div class="d-flex justify-content-between align-items-center mb-2">
                                <span>Zabbix</span>
                                <span class="badge bg-success">OK</span>
                            </div>
                        </div>
                    </div>
                </div>
            `;
        } else if (checkbox.value === 'custom') {
            const customName = document.querySelector('input[name="custom_section_name"]').value || 'Section Personnalisée';
            sectionsHtml += `
                <div class="mb-4">
                    <h5>➕ ${customName}</h5>
                    <div class="row">
                        <div class="col-md-6">
                            <div class="d-flex justify-content-between align-items-center mb-2">
                                <span>Service 1</span>
                                <span class="badge bg-success">OK</span>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="d-flex justify-content-between align-items-center mb-2">
                                <span>Service 2</span>
                                <span class="badge bg-success">OK</span>
                            </div>
                        </div>
                    </div>
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
    updateConfiguration();
    updatePreview();
});
