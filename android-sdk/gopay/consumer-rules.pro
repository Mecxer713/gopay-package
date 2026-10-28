# GoPAY Android SDK Consumer ProGuard Rules
# These rules are automatically packaged into the AAR and applied to consumer applications.

# Keep public API classes, methods, and data structures
-keep class com.mecxer713.gopay.GoPayClient { *; }
-keep class com.mecxer713.gopay.GoPayConfig { *; }
-keep class com.mecxer713.gopay.GoPayResponse { *; }
-keep class com.mecxer713.gopay.GoPayHttpRequest { *; }
-keep class com.mecxer713.gopay.GoPayHttpResponse { *; }
-keep class com.mecxer713.gopay.GoPayTransport { *; }
-keep class com.mecxer713.gopay.GoPayException { *; }
-keep class com.mecxer713.gopay.GoPayConfigurationException { *; }
-keep class com.mecxer713.gopay.GoPaySdkVersion { *; }

# Preserve JSONObject parsing reflection if used
-keepclassmembers class com.mecxer713.gopay.** {
    public <fields>;
    public <methods>;
}
