<?php

/**
 * Récupère les taux de change depuis le cache BDD ou l'API (max 1 appel API par 24h)
 *
 * @param PDO    $pdo             Connexion PDO (définie dans db.php)
 * @param string $exchangeApiKey  Clé API de https://www.exchangerate-api.com
 * @param string $base            Devise de base (EUR par défaut)
 * @param int    $cacheHours      Durée de validité du cache en heures (24 par défaut)
 * @return array                  Tableau des taux ex: ['USD' => 1.085, 'PLN' => 4.32, ...]
 */
function getExchangeRatesCached(
    PDO $pdo,
    string $exchangeApiKey,
    string $base = 'EUR',
    int $cacheHours = 24
): array {
    // 1. Vérifier si cache valide en BDD
    $stmt = $pdo->prepare("
        SELECT rates, fetched_at, expires_at
        FROM cache_exchange_rates
        WHERE base_currency = :base
    ");
    $stmt->execute(['base' => $base]);
    $row = $stmt->fetch();

    $now = new DateTime();
    if ($row && new DateTime($row['expires_at']) > $now) {
        $cachedRates = json_decode($row['rates'], true);
        return is_array($cachedRates) ? $cachedRates : [];
    }

    // 2. Cache périmé ou absent → appel API
    $url = "https://v6.exchangerate-api.com/v6/{$exchangeApiKey}/latest/{$base}";
    $response = @file_get_contents($url);

    if ($response === false) {
        // Fallback : ancien cache si dispo
        if ($row) {
            error_log("ExchangeRate-API indisponible - utilisation ancien cache du " . $row['fetched_at']);
            $cachedRates = json_decode($row['rates'], true);
            return is_array($cachedRates) ? $cachedRates : [];
        }
        // Fallback ultime : taux approximatifs (à mettre à jour manuellement tous les mois)
        return [
            'EUR' => 1.0,
            'USD' => 1.09,
            'PLN' => 4.30,
            'JPY' => 165.0,
            'CNY' => 7.80,
            'KRW' => 1480.0,
            'RUB' => 105.0,
        ];
    }

    $data = json_decode($response, true);
    if (!isset($data['result']) || $data['result'] !== 'success' || !isset($data['conversion_rates'])) {
        error_log("Erreur ExchangeRate-API : " . ($data['error-type'] ?? 'inconnue'));
        // Fallback ancien cache
        return $row ? (json_decode($row['rates'], true) ?? []) : [];
    }

    $rates = $data['conversion_rates'];

    // 3. Enregistrer / mettre à jour en BDD (upsert)
    $fetchedAt = $now->format('Y-m-d H:i:s');
    $expiresAt = (clone $now)->modify("+{$cacheHours} hours")->format('Y-m-d H:i:s');

    $stmtUpsert = $pdo->prepare("
        INSERT INTO cache_exchange_rates 
            (base_currency, rates, fetched_at, expires_at, source)
        VALUES 
            (:base, :rates, :fetched, :expires, :source)
        ON DUPLICATE KEY UPDATE
            rates = :rates,
            fetched_at = :fetched,
            expires_at = :expires,
            source = :source
    ");
    $stmtUpsert->execute([
        'base'    => $base,
        'rates'   => json_encode($rates),
        'fetched' => $fetchedAt,
        'expires' => $expiresAt,
        'source'  => 'exchangerate-api-v6',
    ]);

    return $rates;
}