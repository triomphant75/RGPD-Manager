# Configuration de Mailtrap pour le Développement

## Table des matières
1. [Qu'est-ce que Mailtrap ?](#quest-ce-que-mailtrap-)
2. [Configuration actuelle](#configuration-actuelle)
3. [Comment ça fonctionne](#comment-ça-fonctionne)
4. [Visualiser les emails capturés](#visualiser-les-emails-capturés)
5. [Tester l'envoi d'emails](#tester-lenvoi-demails)
6. [Passer de Mailtrap (Dev) à Resend (Prod)](#passer-de-mailtrap-dev-à-resend-prod)
7. [Exemples d'utilisation](#exemples-dutilisation)
8. [FAQ](#faq)

---

## Qu'est-ce que Mailtrap ?

Mailtrap est un service de **test d'emails** qui **capture** tous les emails envoyés par votre application **sans les envoyer réellement** aux destinataires.

### Avantages pour le développement

✅ **Sécurité** : Aucun email n'est envoyé par erreur à de vrais utilisateurs
✅ **Aucune limite de destinataires** : Envoyez à n'importe quelle adresse email
✅ **Aucune vérification de domaine** : Pas besoin de configurer DNS
✅ **Interface visuelle** : Voir tous les emails capturés dans le dashboard
✅ **Analyse complète** : HTML, texte, pièces jointes, spam score, etc.
✅ **Gratuit** : 500 emails/mois gratuits

---

## Configuration actuelle

### Fichier `.env.local` (Développement)

```env
APP_ENV=dev
ENCRYPTION_KEY=932060996514c350cc6173ec8fcf97de47dc09fcc89bb543be052daaeafb76f2

###> symfony/mailer ###
# Configuration Mailtrap (Développement) - Capture les emails sans les envoyer
MAILER_DSN=smtp://04000119362b97:98809f19923855@sandbox.smtp.mailtrap.io:2525

# Configuration Resend (Production) - Décommenter pour la production
# MAILER_DSN=resend+api://re_GxcMxV1W_5uuCXbCNAVfAUCmbXNNFGsF4@default

MAILER_FROM_ADDRESS=noreply@rgpd.local
MAILER_FROM_NAME="RGPD Manager"
###< symfony/mailer ###
```

### Détails de la configuration

| Paramètre            | Valeur                                          |
|----------------------|-------------------------------------------------|
| Host                 | `sandbox.smtp.mailtrap.io`                      |
| Port                 | `2525`                                          |
| Username             | `04000119362b97`                                |
| Password             | `98809f19923855`                                |
| FROM Address         | `noreply@rgpd.local` (peut être n'importe quoi)|
| FROM Name            | `RGPD Manager`                                  |

---

## Comment ça fonctionne

### Flux d'envoi d'email en développement

```
[Application Symfony]
       ↓
   Envoie email à: dpo@example.com
       ↓
[Mailtrap SMTP]
       ↓
   Capture l'email (ne l'envoie PAS)
       ↓
[Dashboard Mailtrap]
       ↓
   Vous consultez l'email capturé
```

### Ce qui se passe

1. **Votre application** envoie un email (ex: à `dpo@example.com`)
2. **Mailtrap intercepte** l'email avant qu'il ne soit envoyé
3. **L'email est capturé** dans votre inbox Mailtrap
4. **Le destinataire réel ne reçoit RIEN** (protection contre les envois accidentels)
5. **Vous visualisez l'email** dans le dashboard Mailtrap

---

## Visualiser les emails capturés

### Accéder au dashboard Mailtrap

1. Allez sur https://mailtrap.io/signin
2. Connectez-vous avec votre compte
3. Dans le menu de gauche, cliquez sur **"Sandboxes"**
4. Sélectionnez **"My Sandbox"**
5. Vous verrez tous les emails capturés

### Informations disponibles pour chaque email

- ✉️ **Sujet** de l'email
- 👤 **FROM** (expéditeur)
- 👥 **TO** (destinataire)
- 📅 **Date et heure** d'envoi
- 🖼️ **Aperçu HTML** (rendu visuel)
- 📝 **Contenu texte**
- 📎 **Pièces jointes** (si présentes)
- 🔍 **En-têtes HTTP**
- ⚠️ **Spam Score** (vérification anti-spam)
- 📧 **Code source** (HTML brut)

---

## Tester l'envoi d'emails

### Commande de test Symfony

```bash
# Envoyer un email de test
php bin/console mailer:test dpo@example.com

# Avec un sujet personnalisé
php bin/console mailer:test --subject="Test de notification" dpo@example.com

# Avec une adresse FROM personnalisée
php bin/console mailer:test --from="test@example.com" dpo@example.com
```

### Vérification après l'envoi

1. Allez dans votre dashboard Mailtrap
2. Rafraîchissez la page
3. Vous devriez voir le nouvel email capturé (destinataire: dpo@example.com)
4. Cliquez dessus pour voir le contenu complet

---

## Passer de Mailtrap (Dev) à Resend (Prod)

### En développement (Mailtrap)

**Fichier `.env.local`** :

```env
MAILER_DSN=smtp://04000119362b97:98809f19923855@sandbox.smtp.mailtrap.io:2525
MAILER_FROM_ADDRESS=noreply@rgpd.local
```

### En production (Resend)

**Fichier `.env.prod` ou `.env.local` (sur le serveur de production)** :

```env
MAILER_DSN=resend+api://re_GxcMxV1W_5uuCXbCNAVfAUCmbXNNFGsF4@default
MAILER_FROM_ADDRESS=noreply@sainte-agnes.fr
```

⚠️ **Important** : Pour Resend en production, vous devez avoir vérifié un domaine. Voir [MAILER_RESEND_PRODUCTION.md](MAILER_RESEND_PRODUCTION.md).

---

## Exemples d'utilisation

### Exemple 1 : Envoyer une notification au DPO

```php
<?php

namespace App\Service;

use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;

class NotificationService
{
    public function __construct(
        private MailerInterface $mailer
    ) {
    }

    public function notifierDPO(string $dpoEmail, string $message): void
    {
        $email = (new Email())
            ->from('noreply@rgpd.local')
            ->to($dpoEmail)  // Ex: dpo@example.com
            ->subject('Nouvelle notification RGPD')
            ->html("<p>$message</p>");

        $this->mailer->send($email);

        // En dev avec Mailtrap : l'email est capturé dans le dashboard
        // En prod avec Resend : l'email est vraiment envoyé au DPO
    }
}
```

### Exemple 2 : Email avec template Twig

```php
<?php

namespace App\Service;

use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Component\Mailer\MailerInterface;

class EmailService
{
    public function __construct(
        private MailerInterface $mailer
    ) {
    }

    public function envoyerEmailConsentement(
        string $destinataire,
        string $nomUtilisateur
    ): void {
        $email = (new TemplatedEmail())
            ->from('noreply@rgpd.local')
            ->to($destinataire)
            ->subject('Demande de consentement RGPD')
            ->htmlTemplate('emails/consentement.html.twig')
            ->context([
                'nom' => $nomUtilisateur,
                'date' => new \DateTime(),
            ]);

        $this->mailer->send($email);
    }
}
```

### Exemple 3 : Email avec pièce jointe

```php
<?php

use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;

class RapportService
{
    public function __construct(
        private MailerInterface $mailer
    ) {
    }

    public function envoyerRapportPDF(string $destinataire, string $pdfPath): void
    {
        $email = (new Email())
            ->from('noreply@rgpd.local')
            ->to($destinataire)
            ->subject('Rapport RGPD mensuel')
            ->text('Veuillez trouver ci-joint le rapport RGPD du mois.')
            ->attachFromPath($pdfPath);

        $this->mailer->send($email);
    }
}
```

---

## FAQ

### Q: Les emails arrivent-ils vraiment aux destinataires avec Mailtrap ?

**R:** Non ! C'est tout l'intérêt de Mailtrap. Les emails sont capturés et **jamais envoyés** aux vraies adresses. C'est parfait pour le développement.

### Q: Puis-je envoyer à n'importe quelle adresse email ?

**R:** Oui ! Avec Mailtrap, vous pouvez utiliser n'importe quelle adresse :
- `dpo@example.com`
- `admin@example.com`
- `test@example.com`
- `fake@invalid.local`
- Etc.

Tous les emails seront capturés dans votre inbox Mailtrap.

### Q: Combien d'emails puis-je envoyer ?

**R:** Le plan gratuit de Mailtrap permet **500 emails/mois** dans les sandboxes. Largement suffisant pour le développement.

### Q: Comment passer en production ?

**R:** Il suffit de changer le `MAILER_DSN` dans votre fichier `.env.prod` pour utiliser Resend au lieu de Mailtrap. Voir [MAILER_RESEND_PRODUCTION.md](MAILER_RESEND_PRODUCTION.md).

### Q: Puis-je utiliser une vraie adresse FROM ?

**R:** Oui, mais ce n'est pas nécessaire en développement. Vous pouvez utiliser :
- `noreply@rgpd.local` (fictive)
- `test@example.com` (fictive)
- `contact@sainte-agnes.fr` (réelle, si vous l'avez)

Mailtrap accepte tout.

### Q: Les templates Twig fonctionnent-ils avec Mailtrap ?

**R:** Oui ! Mailtrap capture les emails exactement comme ils sont générés par Symfony, templates Twig inclus.

### Q: Comment tester le spam score ?

**R:** Dans le dashboard Mailtrap, cliquez sur un email capturé, puis allez dans l'onglet **"Spam Analysis"**. Vous verrez le score et les recommandations.

### Q: Puis-je partager mes emails de test avec mon équipe ?

**R:** Oui ! Dans Mailtrap, vous pouvez inviter des membres d'équipe à accéder à votre sandbox. Allez dans **"Access Rights"** dans les paramètres de la sandbox.

### Q: Combien de temps sont conservés les emails ?

**R:** Les emails dans les sandboxes Mailtrap sont conservés **indéfiniment** (jusqu'à ce que vous les supprimiez manuellement ou que vous atteigniez la limite de stockage).

---

## Commandes utiles

### Tester l'envoi d'email

```bash
# Test simple
php bin/console mailer:test dpo@example.com

# Avec plusieurs destinataires
php bin/console mailer:test dpo@example.com admin@example.com

# Avec options
php bin/console mailer:test \
  --from="custom@example.com" \
  --subject="Sujet personnalisé" \
  --body="Contenu du message" \
  dpo@example.com
```

### Vider le cache

```bash
php bin/console cache:clear
```

### Lister les transports configurés

```bash
php bin/console debug:config framework mailer
```

---

## Ressources

- 🌐 **Dashboard Mailtrap** : https://mailtrap.io/inboxes
- 📚 **Documentation Mailtrap** : https://help.mailtrap.io/
- 🔧 **Symfony Mailer Docs** : https://symfony.com/doc/current/mailer.html
- 📖 **Guide Resend (Production)** : [MAILER_RESEND_PRODUCTION.md](MAILER_RESEND_PRODUCTION.md)

---

## Récapitulatif

| Environnement  | Service  | Configuration                                                       | Emails envoyés ? |
|----------------|----------|---------------------------------------------------------------------|------------------|
| Développement  | Mailtrap | `smtp://...@sandbox.smtp.mailtrap.io:2525`                         | ❌ Non (capturés)|
| Production     | Resend   | `resend+api://API_KEY@default`                                     | ✅ Oui (réels)   |

---

**Dernière mise à jour** : 2 novembre 2025
**Version** : 1.0
