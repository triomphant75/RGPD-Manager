# Suppression d'Utilisateur Conforme RGPD

## Vue d'ensemble

Ce document décrit l'implémentation de la suppression d'utilisateurs conforme au **RGPD Article 17 - Droit à l'effacement** dans l'application SaintAgnes 2.0.

## Conformité RGPD

### Article 17 - Droit à l'effacement ("droit à l'oubli")

L'implémentation respecte les exigences suivantes :

1. ✅ **Suppression effective** : Hard delete des données personnelles
2. ✅ **Traçabilité** : Logs d'audit minimaux pour prouver la conformité
3. ✅ **Délai de réponse** : Traitement immédiat de la demande
4. ✅ **Gestion des données liées** : Anonymisation des traitements RGPD
5. ✅ **Rétention limitée** : Purge automatique des logs après 5 ans

## Architecture

### Composants

```
┌─────────────────────────────────────────────────────────────┐
│              SUPPRESSION CONFORME RGPD                      │
└─────────────────────────────────────────────────────────────┘
                           │
           ┌───────────────┼───────────────┐
           │               │               │
    ┌──────▼──────┐ ┌──────▼──────┐ ┌─────▼──────┐
    │   User      │ │  Treatment  │ │Notification│
    │  Deletion   │ │Anonymization│ │  Cascade   │
    │   Service   │ │             │ │   Delete   │
    └──────┬──────┘ └─────────────┘ └────────────┘
           │
    ┌──────▼──────┐
    │  Deletion   │
    │   Audit     │
    │   Service   │
    └─────────────┘
```

### Fichiers créés/modifiés

#### Nouvelles Entités
- `src/Entity/DeletionAudit.php` - Log d'audit des suppressions
- `src/Repository/DeletionAuditRepository.php` - Repository pour les logs

#### Nouveaux Services
- `src/Service/UserDeletionService.php` - Service principal de suppression
- `src/Service/DeletionAuditService.php` - Gestion des logs d'audit

#### Contrôleur mis à jour
- `src/Controller/UserController.php` - Utilise les nouveaux services

#### Entités modifiées
- `src/Entity/User.php` - Ajout relation notifications avec cascade
- `src/Entity/Notification.php` - Cascade delete configuré
- `src/Entity/Treatment.php` - Support anonymisation

#### Commandes Console
- `src/Command/PurgeDeletionAuditLogsCommand.php` - Purge des logs expirés

#### Migration
- `migrations/Version20251102010457.php` - Modifications base de données

## Fonctionnement

### Processus de suppression

```
┌──────────────────────────────────────────────────────────┐
│  1. VÉRIFICATIONS PRÉALABLES                              │
│     - Empêcher auto-suppression                          │
│     - Vérifier dernier admin                             │
└────────────────┬─────────────────────────────────────────┘
                 │
┌────────────────▼─────────────────────────────────────────┐
│  2. CRÉATION DU LOG D'AUDIT                              │
│     - Hash SHA256 de l'email                             │
│     - Identifiant anonymisé (USER_12345)                 │
│     - Métadonnées (nombre traitements, notifications)    │
│     - IP et admin qui a supprimé                         │
│     - Rétention: 5 ans                                   │
└────────────────┬─────────────────────────────────────────┘
                 │
┌────────────────▼─────────────────────────────────────────┐
│  3. ANONYMISATION DES TRAITEMENTS                        │
│     - createdBy = NULL                                   │
│     - createdByAnonymized = "Utilisateur supprimé #123"  │
│     - Les traitements restent en base (obligation légale)│
└────────────────┬─────────────────────────────────────────┘
                 │
┌────────────────▼─────────────────────────────────────────┐
│  4. SUPPRESSION EN CASCADE                               │
│     - Notifications supprimées automatiquement           │
│     - (CASCADE DELETE configuré en base)                 │
└────────────────┬─────────────────────────────────────────┘
                 │
┌────────────────▼─────────────────────────────────────────┐
│  5. HARD DELETE DE L'UTILISATEUR                         │
│     - Suppression définitive de la table users           │
│     - Transaction pour garantir l'intégrité              │
└──────────────────────────────────────────────────────────┘
```

