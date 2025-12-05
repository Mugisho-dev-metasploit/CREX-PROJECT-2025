/**
 * Administration MySQL - JavaScript
 * CREx - Interface complète de gestion de base de données
 */

// Configuration
const API_URL = 'admin-database-ajax.php';
let currentDatabase = document.getElementById('databaseSelector')?.value || 'crex_db';
let codeMirrorEditor = null;

// Exposer codeMirrorEditor globalement pour les autres scripts
window.codeMirrorEditor = null;

// Initialisation
document.addEventListener('DOMContentLoaded', function() {
    initializeCodeMirror();
    initializeEventListeners();
    loadDatabases();
    loadTables();
    loadUsers();
    loadViews();
    loadProcedures();
    loadTriggers();
    loadMySQLStatus();
    loadConnections();
    loadErrorLogs();
    
    // Actualiser automatiquement certaines données
    setInterval(() => {
        if (document.getElementById('monitoring-tab').style.display !== 'none') {
            loadConnections();
            loadMySQLStatus();
        }
    }, 5000);
});

// Initialiser CodeMirror pour l'éditeur SQL
function initializeCodeMirror() {
    const textarea = document.getElementById('sqlQuery');
    if (textarea) {
        // Détecter le thème actuel
        const currentTheme = document.documentElement.getAttribute('data-theme') || 
                           (document.documentElement.classList.contains('dark-mode') ? 'dark' : 'light');
        const codeMirrorTheme = currentTheme === 'dark' ? 'monokai' : 'eclipse';
        
        codeMirrorEditor = CodeMirror.fromTextArea(textarea, {
            mode: 'text/x-mysql',
            theme: codeMirrorTheme,
            lineNumbers: true,
            indentWithTabs: true,
            smartIndent: true,
            lineWrapping: true,
            autofocus: true
        });
        
        // Exposer globalement
        window.codeMirrorEditor = codeMirrorEditor;
        
        // Écouter les changements de thème
        function updateCodeMirrorTheme() {
            const newTheme = document.documentElement.getAttribute('data-theme') || 
                           (document.documentElement.classList.contains('dark-mode') ? 'dark' : 'light');
            const newCodeMirrorTheme = newTheme === 'dark' ? 'monokai' : 'eclipse';
            if (codeMirrorEditor) {
                codeMirrorEditor.setOption('theme', newCodeMirrorTheme);
            }
        }
        
        // Écouter les changements de thème
        document.addEventListener('themeChanged', updateCodeMirrorTheme);
        
        // Observer les changements d'attribut data-theme
        const observer = new MutationObserver(function(mutations) {
            mutations.forEach(function(mutation) {
                if (mutation.type === 'attributes' && mutation.attributeName === 'data-theme') {
                    updateCodeMirrorTheme();
                }
            });
        });
        
        observer.observe(document.documentElement, {
            attributes: true,
            attributeFilter: ['data-theme', 'class']
        });
    }
}

// Initialiser les écouteurs d'événements
function initializeEventListeners() {
    // Navigation sidebar
    document.querySelectorAll('.sidebar .nav-link').forEach(link => {
        link.addEventListener('click', function(e) {
            e.preventDefault();
            const tab = this.getAttribute('data-tab');
            switchTab(tab);
        });
    });
    
    // Sélecteur de base de données
    const dbSelector = document.getElementById('databaseSelector');
    if (dbSelector) {
        dbSelector.addEventListener('change', function() {
            currentDatabase = this.value;
            loadTables();
            loadViews();
            loadProcedures();
            loadTriggers();
        });
    }
    
    // Boutons d'action
    document.getElementById('refreshDatabases')?.addEventListener('click', loadDatabases);
    document.getElementById('refreshTables')?.addEventListener('click', loadTables);
    document.getElementById('refreshUsers')?.addEventListener('click', loadUsers);
    document.getElementById('refreshConnections')?.addEventListener('click', loadConnections);
    document.getElementById('refreshLogs')?.addEventListener('click', loadErrorLogs);
    
    // SQL Query
    document.getElementById('executeQuery')?.addEventListener('click', executeQuery);
    document.getElementById('explainQuery')?.addEventListener('click', explainQuery);
    document.getElementById('clearQuery')?.addEventListener('click', clearQuery);
    
    // Import/Export
    document.getElementById('exportFormat')?.addEventListener('change', function() {
        const tableSelect = document.getElementById('exportTableSelect');
        if (this.value === 'csv') {
            tableSelect.style.display = 'block';
            loadTablesForExport();
        } else {
            tableSelect.style.display = 'none';
        }
    });
    document.getElementById('exportData')?.addEventListener('click', exportData);
    document.getElementById('importSQL')?.addEventListener('click', importSQL);
    document.getElementById('importCSV')?.addEventListener('click', importCSV);
    
    // Modals
    document.getElementById('submitCreateDatabase')?.addEventListener('click', createDatabase);
    document.getElementById('submitCreateUser')?.addEventListener('click', createUser);
    document.getElementById('submitCreateTable')?.addEventListener('click', createTable);
    document.getElementById('submitCreateIndex')?.addEventListener('click', createIndex);
    document.getElementById('submitGrantPrivileges')?.addEventListener('click', grantPrivileges);
    document.getElementById('submitRevokePrivileges')?.addEventListener('click', revokePrivileges);
    document.getElementById('loadPrivileges')?.addEventListener('click', loadUserPrivileges);
    document.getElementById('generatePassword')?.addEventListener('click', generatePassword);
    document.getElementById('addColumn')?.addEventListener('click', addTableColumn);
    document.getElementById('addIndexColumn')?.addEventListener('click', addIndexColumn);
    
    // Gestion des colonnes de table
    document.addEventListener('click', function(e) {
        if (e.target.closest('.remove-column')) {
            e.target.closest('.column-row').remove();
        }
    });
    
    // Index table selector
    document.getElementById('indexTable')?.addEventListener('change', function() {
        if (this.value) {
            loadTableIndexes(this.value);
        }
    });
}

