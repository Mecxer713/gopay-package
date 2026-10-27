import 'package:gopay/gopay.dart';

Future<void> main() async {
  // 1. Configuration du client GoPAY avec vos identifiants marchands
  final gopay = GoPayClient(const GoPayConfig(
    baseUrl: 'https://gopay.gooomart.com',
    paymentApiKey: 'VOTRE_PAYMENT_API_KEY',
    paymentSecretKey: 'VOTRE_PAYMENT_SECRET_KEY',
    payoutApiKey: 'VOTRE_PAYOUT_API_KEY', // Optionnel si aucun transfert sortant
  ));

  print('=== Démonstration GoPAY Mobile Money ===');

  // 2. Initialisation d'une demande de paiement mobile money (CDF / USD)
  final paymentRef = 'REF-${DateTime.now().millisecondsSinceEpoch}';
  final paymentResponse = await gopay.initPayment(
    4000.0,
    'CDF',
    '+243990000000',
    paymentRef,
  );

  if (paymentResponse.success) {
    print('Paiement initié avec succès.');
    print('Trans ID: ${paymentResponse.transId}');
    print('Statut: ${paymentResponse.transactionStatus}');
  } else {
    print('Échec: ${paymentResponse.message} (${paymentResponse.errorCode})');
  }

  // 3. Vérification du statut de paiement
  final statusResponse = await gopay.checkPayment(paymentRef);
  print('Statut vérifié: ${statusResponse.transactionStatus}');

  // 4. Consultation du solde Payout
  final balanceResponse = await gopay.getPayoutBalance();
  if (balanceResponse.success) {
    print('Solde Payout: ${balanceResponse.balance} ${balanceResponse.currency}');
  }
}
