package com.mecxer713.gopay

import org.json.JSONObject
import org.json.JSONTokener
import java.net.HttpURLConnection
import java.net.URL
import java.security.SecureRandom
import javax.crypto.Mac
import javax.crypto.spec.SecretKeySpec

data class GoPayConfig(
    val baseUrl: String = "https://gopay.gooomart.com",
    val paymentApiKey: String,
    val paymentSecretKey: String,
    val payoutApiKey: String = "",
    val timeoutMillis: Int = 30_000,
)

data class GoPayResponse(
    val success: Boolean,
    val transactionStatus: String? = null,
    val transId: String? = null,
    val url: String? = null,
    val state: String? = null,
    val message: String? = null,
    val amount: String? = null,
    val currency: String? = null,
    val source: String? = null,
    val date: String? = null,
    val errorCode: String? = null,
    val balance: Double? = null,
    val raw: JSONObject = JSONObject(),
)

data class GoPayHttpRequest(
    val method: String,
    val endpoint: String,
    val headers: Map<String, String>,
    val body: String? = null,
)

data class GoPayHttpResponse(val statusCode: Int, val body: String)

fun interface GoPayTransport { fun execute(request: GoPayHttpRequest): GoPayHttpResponse }

class GoPayException(message: String, cause: Throwable? = null) : Exception(message, cause)
class GoPayConfigurationException(message: String) : IllegalArgumentException(message)