// Changer d'onglet
function switchTab(tabName) {
    // Mettre à jour la sidebar
    document.querySelectorAll('.sidebar .nav-link').forEach(link => {
        link.classList.remove('active');
    });
    document.querySelector(`[data-tab="${tabName}"]`)?.classList.add('active');
    
    // Afficher le bon contenu
    document.querySelectorAll('.tab-pane').forEach(pane => {
        pane.style.display = 'none';
    });
    const targetPane = document.getElementById(`${tabName}-tab`);
    if (targetPane) {
        targetPane.style.display = 'block';
    }
    
    // Charger les données si nécessaire
    if (tabName === 'tables') {
        const tbody = targetPane.querySelector('tbody');
        if (tbody && (!tbody.querySelector('tr') || tbody.querySelector('tr').textContent.includes('Chargement'))) {
            loadTables();
        }
    } else if (tabName === 'users') {
        loadUsers();
    } else if (tabName === 'views') {
        loadViews();
    } else if (tabName === 'procedures') {
        loadProcedures();
    } else if (tabName === 'triggers') {
        loadTriggers();
    } else if (tabName === 'monitoring') {
        loadMySQLStatus();
        loadConnections();
    } else if (tabName === 'logs') {
        loadErrorLogs();
    }
}

// Charger les bases de données
function loadDatabases() {
    callAPI('list_databases', {}, function(response) {
        if (response.success) {
            const tbody = document.querySelector('#databasesTable tbody');
            const selector = document.getElementById('databaseSelector');
            
            if (tbody) {
                tbody.innerHTML = '';
                response.data.forEach(db => {
                    const row = tbody.insertRow();
                    row.innerHTML = `
                        <td><strong>${escapeHtml(db)}</strong></td>
                        <td>-</td>
                        <td>-</td>
                        <td>-</td>
                        <td>
                            <button class="btn btn-sm btn-primary" onclick="selectDatabase('${escapeHtml(db)}')">
                                <i class="fas fa-check"></i> Sélectionner
                            </button>
                            <button class="btn btn-sm btn-danger" onclick="confirmDropDatabase('${escapeHtml(db)}')">
                                <i class="fas fa-trash"></i>
                            </button>
                        </td>
                    `;
                });
            }
            
            if (selector) {
                selector.innerHTML = '';
                response.data.forEach(db => {
                    const option = document.createElement('option');
                    option.value = db;
                    option.textContent = db;
                    if (db === currentDatabase) option.selected = true;
                    selector.appendChild(option);
                });
            }
        }
    });
}

// Charger les tables
function loadTables() {
    callAPI('list_tables', { database: currentDatabase }, function(response) {
        if (response.success) {
            const tbody = document.querySelector('#tablesTable tbody');
            const csvTable = document.getElementById('csvTable');
            const exportTable = document.getElementById('exportTable');
            
            if (tbody) {
                tbody.innerHTML = '';
                response.data.forEach(table => {
                    const row = tbody.insertRow();
                    row.innerHTML = `
                        <td><strong>${escapeHtml(table)}</strong></td>
                        <td>Table</td>
                        <td>-</td>
                        <td>-</td>
                        <td>
                            <button class="btn btn-sm btn-info" onclick="viewTableInfo('${escapeHtml(table)}')">
                                <i class="fas fa-info-circle"></i>
                            </button>
                            <button class="btn btn-sm btn-success" onclick="exportTable('${escapeHtml(table)}', 'csv')">
                                <i class="fas fa-download"></i>
                            </button>
                            <button class="btn btn-sm btn-danger" onclick="confirmDropTable('${escapeHtml(table)}')">
                                <i class="fas fa-trash"></i>
                            </button>
                        </td>
                    `;
                });
            }
            
            // Mettre à jour les sélecteurs
            [csvTable, exportTable].forEach(select => {
                if (select) {
                    select.innerHTML = '<option value="">Sélectionner une table</option>';
                    response.data.forEach(table => {
                        const option = document.createElement('option');
                        option.value = table;
                        option.textContent = table;
                        select.appendChild(option);
                    });
                }
            });
            
            // Mettre à jour le sélecteur d'index
            const indexTable = document.getElementById('indexTable');
            if (indexTable) {
                indexTable.innerHTML = '<option value="">Sélectionner une table</option>';
                response.data.forEach(table => {
                    const option = document.createElement('option');
                    option.value = table;
                    option.textContent = table;
                    indexTable.appendChild(option);
                });
            }
        }
    });
}

