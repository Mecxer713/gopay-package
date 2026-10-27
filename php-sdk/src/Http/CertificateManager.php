<?php

declare(strict_types=1);

namespace Mecxer713\GoPay\Http;

use Mecxer713\GoPay\Exception\GoPayException;
use RuntimeException;

class CertificateManager
{
    private const CERT_URL = 'https://curl.se/ca/cacert.pem';
    private const CERT_NAME = 'api-ca.pem';
    private const MAX_AGE_SECONDS = 31536000; // 1 an (365 jours)

    /**
     * Retourne le chemin vers le fichier du certificat.
     * Le télécharge et le vérifie si nécessaire.
     *
     * @return string
     * @throws GoPayException
     */
    public function getCertificatePath(): string
    {
        $certPath = $this->resolveCertificatePath();

        // Créer le dossier s'il n'existe pas
        $dir = dirname($certPath);
        if (!is_dir($dir)) {
            if (!@mkdir($dir, 0755, true) && !is_dir($dir)) {
                throw new GoPayException("Impossible de créer le répertoire des certificats: {$dir}");
            }
        }

        // Vérifie si le certificat existe et s'il est valide
        if ($this->shouldDownloadCertificate($certPath)) {
            $this->downloadCertificate($certPath);
        }

        return $certPath;
    }

    /**
     * Détermine dynamiquement le chemin de stockage du certificat.
     * Privilégie storage_path() de Laravel, sinon recherche la racine du projet.
     *
     * @return string
     */
    private function resolveCertificatePath(): string
    {
        // 1. Si on est dans Laravel, utiliser storage_path()
        if (function_exists('storage_path')) {
            return storage_path('certificates' . DIRECTORY_SEPARATOR . self::CERT_NAME);
        }

        // 2. Sinon, essayer de trouver la racine du projet en remontant depuis ce fichier
        $currentDir = __DIR__;
        
        $maxDepth = 10;
        $rootDir = $currentDir;
        
        for ($i = 0; $i < $maxDepth; $i++) {
            if (file_exists($rootDir . DIRECTORY_SEPARATOR . 'composer.json')) {
                // On s'assure qu'on n'est pas juste dans vendor/mecxer713/gopay-php
                // Si on a un dossier vendor adjacent, c'est probablement la racine de l'application
                if (is_dir($rootDir . DIRECTORY_SEPARATOR . 'vendor')) {
                    break;
                }
            }
            
            $parentDir = dirname($rootDir);
            if ($parentDir === $rootDir) { // Arrivé à la racine du système de fichiers
                $rootDir = dirname($currentDir, 4); // Repli: vendor/mecxer713/gopay-php/src/Http -> racine
                break;
            }
            $rootDir = $parentDir;
        }

        return rtrim($rootDir, '/\\') . DIRECTORY_SEPARATOR . 'storage' . DIRECTORY_SEPARATOR . 'certificates' . DIRECTORY_SEPARATOR . self::CERT_NAME;
    }

    /**
     * Vérifie si le certificat doit être téléchargé.
     *
     * @param string $path
     * @return bool
     */
    private function shouldDownloadCertificate(string $path): bool
    {
        if (!file_exists($path)) {
            return true;
        }

        $content = @file_get_contents($path);
        if ($content === false) {
            return true;
        }

        // Vérifier que c'est un certificat valide
        if (!@openssl_x509_parse($content)) {
            return true;
        }

        // Vérifier l'âge du certificat (renouveler après 1 an)
        $mtime = filemtime($path);
        if ($mtime !== false && (time() - $mtime) > self::MAX_AGE_SECONDS) {
            return true;
        }

        return false;
    }

    /**
     * Télécharge le certificat depuis une source fiable.
     *
     * @param string $path
     * @throws GoPayException
     */
    private function downloadCertificate(string $path): void
    {
        $options = [
            'http' => [
                'method' => 'GET',
                'header' => "User-Agent: GoPay-PHP-SDK\r\n",
                'timeout' => 15,
            ]
        ];
        $context = stream_context_create($options);

        $content = @file_get_contents(self::CERT_URL, false, $context);

        if ($content === false) {
            $error = error_get_last();
            $msg = $error['message'] ?? 'Erreur réseau inconnue';
            throw new GoPayException("Échec du téléchargement du certificat SSL depuis " . self::CERT_URL . " : " . $msg);
        }

        // Vérification de l'intégrité
        if (!@openssl_x509_parse($content)) {
            throw new GoPayException("Le certificat téléchargé est corrompu ou invalide.");
        }

        // Sauvegarde atomique
        $tempPath = $path . '.tmp.' . uniqid();
        if (@file_put_contents($tempPath, $content) === false) {
            throw new GoPayException("Impossible d'écrire le certificat dans: {$path}");
        }

        // Restreindre les permissions (lecture/écriture pour le propriétaire uniquement)
        @chmod($tempPath, 0600);

        if (!@rename($tempPath, $path)) {
            @unlink($tempPath);
            throw new GoPayException("Impossible de renommer le fichier temporaire du certificat.");
        }
    }
}
