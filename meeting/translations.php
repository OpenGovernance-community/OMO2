<?php
require_once dirname(__DIR__) . '/common/translation_bundles.php';

function meetingT(string $key, array $replace = []): string
{
    static $bundle = null;
    static $sourceLang = null;
    if ($sourceLang === null) {
        $texts = [
            'title' => 'Prise de rendez-vous', 'settings' => 'Paramètres de prise de rendez-vous',
            'enable' => 'Activer la prise de rendez-vous', 'slug' => 'Nom public unique',
            'slug_hint' => '3 à 48 caractères : lettres minuscules, chiffres et tirets. Ce nom ne modifie pas votre agenda externe.',
            'slug_invalid' => 'Choisissez un nom de 3 à 48 caractères, commençant par une lettre, avec uniquement des lettres, des chiffres et des tirets.',
            'slug_taken' => 'Ce nom est déjà utilisé.', 'slug_available' => 'Ce nom est disponible.',
            'calendar' => 'Calendrier de destination', 'calendar_choose' => 'Choisir un calendrier',
            'calendar_hint' => 'Seuls les agendas connectés dont le droit de création est confirmé peuvent être sélectionnés.',
            'readonly' => 'Lecture seule ou droits non vérifiables', 'calendar_invalid' => 'Choisissez un agenda actif vous appartenant et autorisant la création d’événements.',
            'hours' => 'Horaires hebdomadaires', 'open' => 'Ouvert', 'start' => 'Début', 'end' => 'Fin',
            'pause' => 'Pause de midi', 'pause_start' => 'Début de pause', 'pause_end' => 'Fin de pause',
            'hours_invalid' => 'Vérifiez les horaires : le début doit précéder la fin, la pause doit rester à l’intérieur, par pas de 30 minutes.',
            'monday' => 'Lundi', 'tuesday' => 'Mardi', 'wednesday' => 'Mercredi', 'thursday' => 'Jeudi',
            'friday' => 'Vendredi', 'saturday' => 'Samedi', 'sunday' => 'Dimanche',
            'january' => 'Janvier', 'february' => 'Février', 'march' => 'Mars', 'april' => 'Avril', 'may' => 'Mai', 'june' => 'Juin',
            'july' => 'Juillet', 'august' => 'Août', 'september' => 'Septembre', 'october' => 'Octobre', 'november' => 'Novembre', 'december' => 'Décembre',
            'save' => 'Enregistrer', 'saved' => 'Paramètres enregistrés.', 'saving' => 'Enregistrement…',
            'link' => 'Votre lien public', 'copy' => 'Copier le lien', 'copied' => 'Lien copié.',
            'timezone' => 'Horaires Europe/Zurich. Durée : 1 heure. Départs toutes les 30 minutes.',
            'disabled' => 'Cette page de rendez-vous n’est pas disponible.', 'storage' => 'Le service de rendez-vous est indisponible. Vérifiez la mise à jour de la base.',
            'unavailable' => 'Les disponibilités ne peuvent pas être vérifiées pour le moment. Réessayez plus tard.',
            'busy' => 'Une autre opération est en cours. Réessayez dans quelques instants.',
            'csrf' => 'Rechargez cette page avant de réessayer.', 'expired' => 'Cette demande a expiré. Recommencez votre sélection.',
            'date_invalid' => 'Choisissez une date valide dans les douze prochains mois.',
            'guest_invalid' => 'Renseignez votre nom, une adresse e-mail valide et le motif du rendez-vous.',
            'slot_taken' => 'Ce créneau n’est plus disponible. Choisissez un autre horaire.',
            'write_failed' => 'Le calendrier a refusé la réservation. Aucun rendez-vous n’a été confirmé.',
            'pending' => 'La création doit encore être vérifiée. Réessayez avec cette même confirmation pour éviter un doublon ; ne créez pas une autre demande.',
            'rate' => 'Trop de demandes. Patientez quelques minutes avant de réessayer.',
            'previous' => 'Mois précédent', 'next' => 'Mois suivant', 'free' => 'Libre', 'partial' => 'Partiellement occupé',
            'full' => 'Complet', 'closed' => 'Fermé ou passé', 'occupied' => 'Indisponible',
            'select_day' => 'Choisissez un jour', 'select_time' => 'Choisissez votre horaire',
            'name' => 'Votre nom', 'email' => 'Votre adresse e-mail', 'reason' => 'Motif du rendez-vous',
            'review' => 'Vérifier ma réservation', 'summary' => 'Récapitulatif', 'confirm' => 'Confirmer le rendez-vous',
            'back' => 'Modifier ma sélection', 'confirmed' => 'Votre rendez-vous est confirmé.',
            'email_sent' => 'Un e-mail de confirmation avec une fiche calendrier vous a été envoyé.',
            'email_failed' => 'Le rendez-vous est bien réservé, mais l’e-mail n’a pas pu être envoyé. Vous pouvez télécharger la fiche ou relancer l’envoi.',
            'retry_email' => 'Relancer l’envoi de l’e-mail', 'download' => 'Ajouter à mon agenda (.ics)',
            'mail_body' => 'Votre rendez-vous du {date} (Europe/Zurich), pour une durée d’une heure, est confirmé. La fiche calendrier est jointe à cet e-mail.',
            'event_title' => 'Rendez-vous : {owner} / {guest}',
            'privacy' => 'Votre nom, votre e-mail et le motif seront transmis à la personne avec qui vous prenez rendez-vous.',
            'with' => 'Rendez-vous avec {name}', 'wait' => 'Vérification en cours…',
            'book_with' => 'Réserver avec {name}', 'today' => "Aujourd’hui",
            'share_title' => 'Prendre rendez-vous avec {name}',
            'share_description' => 'Choisissez directement un créneau disponible pour un rendez-vous d\'une heure avec {name}.',
            'share_image_alt' => 'Photo de {name}',
            'duration_short' => '1 heure', 'interval_short' => 'Toutes les 30 min',
            'tagline' => 'Un moment pour échanger', 'tagline_hint' => 'Choisissez ce qui vous convient.',
            'step_time' => 'Le créneau', 'step_details' => 'Vos informations', 'step_confirm' => 'Confirmation',
            'steps' => 'Étapes de la réservation', 'availability' => 'Disponibilités',
            'closed_short' => 'Fermé',
            'day_hint' => 'Sélectionnez une date dans le calendrier pour découvrir les horaires disponibles.',
            'no_slots' => 'Aucun créneau disponible pour cette journée.',
            'change_time' => 'Changer de créneau',
            'reason_placeholder' => 'De quoi souhaitez-vous parler ?',
            'summary_hint' => 'Vérifiez les détails de votre rendez-vous avant de confirmer.',
            'date_time' => 'Date et heure', 'your_details' => 'Vos coordonnées',
            'confirmed_heading' => 'Rendez-vous confirmé',
            'confirmed_hint' => 'Votre rendez-vous avec {name} est bien enregistré.',
            'powered_by' => 'Powered by',
        ];
        $sourceLang = [];
        foreach ($texts as $id => $text) { $sourceLang[$id] = ['text' => $text, 'context' => 'Meeting booking: ' . $id]; }
        $bundle = loadTranslationBundle('meeting', translationBundleResolveRequestLocale('lang', translationBundleGetSupportedLocales(), 'fr'), $sourceLang);
    }
    return t($key, $replace, $bundle, $sourceLang);
}
function meetingEscape($value): string { return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8'); }
function meetingWeekdayKeys(): array { return [1 => 'monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'sunday']; }