// Charger les utilisateurs
function loadUsers() {
    callAPI('list_users', {}, function(response) {
        if (response.success) {
            const tbody = document.querySelector('#usersTable tbody');
            const privilegeUser = document.getElementById('privilegeUser');
            
            if (tbody) {
                tbody.innerHTML = '';
                response.data.forEach(user => {
                    const row = tbody.insertRow();
                    row.innerHTML = `
                        <td><strong>${escapeHtml(user.User)}</strong></td>
                        <td>${escapeHtml(user.Host)}</td>
                        <td>
                            <button class="btn btn-sm btn-primary" onclick="manageUserPrivileges('${escapeHtml(user.User)}', '${escapeHtml(user.Host)}')">
                                <i class="fas fa-key"></i> Privilèges
                            </button>
                            <button class="btn btn-sm btn-danger" onclick="confirmDropUser('${escapeHtml(user.User)}', '${escapeHtml(user.Host)}')">
                                <i class="fas fa-trash"></i>
                            </button>
                        </td>
                    `;
                });
            }
            
            if (privilegeUser) {
                privilegeUser.innerHTML = '<option value="">Sélectionner un utilisateur</option>';
                response.data.forEach(user => {
                    const option = document.createElement('option');
                    option.value = user.User;
                    option.textContent = `${user.User}@${user.Host}`;
                    option.dataset.host = user.Host;
                    privilegeUser.appendChild(option);
                });
            }
        }
    });
}

// Charger les vues
function loadViews() {
    callAPI('list_views', { database: currentDatabase }, function(response) {
        if (response.success) {
            const tbody = document.querySelector('#viewsTable tbody');
            if (tbody) {
                tbody.innerHTML = '';
                if (response.data.length === 0) {
                    tbody.innerHTML = '<tr><td colspan="2" class="text-center">Aucune vue trouvée</td></tr>';
                } else {
                    response.data.forEach(view => {
                        const row = tbody.insertRow();
                        row.innerHTML = `
                            <td><strong>${escapeHtml(view)}</strong></td>
                            <td>
                                <button class="btn btn-sm btn-info" onclick="viewViewDefinition('${escapeHtml(view)}')">
                                    <i class="fas fa-eye"></i> Voir définition
                                </button>
                            </td>
                        `;
                    });
                }
            }
        }
    });
}

// Charger les procédures
function loadProcedures() {
    callAPI('list_procedures', { database: currentDatabase }, function(response) {
        if (response.success) {
            const tbody = document.querySelector('#proceduresTable tbody');
            if (tbody) {
                tbody.innerHTML = '';
                if (response.data.length === 0) {
                    tbody.innerHTML = '<tr><td colspan="3" class="text-center">Aucune procédure trouvée</td></tr>';
                } else {
                    response.data.forEach(proc => {
                        const row = tbody.insertRow();
                        row.innerHTML = `
                            <td><strong>${escapeHtml(proc.ROUTINE_NAME)}</strong></td>
                            <td>${escapeHtml(proc.ROUTINE_TYPE)}</td>
                            <td>
                                <button class="btn btn-sm btn-info" onclick="viewProcedureDefinition('${escapeHtml(proc.ROUTINE_NAME)}')">
                                    <i class="fas fa-eye"></i> Voir définition
                                </button>
                            </td>
                        `;
                    });
                }
            }
        }
    });
}

// Charger les triggers
function loadTriggers() {
    callAPI('list_triggers', { database: currentDatabase }, function(response) {
        if (response.success) {
            const tbody = document.querySelector('#triggersTable tbody');
            if (tbody) {
                tbody.innerHTML = '';
                if (response.data.length === 0) {
                    tbody.innerHTML = '<tr><td colspan="4" class="text-center">Aucun trigger trouvé</td></tr>';
                } else {
                    response.data.forEach(trigger => {
                        const row = tbody.insertRow();
                        row.innerHTML = `
                            <td><strong>${escapeHtml(trigger.TRIGGER_NAME)}</strong></td>
                            <td>${escapeHtml(trigger.EVENT_OBJECT_TABLE)}</td>
                            <td>${escapeHtml(trigger.EVENT_MANIPULATION)}</td>
                            <td>
                                <button class="btn btn-sm btn-info" onclick="viewTriggerDefinition('${escapeHtml(trigger.TRIGGER_NAME)}')">
                                    <i class="fas fa-eye"></i> Voir définition
                                </button>
                            </td>
                        `;
                    });
                }
            }
        }
    });
}

// Charger le statut MySQL
function loadMySQLStatus() {
    callAPI('get_status', {}, function(response) {
        if (response.success) {
            const container = document.getElementById('mysqlStatus');
            if (container) {
                const stats = response.data;
                container.innerHTML = `
                    <div class="row">
                        <div class="col-md-6 stats-card">
                            <div class="number">${formatNumber(stats.Uptime || 0)}</div>
                            <div class="label">Uptime (secondes)</div>
                        </div>
                        <div class="col-md-6 stats-card">
                            <div class="number">${formatNumber(stats.Threads_connected || 0)}</div>
                            <div class="label">Connexions actives</div>
                        </div>
                        <div class="col-md-6 stats-card">
                            <div class="number">${formatNumber(stats.Questions || 0)}</div>
                            <div class="label">Requêtes totales</div>
                        </div>
                        <div class="col-md-6 stats-card">
                            <div class="number">${formatNumber(stats.Slow_queries || 0)}</div>
                            <div class="label">Requêtes lentes</div>
                        </div>
                    </div>
                `;
            }
        }
    });
}

