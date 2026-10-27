# GoPAY Android SDK 🤖

[![JitPack](https://jitpack.io/v/Mecxer713/gopay-android.svg)](https://jitpack.io/#Mecxer713/gopay-android)
[![License: MIT](https://img.shields.io/badge/License-MIT-yellow.svg)](LICENSE)
[![API](https://img.shields.io/badge/API-23%2B-brightgreen.svg?style=flat)](https://android-arsenal.com/api?level=23)

Bibliothèque Android native (Kotlin) officielle pour l'intégration de l'API **GoPAY Mobile Money** (Paiements et Payouts). 

Le SDK est 100% autonome, léger, sans dépendances superflues, et ne nécessite que vos clés API marchandes.

---

## 📦 Installation avec Gradle (JitPack)

### 1. Déclarer le dépôt JitPack
Dans votre `settings.gradle.kts` (ou `build.gradle` racine) :

```kotlin
dependencyResolutionManagement {
    repositories {
        google()
        mavenCentral()
        maven { url = uri("https://jitpack.io") }
    }
}
```

### 2. Ajouter la dépendance
Dans le fichier `build.gradle.kts` de votre module application (ex: `app/build.gradle.kts`) :

```kotlin
dependencies {
    implementation("com.github.Mecxer713:gopay-android:0.1.0")
}
```

---

## ⚡ Démarrage Rapide

### 1. Initialisation du client

Instanciez `GoPayClient` en fournissant vos identifiants GoPAY :

```kotlin
import com.mecxer713.gopay.GoPayClient
import com.mecxer713.gopay.GoPayConfig

val gopay = GoPayClient(
    GoPayConfig(
        baseUrl = "https://gopay.gooomart.com", // Optionnel, URL par défaut
        paymentApiKey = "VOTRE_PAYMENT_API_KEY",
        paymentSecretKey = "VOTRE_PAYMENT_SECRET_KEY",
        payoutApiKey = "VOTRE_PAYOUT_API_KEY" // Optionnel si aucun transfert sortant
    )
)
```

### 2. Initier une demande de paiement (Mobile Money)

Les requêtes réseau étant bloquantes, exécutez-les sur un dispatcher d'I/O (Coroutines) ou en tâche d'arrière-plan :

```kotlin
import kotlinx.coroutines.Dispatchers
import kotlinx.coroutines.withContext

val payment = withContext(Dispatchers.IO) {
    gopay.initPayment(
        amount = 4000.0,
        currency = "CDF",       // "CDF" ou "USD"
        phone = "+243825000000",
        reference = "CMD-1001"   // Référence unique de votre commande
    )
}

if (payment.success) {
    println("Paiement initié ! TransId: ${payment.transId}")
} else {
    println("Échec: ${payment.message} (${payment.errorCode})")
}
```

### 3. Vérifier le statut d'un paiement

```kotlin
val check = withContext(Dispatchers.IO) {
    gopay.checkPayment(reference = "CMD-1001")
}

println("Statut de la transaction: ${check.transactionStatus}") // ex: "success", "pending"
```

### 4. Payout (Transferts de masse)

```kotlin
// Consulter le solde Payout
val balance = withContext(Dispatchers.IO) {
    gopay.getPayoutBalance()
}
println("Solde: ${balance.balance} ${balance.currency}")

// Émettre un transfert
val transfer = withContext(Dispatchers.IO) {
    gopay.sendPayoutTransfer(
        amount = 1500.0,
        currency = "CDF",
        phones = listOf("+243825000000"),
        references = listOf("PO-001")
    )
}
```

---

## 📱 Application Démo (`:sample`)

Ce dépôt inclut une application Android complète de test et de démonstration dans le module `:sample`.

Pour tester localement :
```bash
./gradlew :sample:installDebug
```

---

## 🧪 Tests Unitaires

```bash
./gradlew :gopay:testReleaseUnitTest
```

---

## 📄 Licence

Ce SDK est distribué sous licence [MIT](LICENSE).
