<!-- Modal: Créer une base de données -->
<div class="modal fade" id="createDatabaseModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="fas fa-database"></i> Créer une base de données</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="createDatabaseForm">
                    <div class="mb-3">
                        <label class="form-label">Nom de la base de données *</label>
                        <input type="text" class="form-control" id="dbName" required pattern="[a-zA-Z0-9_]+" placeholder="nom_base_donnees">
                        <small class="form-text text-muted">Uniquement lettres, chiffres et underscores</small>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Encodage</label>
                        <select class="form-select" id="dbCharset">
                            <option value="utf8mb4" selected>utf8mb4 (Recommandé)</option>
                            <option value="utf8">utf8</option>
                            <option value="latin1">latin1</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Collation</label>
                        <select class="form-select" id="dbCollation">
                            <option value="utf8mb4_unicode_ci" selected>utf8mb4_unicode_ci</option>
                            <option value="utf8mb4_general_ci">utf8mb4_general_ci</option>
                            <option value="utf8_unicode_ci">utf8_unicode_ci</option>
                        </select>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                <button type="button" class="btn btn-primary" id="submitCreateDatabase">
                    <i class="fas fa-check"></i> Créer
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Modal: Créer une table -->
<div class="modal fade" id="createTableModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="fas fa-table"></i> Créer une table</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="createTableForm">
                    <div class="mb-3">
                        <label class="form-label">Nom de la table *</label>
                        <input type="text" class="form-control" id="tableName" required pattern="[a-zA-Z0-9_]+" placeholder="nom_table">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Colonnes</label>
                        <div id="tableColumns">
                            <div class="row mb-2 column-row">
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
                            </div>
                        </div>
                        <button type="button" class="btn btn-sm btn-secondary" id="addColumn">
                            <i class="fas fa-plus"></i> Ajouter une colonne
                        </button>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                <button type="button" class="btn btn-primary" id="submitCreateTable">
                    <i class="fas fa-check"></i> Créer
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Modal: Créer un utilisateur -->
<div class="modal fade" id="createUserModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="fas fa-user-plus"></i> Créer un utilisateur MySQL</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="createUserForm">
                    <div class="mb-3">
                        <label class="form-label">Nom d'utilisateur *</label>
                        <input type="text" class="form-control" id="userUsername" required pattern="[a-zA-Z0-9_]+" placeholder="nom_utilisateur">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Host *</label>
                        <input type="text" class="form-control" id="userHost" value="%" required placeholder="% ou localhost">
                        <small class="form-text text-muted">% = tous les hosts, localhost = local uniquement</small>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Mot de passe *</label>
                        <input type="password" class="form-control" id="userPassword" required>
                        <button type="button" class="btn btn-sm btn-secondary mt-2" id="generatePassword">
                            <i class="fas fa-key"></i> Générer un mot de passe
                        </button>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                <button type="button" class="btn btn-primary" id="submitCreateUser">
                    <i class="fas fa-check"></i> Créer
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Modal: Gérer les privilèges -->
<div class="modal fade" id="privilegesModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="fas fa-key"></i> Gérer les privilèges</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <strong>Utilisateur:</strong> <span id="privilegeModalUser"></span>@<span id="privilegeModalHost"></span>
                </div>
                <div class="mb-3">
                    <label class="form-label">Base de données</label>
                    <select class="form-select" id="privilegeModalDatabase">
                        <option value="">Sélectionner une base</option>
                    </select>
                </div>
                <div class="mb-3">
                    <label class="form-label">Table (optionnel, * pour toutes)</label>
                    <input type="text" class="form-control" id="privilegeModalTable" value="*">
                </div>
                <div class="mb-3">
                    <label class="form-label">Privilèges</label>
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-check">
                                <input class="form-check-input privilege-check" type="checkbox" value="SELECT" id="privSELECT">
                                <label class="form-check-label" for="privSELECT">SELECT</label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input privilege-check" type="checkbox" value="INSERT" id="privINSERT">
                                <label class="form-check-label" for="privINSERT">INSERT</label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input privilege-check" type="checkbox" value="UPDATE" id="privUPDATE">
                                <label class="form-check-label" for="privUPDATE">UPDATE</label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input privilege-check" type="checkbox" value="DELETE" id="privDELETE">
                                <label class="form-check-label" for="privDELETE">DELETE</label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input privilege-check" type="checkbox" value="CREATE" id="privCREATE">
                                <label class="form-check-label" for="privCREATE">CREATE</label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input privilege-check" type="checkbox" value="DROP" id="privDROP">
                                <label class="form-check-label" for="privDROP">DROP</label>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-check">
                                <input class="form-check-input privilege-check" type="checkbox" value="ALTER" id="privALTER">
                                <label class="form-check-label" for="privALTER">ALTER</label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input privilege-check" type="checkbox" value="INDEX" id="privINDEX">
                                <label class="form-check-label" for="privINDEX">INDEX</label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input privilege-check" type="checkbox" value="ALL PRIVILEGES" id="privALL">
                                <label class="form-check-label" for="privALL"><strong>ALL PRIVILEGES</strong></label>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                <button type="button" class="btn btn-success" id="submitGrantPrivileges">
                    <i class="fas fa-check"></i> Accorder
                </button>
                <button type="button" class="btn btn-danger" id="submitRevokePrivileges">
                    <i class="fas fa-times"></i> Révoquer
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Modal: Créer un index -->
<div class="modal fade" id="createIndexModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="fas fa-list"></i> Créer un index</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="createIndexForm">
                    <div class="mb-3">
                        <label class="form-label">Nom de l'index *</label>
                        <input type="text" class="form-control" id="indexName" required pattern="[a-zA-Z0-9_]+" placeholder="idx_nom">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Type d'index</label>
                        <select class="form-select" id="indexType">
                            <option value="INDEX">INDEX (Standard)</option>
                            <option value="UNIQUE">UNIQUE</option>
                            <option value="FULLTEXT">FULLTEXT</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Colonnes *</label>
                        <div id="indexColumns">
                            <select class="form-select mb-2" required>
                                <option value="">Sélectionner une colonne</option>
                            </select>
                        </div>
                        <button type="button" class="btn btn-sm btn-secondary" id="addIndexColumn">
                            <i class="fas fa-plus"></i> Ajouter une colonne
                        </button>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                <button type="button" class="btn btn-primary" id="submitCreateIndex">
                    <i class="fas fa-check"></i> Créer
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Modal: Voir définition (Vue/Procédure/Trigger) -->
<div class="modal fade" id="definitionModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="definitionModalTitle"></h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <pre id="definitionContent" style="max-height: 500px; overflow-y: auto; background-color: var(--code-bg); color: var(--code-text); border: 1px solid var(--code-border); border-radius: 8px; padding: 1.5rem; font-family: 'Courier New', monospace; line-height: 1.6;"></pre>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Fermer</button>
            </div>
        </div>
    </div>
</div>

<!-- Modal: Confirmation de suppression -->
<div class="modal fade" id="confirmDeleteModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header" style="background-color: var(--color-danger); color: var(--text-inverse);">
                <h5 class="modal-title" style="color: var(--text-inverse);"><i class="fas fa-exclamation-triangle"></i> Confirmation</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" style="filter: brightness(0) invert(1);"></button>
            </div>
            <div class="modal-body">
                <p id="confirmDeleteMessage"></p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                <button type="button" class="btn btn-danger" id="confirmDeleteBtn">
                    <i class="fas fa-trash"></i> Supprimer
                </button>
            </div>
        </div>
    </div>
</div>

