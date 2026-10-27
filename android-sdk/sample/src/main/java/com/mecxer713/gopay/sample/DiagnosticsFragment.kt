package com.mecxer713.gopay.sample

import android.os.Bundle
import android.view.LayoutInflater
import android.view.View
import android.view.ViewGroup
import androidx.fragment.app.Fragment
import androidx.lifecycle.lifecycleScope
import com.mecxer713.gopay.GoPayClient
import com.mecxer713.gopay.GoPayConfig
import com.mecxer713.gopay.GoPayException
import com.mecxer713.gopay.GoPayHttpResponse
import com.mecxer713.gopay.GoPaySdkVersion
import com.mecxer713.gopay.GoPayTransport
import com.mecxer713.gopay.sample.databinding.FragmentDiagnosticsBinding
import kotlinx.coroutines.Dispatchers
import kotlinx.coroutines.launch
import kotlinx.coroutines.withContext
import javax.crypto.Mac
import javax.crypto.spec.SecretKeySpec

class DiagnosticsFragment : Fragment() {

    private var _binding: FragmentDiagnosticsBinding? = null
    private val binding get() = _binding!!

    override fun onCreateView(
        inflater: LayoutInflater,
        container: ViewGroup?,
        savedInstanceState: Bundle?
    ): View {
        _binding = FragmentDiagnosticsBinding.inflate(inflater, container, false)
        return binding.root
    }

    override fun onViewCreated(view: View, savedInstanceState: Bundle?) {
        super.onViewCreated(view, savedInstanceState)

        binding.tvSdkVersion.text = "Version du SDK : ${GoPaySdkVersion.VERSION}"
        binding.tvSdkUserAgent.text = "Package : com.mecxer713.gopay"

        binding.btnRunSdkTests.setOnClickListener {
            runEmbeddedTestSuite()
        }
    }

    private fun runEmbeddedTestSuite() {
        binding.layoutTestResults.visibility = View.VISIBLE
        binding.tvTestSummary.text = "Exécution des tests en cours..."
        binding.tvTestSummary.setTextColor(resources.getColor(R.color.primary, null))
        binding.tvTestDetails.text = "Tests démarrés...\n"

        val mainActivity = activity as? MainActivity
        mainActivity?.showLoading(true, "Exécution de la suite de tests...")

        lifecycleScope.launch {
            val results = withContext(Dispatchers.Default) {
                executeTests()
            }

            mainActivity?.showLoading(false)

            val passedCount = results.count { it.passed }
            val totalCount = results.size
            val allPassed = passedCount == totalCount

            binding.tvTestSummary.text = if (allPassed) {
                "TOUS LES TESTS ONT RÉUSSI ($passedCount / $totalCount)"
            } else {
                "CERTAINS TESTS ONT ÉCHOUÉ ($passedCount / $totalCount)"
            }
            binding.tvTestSummary.setTextColor(
                resources.getColor(if (allPassed) R.color.success else R.color.error, null)
            )

            val details = buildString {
                results.forEachIndexed { i, test ->
                    val mark = if (test.passed) "✔ [PASS]" else "✘ [FAIL]"
                    append("${i + 1}. $mark ${test.name} (${test.durationMs}ms)\n")
                    if (!test.passed && test.error != null) {
                        append("   Erreur: ${test.error}\n")
                    }
                }
            }
            binding.tvTestDetails.text = details

            mainActivity?.displayResponse(
                operation = "SUITE DE TESTS EMBARQUÉE",
                isSuccess = allPassed,
                statusText = if (allPassed) "TOUS RÉUSSIS ($passedCount/$totalCount)" else "ÉCHEC",
                summary = "Vérification complète du SDK GoPAY sur runtime Android",
                rawJson = buildTestReportJson(results)
            )
        }
    }

    private data class TestResult(val name: String, val passed: Boolean, val durationMs: Long, val error: String? = null)

