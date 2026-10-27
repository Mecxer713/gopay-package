package com.mecxer713.gopay.sample

import android.os.Bundle
import android.view.LayoutInflater
import android.view.View
import android.view.ViewGroup
import android.widget.Toast
import androidx.fragment.app.Fragment
import androidx.lifecycle.lifecycleScope
import com.mecxer713.gopay.sample.databinding.FragmentPaymentBinding
import kotlinx.coroutines.Dispatchers
import kotlinx.coroutines.launch
import kotlinx.coroutines.withContext

class PaymentFragment : Fragment() {

    private var _binding: FragmentPaymentBinding? = null
    private val binding get() = _binding!!

    override fun onCreateView(
        inflater: LayoutInflater,
        container: ViewGroup?,
        savedInstanceState: Bundle?
    ): View {
        _binding = FragmentPaymentBinding.inflate(inflater, container, false)
        return binding.root
    }

    override fun onViewCreated(view: View, savedInstanceState: Bundle?) {
        fun generateUniqueRef() = "REF-" + System.currentTimeMillis() + "-" + (1000..9999).random()

        val initialRef = generateUniqueRef()
        binding.etPaymentRef.setText(initialRef)
        binding.etCheckRef.setText(initialRef)

        binding.btnGenerateRef.setOnClickListener {
            val autoRef = generateUniqueRef()
            binding.etPaymentRef.setText(autoRef)
            binding.etCheckRef.setText(autoRef)
        }

        binding.btnInitPayment.setOnClickListener {
            val currentRef = binding.etPaymentRef.text?.toString()?.trim().orEmpty().ifEmpty {
                val newRef = generateUniqueRef()
                binding.etPaymentRef.setText(newRef)
                newRef
            }
            binding.etCheckRef.setText(currentRef)
            initPayment()
        }

        binding.btnCheckPayment.setOnClickListener {
            checkPayment()
        }
    }

    private fun initPayment() {
        val amountStr = binding.etPaymentAmount.text?.toString()?.trim() ?: ""
        val amount = amountStr.toDoubleOrNull()
        if (amount == null || amount <= 0) {
            Toast.makeText(requireContext(), "Veuillez entrer un montant valide", Toast.LENGTH_SHORT).show()
            return
        }
        val currency = binding.etPaymentCurrency.text?.toString()?.trim().orEmpty().ifEmpty { "CDF" }
        val phone = binding.etPaymentPhone.text?.toString()?.trim().orEmpty()
        val ref = binding.etPaymentRef.text?.toString()?.trim().orEmpty().ifEmpty {
            "REF-" + System.currentTimeMillis() + "-" + (1000..9999).random()
        }
        val userId = binding.etPaymentUserId.text?.toString()?.trim().takeUnless { it.isNullOrBlank() }

        val mainActivity = activity as? MainActivity ?: return
        mainActivity.hideKeyboard()
        val client = mainActivity.createClient()

        mainActivity.showLoading(true, "Appel de initPayment()...")

        lifecycleScope.launch {
            try {
                val start = System.currentTimeMillis()
                val response = withContext(Dispatchers.IO) {
                    client.initPayment(amount, currency, phone, ref, userId)
                }
                val duration = System.currentTimeMillis() - start
                mainActivity.displayResponse(
                    operation = "POST /api/v3/payment/init",
                    isSuccess = response.success,
                    statusText = if (response.success) "SUCCÈS (${duration}ms)" else "ÉCHEC (${duration}ms)",
                    summary = "TransID: ${response.transId ?: "N/A"} | Statut: ${response.transactionStatus ?: "N/A"}",
                    rawJson = response.raw.toString(2)
                )
            } catch (e: Exception) {
                mainActivity.displayError("POST /api/v3/payment/init", e)
            } finally {
                mainActivity.showLoading(false)
            }
        }
    }

    private fun checkPayment() {
        val ref = binding.etCheckRef.text?.toString()?.trim().orEmpty()
        if (ref.isEmpty()) {
            Toast.makeText(requireContext(), "Veuillez renseigner une référence", Toast.LENGTH_SHORT).show()
            return
        }

        val mainActivity = activity as? MainActivity ?: return
        mainActivity.hideKeyboard()
        val client = mainActivity.createClient()

        mainActivity.showLoading(true, "Appel de checkPayment($ref)...")

        lifecycleScope.launch {
            try {
                val start = System.currentTimeMillis()
                val response = withContext(Dispatchers.IO) {
                    client.checkPayment(ref)
                }
                val duration = System.currentTimeMillis() - start
                mainActivity.displayResponse(
                    operation = "GET /api/v3/payment/check/$ref",
                    isSuccess = response.success,
                    statusText = if (response.success) "SUCCÈS (${duration}ms)" else "ÉCHEC (${duration}ms)",
                    summary = "Statut: ${response.transactionStatus ?: response.state ?: "N/A"} | Montant: ${response.amount ?: "N/A"} ${response.currency ?: ""}",
                    rawJson = response.raw.toString(2)
                )
            } catch (e: Exception) {
                mainActivity.displayError("GET /api/v3/payment/check/$ref", e)
            } finally {
                mainActivity.showLoading(false)
            }
        }
    }

    override fun onDestroyView() {
        super.onDestroyView()
        _binding = null
    }
}
