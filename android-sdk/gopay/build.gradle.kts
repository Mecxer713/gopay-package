plugins {
    alias(libs.plugins.android.library)
    alias(libs.plugins.kotlin.android)
    id("maven-publish")
}

val gopaySdkVersion = providers.gradleProperty("gopaySdkVersion")
    .orElse(providers.environmentVariable("VERSION"))
    .orElse("0.1.0")
    .get()

android {
    namespace = "com.mecxer713.gopay"
    compileSdk = 35

    defaultConfig { minSdk = 23 }
    publishing {
        singleVariant("release") { withSourcesJar() }
    }
}

dependencies {
    testImplementation(libs.junit)
}

afterEvaluate {
    publishing {
        publications {
            register<MavenPublication>("release") {
                from(components["release"])
                groupId = "com.github.Mecxer713.gopay-package"
                artifactId = "gopay"
                version = gopaySdkVersion
                pom {
                    name.set("GoPAY Android SDK")
                    description.set("Standalone Kotlin Android client for the GoPAY payment and payout API.")
                    url.set("https://github.com/Mecxer713/gopay-package")
                    licenses {
                        license {
                            name.set("The MIT License")
                            url.set("https://opensource.org/licenses/MIT")
                        }
                    }
                    scm {
                        connection.set("scm:git:git://github.com/Mecxer713/gopay-package.git")
                        developerConnection.set("scm:git:ssh://github.com/Mecxer713/gopay-package.git")
                        url.set("https://github.com/Mecxer713/gopay-package/tree/main/android-sdk")
                    }
                }
            }
        }
    }
}
