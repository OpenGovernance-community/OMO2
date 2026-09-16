<?php
require_once dirname(__DIR__) . '/common/translation_bundles.php';

function meetingT(string $key, array $replace = []): string
{
    static $bundle = null;
    static $sourceLang = null;
    if ($sourceLang === null) {
        $texts = [
            'title' => 'Prise de rendez-vous', 'settings' => 'Parametres de prise de rendez-vous',
            'enable' => 'Activer la prise de rendez-vous', 'slug' => 'Nom public unique',
            'slug_hint' => '3 a 48 caracteres : lettres minuscules, chiffres et tirets. Ce nom ne modifie pas votre agenda externe.',
            'slug_invalid' => 'Choisissez un nom de 3 a 48 caracteres, commencant par une lettre, avec uniquement lettres, chiffres et tirets.',
            'slug_taken' => 'Ce nom est deja utilise.', 'slug_available' => 'Ce nom est disponible.',
            'calendar' => 'Calendrier de destination', 'calendar_choose' => 'Choisir un calendrier',
            'calendar_hint' => 'Seuls les agendas connectes dont le droit de creation est confirme sont selectionnables.',
            'readonly' => 'Lecture seule ou droits non verifiables', 'calendar_invalid' => 'Choisissez un agenda actif vous appartenant et autorisant la creation d evenements.',
            'hours' => 'Horaires hebdomadaires', 'open' => 'Ouvert', 'start' => 'Debut', 'end' => 'Fin',
            'pause' => 'Pause de midi', 'pause_start' => 'Debut de pause', 'pause_end' => 'Fin de pause',
            'hours_invalid' => 'Verifiez les horaires : debut avant fin, pause strictement a l interieur, par pas de 30 minutes.',
            'monday' => 'Lundi', 'tuesday' => 'Mardi', 'wednesday' => 'Mercredi', 'thursday' => 'Jeudi',
            'friday' => 'Vendredi', 'saturday' => 'Samedi', 'sunday' => 'Dimanche',
            'january' => 'Janvier', 'february' => 'Fevrier', 'march' => 'Mars', 'april' => 'Avril', 'may' => 'Mai', 'june' => 'Juin',
            'july' => 'Juillet', 'august' => 'Aout', 'september' => 'Septembre', 'october' => 'Octobre', 'november' => 'Novembre', 'december' => 'Decembre',
            'save' => 'Enregistrer', 'saved' => 'Parametres enregistres.', 'saving' => 'Enregistrement...',
            'link' => 'Votre lien public', 'copy' => 'Copier le lien', 'copied' => 'Lien copie.',
            'timezone' => 'Horaires Europe/Zurich. Duree : 1 heure. Departs toutes les 30 minutes.',
            'disabled' => 'Cette page de rendez-vous n est pas disponible.', 'storage' => 'Le service de rendez-vous est indisponible. Verifiez la mise a jour de la base.',
            'unavailable' => 'Les disponibilites ne peuvent pas etre verifiees pour le moment. Reessayez plus tard.',
            'busy' => 'Une autre operation est en cours. Reessayez dans quelques instants.',
            'csrf' => 'Rechargez cette page avant de reessayer.', 'expired' => 'Cette demande a expire. Recommencez votre selection.',
            'date_invalid' => 'Choisissez une date valide dans les douze prochains mois.',
            'guest_invalid' => 'Renseignez votre nom, une adresse e-mail valide et le motif du rendez-vous.',
            'slot_taken' => 'Ce creneau n est plus disponible. Choisissez un autre horaire.',
            'write_failed' => 'Le calendrier a refuse la reservation. Aucun rendez-vous n a ete confirme.',
            'pending' => 'La creation doit encore etre verifiee. Reessayez cette meme confirmation pour eviter un doublon ; ne creez pas une autre demande.',
            'rate' => 'Trop de demandes. Patientez quelques minutes avant de reessayer.',
            'previous' => 'Mois precedent', 'next' => 'Mois suivant', 'free' => 'Libre', 'partial' => 'Partiellement occupe',
            'full' => 'Complet', 'closed' => 'Ferme ou passe', 'occupied' => 'Indisponible',
            'select_day' => 'Choisissez un jour', 'select_time' => 'Choisissez votre horaire',
            'name' => 'Votre nom', 'email' => 'Votre adresse e-mail', 'reason' => 'Motif du rendez-vous',
            'review' => 'Verifier ma reservation', 'summary' => 'Recapitulatif', 'confirm' => 'Confirmer le rendez-vous',
            'back' => 'Modifier ma selection', 'confirmed' => 'Votre rendez-vous est confirme.',
            'email_sent' => 'Un e-mail de confirmation avec une fiche calendrier vous a ete envoye.',
            'email_failed' => 'Le rendez-vous est bien reserve, mais l e-mail n a pas pu etre envoye. Vous pouvez telecharger la fiche ou relancer l envoi.',
            'retry_email' => 'Relancer l envoi de l e-mail', 'download' => 'Ajouter a mon agenda (.ics)',
            'mail_body' => 'Votre rendez-vous du {date} (Europe/Zurich), pour une duree d une heure, est confirme. La fiche calendrier est jointe a cet e-mail.',
            'event_title' => 'Rendez-vous : {owner} / {guest}',
            'privacy' => 'Votre nom, votre e-mail et le motif seront transmis a la personne avec qui vous prenez rendez-vous.',
            'with' => 'Rendez-vous avec {name}', 'wait' => 'Verification en cours...',
            'book_with' => 'Reserver avec {name}', 'today' => "Aujourd'hui",
            'duration_short' => '1 heure', 'interval_short' => 'Toutes les 30 min',
            'tagline' => 'Un moment pour echanger', 'tagline_hint' => 'Choisissez ce qui vous convient.',
            'step_time' => 'Le creneau', 'step_details' => 'Vos informations', 'step_confirm' => 'Confirmation',
            'steps' => 'Etapes de la reservation', 'availability' => 'Disponibilites',
            'closed_short' => 'Ferme',
            'day_hint' => 'Selectionnez une date dans le calendrier pour decouvrir les horaires disponibles.',
            'no_slots' => 'Aucun creneau disponible pour cette journee.',
            'change_time' => 'Changer de creneau',
            'reason_placeholder' => 'De quoi souhaitez-vous parler ?',
            'summary_hint' => 'Verifiez les details de votre rendez-vous avant de confirmer.',
            'date_time' => 'Date et heure', 'your_details' => 'Vos coordonnees',
            'confirmed_heading' => 'Rendez-vous confirme',
            'confirmed_hint' => 'Votre rendez-vous avec {name} est bien enregistre.',
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