// Charger les connexions actives
function loadConnections() {
    callAPI('get_active_connections', {}, function(response) {
        if (response.success) {
            const tbody = document.querySelector('#connectionsTable tbody');
            if (tbody) {
                tbody.innerHTML = '';
                response.data.forEach(conn => {
                    const row = tbody.insertRow();
                    row.innerHTML = `
                        <td>${conn.ID}</td>
                        <td>${escapeHtml(conn.USER || '')}</td>
                        <td>${escapeHtml(conn.HOST || '')}</td>
                        <td>${escapeHtml(conn.DB || '')}</td>
                        <td>${conn.TIME}s</td>
                        <td>
                            ${conn.ID > 0 ? `<button class="btn btn-sm btn-danger" onclick="killProcess(${conn.ID})">
                                <i class="fas fa-stop"></i> Tuer
                            </button>` : ''}
                        </td>
                    `;
                });
            }
        }
    });
}

// Charger les logs d'erreur
function loadErrorLogs() {
    callAPI('get_error_logs', {}, function(response) {
        if (response.success) {
            const container = document.getElementById('errorLogs');
            if (container) {
                if (response.data.length === 0) {
                    container.textContent = 'Aucun log d\'erreur disponible';
                } else {
                    container.textContent = response.data.join('');
                }
            }
        }
    });
}

// Exécuter une requête SQL
function executeQuery() {
    const query = codeMirrorEditor ? codeMirrorEditor.getValue() : document.getElementById('sqlQuery').value;
    if (!query.trim()) {
        showAlert('Veuillez entrer une requête SQL', 'warning');
        return;
    }
    
    const loading = document.getElementById('queryLoading');
    const resultDiv = document.getElementById('queryResult');
    
    loading.classList.add('active');
    resultDiv.innerHTML = '';
    
    callAPI('execute_query', {
        database: currentDatabase,
        query: query,
        limit: 1000
    }, function(response) {
        loading.classList.remove('active');
        
        if (response.success) {
            if (response.data && response.data.length > 0) {
                // Afficher les résultats dans un tableau
                let html = `<div class="alert alert-success">Requête exécutée avec succès. ${response.row_count || response.affected_rows} ligne(s) affectée(s).</div>`;
                html += '<div class="table-responsive result-table"><table class="table table-striped table-bordered"><thead><tr>';
                
                // En-têtes
                Object.keys(response.data[0]).forEach(key => {
                    html += `<th>${escapeHtml(key)}</th>`;
                });
                html += '</tr></thead><tbody>';
                
                // Données
                response.data.forEach(row => {
                    html += '<tr>';
                    Object.values(row).forEach(value => {
                        html += `<td>${escapeHtml(value !== null ? value : 'NULL')}</td>`;
                    });
                    html += '</tr>';
                });
                
                html += '</tbody></table></div>';
                resultDiv.innerHTML = html;
            } else {
                resultDiv.innerHTML = `<div class="alert alert-info">Requête exécutée avec succès. ${response.affected_rows || 0} ligne(s) affectée(s).</div>`;
            }
        } else {
            resultDiv.innerHTML = `<div class="alert alert-danger">Erreur: ${escapeHtml(response.error)}</div>`;
        }
    });
}

// EXPLAIN d'une requête
function explainQuery() {
    const query = codeMirrorEditor ? codeMirrorEditor.getValue() : document.getElementById('sqlQuery').value;
    if (!query.trim()) {
        showAlert('Veuillez entrer une requête SQL', 'warning');
        return;
    }
    
    callAPI('explain_query', {
        database: currentDatabase,
        query: query
    }, function(response) {
        if (response.success) {
            const resultDiv = document.getElementById('queryResult');
            if (response.data && response.data.length > 0) {
                let html = '<div class="alert alert-info">Plan d\'exécution:</div>';
                html += '<div class="table-responsive"><table class="table table-striped"><thead><tr>';
                
                Object.keys(response.data[0]).forEach(key => {
                    html += `<th>${escapeHtml(key)}</th>`;
                });
                html += '</tr></thead><tbody>';
                
                response.data.forEach(row => {
                    html += '<tr>';
                    Object.values(row).forEach(value => {
                        html += `<td>${escapeHtml(value !== null ? value : 'NULL')}</td>`;
                    });
                    html += '</tr>';
                });
                
                html += '</tbody></table></div>';
                resultDiv.innerHTML = html;
            }
        } else {
            document.getElementById('queryResult').innerHTML = `<div class="alert alert-danger">Erreur: ${escapeHtml(response.error)}</div>`;
        }
    });
}

// Effacer la requête
function clearQuery() {
    if (codeMirrorEditor) {
        codeMirrorEditor.setValue('');
    } else {
        document.getElementById('sqlQuery').value = '';
    }
}

// Créer une base de données
function createDatabase() {
    const name = document.getElementById('dbName').value;
    const charset = document.getElementById('dbCharset').value;
    const collation = document.getElementById('dbCollation').value;
    
    if (!name) {
        showAlert('Le nom de la base de données est requis', 'danger');
        return;
    }
    
    callAPI('create_database', {
        name: name,
        charset: charset,
        collation: collation
    }, function(response) {
        if (response.success) {
            showAlert(response.message, 'success');
            bootstrap.Modal.getInstance(document.getElementById('createDatabaseModal')).hide();
            document.getElementById('createDatabaseForm').reset();
            loadDatabases();
        } else {
            showAlert(response.error, 'danger');
        }
    });
}

// Supprimer une base de données
function confirmDropDatabase(dbName) {
    if (dbName === currentDatabase) {
        showAlert('Impossible de supprimer la base de données actuellement sélectionnée', 'warning');
        return;
    }
    
    if (confirm(`Êtes-vous sûr de vouloir supprimer la base de données "${dbName}" ?\n\nCette action est irréversible !`)) {
        callAPI('drop_database', { name: dbName }, function(response) {
            if (response.success) {
                showAlert(response.message, 'success');
                loadDatabases();
            } else {
                showAlert(response.error, 'danger');
            }
        });
    }
}

