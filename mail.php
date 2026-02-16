<?php
// Traitement du formulaire
$status = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $from    = trim($_POST['from'] ?? '');
    $to      = trim($_POST['to'] ?? '');
    $subject = trim($_POST['subject'] ?? '');
    $message = trim($_POST['message'] ?? '');

    // Validation minimale
    if (!filter_var($from, FILTER_VALIDATE_EMAIL)) {
        $status = "Expéditeur invalide";
    } elseif (!filter_var($to, FILTER_VALIDATE_EMAIL)) {
        $status = "Destinataire invalide";
    } elseif ($subject === '' || $message === '') {
        $status = "Sujet ou message vide";
    } else {
        $headers = [];
        $headers[] = "From: $from";
        $headers[] = "Reply-To: $from";
        $headers[] = "MIME-Version: 1.0";
        $headers[] = "Content-Type: text/plain; charset=UTF-8";

        $success = mail(
            $to,
            $subject,
            $message,
            implode("\r\n", $headers)
        );

        $status = $success ? "Mail envoyé" : "Échec d'envoi";
    }
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Test envoi mail</title>
    <style>
        body { font-family: Arial, sans-serif; max-width: 700px; margin: 40px auto; }
        label { display: block; margin-top: 15px; }
        input, textarea { width: 100%; padding: 8px; }
        textarea { height: 150px; }
        button { margin-top: 20px; padding: 10px 20px; }
        .status { margin-top: 20px; font-weight: bold; }
    </style>
</head>
<body>

<h1>Envoi de mail (test)</h1>

<form method="post">
    <label>Expéditeur (From)</label>
    <input type="email" name="from" required>

    <label>Destinataire (To)</label>
    <input type="email" name="to" required>

    <label>Sujet</label>
    <input type="text" name="subject" required>

    <label>Message</label>
    <textarea name="message" required></textarea>

    <button type="submit">Envoyer</button>
</form>

<?php if ($status): ?>
    <div class="status"><?= htmlspecialchars($status) ?></div>
<?php endif; ?>

</body>
</html>
