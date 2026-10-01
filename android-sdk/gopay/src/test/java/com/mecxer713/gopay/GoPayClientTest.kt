package com.mecxer713.gopay

import org.junit.Assert.*
import org.junit.Test
import javax.crypto.Mac
import javax.crypto.spec.SecretKeySpec

class GoPayClientTest {
    private val config = GoPayConfig(paymentApiKey = "pay-key", paymentSecretKey = "secret", payoutApiKey = "out-key")

    @Test fun paymentUsesSignedJsonRequestAndParsesResponse() {
        var captured: GoPayHttpRequest? = null
        val client = GoPayClient(config, GoPayTransport { request ->
            captured = request
            GoPayHttpResponse(200, """{"success":true,"transaction":{"status":"success","trans_id":"T1"}}""")
        }, { 1234L }, { "nonce" })
        val response = client.initPayment(12.0, "CDF", "+2431", "ref")
        assertTrue(response.success)
        assertEquals("T1", response.transId)
        assertEquals("POST", captured?.method)
        assertEquals("pay-key", captured?.headers?.get("x-api-key"))
        assertEquals("1234", captured?.headers?.get("x-timestamp"))
        val mac = Mac.getInstance("HmacSHA256")
        mac.init(SecretKeySpec("secret".toByteArray(), "HmacSHA256"))
        val expected = mac.doFinal("/api/v3/payment/initPOSTamount=12&devise=CDF&telephone=%2B2431&myref=refnonce1234".toByteArray())
            .joinToString("") { "%02x".format(it) }
        assertEquals(expected, captured?.headers?.get("x-signature"))
        assertTrue(captured?.body?.contains("\"myref\":\"ref\"") == true)
    }

    @Test fun payoutUsesPayoutKeyAndKeepsApiErrors() {
        var captured: GoPayHttpRequest? = null
        val client = GoPayClient(config, GoPayTransport { request ->
            captured = request
            GoPayHttpResponse(400, """{"success":false,"error_code":"ERR_VALIDATION","message":"invalid"}""")
        }, { 1L }, { "n" })
        val response = client.sendPayoutTransfer(3.0, "CDF", listOf("1"), listOf("r"))
        assertEquals("out-key", captured?.headers?.get("x-api-key"))
        assertEquals("ERR_VALIDATION", response.errorCode)
        assertFalse(response.success)
    }

    @Test fun payoutBalanceReadsNestedData() {
        val client = GoPayClient(config, GoPayTransport {
            GoPayHttpResponse(200, "{\"success\":true,\"data\":{\"balance\":5000,\"currency\":\"USD\"}}")
        })
        val response = client.getPayoutBalance()
        assertEquals(5000.0, response.balance!!, 0.0)
        assertEquals("USD", response.currency)
    }

    @Test fun malformedJsonBecomesGoPayException() {
        val client = GoPayClient(config, GoPayTransport { GoPayHttpResponse(200, "not-json") })
        try { client.checkPayment("ref"); fail("Expected GoPayException") }
        catch (_: GoPayException) { }
    }

    @Test fun networkFailureBecomesGoPayException() {
        val client = GoPayClient(config, GoPayTransport { throw java.io.IOException("offline") })
        try { client.checkPayment("ref"); fail("Expected GoPayException") }
        catch (_: GoPayException) { }
    }

    @Test fun everyPayoutOperationUsesItsDocumentedRoute() {
        val paths = mutableListOf<String>()
        val client = GoPayClient(config, GoPayTransport { request ->
            paths += "${request.method} ${request.endpoint}"
            val body = if (request.method == "GET" && request.endpoint == "/api/payout/v3/transfer") {
                "[{\"trans_id\":\"T1\"}]"
            } else {
                "{\"success\":true,\"balance\":10}"
            }
            GoPayHttpResponse(200, body)
        })
        client.getPayoutBalance()
        val transfers = client.getPayoutTransfers() as org.json.JSONArray
        client.sendPayoutTransfer(1.0, "CDF", listOf("1"), listOf("r"))
        client.getPayoutTransferStatus("T1")
        client.deletePayoutTransfer("T1")
        assertEquals(listOf(
            "GET /api/payout/v3/balance", "GET /api/payout/v3/transfer", "POST /api/payout/v3/transfer",
            "GET /api/payout/v3/transfer/T1", "DELETE /api/payout/v3/transfer/T1",
        ), paths)
        assertEquals("T1", transfers.getJSONObject(0).getString("trans_id"))
    }

    @Test fun paymentOnlyConfigurationDoesNotRequirePayoutKey() {
        val paymentOnly = GoPayConfig(paymentApiKey = "merchant-payment-key", paymentSecretKey = "merchant-secret")
        var apiKey: String? = null
        val client = GoPayClient(paymentOnly, GoPayTransport { request ->
            apiKey = request.headers["x-api-key"]
            GoPayHttpResponse(200, "{\"success\":true}")
        })
        assertTrue(client.checkPayment("merchant-ref").success)
        assertEquals("merchant-payment-key", apiKey)
        try {
            client.getPayoutBalance()
            fail("Expected payout configuration error")
        } catch (e: GoPayConfigurationException) {
            assertTrue(e.message!!.contains("payoutApiKey"))
        }
    }
}