// Sélectionner une base de données
function selectDatabase(dbName) {
    currentDatabase = dbName;
    document.getElementById('databaseSelector').value = dbName;
    loadTables();
    loadViews();
    loadProcedures();
    loadTriggers();
    showAlert(`Base de données "${dbName}" sélectionnée`, 'success');
}

// Créer un utilisateur
function createUser() {
    const username = document.getElementById('userUsername').value;
    const host = document.getElementById('userHost').value;
    const password = document.getElementById('userPassword').value;
    
    if (!username || !password) {
        showAlert('Tous les champs sont requis', 'danger');
        return;
    }
    
    callAPI('create_user', {
        username: username,
        host: host,
        password: password
    }, function(response) {
        if (response.success) {
            showAlert(response.message, 'success');
            bootstrap.Modal.getInstance(document.getElementById('createUserModal')).hide();
            document.getElementById('createUserForm').reset();
            loadUsers();
        } else {
            showAlert(response.error, 'danger');
        }
    });
}

// Supprimer un utilisateur
function confirmDropUser(username, host) {
    if (confirm(`Êtes-vous sûr de vouloir supprimer l'utilisateur "${username}@${host}" ?\n\nCette action est irréversible !`)) {
        callAPI('drop_user', { username: username, host: host }, function(response) {
            if (response.success) {
                showAlert(response.message, 'success');
                loadUsers();
            } else {
                showAlert(response.error, 'danger');
            }
        });
    }
}

// Gérer les privilèges d'un utilisateur
function manageUserPrivileges(username, host) {
    document.getElementById('privilegeModalUser').textContent = username;
    document.getElementById('privilegeModalHost').textContent = host;
    loadDatabasesForPrivileges();
    bootstrap.Modal.getInstance(document.getElementById('privilegesModal')).show();
}

// Charger les privilèges d'un utilisateur
function loadUserPrivileges() {
    const username = document.getElementById('privilegeUser').value;
    const hostSelect = document.getElementById('privilegeUser').selectedOptions[0];
    const host = hostSelect ? hostSelect.dataset.host : '%';
    
    if (!username) {
        showAlert('Sélectionnez un utilisateur', 'warning');
        return;
    }
    
    callAPI('get_user_privileges', {
        user: username,
        host: host
    }, function(response) {
        if (response.success) {
            const container = document.getElementById('privilegesContent');
            let html = '<h6>Privilèges actuels:</h6><div class="mb-3">';
            
            response.data.forEach(grant => {
                html += `<div class="privilege-badge active">${escapeHtml(grant)}</div>`;
            });
            
            html += '</div>';
            container.innerHTML = html;
            
            // Pré-remplir le modal
            document.getElementById('privilegeModalUser').textContent = username;
            document.getElementById('privilegeModalHost').textContent = host;
        }
    });
}

// Accorder des privilèges
function grantPrivileges() {
    const username = document.getElementById('privilegeModalUser').textContent;
    const host = document.getElementById('privilegeModalHost').textContent;
    const database = document.getElementById('privilegeModalDatabase').value;
    const table = document.getElementById('privilegeModalTable').value || '*';
    
    const privileges = [];
    document.querySelectorAll('.privilege-check:checked').forEach(cb => {
        if (cb.value === 'ALL PRIVILEGES') {
            privileges.push('ALL PRIVILEGES');
        } else {
            privileges.push(cb.value);
        }
    });
    
    if (!database || privileges.length === 0) {
        showAlert('Sélectionnez une base de données et au moins un privilège', 'warning');
        return;
    }
    
    callAPI('grant_privileges', {
        username: username,
        host: host,
        database: database,
        table: table,
        privileges: privileges
    }, function(response) {
        if (response.success) {
            showAlert(response.message, 'success');
            loadUserPrivileges();
        } else {
            showAlert(response.error, 'danger');
        }
    });
}

// Révoquer des privilèges
function revokePrivileges() {
    const username = document.getElementById('privilegeModalUser').textContent;
    const host = document.getElementById('privilegeModalHost').textContent;
    const database = document.getElementById('privilegeModalDatabase').value;
    const table = document.getElementById('privilegeModalTable').value || '*';
    
    const privileges = [];
    document.querySelectorAll('.privilege-check:checked').forEach(cb => {
        privileges.push(cb.value);
    });
    
    if (!database || privileges.length === 0) {
        showAlert('Sélectionnez une base de données et au moins un privilège', 'warning');
        return;
    }
    
    callAPI('revoke_privileges', {
        username: username,
        host: host,
        database: database,
        table: table,
        privileges: privileges
    }, function(response) {
        if (response.success) {
            showAlert(response.message, 'success');
            loadUserPrivileges();
        } else {
            showAlert(response.error, 'danger');
        }
    });
}

// Exporter des données
function exportData() {
    const format = document.getElementById('exportFormat').value;
    
    if (format === 'sql') {
        // Export SQL complet
        const form = document.createElement('form');
        form.method = 'POST';
        form.action = API_URL;
        form.target = '_blank';
        
        const actionInput = document.createElement('input');
        actionInput.type = 'hidden';
        actionInput.name = 'action';
        actionInput.value = 'export_database';
        form.appendChild(actionInput);
        
        const dbInput = document.createElement('input');
        dbInput.type = 'hidden';
        dbInput.name = 'database';
        dbInput.value = currentDatabase;
        form.appendChild(dbInput);
        
        document.body.appendChild(form);
        form.submit();
        document.body.removeChild(form);
    } else if (format === 'csv') {
        const table = document.getElementById('exportTable').value;
        if (!table) {
            showAlert('Sélectionnez une table', 'warning');
            return;
        }
        exportTable(table, 'csv');
    }
}