### Table deletion_audit

```sql
CREATE TABLE deletion_audit (
    id SERIAL PRIMARY KEY,
    email_hash VARCHAR(64) NOT NULL,              -- SHA256(email)
    user_id_anonymized VARCHAR(50) NOT NULL,      -- "USER_12345"
    deletion_date TIMESTAMP NOT NULL,
    deleted_by_admin_id INT,                      -- Référence admin
    deletion_reason TEXT,
    ip_address VARCHAR(45),
    retention_until TIMESTAMP,                    -- Date de purge (+5 ans)
    metadata JSON                                 -- Statistiques
);
```

## Utilisation

### API Endpoint

#### DELETE /api/users/{id}

**Headers:**
```
Authorization: Bearer {admin_token}
Content-Type: application/json
```

**Body (optionnel):**
```json
{
  "reason": "Demande de l'utilisateur (droit à l'effacement RGPD)"
}
```

**Réponse succès:**
```json
{
  "message": "Utilisateur supprimé avec succès (conforme RGPD)",
  "details": {
    "user_id": 123,
    "treatments_anonymized": 5,
    "notifications_deleted": 12,
    "deleted_at": "2025-11-02 10:30:00"
  }
}
```

#### GET /api/users/{id}/preview-deletion

Prévisualise l'impact de la suppression avant de l'exécuter.

**Réponse:**
```json
{
  "user_id": 123,
  "user_email": "user@example.com",
  "user_roles": ["ROLE_USER"],
  "treatments_count": 5,
  "notifications_count": 12,
  "created_at": "2024-01-15 08:00:00",
  "can_delete": true,
  "deletion_blocked_reason": null,
  "warning": "Les traitements seront anonymisés, les notifications seront supprimées"
}
```

### Commande Console

#### Purger les logs expirés

```bash
php bin/console app:purge-deletion-logs
```

**Configuration Cron recommandée (1er du mois à 3h):**
```cron
0 3 1 * * cd /var/www/saintagnes && php bin/console app:purge-deletion-logs
```

## Données Conservées vs Supprimées

### ✅ SUPPRIMÉ (Hard Delete)

| Donnée | Table | Action |
|--------|-------|--------|
| Email | users | Supprimé |
| Mot de passe | users | Supprimé |
| Rôles | users | Supprimé |
| Notifications | notifications | Cascade delete |
| Toutes données personnelles | users | Supprimé |

### ✅ ANONYMISÉ

| Donnée | Table | Action |
|--------|-------|--------|
| Créateur des traitements | treatments | `createdBy = NULL`, `createdByAnonymized = "USER_123"` |

### ✅ CONSERVÉ (Obligation légale)

| Donnée | Table | Raison | Durée |
|--------|-------|--------|-------|
| Traitements RGPD | treatments | Article 30 RGPD - Registre des traitements | Permanente |
| Logs d'audit suppression | deletion_audit | Preuve de conformité Article 17 | 5 ans |

## Log d'Audit - Données Stockées

Le log d'audit contient **uniquement** :

```json
{
  "email_hash": "a665a45920422f9d417e4867efdc4fb8a04a1f3fff1fa07e998e86f7f7a27ae3",
  "user_id_anonymized": "USER_123",
  "deletion_date": "2025-11-02 10:30:00",
  "deleted_by_admin": {
    "id": 1,
    "email": "admin@example.com"
  },
  "deletion_reason": "Demande utilisateur RGPD",
  "ip_address": "192.168.1.100",
  "retention_until": "2030-11-02 10:30:00",
  "metadata": {
    "user_id": 123,
    "user_email_domain": "example.com",
    "user_roles": ["ROLE_USER"],
    "treatments_count": 5,
    "notifications_count": 12,
    "deletion_timestamp": "2025-11-02 10:30:00"
  }
}
```

### Justification RGPD

