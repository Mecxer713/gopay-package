# GoPAY Flutter & Dart SDK 💙

[![pub package](https://img.shields.io/pub/v/gopay.svg)](https://pub.dev/packages/gopay)
[![pub points](https://img.shields.io/pub/points/gopay?color=2E8B57)](https://pub.dev/packages/gopay/score)
[![License: MIT](https://img.shields.io/badge/License-MIT-yellow.svg)](LICENSE)

SDK officiel Dart & Flutter pour l'intégration de la passerelle de paiement **GoPAY Mobile Money** (Paiements et Payouts en République Démocratique du Congo - RDC).

Acceptation des paiements mobiles : **M-Pesa (Vodacom)**, **Airtel Money**, **Orange Money** et **Afrimoney** en devises Franc Congolais (**CDF**) et Dollar Américain (**USD**).

Compatible sur toutes les plateformes : **Android**, **iOS**, **Web**, **Windows**, **macOS** et **Linux** (prêt pour WASM).

---

## 📦 Installation

Dans le dossier de votre projet Flutter :

```bash
flutter pub add gopay
```

Ou ajoutez manuellement dans votre `pubspec.yaml` :

```yaml
dependencies:
  gopay: ^0.2.1
```

---

## ⚡ Démarrage Rapide

### 1. Initialiser le client GoPAY

```dart
import 'package:gopay/gopay.dart';

final gopay = GoPayClient(const GoPayConfig(
  baseUrl: 'https://gopay.gooomart.com', // Optionnel (HTTPS obligatoire)
  paymentApiKey: 'VOTRE_PAYMENT_API_KEY',
  paymentSecretKey: 'VOTRE_PAYMENT_SECRET_KEY',
  payoutApiKey: 'VOTRE_PAYOUT_API_KEY', // Optionnel si aucun transfert sortant
));
```

### 2. Initier une demande de paiement (Mobile Money)

```dart
final payment = await gopay.initPayment(
  4000.0,
  'CDF',          // 'CDF' ou 'USD'
  '+243825000000',
  'REF-CMD-1001',
);

if (payment.success) {
  print('Transaction initiée avec succès: ${payment.transId}');
} else {
  print('Échec: ${payment.message} (Code: ${payment.errorCode})');
}
```

### 3. Vérifier l'état d'un paiement

```dart
final check = await gopay.checkPayment('REF-CMD-1001');
print('Statut de la transaction: ${check.transactionStatus}'); // "success", "pending", etc.
```

### 4. Payout (Transferts de fonds sortants)

> [!NOTE]
> Pour des raisons de sécurité, nous recommandons de traiter les payouts depuis un serveur backend sécurisé afin de ne pas exposer votre `payoutApiKey` dans une application mobile décompilable.

```dart
// 1. Consulter le solde du portefeuille Payout
final balance = await gopay.getPayoutBalance();
if (balance.success) {
  print('Solde disponible: ${balance.balance} ${balance.currency}');
}

// 2. Envoyer un transfert groupé
final transfer = await gopay.sendPayoutTransfer(
  1500.0,
  'CDF',
  ['+243825000000'],
  ['TRANSFERT-REF-001'],
);
```

---

## 🔒 Sécurité et Signature Cryptographique

- **Signature HMAC-SHA256** : Toutes les requêtes sont signées avec un horodatage (`x-timestamp`) et un jeton anti-rejeu (`x-nonce`).
- **Retry Automatique** : Les requêtes idempotentes `GET` sont automatiquement rejouées en cas d'erreurs réseau transitoires avec un backoff exponentiel et jitter. Les requêtes `POST` et `DELETE` ne sont jamais rejouées pour éviter les doubles débits.

---

## 🧪 Exemple Exécutable

Un exemple complet et prêt à l'emploi est disponible dans [`example/main.dart`](example/main.dart).

---

## 📄 Licence

Ce package est distribué sous licence [MIT](LICENSE).
Propulsé par [GoPAY Gateway](https://gopay.gooomart.com).