// Exporter une table
function exportTable(tableName, format) {
    if (format === 'csv') {
        const form = document.createElement('form');
        form.method = 'POST';
        form.action = API_URL;
        form.target = '_blank';
        
        const actionInput = document.createElement('input');
        actionInput.type = 'hidden';
        actionInput.name = 'action';
        actionInput.value = 'export_table_csv';
        form.appendChild(actionInput);
        
        const dbInput = document.createElement('input');
        dbInput.type = 'hidden';
        dbInput.name = 'database';
        dbInput.value = currentDatabase;
        form.appendChild(dbInput);
        
        const tableInput = document.createElement('input');
        tableInput.type = 'hidden';
        tableInput.name = 'table';
        tableInput.value = tableName;
        form.appendChild(tableInput);
        
        document.body.appendChild(form);
        form.submit();
        document.body.removeChild(form);
    }
}

// Importer SQL
function importSQL() {
    const fileInput = document.getElementById('sqlFile');
    const contentTextarea = document.getElementById('sqlContent');
    
    let sqlContent = '';
    
    if (fileInput.files.length > 0) {
        const file = fileInput.files[0];
        const reader = new FileReader();
        reader.onload = function(e) {
            sqlContent = e.target.result;
            performSQLImport(sqlContent);
        };
        reader.readAsText(file);
    } else if (contentTextarea.value) {
        sqlContent = contentTextarea.value;
        performSQLImport(sqlContent);
    } else {
        showAlert('Sélectionnez un fichier ou entrez du contenu SQL', 'warning');
    }
}

function performSQLImport(sqlContent) {
    callAPI('import_sql', {
        database: currentDatabase,
        sql_content: sqlContent
    }, function(response) {
        if (response.success) {
            showAlert(`Import réussi: ${response.executed} requête(s) exécutée(s)`, 'success');
            loadTables();
        } else {
            let errorMsg = response.error || 'Erreur lors de l\'import';
            if (response.errors && response.errors.length > 0) {
                errorMsg += '\n' + response.errors.join('\n');
            }
            showAlert(errorMsg, 'danger');
        }
    });
}

// Importer CSV
function importCSV() {
    const table = document.getElementById('csvTable').value;
    const fileInput = document.getElementById('csvFile');
    
    if (!table) {
        showAlert('Sélectionnez une table', 'warning');
        return;
    }
    
    if (fileInput.files.length === 0) {
        showAlert('Sélectionnez un fichier CSV', 'warning');
        return;
    }
    
    const file = fileInput.files[0];
    const reader = new FileReader();
    reader.onload = function(e) {
        const csvContent = e.target.result;
        
        callAPI('import_csv', {
            database: currentDatabase,
            table: table,
            csv_content: csvContent,
            delimiter: ',',
            enclosure: '"'
        }, function(response) {
            if (response.success) {
                showAlert(`Import réussi: ${response.inserted} ligne(s) insérée(s)`, 'success');
            } else {
                let errorMsg = response.error || 'Erreur lors de l\'import';
                if (response.errors && response.errors.length > 0) {
                    errorMsg += '\n' + response.errors.slice(0, 5).join('\n');
                    if (response.errors.length > 5) {
                        errorMsg += `\n... et ${response.errors.length - 5} autre(s) erreur(s)`;
                    }
                }
                showAlert(errorMsg, 'danger');
            }
        });
    };
    reader.readAsText(file);
}

// Créer une table
function createTable() {
    const tableName = document.getElementById('tableName').value;
    const columns = [];
    
    document.querySelectorAll('.column-row').forEach(row => {
        const inputs = row.querySelectorAll('input, select');
        const name = inputs[0].value;
        const type = inputs[1].value;
        const length = inputs[2].value;
        const nullCheck = inputs[3].checked;
        const primaryCheck = inputs[4].checked;
        
        if (name) {
            columns.push({
                name: name,
                type: type,
                length: length || null,
                null: nullCheck,
                primary: primaryCheck,
                auto_increment: false,
                default: null
            });
        }
    });
    
    if (!tableName || columns.length === 0) {
        showAlert('Le nom de la table et au moins une colonne sont requis', 'warning');
        return;
    }
    
    callAPI('create_table', {
        database: currentDatabase,
        table_name: tableName,
        columns: columns
    }, function(response) {
        if (response.success) {
            showAlert('Table créée avec succès', 'success');
            bootstrap.Modal.getInstance(document.getElementById('createTableModal')).hide();
            document.getElementById('createTableForm').reset();
            loadTables();
        } else {
            showAlert(response.error, 'danger');
        }
    });
}

// Supprimer une table
function confirmDropTable(tableName) {
    if (confirm(`Êtes-vous sûr de vouloir supprimer la table "${tableName}" ?\n\nCette action est irréversible !`)) {
        callAPI('drop_table', {
            database: currentDatabase,
            table_name: tableName
        }, function(response) {
            if (response.success) {
                showAlert('Table supprimée avec succès', 'success');
                loadTables();
            } else {
                showAlert(response.error, 'danger');
            }
        });
    }
}

