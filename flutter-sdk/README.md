# GoPAY Flutter & Dart SDK 💙

[![pub package](https://img.shields.io/pub/v/gopay.svg)](https://pub.dev/packages/gopay)
[![License: MIT](https://img.shields.io/badge/License-MIT-yellow.svg)](LICENSE)

SDK officiel Dart & Flutter pour l'intégration de la passerelle **GoPAY Mobile Money** (Paiements et Payouts).

Multiplateforme : Android, iOS, Web, Windows, macOS, Linux.

---

## 📦 Installation

### Option 1 : Via pub.dev (Recommandé)

```bash
flutter pub add gopay
```
Ou ajoutez dans votre `pubspec.yaml` :
```yaml
dependencies:
  gopay: ^0.1.0
```

### Option 2 : Via Git directement

```yaml
dependencies:
  gopay:
    git:
      url: https://github.com/Mecxer713/gopay-flutter.git
      ref: v0.1.0
```

---

## ⚡ Démarrage Rapide

### 1. Initialiser le client

```dart
import 'package:gopay/gopay.dart';

final gopay = GoPayClient(const GoPayConfig(
  baseUrl: 'https://gopay.gooomart.com', // Optionnel
  paymentApiKey: 'VOTRE_PAYMENT_API_KEY',
  paymentSecretKey: 'VOTRE_PAYMENT_SECRET_KEY',
  payoutApiKey: 'VOTRE_PAYOUT_API_KEY', // Optionnel si aucun transfert sortant
));
```

### 2. Initier une demande de paiement (Mobile Money)

```dart
final payment = await gopay.initPayment(
  4000.0,
  'CDF',          // "CDF" ou "USD"
  '+243825000000',
  'CMD-1001',
);

if (payment.success) {
  print('Transaction initiée: ${payment.transId}');
} else {
  print('Échec: ${payment.message} (${payment.errorCode})');
}
```

### 3. Vérifier le statut d'un paiement

```dart
final check = await gopay.checkPayment('CMD-1001');
print('Statut: ${check.transactionStatus}'); // "success", "pending", etc.
```

### 4. Payout (Transferts de masse)

```dart
// Consulter le solde Payout
final balance = await gopay.getPayoutBalance();
if (balance.success) {
  print('Solde: ${balance.balance} ${balance.currency}');
}

// Envoyer un transfert
final transfer = await gopay.sendPayoutTransfer(
  1500.0,
  'CDF',
  ['+243825000000'],
  ['PO-001'],
);
```

---

## 🧪 Exemple Exécutable

Un exemple complet est disponible dans [`example/main.dart`](example/main.dart).

---

## 📄 Licence

Ce package est distribué sous licence [MIT](LICENSE).
