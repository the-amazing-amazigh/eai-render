# Déploiement Render.com - Guide Étape par Étape

## 📁 Structure du dossier render-deploy/

```
render-deploy/
├── api/
│   ├── composer.json      ← Config PHP
│   └── index.php          ← API (version Render compatible)
├── frontend/
│   └── index.html         ← Application React (modifiée pour Render)
└── render.yaml            ← Configuration Render
```

## 🚀 ÉTAPES DE DÉPLOIEMENT

### ÉTAPE 1 : Créer compte Render (2 min)
1. Aller sur https://render.com
2. Cliquer "Get Started for Free"
3. Sign up avec GitHub
4. Confirmer email

### ÉTAPE 2 : Préparer les fichiers (5 min)

Les fichiers sont déjà créés dans `C:\CarrierEAI\render-deploy\`

**Vérifier que vous avez :**
- ✅ `api/composer.json`
- ✅ `api/index.php`
- ✅ `render.yaml`
- ✅ `frontend/index.html` (à copier depuis explorer/)

### ÉTAPE 3 : Créer la base de données (3 min)

1. Dashboard Render → **New** → **PostgreSQL**
2. Name: `eai-postgres`
3. Plan: **Free**
4. Region: **Frankfurt (EU)**
5. Create Database

**Attendre 2-3 minutes** que la DB soit prête.

### ÉTAPE 4 : Migrer les données MySQL → PostgreSQL

Option 1 : Script de migration (si vous avez beaucoup de données)
Option 2 : Recréer les tables vides sur PostgreSQL

**Pour recréer les tables :**
```sql
-- Connect to Render PostgreSQL and run:
CREATE TABLE cwReturnVehiclePositions (
    id SERIAL PRIMARY KEY,
    CWVehicleID VARCHAR(50),
    MeasuredTime TIMESTAMP,
    Latitude INTEGER,
    Longitude INTEGER,
    CurrentSpeed INTEGER,
    -- ... autres colonnes
);

-- Répéter pour autres tables
```

### ÉTAPE 5 : Git & Push (5 min)

```powershell
cd C:\CarrierEAI\render-deploy

git init
git add .
git commit -m "Initial Render deployment"

# Créer repo sur GitHub d'abord, puis :
git remote add origin https://github.com/VOTRE_USERNAME/eai-render.git
git push -u origin main
```

### ÉTAPE 6 : Déployer sur Render (10 min)

1. Render Dashboard → **New** → **Blueprint**
2. Connecter votre repo GitHub `eai-render`
3. Cliquer **Apply Blueprint**
4. Render va créer automatiquement :
   - Web Service `eai-api`
   - Static Site `eai-frontend`

**Configurer les variables d'environnement :**
1. Aller sur le service `eai-api`
2. Environment → Add Environment Variable
3. Ajouter :
   - `DB_HOST` = host de votre PostgreSQL (copier depuis la page de la DB)
   - `DB_PORT` = 5432
   - `DB_USER` = user de la DB
   - `DB_PASSWORD` = password de la DB
   - `DB_NAME` = nom de la DB

### ÉTAPE 7 : Tester le déploiement

**URLs après déploiement :**
- API: `https://eai-api.onrender.com/?action=stats`
- Frontend: `https://eai-frontend.onrender.com`

**Tester :**
1. Ouvrir `https://eai-api.onrender.com/?action=stats` dans navigateur
2. Vérifier que JSON s'affiche
3. Ouvrir le frontend et tester le tracking

---

## 🔧 Dépannage

| Problème | Solution |
|----------|----------|
| "Build failed" | Vérifier composer.json est bien présent |
| "DB connection failed" | Vérifier les variables d'environnement DB_* |
| "CORS error" | Vérifier header Access-Control-Allow-Origin dans index.php |
| Service "sleeping" | Normal sur plan gratuit, réveil en 30s |
| "Table not found" | Migrer les tables MySQL vers PostgreSQL |

---

## 📊 Coûts

| Composant | Plan | Coût |
|-----------|------|------|
| Web Service | Free | $0 |
| PostgreSQL | Free | $0 |
| Static Site | Free | $0 |
| **TOTAL** | | **$0** |

**Après 1 an :** Upgrade nécessaire ou migration vers autre hébergeur

---

## ✅ Checklist avant déploiement

- [ ] Compte Render.com créé
- [ ] Repo GitHub créé et pushé
- [ ] PostgreSQL créée sur Render
- [ ] Variables d'environnement DB configurées
- [ ] Tables migrées (ou vides prêtes)
- [ ] Blueprint appliqué
- [ ] Services déployés avec succès
- [ ] Test API réussi
- [ ] Test Frontend réussi

---

## 🎉 Une fois déployé

Votre application EAI sera accessible partout dans le monde via :
- **Frontend** : `https://eai-frontend.onrender.com`
- **API** : `https://eai-api.onrender.com`

Les données se synchroniseront automatiquement avec votre base PostgreSQL cloud.
