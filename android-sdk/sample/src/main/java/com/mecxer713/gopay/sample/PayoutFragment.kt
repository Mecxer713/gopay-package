package com.mecxer713.gopay.sample

import android.os.Bundle
import android.view.LayoutInflater
import android.view.View
import android.view.ViewGroup
import android.widget.Toast
import androidx.fragment.app.Fragment
import androidx.lifecycle.lifecycleScope
import com.mecxer713.gopay.GoPayResponse
import com.mecxer713.gopay.sample.databinding.FragmentPayoutBinding
import kotlinx.coroutines.Dispatchers
import kotlinx.coroutines.launch
import kotlinx.coroutines.withContext
import org.json.JSONArray
import org.json.JSONObject

class PayoutFragment : Fragment() {

    private var _binding: FragmentPayoutBinding? = null
    private val binding get() = _binding!!

    override fun onCreateView(
        inflater: LayoutInflater,
        container: ViewGroup?,
        savedInstanceState: Bundle?
    ): View {
        _binding = FragmentPayoutBinding.inflate(inflater, container, false)
        return binding.root
    }

    override fun onViewCreated(view: View, savedInstanceState: Bundle?) {
        super.onViewCreated(view, savedInstanceState)

        binding.btnGetPayoutBalance.setOnClickListener {
            getPayoutBalance()
        }

        binding.btnGetPayoutTransfers.setOnClickListener {
            getPayoutTransfers()
        }

        binding.btnSendPayoutTransfer.setOnClickListener {
            sendPayoutTransfer()
        }

        binding.btnGetTransferStatus.setOnClickListener {
            getTransferStatus()
        }

        binding.btnDeleteTransfer.setOnClickListener {
            deleteTransfer()
        }
    }

    private fun getPayoutBalance() {
        val mainActivity = activity as? MainActivity ?: return
        val client = mainActivity.createClient()

        mainActivity.showLoading(true, "Appel de getPayoutBalance()...")
        lifecycleScope.launch {
            try {
                val start = System.currentTimeMillis()
                val response = withContext(Dispatchers.IO) {
                    client.getPayoutBalance()
                }
                val duration = System.currentTimeMillis() - start
                mainActivity.displayResponse(
                    operation = "GET /api/payout/v3/balance",
                    isSuccess = response.success,
                    statusText = if (response.success) "SUCCÈS (${duration}ms)" else "ÉCHEC (${duration}ms)",
                    summary = "Solde: ${response.balance ?: "N/A"} ${response.currency ?: ""}",
                    rawJson = response.raw.toString(2)
                )
            } catch (e: Exception) {
                mainActivity.displayError("GET /api/payout/v3/balance", e)
            } finally {
                mainActivity.showLoading(false)
            }
        }
    }

    private fun getPayoutTransfers() {
        val mainActivity = activity as? MainActivity ?: return
        val client = mainActivity.createClient()

        mainActivity.showLoading(true, "Appel de getPayoutTransfers()...")
        lifecycleScope.launch {
            try {
                val start = System.currentTimeMillis()
                val rawResult = withContext(Dispatchers.IO) {
                    client.getPayoutTransfers()
                }
                val duration = System.currentTimeMillis() - start

                val formattedJson = when (rawResult) {
                    is JSONObject -> rawResult.toString(2)
                    is JSONArray -> rawResult.toString(2)
                    else -> rawResult.toString()
                }

                mainActivity.displayResponse(
                    operation = "GET /api/payout/v3/transfer",
                    isSuccess = true,
                    statusText = "SUCCÈS (${duration}ms)",
                    summary = "Historique récupéré avec succès",
                    rawJson = formattedJson
                )
            } catch (e: Exception) {
                mainActivity.displayError("GET /api/payout/v3/transfer", e)
            } finally {
                mainActivity.showLoading(false)
            }
        }
    }