// Voir les informations d'une table
function viewTableInfo(tableName) {
    callAPI('get_table_info', {
        database: currentDatabase,
        table: tableName
    }, function(response) {
        if (response.success) {
            // Afficher dans un modal ou une section dédiée
            let html = `<h5>Table: ${escapeHtml(tableName)}</h5>`;
            html += '<h6 class="mt-3">Colonnes:</h6><div class="table-responsive"><table class="table table-sm"><thead><tr><th>Nom</th><th>Type</th><th>Null</th><th>Default</th></tr></thead><tbody>';
            
            response.data.columns.forEach(col => {
                html += `<tr><td>${escapeHtml(col.Field)}</td><td>${escapeHtml(col.Type)}</td><td>${escapeHtml(col.Null)}</td><td>${escapeHtml(col.Default || 'NULL')}</td></tr>`;
            });
            
            html += '</tbody></table></div>';
            
            if (response.data.indexes.length > 0) {
                html += '<h6 class="mt-3">Index:</h6><div class="table-responsive"><table class="table table-sm"><thead><tr><th>Nom</th><th>Colonnes</th><th>Type</th></tr></thead><tbody>';
                response.data.indexes.forEach(idx => {
                    html += `<tr><td>${escapeHtml(idx.Key_name)}</td><td>${escapeHtml(idx.Column_name)}</td><td>${escapeHtml(idx.Non_unique ? 'INDEX' : 'UNIQUE')}</td></tr>`;
                });
                html += '</tbody></table></div>';
            }
            
            document.getElementById('queryResult').innerHTML = html;
            switchTab('sql');
        }
    });
}

// Créer un index
function createIndex() {
    const indexName = document.getElementById('indexName').value;
    const indexType = document.getElementById('indexType').value;
    const table = document.getElementById('indexTable').value;
    
    const columns = [];
    document.querySelectorAll('#indexColumns select').forEach(select => {
        if (select.value) columns.push(select.value);
    });
    
    if (!indexName || !table || columns.length === 0) {
        showAlert('Tous les champs sont requis', 'warning');
        return;
    }
    
    callAPI('create_index', {
        database: currentDatabase,
        table: table,
        index_name: indexName,
        index_type: indexType,
        columns: columns
    }, function(response) {
        if (response.success) {
            showAlert(response.message, 'success');
            bootstrap.Modal.getInstance(document.getElementById('createIndexModal')).hide();
            document.getElementById('createIndexForm').reset();
            loadTableIndexes(table);
        } else {
            showAlert(response.error, 'danger');
        }
    });
}

// Charger les index d'une table
function loadTableIndexes(tableName) {
    callAPI('get_table_info', {
        database: currentDatabase,
        table: tableName
    }, function(response) {
        if (response.success) {
            const container = document.getElementById('indexesContent');
            let html = '<div class="table-responsive"><table class="table table-hover"><thead><tr><th>Nom</th><th>Colonnes</th><th>Type</th><th>Actions</th></tr></thead><tbody>';
            
            const indexesMap = {};
            response.data.indexes.forEach(idx => {
                if (!indexesMap[idx.Key_name]) {
                    indexesMap[idx.Key_name] = {
                        name: idx.Key_name,
                        columns: [],
                        unique: !idx.Non_unique
                    };
                }
                indexesMap[idx.Key_name].columns.push(idx.Column_name);
            });
            
            Object.values(indexesMap).forEach(idx => {
                html += `<tr>
                    <td><strong>${escapeHtml(idx.name)}</strong></td>
                    <td>${escapeHtml(idx.columns.join(', '))}</td>
                    <td>${idx.unique ? '<span class="badge bg-warning">UNIQUE</span>' : '<span class="badge bg-info">INDEX</span>'}</td>
                    <td>
                        <button class="btn btn-sm btn-danger" onclick="confirmDropIndex('${escapeHtml(tableName)}', '${escapeHtml(idx.name)}')">
                            <i class="fas fa-trash"></i>
                        </button>
                    </td>
                </tr>`;
            });
            
            html += '</tbody></table></div>';
            container.innerHTML = html;
        }
    });
}

// Supprimer un index
function confirmDropIndex(tableName, indexName) {
    if (confirm(`Êtes-vous sûr de vouloir supprimer l'index "${indexName}" ?`)) {
        callAPI('drop_index', {
            database: currentDatabase,
            table: tableName,
            index_name: indexName
        }, function(response) {
            if (response.success) {
                showAlert(response.message, 'success');
                loadTableIndexes(tableName);
            } else {
                showAlert(response.error, 'danger');
            }
        });
    }
}

// Voir la définition d'une vue
function viewViewDefinition(viewName) {
    callAPI('get_view_definition', {
        database: currentDatabase,
        view_name: viewName
    }, function(response) {
        if (response.success) {
            document.getElementById('definitionModalTitle').textContent = `Vue: ${viewName}`;
            document.getElementById('definitionContent').textContent = response.data || 'Aucune définition disponible';
            new bootstrap.Modal(document.getElementById('definitionModal')).show();
        }
    });
}

// Voir la définition d'une procédure
function viewProcedureDefinition(procedureName) {
    callAPI('get_procedure_definition', {
        database: currentDatabase,
        procedure_name: procedureName
    }, function(response) {
        if (response.success) {
            document.getElementById('definitionModalTitle').textContent = `Procédure: ${procedureName}`;
            document.getElementById('definitionContent').textContent = response.data || 'Aucune définition disponible';
            new bootstrap.Modal(document.getElementById('definitionModal')).show();
        }
    });
}

