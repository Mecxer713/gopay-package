# GoPay PHP SDK (Laravel & Symfony)

Ce package permet une intégration rapide et facile de l'API GoPAY (Paiement et Payout) pour vos projets PHP, avec un support natif pour **Laravel** et **Symfony**.

## Installation

Installez le package via Composer :

```bash
composer require mecxer713/gopay-php
```

## Intégration Laravel

### 1. Configuration
Publiez le fichier de configuration :
```bash
php artisan vendor:publish --provider="Mecxer713\GoPay\GoPayServiceProvider" --tag="config"
```

Ajoutez vos clés dans le fichier `.env` :
```env
GOPAY_BASE_URL="https://gopay.gooomart.com"
GOPAY_API_KEY="votre_cle_api_standard"
GOPAY_SECRET_KEY="votre_cle_secrete_standard"
GOPAY_PAYOUT_API_KEY="votre_cle_api_payout"
GOPAY_PAYOUT_SECRET_KEY="votre_cle_secrete_payout"
```

### 2. Utilisation
Vous pouvez utiliser la Facade `GoPay` ou l'injection de dépendances :

```php
use Mecxer713\GoPay\Facades\GoPay;

// Paiement
$response = GoPay::initPayment(500, 'CDF', '+24399000000', 'ref-1234');

// Payout
$balance = GoPay::getPayoutBalance();
```

---

## Intégration Symfony

### 1. Configuration
Activez le Bundle dans votre fichier `config/bundles.php` (si Flex ne l'a pas fait automatiquement) :
```php
return [
    // ...
    Mecxer713\GoPay\Symfony\GoPayBundle::class => ['all' => true],
];
```

Créez le fichier de configuration `config/packages/go_pay.yaml` :
```yaml
go_pay:
    base_url: '%env(GOPAY_BASE_URL)%'
    api_key: '%env(GOPAY_API_KEY)%'
    secret_key: '%env(GOPAY_SECRET_KEY)%'
    payout_api_key: '%env(GOPAY_PAYOUT_API_KEY)%'
    payout_secret_key: '%env(GOPAY_PAYOUT_SECRET_KEY)%'
```

Ajoutez vos clés dans votre `.env` :
```env
GOPAY_BASE_URL="https://gopay.gooomart.com"
GOPAY_API_KEY="votre_cle_api_standard"
GOPAY_SECRET_KEY="votre_cle_secrete_standard"
GOPAY_PAYOUT_API_KEY="votre_cle_api_payout"
GOPAY_PAYOUT_SECRET_KEY="votre_cle_secrete_payout"
```

### 2. Utilisation
Utilisez l'injection de dépendances (`Mecxer713\GoPay\GoPayService`) dans vos contrôleurs ou services :

```php
namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Mecxer713\GoPay\GoPayService;

class PaymentController extends AbstractController
{
    public function index(GoPayService $goPayService)
    {
        // Paiement
        $response = $goPayService->initPayment(500, 'CDF', '+24399000000', 'ref-1234');

        // Payout
        $balance = $goPayService->getPayoutBalance();
    }
}
```

---

## PHP Natif (Framework Agnostic)

Vous pouvez également utiliser le SDK sans framework :

```php
require 'vendor/autoload.php';

use Mecxer713\GoPay\GoPayService;

$goPay = new GoPayService(
    'https://gopay.gooomart.com',
    'api_key',
    'secret_key',
    'payout_api_key',
    'payout_secret_key'
);

$balance = $goPay->getPayoutBalance();
```

---

## Méthodes Disponibles

Voici la liste de toutes les méthodes que vous pouvez appeler depuis l'instance `GoPayService` (ou via la Facade `GoPay` sous Laravel) :

### 💳 Paiements (Payment API)

- **`initPayment(float $amount, string $devise, string $telephone, string $myref, ?string $usersId = null)`**
  Initie une demande de paiement. Renvoie les instructions (ex: saisir le PIN) et les références.
- **`checkPayment(string $ref)`**
  Vérifie l'état d'une transaction de paiement en utilisant la référence retournée lors de l'initialisation.

### 💸 Transferts d'Argent (Payout API)

- **`getPayoutBalance()`**
  Récupère le solde disponible dans votre Wallet (en différentes devises).
- **`getPayoutTransfers()`**
  Récupère la liste paginée de vos transferts d'argent (historique).
- **`sendPayoutTransfer(float $montant, string $devise, array $telephones, array $myrefs, ?string $dateDenvoi = null)`**
  Permet d'envoyer de l'argent à une ou plusieurs personnes (Mobile Money) en une seule requête.
- **`getPayoutTransferStatus(string $transIdOrMyref)`**
  Affiche le statut actuel d'un transfert d'argent spécifique (`EN ATTENTE`, `TRAITÉE`, `REJETÉE`).
- **`deletePayoutTransfer(string $transId)`**
  Permet d'annuler et de supprimer un transfert d'argent (seulement s'il est au statut `EN ATTENTE`).
