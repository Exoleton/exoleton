<?php

require_once __DIR__ . '/../../../vendor/autoload.php'; // Chemin vers vendor depuis admin/utils/
require_once __DIR__ . '/../../../db.php';               // $pdo
require_once __DIR__ . '/../../../utils.php';            // getExchangeRatesCached

use OpenAI;

/**
 * Optimise UN produit exosquelette (FR) : traductions, meta SEO, alt_text, prix local, keywords, slug
 * Appelé lors de la création/mise à jour produit dans l'admin
 *
 * @param PDO    $pdo             Connexion PDO
 * @param string $texteFr         Description complète en français
 * @param string $nomProduit      Nom produit (ex: "Exosquelette SportBoost Endurance")
 * @param float  $prixFr          Prix TTC en EUR
 * @param string $openaiApiKey    Clé OpenAI (sk-proj-...)
 * @param string $exchangeApiKey  Clé ExchangeRate-API
 * @param string $model           Modèle OpenAI ('gpt-4o-mini' ou 'gpt-4o')
 * @return array                  Tableau par langue avec champs optimisés
 */
function optimiserProduitExosqueletteOpenAI(
    PDO $pdo,
    string $texteFr,
    string $nomProduit,
    float $prixFr,
    string $openaiApiKey,
    string $exchangeApiKey,
    string $model = 'gpt-4o-mini'
): array {
    // 1. Récupérer taux avec cache (EUR base)
    $devises = [
        'en' => 'USD',
        'de' => 'EUR',
        'it' => 'EUR',
        'es' => 'EUR',
        'pt' => 'EUR', // Change en 'BRL' si focus Brésil
        'nl' => 'EUR',
        'pl' => 'PLN',
        'ja' => 'JPY',
        'zh' => 'CNY',
        'ko' => 'KRW',
        'ru' => 'RUB',
    ];

    $rates = getExchangeRatesCached($pdo, $exchangeApiKey);

    $prixBruts = [];
    foreach ($devises as $code => $dev) {
        $taux = $rates[$dev] ?? 1.0;
        $prixBruts[$code] = round($prixFr * $taux, 2);
    }

    // 2. Client OpenAI
    $client = OpenAI::client($openaiApiKey);

    // Tons locaux par langue (identique au guide)
    $langues = [
        'en' => 'Anglais – ton professionnel, technique, orienté bénéfices santé/industrie, style US/UK B2B/B2C',
        'de' => 'Allemand – ton précis, factuel, qualité/certifications en avant (style DACH médical/industriel)',
        'it' => 'Italien – ton chaleureux, humain, focus rééducation et bien-être',
        'es' => 'Espagnol – ton accessible, enthousiaste, adapté Espagne + Amérique latine (rééducation, autonomie)',
        'pt' => 'Portugais – ton convivial, proche, focus Portugal/EU (ou Brésil si adapté)',
        'nl' => 'Néerlandais – ton direct, pragmatique, avantages concrets (mobilité, travail lourd)',
        'pl' => 'Polonais – ton dynamique, vendeur, emphase sur innovation et prix/performance',
        'ja' => 'Japonais – ton poli, respectueux, détaillé (technologie avancée, rééducation, certifications)',
        'zh' => 'Chinois simplifié – ton moderne, lifestyle + médical, attractif pour hôpitaux/centres',
        'ko' => 'Coréen – ton poli, jeune, high-tech, shopping-friendly (rééducation, seniors, industrie)',
        'ru' => 'Russe – ton expressif, descriptif, focus sur fiabilité et force assistée',
    ];

    // Prompt (corrigé, même que dans le PDF)
    $prompt = "Expert e-commerce médical/industriel 2026 – exosquelettes.\n\n";
    $prompt .= "Produit : « {$nomProduit} »\nDescription FR :\n« {$texteFr} »\nPrix EUR : {$prixFr} €\n\n";
    $prompt .= "Pour CHAQUE langue, génère :\n";
    $prompt .= "1. description : traduction marketing/technique adaptée (longueur similaire, jargon localisé)\n";
    $prompt .= "2. meta_title : 50–60 caractères, keyword principal devant\n";
    $prompt .= "3. meta_description : 140–160 caractères, bénéfices + CTA + keywords\n";
    $prompt .= "4. alt_text : 80–125 caractères, descriptif image SEO\n";
    $prompt .= "5. prix_local : prix attractif en devise locale (arrondi vendeur .99/.00/.90, format local)\n";
    $prompt .= "6. keywords : array JSON de 6–8 mots-clés SEO locaux\n";
    $prompt .= "7. slug : slug URL SEO court (minuscules, tirets)\n\n";
    $prompt .= "Prix convertis bruts :\n";
    foreach ($prixBruts as $code => $p) {
        $prompt .= "- {$code} ({$devises[$code]}) : {$p}\n";
    }
    $prompt .= "\nRègles : Localise culturellement (précis DE, poli JA/KO/ZH, direct EN/NL...). Prix attractif, pas exact brut. JSON valide seulement !\n";
    $prompt .= "Output JSON strict :\n{\n";
    foreach (array_keys($langues) as $code) {
        $prompt .= "  \"{$code}\": {\n    \"description\": \"\",\n    \"meta_title\": \"\",\n    \"meta_description\": \"\",\n    \"alt_text\": \"\",\n    \"prix_local\": \"\",\n    \"keywords\": [],\n    \"slug\": \"\"\n  },\n";
    }
    $prompt .= "}\n";

    try {
        $response = $client->chat()->create([
            'model' => $model,
            'messages' => [['role' => 'user', 'content' => $prompt]],
            'temperature' => 0.2,
            'max_tokens' => 5000,
        ]);

        $json = trim($response->choices[0]->message->content ?? '');
        $data = json_decode($json, true);

        if (json_last_error() !== JSON_ERROR_NONE || !is_array($data)) {
            throw new Exception("JSON invalide d'OpenAI : " . json_last_error_msg());
        }

        return $data;
    } catch (Exception $e) {
        error_log("OpenAI API erreur : " . $e->getMessage());
        // Fallback basique
        $result = [];
        foreach ($langues as $code => $_) {
            $result[$code] = [
                'description' => "Erreur : {$texteFr}",
                'meta_title' => $nomProduit,
                'meta_description' => "Erreur optimisation",
                'alt_text' => "Image exosquelette - erreur",
                'prix_local' => number_format($prixBruts[$code], 2, '.', ',') . ' ' . $devises[$code],
                'keywords' => [],
                'slug' => strtolower(str_replace(' ', '-', $nomProduit))
            ];
        }
        return $result;
    }
}