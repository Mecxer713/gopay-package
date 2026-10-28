# GoPAY Android SDK 🤖

[![JitPack](https://jitpack.io/v/Mecxer713/gopay-android.svg)](https://jitpack.io/#Mecxer713/gopay-android)
[![License: MIT](https://img.shields.io/badge/License-MIT-yellow.svg)](LICENSE)
[![API](https://img.shields.io/badge/API-23%2B-brightgreen.svg?style=flat)](https://android-arsenal.com/api?level=23)

Bibliothèque Android native (Kotlin) officielle pour l'intégration de l'API **GoPAY Mobile Money** (Paiements et Payouts). 

Le SDK est 100% autonome, léger, sans dépendances superflues, et ne nécessite que vos clés API marchandes.

---

## 📦 Installation dans un Projet Android Tiers

Le SDK GoPAY Android est une bibliothèque Android native (`.aar`) 100% autonome (aucune dépendance tierce requise). Vous pouvez l'installer soit automatiquement via **JitPack**, soit manuellement via le **fichier binaire AAR**.

### Méthode 1 : Via JitPack (Recommandé)

#### 1.1 Déclarer le dépôt JitPack
Ajoutez JitPack à votre gestionnaire de dépôts dans `settings.gradle.kts` (ou `settings.gradle`) :

**Kotlin DSL (`settings.gradle.kts`) :**
```kotlin
dependencyResolutionManagement {
    repositoriesMode.set(RepositoriesMode.FAIL_ON_PROJECT_REPOS)
    repositories {
        google()
        mavenCentral()
        maven { url = uri("https://jitpack.io") }
    }
}
```

**Groovy DSL (`settings.gradle`) :**
```groovy
dependencyResolutionManagement {
    repositoriesMode.set(RepositoriesMode.FAIL_ON_PROJECT_REPOS)
    repositories {
        google()
        mavenCentral()
        maven { url 'https://jitpack.io' }
    }
}
```

#### 1.2 Ajouter la dépendance GoPAY
Dans le fichier `build.gradle.kts` (ou `build.gradle`) de votre module application (ex: `app/build.gradle.kts`) :

**Kotlin DSL (`app/build.gradle.kts`) :**
```kotlin
dependencies {
    implementation("com.github.Mecxer713.gopay-package:gopay-android:0.1.0")
}
```

**Groovy DSL (`app/build.gradle`) :**
```groovy
dependencies {
    implementation 'com.github.Mecxer713.gopay-package:gopay-android:0.1.0'
}
```

---

### Méthode 2 : Via le Fichier Binaire AAR (Installation Locale / Hors-Ligne)

Si vous préférez intégrer le SDK sans dépendre d'un gestionnaire de paquets distant :

1. Téléchargez la dernière version de `gopay-android-0.1.0.aar` depuis les [GitHub Releases](https://github.com/Mecxer713/gopay-package/releases).
2. Déposez le fichier `.aar` dans le sous-dossier `libs/` de votre module application (ex: `app/libs/gopay-android-0.1.0.aar`).
3. Dans votre `app/build.gradle.kts` (ou `app/build.gradle`) :

**Kotlin DSL (`app/build.gradle.kts`) :**
```kotlin
dependencies {
    implementation(files("libs/gopay-android-0.1.0.aar"))
}
```

**Groovy DSL (`app/build.gradle`) :**
```groovy
dependencies {
    implementation files('libs/gopay-android-0.1.0.aar')
}
```

---

### 🌐 Permissions Android

L'API GoPAY nécessite l'accès au réseau. Assurez-vous d'avoir déclaré la permission Internet dans votre `AndroidManifest.xml` :

```xml
<manifest xmlns:android="http://schemas.android.com/apk/res/android">
    <uses-permission android:name="android.permission.INTERNET" />
    ...
</manifest>
```

---

### 🛡️ Obfuscation & ProGuard / R8

Le fichier `.aar` intègre automatiquement ses propres règles `consumer-rules.pro`. Si vous activez `isMinifyEnabled = true` dans votre application, les classes du SDK GoPAY (`GoPayClient`, `GoPayConfig`, `GoPayResponse`, etc.) sont automatiquement préservées sans configuration supplémentaire requise.


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
