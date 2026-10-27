package com.mecxer713.gopay.sample

import com.mecxer713.gopay.GoPayHttpRequest
import com.mecxer713.gopay.GoPayHttpResponse
import com.mecxer713.gopay.GoPayTransport
import org.json.JSONArray
import org.json.JSONObject

class SampleMockTransport : GoPayTransport {
    override fun execute(request: GoPayHttpRequest): GoPayHttpResponse {
        // Simulate a small network delay
        Thread.sleep(300)

        val endpoint = request.endpoint
        val method = request.method

        return when {
            method == "POST" && endpoint == "/api/v3/payment/init" -> {
                val reqBody = request.body?.let { JSONObject(it) } ?: JSONObject()
                val amount = reqBody.optDouble("amount", 1000.0)
                val devise = reqBody.optString("devise", "CDF")
                val myref = reqBody.optString("myref", "REF-DEMO")
                val transId = "TX-" + System.currentTimeMillis().toString().takeLast(6)

                val resp = JSONObject().apply {
                    put("success", true)
                    put("message", "Payment session created successfully (Simulated)")
                    put("transaction", JSONObject().apply {
                        put("status", "pending")
                        put("trans_id", transId)
                        put("myref", myref)
                        put("amount", amount.toString())
                        put("currency", devise)
                        put("url", "https://gopay.gooomart.com/checkout/demo-$transId")
                        put("date", java.text.SimpleDateFormat("yyyy-MM-dd HH:mm:ss", java.util.Locale.US).format(java.util.Date()))
                    })
                }
                GoPayHttpResponse(200, resp.toString(2))
            }

            method == "GET" && endpoint.startsWith("/api/v3/payment/check/") -> {
                val ref = endpoint.removePrefix("/api/v3/payment/check/")
                val resp = JSONObject().apply {
                    put("success", true)
                    put("status", "success")
                    put("message", "Transaction checked successfully (Simulated)")
                    put("transaction", JSONObject().apply {
                        put("status", "success")
                        put("trans_id", "TX-CHK-${System.currentTimeMillis() % 100000}")
                        put("myref", ref)
                        put("amount", "5000.00")
                        put("currency", "CDF")
                        put("source", "Orange Money")
                        put("date", java.text.SimpleDateFormat("yyyy-MM-dd HH:mm:ss", java.util.Locale.US).format(java.util.Date()))
                    })
                }
                GoPayHttpResponse(200, resp.toString(2))
            }

            method == "GET" && endpoint == "/api/payout/v3/balance" -> {
                val resp = JSONObject().apply {
                    put("success", true)
                    put("message", "Payout balance retrieved (Simulated)")
                    put("data", JSONObject().apply {
                        put("balance", 250000.0)
                        put("currency", "CDF")
                    })
                }
                GoPayHttpResponse(200, resp.toString(2))
            }

            method == "GET" && endpoint == "/api/payout/v3/transfer" -> {
                val array = JSONArray().apply {
                    put(JSONObject().apply {
                        put("trans_id", "PO-1001")
                        put("montant", 15000.0)
                        put("devise", "CDF")
                        put("telephone", "+243990000001")
                        put("status", "SUCCESS")
                        put("date", "2026-10-27 10:15:00")
                    })
                    put(JSONObject().apply {
                        put("trans_id", "PO-1002")
                        put("montant", 50.0)
                        put("devise", "USD")
                        put("telephone", "+243810000002")
                        put("status", "PENDING")
                        put("date", "2026-10-27 11:30:00")
                    })
                }
                GoPayHttpResponse(200, array.toString(2))
            }

            method == "POST" && endpoint == "/api/payout/v3/transfer" -> {
                val reqBody = request.body?.let { JSONObject(it) } ?: JSONObject()
                val montant = reqBody.optDouble("montant", 100.0)
                val devise = reqBody.optString("devise", "CDF")
                val transId = "PO-" + System.currentTimeMillis().toString().takeLast(6)

                val resp = JSONObject().apply {
                    put("success", true)
                    put("message", "Payout batch queued successfully (Simulated)")
                    put("transaction", JSONObject().apply {
                        put("trans_id", transId)
                        put("status", "PROCESSING")
                        put("amount", montant.toString())
                        put("currency", devise)
                    })
                }
                GoPayHttpResponse(200, resp.toString(2))
            }

            method == "GET" && endpoint.startsWith("/api/payout/v3/transfer/") -> {
                val id = endpoint.removePrefix("/api/payout/v3/transfer/")
                val resp = JSONObject().apply {
                    put("success", true)
                    put("message", "Payout status (Simulated)")
                    put("data", JSONObject().apply {
                        put("trans_id", id)
                        put("status", "SUCCESS")
                        put("amount", "25000")
                        put("currency", "CDF")
                    })
                }
                GoPayHttpResponse(200, resp.toString(2))
            }

            method == "DELETE" && endpoint.startsWith("/api/payout/v3/transfer/") -> {
                val id = endpoint.removePrefix("/api/payout/v3/transfer/")
                val resp = JSONObject().apply {
                    put("success", true)
                    put("message", "Payout transfer $id cancelled successfully (Simulated)")
                }
                GoPayHttpResponse(200, resp.toString(2))
            }

            else -> {
                GoPayHttpResponse(404, """{"success":false,"message":"Endpoint not found (Simulated)"}""")
            }
        }
    }
}
