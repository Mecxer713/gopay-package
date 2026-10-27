package com.mecxer713.gopay.sample

import android.content.ClipData
import android.content.ClipboardManager
import android.content.Context
import android.os.Bundle
import android.view.View
import android.widget.Toast
import androidx.appcompat.app.AppCompatActivity
import androidx.fragment.app.Fragment
import androidx.viewpager2.adapter.FragmentStateAdapter
import com.google.android.material.tabs.TabLayoutMediator
import com.mecxer713.gopay.GoPayClient
import com.mecxer713.gopay.GoPayConfig
import com.mecxer713.gopay.sample.databinding.ActivityMainBinding

class MainActivity : AppCompatActivity() {

    private lateinit var binding: ActivityMainBinding
    private val prefs by lazy { getSharedPreferences("gopay_sample_prefs", Context.MODE_PRIVATE) }
    private var lastRawJson: String = ""

    override fun onCreate(savedInstanceState: Bundle?) {
        super.onCreate(savedInstanceState)
        binding = ActivityMainBinding.inflate(layoutInflater)
        setContentView(binding.root)

        loadSavedConfig()
        setupViewPager()
        setupListeners()
    }

    private fun setupViewPager() {
        val adapter = object : FragmentStateAdapter(this) {
            override fun getItemCount(): Int = 3
            override fun createFragment(position: Int): Fragment {
                return when (position) {
                    0 -> PaymentFragment()
                    1 -> PayoutFragment()
                    else -> DiagnosticsFragment()
                }
            }
        }
        binding.viewPager.adapter = adapter

        TabLayoutMediator(binding.tabLayout, binding.viewPager) { tab, position ->
            tab.text = when (position) {
                0 -> getString(R.string.tab_payments)
                1 -> getString(R.string.tab_payouts)
                else -> getString(R.string.tab_diagnostics)
            }
            tab.setIcon(when (position) {
                0 -> R.drawable.ic_payment
                1 -> R.drawable.ic_payout
                else -> R.drawable.ic_diagnostics
            })
        }.attach()
    }

    private fun setupListeners() {
        binding.btnToggleConfig.setOnClickListener {
            binding.layoutConfig.visibility = if (binding.layoutConfig.visibility == View.VISIBLE) {
                View.GONE
            } else {
                View.VISIBLE
            }
        }

        binding.btnSaveConfig.setOnClickListener {
            saveConfig()
            binding.layoutConfig.visibility = View.GONE
            Toast.makeText(this, "Configuration enregistrée", Toast.LENGTH_SHORT).show()
        }

        binding.btnClearLogs.setOnClickListener {
            binding.tvConsoleStatus.text = "PRÊT"
            binding.tvConsoleStatus.setBackgroundColor(resources.getColor(R.color.primary, null))
            binding.tvConsoleTitle.text = "Console effacée"
            binding.tvConsoleContent.text = "// Prêt pour une nouvelle requête."
            lastRawJson = ""
        }

        binding.btnCopyJson.setOnClickListener {
            if (lastRawJson.isNotBlank()) {
                val clipboard = getSystemService(Context.CLIPBOARD_SERVICE) as ClipboardManager
                val clip = ClipData.newPlainText("GoPay JSON", lastRawJson)
                clipboard.setPrimaryClip(clip)
                Toast.makeText(this, "JSON copié dans le presse-papiers", Toast.LENGTH_SHORT).show()
            } else {
                Toast.makeText(this, "Aucun contenu à copier", Toast.LENGTH_SHORT).show()
            }
        }
    }

    private fun loadSavedConfig() {
        binding.inputBaseUrl.setText(prefs.getString("base_url", "https://gopay.gooomart.com"))
        binding.inputPaymentApiKey.setText(prefs.getString("payment_api_key", ""))
        binding.inputPaymentSecretKey.setText(prefs.getString("payment_secret_key", ""))
        binding.inputPayoutApiKey.setText(prefs.getString("payout_api_key", ""))
        binding.switchMockMode.isChecked = prefs.getBoolean("mock_mode", true)
    }

    private fun saveConfig() {
        prefs.edit().apply {
            putString("base_url", binding.inputBaseUrl.text?.toString()?.trim().orEmpty())
            putString("payment_api_key", binding.inputPaymentApiKey.text?.toString()?.trim().orEmpty())
            putString("payment_secret_key", binding.inputPaymentSecretKey.text?.toString()?.trim().orEmpty())
            putString("payout_api_key", binding.inputPayoutApiKey.text?.toString()?.trim().orEmpty())
            putBoolean("mock_mode", binding.switchMockMode.isChecked)
            apply()
        }
    }

    fun createClient(): GoPayClient {
        saveConfig()
        val baseUrl = binding.inputBaseUrl.text?.toString()?.trim().orEmpty().ifEmpty { "https://gopay.gooomart.com" }
        val paymentApiKey = binding.inputPaymentApiKey.text?.toString()?.trim().orEmpty()
        val paymentSecretKey = binding.inputPaymentSecretKey.text?.toString()?.trim().orEmpty()
        val payoutApiKey = binding.inputPayoutApiKey.text?.toString()?.trim().orEmpty()

        val config = GoPayConfig(
            baseUrl = baseUrl,
            paymentApiKey = paymentApiKey,
            paymentSecretKey = paymentSecretKey,
            payoutApiKey = payoutApiKey,
        )

        return if (binding.switchMockMode.isChecked) {
            GoPayClient(config, transport = SampleMockTransport())
        } else {
            GoPayClient(config)
        }
    }

    fun hideKeyboard() {
        val imm = getSystemService(Context.INPUT_METHOD_SERVICE) as? android.view.inputmethod.InputMethodManager
        val view = currentFocus ?: binding.root
        imm?.hideSoftInputFromWindow(view.windowToken, 0)
        view.clearFocus()
    }

    fun showLoading(show: Boolean, message: String = "") {
        binding.progressBar.visibility = if (show) View.VISIBLE else View.GONE
        if (show) {
            binding.tvConsoleStatus.text = "EN COURS"
            binding.tvConsoleStatus.setBackgroundColor(resources.getColor(R.color.warning, null))
            binding.tvConsoleTitle.text = message
        }
    }

    fun displayResponse(operation: String, isSuccess: Boolean, statusText: String, summary: String, rawJson: String) {
        lastRawJson = rawJson
        binding.tvConsoleStatus.text = if (isSuccess) "SUCCÈS" else "ÉCHEC"
        binding.tvConsoleStatus.setBackgroundColor(
            resources.getColor(if (isSuccess) R.color.success else R.color.error, null)
        )
        binding.tvConsoleTitle.text = "$operation - $summary"
        binding.tvConsoleContent.text = buildString {
            append("// ").append(operation).append(" -> ").append(statusText).append("\n")
            append("// Summary: ").append(summary).append("\n\n")
            append(rawJson)
        }
    }

    fun displayError(operation: String, error: Throwable) {
        lastRawJson = """{"error": "${error.message}"}"""
        binding.tvConsoleStatus.text = "ERREUR"
        binding.tvConsoleStatus.setBackgroundColor(resources.getColor(R.color.error, null))
        binding.tvConsoleTitle.text = "$operation : ${error.javaClass.simpleName}"
        binding.tvConsoleContent.text = buildString {
            append("// ").append(operation).append(" a échoué\n")
            append("// Exception: ").append(error.javaClass.name).append("\n")
            append("// Message: ").append(error.message ?: "Aucun message").append("\n\n")
            val stack = error.stackTrace.take(8).joinToString("\n") { "    at $it" }
            append(stack)
        }
    }
}