class GoPayClient @JvmOverloads constructor(
    private val config: GoPayConfig,
    private val transport: GoPayTransport = UrlConnectionTransport(config.baseUrl, config.timeoutMillis),
    private val clockSeconds: () -> Long = { System.currentTimeMillis() / 1000 },
    private val nonceProvider: () -> String = { randomNonce() },
) {
    init {
        require(config.baseUrl.startsWith("https://")) { "GoPay baseUrl must use HTTPS." }
    }

    @Throws(GoPayException::class)
    fun initPayment(
        amount: Double,
        currency: String,
        phone: String,
        reference: String,
        userId: String? = null
    ): GoPayResponse {
        val payload = linkedMapOf<String, Any>("amount" to amount, "devise" to currency, "telephone" to phone, "myref" to reference)
        userId?.let { payload["users_id"] = it }
        return request("POST", "/api/v3/payment/init", payload, false)
    }

    fun checkPayment(ref: String) = request("GET", "/api/v3/payment/check/${pathSegment(ref)}", emptyMap(), false)
    fun getPayoutBalance() = request("GET", "/api/payout/v3/balance", emptyMap(), true)
    fun getPayoutTransfers(): Any = requestAny("GET", "/api/payout/v3/transfer", emptyMap(), true)

    fun sendPayoutTransfer(
        amount: Double,
        currency: String,
        phones: List<String>,
        references: List<String>,
        scheduledDate: String? = null
    ): GoPayResponse {
        val payload = linkedMapOf<String, Any>("montant" to amount, "devise" to currency, "telephone" to phones, "myref" to references)
        scheduledDate?.let { payload["date_denvoi"] = it }
        return request("POST", "/api/payout/v3/transfer", payload, true)
    }

    fun getPayoutTransferStatus(transIdOrMyref: String) = request("GET", "/api/payout/v3/transfer/${pathSegment(transIdOrMyref)}", emptyMap(), true)
    fun deletePayoutTransfer(transId: String) = request("DELETE", "/api/payout/v3/transfer/${pathSegment(transId)}", emptyMap(), true)

    private fun request(method: String, endpoint: String, payload: Map<String, Any>, payout: Boolean): GoPayResponse =
        parseResponse(requestRaw(method, endpoint, payload, payout))

    private fun requestRaw(method: String, endpoint: String, payload: Map<String, Any>, payout: Boolean): JSONObject {
        val result = requestAny(method, endpoint, payload, payout)
        return result as? JSONObject ?: throw GoPayException("Expected a JSON object from GoPAY.")
    }

    private fun requestAny(method: String, endpoint: String, payload: Map<String, Any>, payout: Boolean): Any {
        val apiKey = if (payout) config.payoutApiKey else config.paymentApiKey
        val secret = config.paymentSecretKey
        if (apiKey.isBlank()) {
            val keyName = if (payout) "payoutApiKey" else "paymentApiKey"
            throw GoPayConfigurationException("GoPAY $keyName must be configured before this operation.")
        }
        if (secret.isBlank()) throw GoPayConfigurationException("GoPAY paymentSecretKey must be configured before this operation.")
        val nonce = nonceProvider()
        val timestamp = clockSeconds()
        val signature = sign(endpoint, method, payload, nonce, timestamp, secret)
        val headers = mapOf("Accept" to "application/json", "Content-Type" to "application/json", "x-api-key" to apiKey,
            "x-signature" to signature, "x-timestamp" to timestamp.toString(), "x-nonce" to nonce)
        val body = if (method == "POST") JSONObject(payload).toString() else null
        val response = try { transport.execute(GoPayHttpRequest(method, endpoint, headers, body)) }
        catch (e: Exception) { throw GoPayException("GoPAY HTTP request failed: ${e.message}", e) }
        if (response.body.isBlank()) return JSONObject()
        try { return JSONTokener(response.body).nextValue() }
        catch (e: Exception) { throw GoPayException("Invalid JSON response from GoPAY.", e) }
    }

    internal fun sign(endpoint: String, method: String, payload: Map<String, Any>, nonce: String, timestamp: Long, secret: String): String {
        val path = URL(config.baseUrl.trimEnd('/') + endpoint).path
        val params = payload.entries.flatMap { (key, value) ->
            if (value is List<*>) value.mapIndexed { i, item -> encode("$key[$i]") + "=" + encode(queryValue(item)) }
            else listOf(encode(key) + "=" + encode(queryValue(value)))
        }.joinToString("&")
        val message = path + method + params + nonce + timestamp
        val mac = Mac.getInstance("HmacSHA256")
        mac.init(SecretKeySpec(secret.toByteArray(Charsets.UTF_8), "HmacSHA256"))
        return mac.doFinal(message.toByteArray(Charsets.UTF_8)).joinToString("") { "%02x".format(it) }
    }

    private fun parseResponse(raw: JSONObject): GoPayResponse {
        if (!raw.has("success") && !raw.has("status")) throw GoPayException("GoPAY response is missing success/status.")
        val tx = raw.optJSONObject("transaction") ?: raw.optJSONObject("data") ?: JSONObject()
        val status = raw.optString("status")
        return GoPayResponse(
            success = if (raw.has("success")) raw.optBoolean("success") else status == "success",
            transactionStatus = tx.optNullableString("status"), transId = tx.optNullableString("trans_id"),
            url = tx.optNullableString("url"), state = tx.optNullableString("state"), message = raw.optNullableString("message"),
            amount = tx.optNullableString("amount"), currency = tx.optNullableString("currency"),
            source = tx.optNullableString("source"), date = tx.optNullableString("date"),
            errorCode = raw.optNullableString("error_code"),
            balance = tx.optDouble("balance", raw.optDouble("balance")).takeUnless { it.isNaN() }, raw = raw,
        )
    }

    private fun queryValue(value: Any?): String {
        if (value is Number && value.toDouble().isFinite() && value.toDouble() % 1.0 == 0.0) return value.toLong().toString()
        return value.toString()
    }
    private fun encode(value: String): String = buildString {
        value.toByteArray(Charsets.UTF_8).forEach { byte ->
            val c = byte.toInt() and 0xff
            if ((c in 0x41..0x5a) || (c in 0x61..0x7a) || (c in 0x30..0x39) || c == 0x2d || c == 0x5f || c == 0x2e) {
                append(c.toChar())
            } else if (c == 0x20) {
                append('+')
            } else {
                append('%').append("0123456789ABCDEF"[c ushr 4]).append("0123456789ABCDEF"[c and 0x0f])
            }
        }
    }
    private fun pathSegment(value: String) = java.net.URLEncoder.encode(value, "UTF-8").replace("+", "%20")
    companion object { private fun randomNonce(): String = ByteArray(16).also { SecureRandom().nextBytes(it) }.joinToString("") { "%02x".format(it) } }
}

private fun JSONObject.optNullableString(key: String): String? = if (!has(key) || isNull(key)) null else opt(key)?.toString()

private class UrlConnectionTransport(baseUrl: String, private val timeoutMillis: Int) : GoPayTransport {
    private val baseUrl = baseUrl.trimEnd('/')
    override fun execute(request: GoPayHttpRequest): GoPayHttpResponse {
        val url = URL("$baseUrl${request.endpoint}")
        val connection = (url.openConnection() as HttpURLConnection).apply {
            requestMethod = request.method
            connectTimeout = timeoutMillis
            readTimeout = timeoutMillis
            request.headers.forEach { (key, value) -> setRequestProperty(key, value) }
        }
        if (request.body != null) { connection.doOutput = true; connection.outputStream.use { it.write(request.body.toByteArray()) } }
        val status = connection.responseCode
        val stream = if (status >= 400) connection.errorStream else connection.inputStream
        val body = stream?.bufferedReader()?.use { it.readText() } ?: ""
        connection.disconnect()
        return GoPayHttpResponse(status, body)
    }
}
