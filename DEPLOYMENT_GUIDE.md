# 🚀 Guide Pas à Pas de Déploiement des SDKs GoPAY

Ce document détaille la procédure étape par étape pour publier et déployer chacun des 4 SDKs officiels de la suite GoPAY sur leurs registres respectifs.

---

## 📑 Sommaire
1. [🟡 JavaScript / TypeScript SDK (npm)](#-1-javascript--typescript-sdk--npm-gopay-js)
2. [🐘 PHP SDK (Packagist / Composer)](#-2-php-sdk--packagist--composer-mecxer713gopay-php)
3. [🤖 Android SDK (JitPack & GitHub Release)](#-3-android-sdk--jitpack--github-release-aar)
4. [💙 Flutter & Dart SDK (pub.dev)](#-4-flutter--dart-sdk--pubdev-gopay)
5. [📋 Mémo des Tags Git](#-5-mémo-des-tags-git)

---

## 🟡 1. JavaScript / TypeScript SDK → **npm** (`gopay-js`)

La publication peut être soit **100% automatisée via GitHub Actions**, soit exécutée **manuellement depuis votre terminal**.

### Option A : Déploiement Automatisé via GitHub (Recommandé)

1. **Générer un token npm :**
   - Connectez-vous sur [npmjs.com](https://www.npmjs.com/).
   - Cliquez sur votre avatar > **Access Tokens**.
   - Cliquez sur **Generate New Token** > Choisissez **Automation** (ou un *Granular Access Token* avec permission *Read and Write* sur le package `gopay-js`).
2. **Configurer le secret GitHub :**
   - Sur votre dépôt GitHub, allez dans **Settings** > **Secrets and variables** > **Actions**.
   - Cliquez sur **New repository secret**.
   - Nom : `NPM_TOKEN`
   - Valeur : collez votre token npm.
3. **Déclencher la publication :**
   - Assurez-vous que la version dans `javascript-sdk/package.json` et `javascript-sdk/CHANGELOG.md` correspond.
   - Poussez le tag Git :
     ```bash
     git tag js-v0.2.0
     git push origin js-v0.2.0
     ```
   - Le workflow `.github/workflows/javascript-sdk.yml` compile le dual-package (ESM + CJS), exécute les tests et publie sur npm avec attestation de provenance SLSA (`--provenance`).

### Option B : Déploiement Manuel en ligne de commande

```bash
cd javascript-sdk

# 1. Vérifier les types et lancer les tests
npm test
npm run test:package

# 2. Vérifier les fichiers embarqués dans le package
npm pack --dry-run

# 3. Connexion à votre compte npm
npm login

# 4. Publication publique
npm publish --access public
```

---

## 🐘 2. PHP SDK → **Packagist / Composer** (`mecxer713/gopay-php`)

Packagist ne stocke pas directement le code : il lit automatiquement les tags Git de votre dépôt GitHub.

### Étape 1 : Première déclaration sur Packagist (à faire une seule fois)

1. Connectez-vous sur [packagist.org](https://packagist.org/) avec votre compte GitHub.
2. Cliquez sur le bouton vert **Submit**.
3. Renseignez l'URL de votre dépôt : `https://github.com/Mecxer713/gopay-package`.
4. Packagist détecte le `composer.json` et enregistre le package `mecxer713/gopay-php`.

### Étape 2 : Activer la synchronisation automatique (Webhook)

1. Sur Packagist, accédez à votre profil > cliquez sur **Show API Token**.
2. Sur GitHub, ouvrez votre dépôt > **Settings** > **Webhooks** > **Add webhook** :
   - **Payload URL** : `https://packagist.org/api/github?username=Mecxer713`
   - **Content type** : `application/json`
   - **Secret** : *(Votre token API Packagist)*
   - **Which events** : Choisissez *"Just the push event"*.
3. *(Optionnel pour la CI)* : Vous pouvez ajouter un secret GitHub `PACKAGIST_TOKEN` pour que le workflow `.github/workflows/php-sdk.yml` appelle l'API de mise à jour lors de chaque tag.

### Étape 3 : Déployer une nouvelle version

```bash
cd php-sdk

# 1. Valider localement les tests et l'analyse statique PHPStan (Level 8)
composer test
composer analyse

# 2. Créer et pousser le tag Git
git tag php-v1.6.0
git push origin php-v1.6.0
```

> Packagist synchronise immédiatement la version `1.6.0`. Vos utilisateurs peuvent l'installer via :
> ```bash
> composer require mecxer713/gopay-php
> ```

---

## 🤖 3. Android SDK → **JitPack** & **GitHub Release (AAR)**

JitPack construit la bibliothèque Android directement à la demande depuis GitHub. De plus, votre workflow génère automatiquement l'archive `.aar` autonome dans les Releases GitHub.

### Déploiement d'une nouvelle version :

1. **Vérifier la version :**
   - `android-sdk/gradle.properties` : `gopaySdkVersion=0.2.0`
   - `android-sdk/CHANGELOG.md` contient la section `## 0.2.0`.

2. **Créer et pousser le tag Git :**
   ```bash
   git tag android-v0.2.0
   git push origin android-v0.2.0
   ```

3. **Ce qui se passe automatiquement :**
   - Le workflow `.github/workflows/android-sdk.yml` se lance sur GitHub Actions.
   - Les tests unitaires sont exécutés (`:gopay:testReleaseUnitTest`).
   - Les artefacts sont exportés (`gopay-android-0.2.0.aar` et `-sources.jar`).
   - Une **GitHub Release** est créée automatiquement avec ces fichiers attachés.
   - Sur [jitpack.io](https://jitpack.io/#Mecxer713/gopay-package), le build est mis à disposition des développeurs Android.

4. **Intégration par vos utilisateurs :**
   ```kotlin
   // settings.gradle.kts
   dependencyResolutionManagement {
       repositories {
           mavenCentral()
           maven { url = uri("https://jitpack.io") }
       }
   }

   // app/build.gradle.kts
   dependencies {
       implementation("com.github.Mecxer713.gopay-package:gopay-android:0.2.0")
   }
   ```

---

## 💙 4. Flutter & Dart SDK → **pub.dev** (`gopay`)

Pour des raisons de sécurité imposées par Google et Dart, la publication finale sur **pub.dev** s'effectue via votre compte Google avec authentification OAuth2.

### Étape 1 : Vérifications locales préalables

Ouvrez un terminal avec l'environnement Flutter/Dart disponible :

```bash
cd flutter-sdk

# 1. Récupérer les dépendances
flutter pub get

# 2. Vérifier l'analyse statique du code
flutter analyze

# 3. Exécuter les tests unitaires
flutter test

# 4. Simulation de publication (dry-run)
dart pub publish --dry-run
```
> Assurez-vous que le résultat du *dry-run* affiche `Package has 0 warnings.`

### Étape 2 : Publier officiellement sur pub.dev

```bash
dart pub publish
```

1. Le terminal demande confirmation : `Do you want to publish gopay 0.2.0 (y/N)?` -> Entrez **y**.
2. Un lien d'authentification Google s'affiche dans votre console.
3. Ouvrez ce lien dans votre navigateur web et connectez-vous avec le compte Google propriétaire de votre éditeur pub.dev.
4. Une fois validé, le terminal affiche : `Successfully uploaded package.`

### Étape 3 : Tagger la version dans Git

```bash
git tag flutter-v0.2.0
git push origin flutter-v0.2.0
```

> Vos utilisateurs peuvent désormais l'ajouter directement via :
> ```bash
> flutter pub add gopay
> ```

---

## 📋 5. Mémo des Tags Git

Chaque SDK possède son propre préfixe de tag indépendant. Voici les commandes rapides de déclenchement :

| Package | Tag Git à pousser | Effet immédiat |
|---|---|---|
| **JavaScript** | `git tag js-v0.2.0 && git push origin js-v0.2.0` | CI GitHub : Build ESM/CJS + publication npm avec provenance |
| **PHP** | `git tag php-v1.6.0 && git push origin php-v1.6.0` | Packagist : Indexation de la version Composer |
| **Android** | `git tag android-v0.2.0 && git push origin android-v0.2.0` | CI GitHub : Release GitHub (AAR) + disponibilité JitPack |
| **Flutter** | `dart pub publish` puis `git tag flutter-v0.2.0 && git push origin flutter-v0.2.0` | Publication sur pub.dev + tag d'archive Git |
