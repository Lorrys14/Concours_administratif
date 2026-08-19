<?php
require 'config/database.php';
if (session_status() === PHP_SESSION_NONE) session_start();

$title = 'Foire Aux Questions (FAQ) — Concours Administratifs';
require 'includes/header.php';
?>

<div class="text-center mb-5 py-4 bg-primary text-white rounded-4 shadow-sm">
    <h1 class="display-5 fw-bold mb-2"><i class="bi bi-question-circle me-2"></i>Foire Aux Questions</h1>
    <p class="lead mb-0">Retrouvez les réponses aux questions les plus fréquemment posées sur les concours administratifs.</p>
</div>

<div class="row justify-content-center">
    <div class="col-lg-10">
        <div class="accordion shadow-sm border-0 rounded-3 overflow-hidden" id="faqAccordion">

            <div class="accordion-item border-0 border-bottom">
                <h2 class="accordion-header" id="headingOne">
                    <button class="accordion-button fw-bold text-dark fs-5" type="button" data-bs-toggle="collapse" data-bs-target="#collapseOne" aria-expanded="true" aria-controls="collapseOne">
                        <i class="bi bi-person-plus text-primary me-2"></i>Comment créer un compte candidat ?
                    </button>
                </h2>
                <div id="collapseOne" class="accordion-collapse collapse show" aria-labelledby="headingOne" data-bs-parent="#faqAccordion">
                    <div class="accordion-body text-secondary">
                        Pour créer un compte, cliquez sur le bouton <strong>"Inscription"</strong> dans le menu supérieur. Renseignez vos informations personnelles (Nom, Prénom, Date de naissance, Email, Mot de passe) et validez. Vous pourrez ensuite vous connecter immédiatement à votre tableau de bord.
                    </div>
                </div>
            </div>

            <div class="accordion-item border-0 border-bottom">
                <h2 class="accordion-header" id="headingTwo">
                    <button class="accordion-button collapsed fw-bold text-dark fs-5" type="button" data-bs-toggle="collapse" data-bs-target="#collapseTwo" aria-expanded="false" aria-controls="collapseTwo">
                        <i class="bi bi-file-earmark-check text-primary me-2"></i>Quels sont les documents obligatoires pour postuler ?
                    </button>
                </h2>
                <div id="collapseTwo" class="accordion-collapse collapse" aria-labelledby="headingTwo" data-bs-parent="#faqAccordion">
                    <div class="accordion-body text-secondary">
                        Les pièces obligatoires comprennent :
                        <ul class="mt-2">
                            <li>Une <strong>photo d'identité officielle récente</strong> (JPG ou PNG) ;</li>
                            <li>Une pièce d'identité en cours de validité (CNI, Passeport ou Attestation d'identité) ;</li>
                            <li>Le diplôme requis (ou l'attestation de réussite officielle) ;</li>
                            <li>Un extrait d'acte de naissance lisible.</li>
                        </ul>
                        Tous les fichiers doivent être téléversés au format PDF, JPG ou PNG (maximum 5 Mo par fichier).
                    </div>
                </div>
            </div>

            <div class="accordion-item border-0 border-bottom">
                <h2 class="accordion-header" id="headingThree">
                    <button class="accordion-button collapsed fw-bold text-dark fs-5" type="button" data-bs-toggle="collapse" data-bs-target="#collapseThree" aria-expanded="false" aria-controls="collapseThree">
                        <i class="bi bi-printer text-primary me-2"></i>Comment télécharger ma convocation ?
                    </button>
                </h2>
                <div id="collapseThree" class="accordion-collapse collapse" aria-labelledby="headingThree" data-bs-parent="#faqAccordion">
                    <div class="accordion-body text-secondary">
                        Lors de l'inscription, vous téléversez votre photo d'identité. <strong>Vous ne choisissez pas votre centre de composition</strong> lors de l'inscription : celui-ci vous est automatiquement attribué par la commission administrative lors de la vérification de votre dossier.<br><br>
                        Une fois votre dossier validé par l'administration, votre fiche de convocation (portant votre photo et votre centre d'examen) est générée. Rendez-vous dans votre espace candidat, rubrique <strong>"Mes Candidatures"</strong>, puis cliquez sur <strong>"Convocation"</strong> pour l'imprimer.
                    </div>
                </div>
            </div>

            <div class="accordion-item border-0 border-bottom">
                <h2 class="accordion-header" id="headingFour">
                    <button class="accordion-button collapsed fw-bold text-dark fs-5" type="button" data-bs-toggle="collapse" data-bs-target="#collapseFour" aria-expanded="false" aria-controls="collapseFour">
                        <i class="bi bi-trophy text-primary me-2"></i>Où et quand consulter les résultats ?
                    </button>
                </h2>
                <div id="collapseFour" class="accordion-collapse collapse" aria-labelledby="headingFour" data-bs-parent="#faqAccordion">
                    <div class="accordion-body text-secondary">
                        Les résultats officiels sont publiés dans l'onglet <strong>"Résultats"</strong> accessible depuis le menu principal dès la clôture des délibérations. Vous pouvez rechercher directement vos notes avec votre <strong>N° de candidature</strong> ou consulter la liste définitive des candidats admis par ordre de mérite.
                    </div>
                </div>
            </div>

        </div>
    </div>
</div>

<?php require 'includes/footer.php'; ?>
