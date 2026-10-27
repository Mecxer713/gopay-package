# GoPAY JavaScript & TypeScript SDK 🟡

[![npm version](https://img.shields.io/npm/v/gopay-js.svg)](https://www.npmjs.com/package/gopay-js)
[![License: MIT](https://img.shields.io/badge/License-MIT-yellow.svg)](LICENSE)
[![Node.js](https://img.shields.io/badge/Node.js-%3E%3D18.0.0-green.svg)](https://nodejs.org/)

SDK officiel JavaScript et TypeScript universel (Node.js, Express, NestJS, Next.js, Fastify et navigateurs) pour intégrer la passerelle de paiement et payout **GoPAY Mobile Money**.

0 dépendance externe à l'exécution (utilise l'API `fetch` et `crypto` natives).

---

## 📦 Installation

```bash
# Avec npm
npm install gopay-js

# Avec yarn
yarn add gopay-js

# Avec pnpm
pnpm add gopay-js
```

---

## ⚡ Démarrage Rapide

### 1. Initialiser le client GoPAY

```typescript
import { GoPayClient } from 'gopay-js';

const gopay = new GoPayClient({
  baseUrl: 'https://gopay.gooomart.com', // Optionnel, URL par défaut
  paymentApiKey: process.env.GOPAY_PAYMENT_API_KEY!,
  paymentSecretKey: process.env.GOPAY_PAYMENT_SECRET_KEY!,
  payoutApiKey: process.env.GOPAY_PAYOUT_API_KEY, // Optionnel si aucun payout
});
```

### 2. Initier un paiement Mobile Money

```typescript
const payment = await gopay.initPayment(
  4000,              // Montant
  'CDF',             // Devise : 'CDF' ou 'USD'
  '+243825000000',   // Numéro de téléphone client
  'COMMANDE-1001'    // Référence unique de votre commande
);

if (payment.success) {
  console.log(`Paiement initié avec succès: ${payment.transId}`);
} else {
  console.error(`Échec: ${payment.message} (${payment.errorCode})`);
}
```

### 3. Vérifier le statut d'un paiement

```typescript
const check = await gopay.checkPayment('COMMANDE-1001');

console.log(`Statut: ${check.transactionStatus}`); // 'success', 'pending', etc.
```

### 4. Payout (Transferts sortants)

```typescript
// Consulter le solde Payout
const balance = await gopay.getPayoutBalance();
console.log(`Solde: ${balance.balance} ${balance.currency}`);

// Émettre un transfert
const transfer = await gopay.sendPayoutTransfer(
  1500,
  'CDF',
  ['+243825000000'],
  ['PO-001']
);
```

---

## 🧪 Tests Unitaires

```bash
npm test
```

---

## 📄 Licence

Ce package est distribué sous licence [MIT](LICENSE).