    private fun executeTests(): List<TestResult> {
        val list = mutableListOf<TestResult>()

        // Test 1: Configuration & HTTPS Validation
        list += runSingleTest("Validation HTTPS baseUrl") {
            try {
                GoPayConfig(baseUrl = "http://insecure.url", paymentApiKey = "key", paymentSecretKey = "secret")
                val client = GoPayClient(GoPayConfig(baseUrl = "http://insecure.url", paymentApiKey = "k", paymentSecretKey = "s"))
                throw AssertionError("Devrait rejeter HTTP")
            } catch (e: IllegalArgumentException) {
                // Expected
            }
        }

        // Test 2: Version constants
        list += runSingleTest("Constante SDK Version") {
            if (GoPaySdkVersion.VERSION.isBlank()) throw AssertionError("Version vide")
        }

        // Test 3: HMAC-SHA256 Signature calculation via public API
        list += runSingleTest("Génération Signature HMAC-SHA256") {
            val config = GoPayConfig(paymentApiKey = "test-key", paymentSecretKey = "my-secret")
            var captured: com.mecxer713.gopay.GoPayHttpRequest? = null
            val client = GoPayClient(config, GoPayTransport { req ->
                captured = req
                GoPayHttpResponse(200, """{"success":true,"transaction":{"status":"success"}}""")
            }, { 1700000000L }, { "12345678" })

            client.initPayment(5000.0, "CDF", "+2431", "ref")

            val signature = captured?.headers?.get("x-signature") ?: throw AssertionError("x-signature manquant")
            val mac = Mac.getInstance("HmacSHA256")
            mac.init(SecretKeySpec("my-secret".toByteArray(Charsets.UTF_8), "HmacSHA256"))
            val expected = mac.doFinal("/api/v3/payment/initPOSTamount=5000&devise=CDF&telephone=%2B2431&myref=ref123456781700000000".toByteArray(Charsets.UTF_8))
                .joinToString("") { "%02x".format(it) }

            if (signature != expected) throw AssertionError("Signature mismatch: $signature != $expected")
        }

        // Test 4: Parsing Payment Success Response
        list += runSingleTest("Décodage Réponse Payment JSON") {
            val config = GoPayConfig(paymentApiKey = "k", paymentSecretKey = "s")
            val client = GoPayClient(config, GoPayTransport {
                GoPayHttpResponse(200, """{"success":true,"transaction":{"status":"success","trans_id":"T-999","amount":"1200","currency":"CDF"}}""")
            })
            val res = client.checkPayment("myref")
            if (!res.success) throw AssertionError("res.success false")
            if (res.transId != "T-999") throw AssertionError("transId mismatch")
            if (res.amount != "1200") throw AssertionError("amount mismatch")
        }

        // Test 5: Parsing Nested Payout Balance
        list += runSingleTest("Décodage Solde Payout (data.balance)") {
            val config = GoPayConfig(paymentApiKey = "k", paymentSecretKey = "s", payoutApiKey = "pk")
            val client = GoPayClient(config, GoPayTransport {
                GoPayHttpResponse(200, """{"success":true,"data":{"balance":75000.5,"currency":"USD"}}""")
            })
            val res = client.getPayoutBalance()
            if (res.balance != 75000.5) throw AssertionError("balance mismatch: ${res.balance}")
            if (res.currency != "USD") throw AssertionError("currency mismatch: ${res.currency}")
        }

        // Test 6: Network / Invalid JSON Exception
        list += runSingleTest("Gestion d'erreur format JSON invalide") {
            val config = GoPayConfig(paymentApiKey = "k", paymentSecretKey = "s")
            val client = GoPayClient(config, GoPayTransport {
                GoPayHttpResponse(500, "Internal Server Error - HTML")
            })
            try {
                client.checkPayment("any-ref")
                throw AssertionError("Devrait lever GoPayException")
            } catch (e: GoPayException) {
                // Expected
            }
        }

        return list
    }

    private inline fun runSingleTest(name: String, block: () -> Unit): TestResult {
        val start = System.currentTimeMillis()
        return try {
            block()
            TestResult(name, true, System.currentTimeMillis() - start)
        } catch (t: Throwable) {
            TestResult(name, false, System.currentTimeMillis() - start, t.message ?: t.toString())
        }
    }

    private fun buildTestReportJson(results: List<TestResult>): String {
        val array = org.json.JSONArray()
        results.forEach {
            array.put(org.json.JSONObject().apply {
                put("test", it.name)
                put("passed", it.passed)
                put("duration_ms", it.durationMs)
                it.error?.let { err -> put("error", err) }
            })
        }
        return org.json.JSONObject().apply {
            put("suite", "GoPay Android SDK Diagnostics")
            put("total", results.size)
            put("passed", results.count { it.passed })
            put("results", array)
        }.toString(2)
    }

    override fun onDestroyView() {
        super.onDestroyView()
        _binding = null
    }
}