- ✅ **Hash de l'email** : Empêche recréation du compte sans stocker l'email
- ✅ **Identifiant anonymisé** : Permet audit sans lien vers personne réelle
- ✅ **Métadonnées statistiques** : Preuve de conformité (nombre de données supprimées)
- ✅ **Rétention limitée** : 5 ans maximum (principe de minimisation)
- ✅ **Domaine email uniquement** : Pas d'email complet

## Sécurité

### Vérifications

1. **Auto-suppression** : Interdit (HTTP 403)
2. **Dernier admin** : Impossible de supprimer (HTTP 403)
3. **Autorisation** : Requiert `ROLE_ADMIN`
4. **Transaction** : Rollback automatique en cas d'erreur
5. **Logging** : Toutes les suppressions sont loguées

### Exemple de refus

```json
{
  "error": "Impossible de supprimer le dernier administrateur du système"
}
```

## Tests Recommandés

### Test 1 : Suppression utilisateur standard

```bash
# Créer un utilisateur test
curl -X POST http://localhost:8000/api/users \
  -H "Authorization: Bearer {admin_token}" \
  -H "Content-Type: application/json" \
  -d '{"email": "test@example.com", "password": "Test123!", "role": "user"}'

# Supprimer l'utilisateur
curl -X DELETE http://localhost:8000/api/users/123 \
  -H "Authorization: Bearer {admin_token}" \
  -H "Content-Type: application/json" \
  -d '{"reason": "Test suppression RGPD"}'

# Vérifier que l'email ne peut plus être réutilisé (hash existe)
```

### Test 2 : Vérification logs d'audit

```sql
-- Vérifier le log dans la base
SELECT * FROM deletion_audit ORDER BY deletion_date DESC LIMIT 1;

-- Vérifier que le hash correspond
SELECT encode(digest('test@example.com', 'sha256'), 'hex');
```

### Test 3 : Anonymisation des traitements

```sql
-- Vérifier que les traitements sont anonymisés
SELECT
  id,
  created_by_id,
  created_by_anonymized
FROM treatments
WHERE created_by_anonymized LIKE 'Utilisateur supprimé%';
```

### Test 4 : Cascade delete des notifications

```sql
-- Les notifications doivent être supprimées
SELECT COUNT(*) FROM notifications WHERE user_id = 123;
-- Résultat attendu : 0
```

## Obligations RGPD Respectées

### ✅ Article 17 - Droit à l'effacement

- [x] Suppression effective des données personnelles
- [x] Suppression dans un délai raisonnable (immédiat)
- [x] Suppression complète (hard delete)
- [x] Conservation des données nécessaires (obligation légale)

### ✅ Article 5 - Principes

- [x] Minimisation des données (logs minimaux)
- [x] Limitation de la conservation (purge après 5 ans)
- [x] Intégrité et confidentialité (transaction sécurisée)

### ✅ Article 30 - Registre des traitements

- [x] Conservation des traitements RGPD (obligation légale)
- [x] Anonymisation du créateur

### ✅ Responsabilité (Accountability)

- [x] Documentation complète
- [x] Logs d'audit pour prouver la conformité
- [x] Processus traçable et auditable

## Maintenance

### Purge automatique des logs

**Recommandation** : Configurer un cron job mensuel

```bash
# Crontab
0 3 1 * * cd /var/www/saintagnes/backEnd && php bin/console app:purge-deletion-logs >> /var/log/saintagnes/purge-logs.log 2>&1
```

### Monitoring

**Surveiller :**
- Nombre de suppressions par mois
- Taille de la table `deletion_audit`
- Succès/échecs des suppressions

**Requête statistiques :**
```sql
SELECT
  DATE_TRUNC('month', deletion_date) as mois,
  COUNT(*) as nombre_suppressions
FROM deletion_audit
GROUP BY DATE_TRUNC('month', deletion_date)
ORDER BY mois DESC;
```

## Support et Questions

Pour toute question concernant la conformité RGPD de ce système :

- **Documentation RGPD** : [backEnd/docs/](.)
- **CNIL** : https://www.cnil.fr/
- **EDPB Guidelines** : https://edpb.europa.eu/

---

**Version** : 1.0
**Date** : 2025-11-02
**Conformité** : RGPD Article 17
**Rétention des logs** : 5 ans
