<div align="center">

# GoPay PHP SDK

**SDK PHP officiel pour l'API GoPAY — Paiement & Payout Mobile Money**

[![Latest Version on Packagist](https://img.shields.io/packagist/v/mecxer713/gopay-php.svg?style=flat-square)](https://packagist.org/packages/mecxer713/gopay-php)
[![PHP Version](https://img.shields.io/badge/PHP-%5E8.1-blue?style=flat-square&logo=php)](https://www.php.net)
[![Laravel](https://img.shields.io/badge/Laravel-9%2B%20%7C%2010%2B%20%7C%2011%2B-orange?style=flat-square&logo=laravel)](https://laravel.com)
[![Tests](https://img.shields.io/badge/tests-passing-brightgreen?style=flat-square)](https://github.com/Mecxer713/gopay-php)
[![License: MIT](https://img.shields.io/badge/License-MIT-yellow.svg?style=flat-square)](LICENSE)

Intégrez l'API GoPAY en quelques minutes dans vos projets **PHP natif**, **Laravel** ou **Symfony**.

[Installation](#-installation) · [Démarrage rapide](#-démarrage-rapide) · [Laravel](#-laravel) · [Symfony](#-symfony) · [PHP natif](#-php-natif) · [Gestion des erreurs](#-gestion-des-erreurs) · [Référence API](#-référence-api)

</div>

---

## ✅ Prérequis

| Dépendance | Version minimale |
|---|---|
| PHP | `^8.1` |
| Guzzle HTTP | `^7.2` |
| Laravel *(optionnel)* | `^9.0 \| ^10.0 \| ^11.0` |
| Symfony *(optionnel)* | `^5.4 \| ^6.0 \| ^7.0` |

---

## 📦 Installation

```bash
composer require mecxer713/gopay-php
```

---

## ⚡ Démarrage rapide

```php
use Mecxer713\GoPay\GoPayService;

$gopay = new GoPayService(
    baseUrl:          'https://gopay.gooomart.com',
    paymentApiKey:    'votre_api_key',
    paymentSecretKey: 'votre_secret_key',
    payoutApiKey:     'votre_payout_api_key',
);

// Initier un paiement
$response = $gopay->initPayment(4000, 'CDF', '+24399000000', 'REF-001');

if ($response->isSuccessful()) {
    echo $response->transId;           // TRANS-001.94786.53389
    echo $response->transactionStatus; // "success"
    echo $response->amount;            // "4000"
    echo $response->currency;          // "CDF"
}
```

---

## 🔷 Laravel

### 1. Configuration

Le package supporte **Laravel Package Auto-Discovery**. Aucune déclaration manuelle n'est nécessaire.

Publiez le fichier de configuration :

```bash
php artisan vendor:publish --provider="Mecxer713\GoPay\GoPayServiceProvider" --tag="gopay-config"
```

Ajoutez vos clés dans le fichier `.env` :

```env
GOPAY_BASE_URL=https://gopay.gooomart.com
GOPAY_API_KEY=votre_cle_api
GOPAY_SECRET_KEY=votre_cle_secrete
GOPAY_PAYOUT_API_KEY=votre_cle_api_payout
```

### 2. Via la Facade

```php
use Mecxer713\GoPay\Facades\GoPay;

// Initier un paiement
$response = GoPay::initPayment(4000, 'CDF', '+24399000000', 'REF-001');

if ($response->isSuccessful()) {
    $transId = $response->transId;
}

// Vérifier le statut d'un paiement
$check = GoPay::checkPayment('REF-001');
echo $check->transactionStatus; // "success", "pending", ...
```

### 3. Via l'injection de dépendances

```php
use Mecxer713\GoPay\GoPayServiceInterface;

class PaymentController extends Controller
{
    public function __construct(private GoPayServiceInterface $gopay) {}

    public function pay(): JsonResponse
    {
        $response = $this->gopay->initPayment(4000, 'CDF', '+24399000000', 'REF-001');

        return response()->json([
            'success' => $response->isSuccessful(),
            'trans_id' => $response->transId,
        ]);
    }
}
```

---

## 🟣 Symfony

### 1. Activer le Bundle

Dans `config/bundles.php` (si Flex ne l'a pas fait automatiquement) :

```php
return [
    // ...
    Mecxer713\GoPay\Symfony\GoPayBundle::class => ['all' => true],
];
```

### 2. Créer la configuration

`config/packages/go_pay.yaml` :

```yaml
go_pay:
    base_url:       '%env(GOPAY_BASE_URL)%'
    api_key:        '%env(GOPAY_API_KEY)%'
    secret_key:     '%env(GOPAY_SECRET_KEY)%'
    payout_api_key: '%env(GOPAY_PAYOUT_API_KEY)%'
```

`.env` :

```env
GOPAY_BASE_URL=https://gopay.gooomart.com
GOPAY_API_KEY=votre_cle_api
GOPAY_SECRET_KEY=votre_cle_secrete
GOPAY_PAYOUT_API_KEY=votre_cle_api_payout
```

### 3. Utilisation via l'injection de dépendances

```php
use Mecxer713\GoPay\GoPayServiceInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

class PaymentController extends AbstractController
{
    public function __construct(private GoPayServiceInterface $gopay) {}

    public function pay(): Response
    {
        $balance = $this->gopay->getPayoutBalance();

        return $this->json(['balance' => $balance->balance]);
    }
}
```

---

## 🔧 PHP Natif

```php
require 'vendor/autoload.php';

use Mecxer713\GoPay\GoPayService;

$gopay = new GoPayService(
    baseUrl:          'https://gopay.gooomart.com',
    paymentApiKey:    'votre_api_key',
    paymentSecretKey: 'votre_secret_key',
    payoutApiKey:     'votre_payout_api_key',
);

$balance = $gopay->getPayoutBalance();
echo $balance->balance; // 5000.0
```

---

## 📖 Référence API

### 💳 Payment API

#### `initPayment()` — Initier un paiement

```php
$response = $gopay->initPayment(
    amount:    4000,
    devise:    'CDF',
    telephone: '+24399000000',
    myref:     'REF-001',
    usersId:   null // optionnel
);
```

**Retourne** : [`PaymentResponse`](#paymentresponse)

---

#### `checkPayment()` — Vérifier le statut

```php
$response = $gopay->checkPayment(ref: 'REF-001');
```

**Retourne** : [`PaymentResponse`](#paymentresponse)

---

### 💸 Payout API

#### `getPayoutBalance()` — Solde du wallet

```php
$response = $gopay->getPayoutBalance();

echo $response->balance;  // 5000.0
echo $response->currency; // "USD"
```

**Retourne** : [`PayoutBalanceResponse`](#payoutbalanceresponse)

---

#### `sendPayoutTransfer()` — Envoyer de l'argent

```php
$response = $gopay->sendPayoutTransfer(
    montant:    500,
    devise:     'CDF',
    telephones: ['0991234567', '0811234567'],
    myrefs:     ['ref-001', 'ref-002'],
    dateDenvoi: null // optionnel : "2024/12/25 14:00"
);
```

**Retourne** : [`PayoutTransferResponse`](#payouttransferresponse)

---

#### `getPayoutTransferStatus()` — Statut d'un transfert

```php
$response = $gopay->getPayoutTransferStatus(transIdOrMyref: 'TRANS-001.94786.53389');

echo $response->transactionStatus; // "EN ATTENTE" | "TRAITÉE" | "REJETÉE"
```

**Retourne** : [`PayoutTransferResponse`](#payouttransferresponse)

---

#### `getPayoutTransfers()` — Liste des transferts

```php
$transfers = $gopay->getPayoutTransfers(); // array brut
```

---

#### `deletePayoutTransfer()` — Supprimer un transfert

> ⚠️ Seuls les transferts au statut `EN ATTENTE` peuvent être supprimés.

```php
$response = $gopay->deletePayoutTransfer(transId: 'TRANS-001.94786.53389');
```

**Retourne** : [`PayoutTransferResponse`](#payouttransferresponse)

---

## 🗂 Objets de Réponse (DTOs)

Toutes les méthodes retournent des objets PHP fortement typés, avec autocomplétion IDE complète.

### `PaymentResponse`

| Propriété | Type | Description |
|---|---|---|
| `$success` | `bool` | `true` si la requête a réussi |
| `$transactionStatus` | `?string` | Statut textuel de la transaction (ex: `"success"`) |
| `$transId` | `?string` | Identifiant unique de la transaction |
| `$url` | `?string` | URL de redirection (si applicable) |
| `$state` | `?string` | État interne de la transaction |
| `$message` | `?string` | Message humain retourné par l'API |
| `$amount` | `?string` | Montant de la transaction |
| `$currency` | `?string` | Devise (ex: `"CDF"`, `"USD"`) |
| `$source` | `?string` | Source de la transaction (ex: `"API"`) |
| `$date` | `?string` | Date ISO de la transaction |
| `$raw` | `array` | Réponse JSON brute complète |

**Méthode helper** : `isSuccessful(): bool`

---

### `PayoutBalanceResponse`

| Propriété | Type | Description |
|---|---|---|
| `$success` | `bool` | `true` si la requête a réussi |
| `$balance` | `float` | Solde du wallet |
| `$currency` | `?string` | Devise du solde |
| `$message` | `?string` | Message de l'API |
| `$raw` | `array` | Réponse JSON brute complète |

**Méthode helper** : `isSuccessful(): bool`

---

### `PayoutTransferResponse`

| Propriété | Type | Description |
|---|---|---|
| `$success` | `bool` | `true` si la requête a réussi |
| `$transactionStatus` | `?string` | Statut (`"EN ATTENTE"`, `"TRAITÉE"`, `"REJETÉE"`) |
| `$transId` | `?string` | Identifiant unique du transfert |
| `$state` | `?string` | État interne |
| `$message` | `?string` | Message de l'API |
| `$amount` | `?string` | Montant du transfert |
| `$currency` | `?string` | Devise |
| `$source` | `?string` | Source |
| `$date` | `?string` | Date ISO du transfert |
| `$raw` | `array` | Réponse JSON brute complète |

**Méthode helper** : `isSuccessful(): bool`

---

## 🚨 Gestion des Erreurs

Le SDK intercepte automatiquement les erreurs de l'API — qu'elles proviennent d'une réponse HTTP `4xx/5xx` ou d'une réponse `200` contenant `"success": false` — et lève une exception typée.

### Hiérarchie des exceptions

```
\Exception
└── GoPayException          — erreur réseau ou décodage JSON
    ├── GoPayApiException   — erreur métier retournée par l'API
    └── ConfigurationException — clés API manquantes
```

### Exemple complet

```php
use Mecxer713\GoPay\Exception\GoPayApiException;
use Mecxer713\GoPay\Exception\GoPayException;
use Mecxer713\GoPay\Enums\GoPayErrorCode;

try {
    $response = GoPay::initPayment(4000, 'CDF', '+24399000000', 'REF-001');

    if ($response->isSuccessful()) {
        // Traitement du succès
        $transId = $response->transId;
    }

} catch (GoPayApiException $e) {
    // Erreur retournée par l'API GoPAY
    echo $e->getMessage();     // "[ERR_NO_PAYMENT_FOUND] Aucune transaction..."
    echo $e->getCode();        // Code HTTP (ex: 400)
    echo $e->getErrorCode();   // "ERR_NO_PAYMENT_FOUND"

    // Comparaison typée via l'enum
    if ($e->isErrorCode(GoPayErrorCode::TIMESTAMP_EXPIRED)) {
        // Le timestamp est trop ancien, relancer la requête
    }

    if ($e->isErrorCode(GoPayErrorCode::APIKEY_INVALID)) {
        // Clé API invalide
    }

    // Accès aux données brutes de la réponse
    $raw = $e->getResponseData();

} catch (GoPayException $e) {
    // Erreur réseau, timeout, ou JSON invalide
    echo $e->getMessage();
}
```

### Codes d'erreur (`GoPayErrorCode`)

| Enum | Valeur | Description |
|---|---|---|
| `APIKEY_MISSING` | `ERR_APIKEY_MISSING` | Header `x-api-key` absent |
| `SIGNATURE_MISSING` | `ERR_SIGNATURE_MISSING` | Header `x-signature` absent |
| `TIMESTAMP_MISSING` | `ERR_TIMESTAMP_MISSING` | Header `x-timestamp` absent |
| `NONCE_MISSING` | `ERR_NONCE_MISSING` | Header `x-nonce` absent |
| `APIKEY_INVALID` | `ERR_APIKEY_INVALID` | Clé API invalide |
| `APIKEY_FORBIDDEN` | `ERR_APIKEY_FORBIDDEN` | Permissions insuffisantes |
| `APIKEY_INACTIVE` | `ERR_APIKEY_INACTIVE` | Clé API désactivée |
| `SIGNATURE_INVALID` | `ERR_SIGNATURE_INVALID` | Signature HMAC incorrecte |
| `TIMESTAMP_EXPIRED` | `ERR_TIMESTAMP_EXPIRED` | Timestamp expiré (> 120 sec) |
| `NONCE_REPLAY` | `ERR_NONCE_REPLAY` | Nonce déjà utilisé |
| `VALIDATION` | `ERR_VALIDATION` | Paramètres invalides |
| `NO_PAYMENT_FOUND` | `ERR_NO_PAYMENT_FOUND` | Transaction introuvable |

---

## 🧪 Tests

```bash
# Lancer les tests
composer test

# Analyse statique (PHPStan)
composer analyse

# Formatage du code (Laravel Pint)
composer format
```

---

## 📄 Changelog

Consultez le fichier [CHANGELOG.md](CHANGELOG.md) pour l'historique des versions.

| Version | Changements clés |
|---|---|
| `v1.3.0` | Refactoring architectural : séparation en PaymentService, PayoutService et HTTP Client |
| `v1.2.0` | Refactoring complet : enums, DTOs enrichis, helpers, ServiceProvider amélioré |
| `v1.1.x` | Gestion des erreurs API, support clés `success`/`transaction` |
| `v1.0.x` | Version initiale |

---

## 🤝 Contribution

Les contributions sont les bienvenues ! Pour contribuer :

1. Forkez le dépôt
2. Créez une branche : `git checkout -b feature/ma-fonctionnalite`
3. Committez vos changements avec des messages clairs
4. Ouvrez une Pull Request

Assurez-vous que les tests passent avant de soumettre : `composer test`.

---

## 📃 Licence

Ce package est distribué sous la licence **MIT**. Consultez le fichier [LICENSE](LICENSE) pour plus de détails.

---

<div align="center">
  Développé par <a href="https://github.com/Mecxer713">Mecxer713</a> · Propulsé par <a href="https://gopay.gooomart.com">GoPAY</a>
</div>