// Voir la définition d'un trigger
function viewTriggerDefinition(triggerName) {
    callAPI('get_trigger_definition', {
        database: currentDatabase,
        trigger_name: triggerName
    }, function(response) {
        if (response.success) {
            const trigger = response.data;
            document.getElementById('definitionModalTitle').textContent = `Trigger: ${triggerName}`;
            let definition = `Table: ${trigger.EVENT_OBJECT_TABLE}\n`;
            definition += `Événement: ${trigger.EVENT_MANIPULATION}\n`;
            definition += `Timing: ${trigger.ACTION_TIMING}\n`;
            definition += `\nAction:\n${trigger.ACTION_STATEMENT}`;
            document.getElementById('definitionContent').textContent = definition;
            new bootstrap.Modal(document.getElementById('definitionModal')).show();
        }
    });
}

// Tuer un processus
function killProcess(processId) {
    if (confirm(`Êtes-vous sûr de vouloir terminer le processus ${processId} ?`)) {
        callAPI('kill_process', {
            process_id: processId
        }, function(response) {
            if (response.success) {
                showAlert(response.message, 'success');
                loadConnections();
            } else {
                showAlert(response.error, 'danger');
            }
        });
    }
}

// Fonctions utilitaires
function callAPI(action, data, callback) {
    const formData = new FormData();
    formData.append('action', action);
    
    Object.keys(data).forEach(key => {
        if (Array.isArray(data[key])) {
            formData.append(key, JSON.stringify(data[key]));
        } else {
            formData.append(key, data[key]);
        }
    });
    
    fetch(API_URL, {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(callback)
    .catch(error => {
        console.error('Erreur API:', error);
        showAlert('Erreur de communication avec le serveur', 'danger');
    });
}

function showAlert(message, type) {
    const alertDiv = document.createElement('div');
    alertDiv.className = `alert alert-${type} alert-dismissible fade show`;
    alertDiv.innerHTML = `
        ${escapeHtml(message)}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    `;
    
    const container = document.querySelector('.main-content');
    container.insertBefore(alertDiv, container.firstChild);
    
    setTimeout(() => {
        alertDiv.remove();
    }, 5000);
}

function escapeHtml(text) {
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

function formatNumber(num) {
    return new Intl.NumberFormat('fr-FR').format(num);
}

function generatePassword() {
    const chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789!@#$%^&*';
    let password = '';
    for (let i = 0; i < 16; i++) {
        password += chars.charAt(Math.floor(Math.random() * chars.length));
    }
    document.getElementById('userPassword').value = password;
}

function addTableColumn() {
    const container = document.getElementById('tableColumns');
    const newRow = document.createElement('div');
    newRow.className = 'row mb-2 column-row';
    newRow.innerHTML = `
        <div class="col-md-3">
            <input type="text" class="form-control form-control-sm" placeholder="Nom" required>
        </div>
        <div class="col-md-2">
            <select class="form-select form-select-sm" required>
                <option value="INT">INT</option>
                <option value="VARCHAR">VARCHAR</option>
                <option value="TEXT">TEXT</option>
                <option value="DATE">DATE</option>
                <option value="DATETIME">DATETIME</option>
                <option value="DECIMAL">DECIMAL</option>
            </select>
        </div>
        <div class="col-md-2">
            <input type="number" class="form-control form-control-sm" placeholder="Longueur">
        </div>
        <div class="col-md-2">
            <div class="form-check">
                <input class="form-check-input" type="checkbox" value="null">
                <label class="form-check-label">NULL</label>
            </div>
        </div>
        <div class="col-md-2">
            <div class="form-check">
                <input class="form-check-input" type="checkbox" value="primary">
                <label class="form-check-label">PRIMARY</label>
            </div>
        </div>
        <div class="col-md-1">
            <button type="button" class="btn btn-sm btn-danger remove-column">
                <i class="fas fa-times"></i>
            </button>
        </div>
    `;
    container.appendChild(newRow);
}

function addIndexColumn() {
    const container = document.getElementById('indexColumns');
    const table = document.getElementById('indexTable').value;
    
    if (!table) {
        showAlert('Sélectionnez d\'abord une table', 'warning');
        return;
    }
    
    // Charger les colonnes de la table
    callAPI('get_table_info', {
        database: currentDatabase,
        table: table
    }, function(response) {
        if (response.success) {
            const select = document.createElement('select');
            select.className = 'form-select mb-2';
            select.required = true;
            select.innerHTML = '<option value="">Sélectionner une colonne</option>';
            
            response.data.columns.forEach(col => {
                const option = document.createElement('option');
                option.value = col.Field;
                option.textContent = col.Field;
                select.appendChild(option);
            });
            
            container.appendChild(select);
        }
    });
}

function loadDatabasesForPrivileges() {
    callAPI('list_databases', {}, function(response) {
        if (response.success) {
            const select = document.getElementById('privilegeModalDatabase');
            select.innerHTML = '<option value="">Sélectionner une base</option>';
            response.data.forEach(db => {
                const option = document.createElement('option');
                option.value = db;
                option.textContent = db;
                select.appendChild(option);
            });
        }
    });
}

function loadTablesForExport() {
    callAPI('list_tables', { database: currentDatabase }, function(response) {
        if (response.success) {
            const select = document.getElementById('exportTable');
            select.innerHTML = '<option value="">Sélectionner une table</option>';
            response.data.forEach(table => {
                const option = document.createElement('option');
                option.value = table;
                option.textContent = table;
                select.appendChild(option);
            });
        }
    });
}

