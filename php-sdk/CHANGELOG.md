# Changelog - GoPAY PHP SDK

Toutes les modifications notables apportées à ce package seront documentées dans ce fichier.

Le versionnage suit [SemVer](https://semver.org/).

## [1.5.1] - 2026-06-29
### Corrigé
- Utilisation de `http_build_query` avec encodage RFC 1738 pour le calcul de la signature HMAC.

## [1.5.0] - 2026-06-25
### Ajouté
- Implémentation du client HTTP `GoPayClient` gérant l'authentification et la génération de signatures avec en-têtes sécurisés (`x-api-key`, `x-timestamp`, `x-nonce`, `x-signature`).

## [1.4.0] - 2026-06-20
### Modifié
- Refonte de la gestion des erreurs : suppression de `GoPayApiException` au profit de retours `PaymentResponse` enrichis avec code d'erreur et détails API.

## [1.3.0] - 2026-06-15
### Ajouté
- Refactoring architectural : séparation en services dédiés `PaymentService` et `PayoutService`.
- Intégration Symfony (Configuration, DependencyInjection, Bundle) et Laravel (Facades, ServiceProvider).