    private fun sendPayoutTransfer() {
        val amountStr = binding.etPayoutAmount.text?.toString()?.trim() ?: ""
        val amount = amountStr.toDoubleOrNull()
        if (amount == null || amount <= 0) {
            Toast.makeText(requireContext(), "Veuillez entrer un montant valide", Toast.LENGTH_SHORT).show()
            return
        }

        val currency = binding.etPayoutCurrency.text?.toString()?.trim().orEmpty().ifEmpty { "CDF" }
        val phones = binding.etPayoutPhones.text?.toString()
            ?.split(",")
            ?.map { it.trim() }
            ?.filter { it.isNotEmpty() }
            ?: emptyList()

        if (phones.isEmpty()) {
            Toast.makeText(requireContext(), "Veuillez renseigner au moins un numéro", Toast.LENGTH_SHORT).show()
            return
        }

        val refs = binding.etPayoutRefs.text?.toString()
            ?.split(",")
            ?.map { it.trim() }
            ?.filter { it.isNotEmpty() }
            ?: emptyList()

        val mainActivity = activity as? MainActivity ?: return
        val client = mainActivity.createClient()

        mainActivity.showLoading(true, "Appel de sendPayoutTransfer()...")
        lifecycleScope.launch {
            try {
                val start = System.currentTimeMillis()
                val response: GoPayResponse = withContext(Dispatchers.IO) {
                    client.sendPayoutTransfer(amount, currency, phones, refs)
                }
                val duration = System.currentTimeMillis() - start
                mainActivity.displayResponse(
                    operation = "POST /api/payout/v3/transfer",
                    isSuccess = response.success,
                    statusText = if (response.success) "SUCCÈS (${duration}ms)" else "ÉCHEC (${duration}ms)",
                    summary = "TransID: ${response.transId ?: "N/A"} | Message: ${response.message ?: ""}",
                    rawJson = response.raw.toString(2)
                )
            } catch (e: Exception) {
                mainActivity.displayError("POST /api/payout/v3/transfer", e)
            } finally {
                mainActivity.showLoading(false)
            }
        }
    }

    private fun getTransferStatus() {
        val transId = binding.etPayoutTransId.text?.toString()?.trim().orEmpty()
        if (transId.isEmpty()) {
            Toast.makeText(requireContext(), "Veuillez renseigner un ID ou référence", Toast.LENGTH_SHORT).show()
            return
        }

        val mainActivity = activity as? MainActivity ?: return
        val client = mainActivity.createClient()

        mainActivity.showLoading(true, "Appel de getPayoutTransferStatus($transId)...")
        lifecycleScope.launch {
            try {
                val start = System.currentTimeMillis()
                val response = withContext(Dispatchers.IO) {
                    client.getPayoutTransferStatus(transId)
                }
                val duration = System.currentTimeMillis() - start
                mainActivity.displayResponse(
                    operation = "GET /api/payout/v3/transfer/$transId",
                    isSuccess = response.success,
                    statusText = if (response.success) "SUCCÈS (${duration}ms)" else "ÉCHEC (${duration}ms)",
                    summary = "Statut: ${response.transactionStatus ?: response.state ?: "N/A"} | TransId: ${response.transId ?: transId}",
                    rawJson = response.raw.toString(2)
                )
            } catch (e: Exception) {
                mainActivity.displayError("GET /api/payout/v3/transfer/$transId", e)
            } finally {
                mainActivity.showLoading(false)
            }
        }
    }

    private fun deleteTransfer() {
        val transId = binding.etPayoutTransId.text?.toString()?.trim().orEmpty()
        if (transId.isEmpty()) {
            Toast.makeText(requireContext(), "Veuillez renseigner un Trans ID", Toast.LENGTH_SHORT).show()
            return
        }

        val mainActivity = activity as? MainActivity ?: return
        val client = mainActivity.createClient()

        mainActivity.showLoading(true, "Appel de deletePayoutTransfer($transId)...")
        lifecycleScope.launch {
            try {
                val start = System.currentTimeMillis()
                val response = withContext(Dispatchers.IO) {
                    client.deletePayoutTransfer(transId)
                }
                val duration = System.currentTimeMillis() - start
                mainActivity.displayResponse(
                    operation = "DELETE /api/payout/v3/transfer/$transId",
                    isSuccess = response.success,
                    statusText = if (response.success) "SUCCÈS (${duration}ms)" else "ÉCHEC (${duration}ms)",
                    summary = "Message: ${response.message ?: "Transfert supprimé"}",
                    rawJson = response.raw.toString(2)
                )
            } catch (e: Exception) {
                mainActivity.displayError("DELETE /api/payout/v3/transfer/$transId", e)
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
