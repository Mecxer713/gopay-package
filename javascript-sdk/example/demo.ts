import { GoPayClient } from '../src';

async function runDemo() {
  // 1. Initialisez le client avec vos clés marchandes
  const gopay = new GoPayClient({
    baseUrl: 'https://gopay.gooomart.com',
    paymentApiKey: 'VOTRE_PAYMENT_API_KEY',
    paymentSecretKey: 'VOTRE_PAYMENT_SECRET_KEY',
    payoutApiKey: 'VOTRE_PAYOUT_API_KEY', // Optionnel si aucun transfert sortant
  });

  console.log('=== Démonstration GoPAY JavaScript / TypeScript SDK ===');

  try {
    // 2. Initier une demande de paiement (Mobile Money)
    const reference = `CMD-${Date.now()}`;
    const payment = await gopay.initPayment(4000, 'CDF', '+243825000000', reference);
    
    if (payment.success) {
      console.log('Paiement initié avec succès !');
      console.log(`Trans ID: ${payment.transId}`);
      console.log(`Statut: ${payment.transactionStatus}`);
    } else {
      console.log(`Échec: ${payment.message} (${payment.errorCode})`);
    }

    // 3. Vérifier le statut
    const check = await gopay.checkPayment(reference);
    console.log(`Statut vérifié: ${check.transactionStatus}`);
  } catch (error) {
    console.error('Erreur API GoPAY:', error);
  }
}

// Exécuter si appelé directement
if (require.main === module) {
  runDemo();
}
