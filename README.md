<div align="center">

# GoPAY Multi-Platform SDK Suite

**Suite officielle des SDKs GoPAY — Passerelle de Paiement et Payout Mobile Money**

[![License: MIT](https://img.shields.io/badge/License-MIT-yellow.svg)](LICENSE)
[![GoPAY Gateway](https://img.shields.io/badge/Gateway-GoPAY-blue.svg)](https://gopay.gooomart.com)

Ce dépôt rassemble les SDKs officiels et autonomes pour intégrer facilement l'API GoPAY dans vos applications : **Android (Kotlin)**, **Flutter (Dart)**, **JavaScript / TypeScript (Node.js & Navigateur)** et **PHP (Laravel & Symfony)**.

</div>

---

## 📦 Organisation des Packages

Chaque SDK est rangé dans son propre sous-dossier, entièrement autonome avec ses tests, exemples et configurations de publication :

| Package | Dossier | Plateformes & Frameworks | Gestionnaire de Paquets | Statut |
| 🤖 **Android SDK** | [`android-sdk/`](android-sdk/) | Kotlin, Android (API 23+) | **JitPack** / **AAR** (`com.github.Mecxer713.gopay-package:gopay-android:0.1.0`) | ✅ Prêt |
| 💙 **Flutter SDK** | [`flutter-sdk/`](flutter-sdk/) | Flutter & Dart (Cross-Platform) | **pub.dev** (`flutter pub add gopay`) & Git | ✅ Prêt |
| 🟡 **JavaScript SDK** | [`javascript-sdk/`](javascript-sdk/) | Node.js, TypeScript, Next.js, Web | **npm** (`npm install gopay-js`) | ✅ Prêt |
| 🐘 **PHP SDK** | [`php-sdk/`](php-sdk/) | PHP 8.1+, Laravel, Symfony, Natif | **Packagist** (`composer require mecxer713/gopay-php`) | ✅ Prêt |

---

## ⚡ Démarrages Rapides par Technologie

### 🤖 1. Android (Kotlin)
Dossier : [`android-sdk/`](android-sdk/)

```kotlin
// 1. Dépendance dans app/build.gradle.kts
dependencies {
    implementation("com.github.Mecxer713.gopay-package:gopay-android:0.1.0")
}

// 2. Initialisation avec vos clés marchandes
val gopay = GoPayClient(GoPayConfig(
    paymentApiKey = "VOTRE_PAYMENT_API_KEY",
    paymentSecretKey = "VOTRE_PAYMENT_SECRET_KEY",
    payoutApiKey = "VOTRE_PAYOUT_API_KEY" // Optionnel
))

// 3. Initier un paiement
val payment = withContext(Dispatchers.IO) {
    gopay.initPayment(4000.0, "CDF", "+243825000000", "REF-001")
}
```
*Une application démo complète est disponible dans [`android-sdk/sample/`](android-sdk/sample/).*

---

### 💙 2. Flutter & Dart
Dossier : [`flutter-sdk/`](flutter-sdk/)

```bash
flutter pub add gopay
```

```dart
import 'package:gopay/gopay.dart';

// 1. Initialisation avec vos clés marchandes
final gopay = GoPayClient(const GoPayConfig(
  paymentApiKey: 'VOTRE_PAYMENT_API_KEY',
  paymentSecretKey: 'VOTRE_PAYMENT_SECRET_KEY',
  payoutApiKey: 'VOTRE_PAYOUT_API_KEY',
));

// 2. Initier un paiement
final response = await gopay.initPayment(4000.0, 'CDF', '+243825000000', 'REF-001');
```
*Un exemple complet est disponible dans [`flutter-sdk/example/main.dart`](flutter-sdk/example/main.dart).*

---

### 🟡 3. JavaScript & TypeScript (Node.js & Web)
Dossier : [`javascript-sdk/`](javascript-sdk/)

```bash
npm install gopay-js
```

```typescript
import { GoPayClient } from 'gopay-js';

// 1. Initialisation avec vos clés marchandes
const gopay = new GoPayClient({
  paymentApiKey: process.env.GOPAY_PAYMENT_API_KEY!,
  paymentSecretKey: process.env.GOPAY_PAYMENT_SECRET_KEY!,
  payoutApiKey: process.env.GOPAY_PAYOUT_API_KEY,
});

// 2. Initier un paiement
const payment = await gopay.initPayment(4000, 'CDF', '+243825000000', 'REF-001');
```
*Un script exécutable est disponible dans [`javascript-sdk/example/demo.ts`](javascript-sdk/example/demo.ts).*

---

### 🐘 4. PHP (Natif, Laravel, Symfony)
Dossier : [`php-sdk/`](php-sdk/)

```bash
composer require mecxer713/gopay-php
```

```php
use Mecxer713\GoPay\GoPayService;

// 1. Initialisation avec vos clés marchandes
$gopay = new GoPayService(
    paymentApiKey:    'votre_api_key',
    paymentSecretKey: 'votre_secret_key',
    payoutApiKey:     'votre_payout_api_key'
);

// 2. Initier un paiement
$response = $gopay->initPayment(4000, 'CDF', '+243825000000', 'REF-001');
```
*Supporte également les Facades et ServiceProvider Laravel dans `php-sdk/src/Laravel/` et les bundles Symfony dans `php-sdk/src/Symfony/`.*

---

## 🔒 Sécurité et Bonnes Pratiques

- **Signature HMAC-SHA256** : Toutes les requêtes HTTP sont signées cryptographiquement en temps réel avec un horodatage (`x-timestamp`) et un jeton anti-rejeu (`x-nonce`).
- **Secret Marchand** : Ne stockez jamais vos clés secrètes en clair dans des dépôts publics. Utilisez des variables d'environnement (`.env`) ou des secrets sécurisés.
- **Environnements Mobiles** : Les transactions de débit direct (paiements clients) s'exécutent en toute sécurité via le SDK mobile. Pour les transferts sortants de fonds (**Payouts**), il est fortement recommandé d'utiliser un serveur backend (PHP / Node.js) afin de conserver la clé Payout hors des applications mobiles décompilables.

---

## 📄 Licence

L'ensemble des packages de cette suite est distribué sous licence [MIT](LICENSE).

<div align="center">
  Développé par <a href="https://github.com/Mecxer713">Mecxer713</a> · Propulsé par <a href="https://gopay.gooomart.com">GoPAY</a>
</div>
