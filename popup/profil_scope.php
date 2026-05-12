<?php
require_once("../config.php");
require_once("../shared_functions.php");
require_once("../common/auth.php");
require_once("../common/user_competence_ui.php");

$connected = checklogin();
if (!$connected) {
	die("Login requis");
}

$currentUserId = function_exists('commonGetCurrentUserId')
	? (int)commonGetCurrentUserId()
	: (int)($_SESSION["currentUser"] ?? 0);
$currentOrganizationId = (int)($_SESSION['currentOrganization'] ?? 0);
$requestedScope = isset($_GET['scope']) && $_GET['scope'] === 'organization' ? 'organization' : 'general';

$user = new \dbObject\User();
$user->load($currentUserId);
if (!($user->get("id") > 0)) {
	die("Utilisateur inconnu");
}

$scope = 'general';
$organizationMembership = null;

if ($requestedScope === 'organization' && $currentOrganizationId > 0) {
	$organization = new \dbObject\Organization();
	if ($organization->load($currentOrganizationId)) {
		$organizationMembership = $user->getOrganizationMembership($currentOrganizationId);
		if (!$organizationMembership) {
			$organizationMembership = new \dbObject\UserOrganization();
			$organizationMembership->set('IDuser', (int)$user->getId());
			$organizationMembership->set('IDorganization', $currentOrganizationId);
			$organizationMembership->set('active', true);
		}
		$scope = 'organization';
	}
}

$competenceRows = $user->getCompetenceRowsForScope($scope, $currentOrganizationId, $currentUserId);
$competenceSectionTitle = $scope === 'organization'
	? "Competences liees a l'organisation"
	: 'Competences generales';
$competenceSectionHelp = $scope === 'organization'
	? "Ajoutez ici les competences visibles uniquement dans l'organisation active."
	: 'Ajoutez ici les competences visibles dans toutes vos organisations.';
?>
<div class="profile-panel__scope-fragment" data-profile-loaded-scope="<?= htmlspecialchars($scope, ENT_QUOTES, 'UTF-8') ?>">
	<?php if ($scope === 'organization' && $organizationMembership): ?>
	<p class="profile-panel__scope-help">
		Ces données remplacent le profil général uniquement dans cette organisation. Laissez un champ vide pour continuer à utiliser la valeur générale.
	</p>
	<?php
	$params = array(
		"buttons" => false,
		"action" => "/ajax/saveaccount_organization.php?origin=profil&scope=organization",
		"success" => "profileHandleOrganizationSaved()",
		"fields" => array(
			"image",
			"username",
			"email",
		),
	);
	$organizationMembership->display("adminEdit.php", $params);
	?>
	<div class="profile-panel__actions">
		<button type="button" id="updateprofil-organization">Mettre à jour les données de l'organisation</button>
	</div>
	<script>
	(function () {
		var updateButton = document.getElementById("updateprofil-organization");
		if (!updateButton) {
			return;
		}

		updateButton.addEventListener("click", function () {
			if (window.jQuery && window.jQuery("#formulaire-edit").length) {
				window.jQuery("#formulaire-edit").trigger("submit");
				return;
			}

			alert("jQuery est requis pour ce formulaire.");
		});
	})();
	</script>
	<?php else: ?>
	<?php
	$params = array(
		"buttons" => false,
		"action" => "/ajax/saveaccount.php?origin=profil&scope=general",
		"success" => "profileHandleGeneralSaved()",
		"allowProtectedFields" => true,
		"fields" => array(
			"image",
			"username",
			"firstname",
			"lastname",
			"email",
		),
	);
	$user->display("adminEdit.php", $params);
	?>
	<div class="profile-panel__actions">
		<button type="button" id="updateprofil-general">Mettre à jour les données générales</button>
	</div>
	<script>
	(function () {
		var updateButton = document.getElementById("updateprofil-general");
		if (!updateButton) {
			return;
		}

		updateButton.addEventListener("click", function () {
			if (window.jQuery && window.jQuery("#formulaire-edit").length) {
				window.jQuery("#formulaire-edit").trigger("submit");
				return;
			}

			alert("jQuery est requis pour ce formulaire.");
		});
	})();
	</script>
	<?php endif; ?>

	<section class="profile-panel__competence-section generic-section generic-section--stack">
		<div class="profile-panel__competence-head">
			<h4 class="generic-card-title generic-card-title--section"><?= htmlspecialchars($competenceSectionTitle, ENT_QUOTES, 'UTF-8') ?></h4>
			<p class="profile-panel__scope-help"><?= htmlspecialchars($competenceSectionHelp, ENT_QUOTES, 'UTF-8') ?></p>
		</div>

		<div class="profile-panel__competence-list">
			<?php if (count($competenceRows) === 0): ?>
				<div class="profile-panel__competence-empty">Aucune competence pour ce scope.</div>
			<?php else: ?>
				<?php foreach ($competenceRows as $competenceRow): ?>
					<form class="profile-panel__competence-card generic-soft-panel" data-profile-competence-form="1">
						<input type="hidden" name="scope" value="<?= htmlspecialchars($scope, ENT_QUOTES, 'UTF-8') ?>">
						<input type="hidden" name="id" value="<?= (int)$competenceRow['id'] ?>">

						<div class="profile-panel__competence-grid">
							<label class="profile-panel__competence-field">
								<span>Competence</span>
								<input type="text" name="name" value="<?= htmlspecialchars((string)$competenceRow['name'], ENT_QUOTES, 'UTF-8') ?>" maxlength="190" required>
							</label>

							<label class="profile-panel__competence-field">
								<span>Descriptif</span>
								<input type="text" name="description" value="<?= htmlspecialchars((string)($competenceRow['description'] ?? ''), ENT_QUOTES, 'UTF-8') ?>" maxlength="500" placeholder="Ex: PHP / MySQL">
							</label>

							<label class="profile-panel__competence-field">
								<span>Type</span>
								<select name="category" class="generic-form-control">
									<?php omoRenderCompetenceTypeOptions((string)$competenceRow['category']); ?>
								</select>
							</label>

							<label class="profile-panel__competence-field">
								<span>Votre niveau</span>
								<select name="level" class="generic-form-control" required>
									<?php omoRenderCompetenceLevelOptions((int)$competenceRow['level']); ?>
								</select>
							</label>
						</div>

						<div class="profile-panel__competence-meta">
							<span class="profile-panel__competence-badge"><?= htmlspecialchars((string)$competenceRow['levelLabel'], ENT_QUOTES, 'UTF-8') ?></span>
							<span class="profile-panel__competence-badge profile-panel__competence-badge--muted"><?= htmlspecialchars((string)$competenceRow['categoryLabel'], ENT_QUOTES, 'UTF-8') ?></span>
							<?php if ((int)$competenceRow['validationCount'] > 0): ?>
								<span class="profile-panel__competence-badge profile-panel__competence-badge--muted"><?= (int)$competenceRow['validationCount'] ?> validation<?= (int)$competenceRow['validationCount'] > 1 ? 's' : '' ?></span>
							<?php endif; ?>
						</div>

						<?php if (!empty($competenceRow['validators'])): ?>
							<div class="profile-panel__competence-validators">
								<div class="profile-panel__competence-validators-label">Reconnu par</div>
								<div class="profile-panel__competence-avatar-stack">
									<?php foreach ($competenceRow['validators'] as $validator): ?>
										<?php omoRenderCompetenceAvatar([
											'photoUrl' => (string)($validator['photoUrl'] ?? ''),
											'displayName' => (string)($validator['displayName'] ?? ''),
											'initials' => (string)($validator['initials'] ?? 'P'),
											'levelLabel' => (string)($validator['levelLabel'] ?? ''),
										], 'profile-panel__competence-avatar'); ?>
									<?php endforeach; ?>
								</div>
							</div>
						<?php endif; ?>

						<div class="profile-panel__competence-actions">
							<button type="submit" class="generic-action-button generic-action-button--main">Enregistrer</button>
							<button type="button" class="generic-action-button generic-action-button--secondary" data-profile-competence-delete="1">Supprimer</button>
						</div>
					</form>
				<?php endforeach; ?>
			<?php endif; ?>
		</div>

		<form class="profile-panel__competence-card profile-panel__competence-card--new generic-soft-panel" data-profile-competence-form="1">
			<input type="hidden" name="scope" value="<?= htmlspecialchars($scope, ENT_QUOTES, 'UTF-8') ?>">
			<input type="hidden" name="id" value="">

			<div class="profile-panel__competence-grid">
				<label class="profile-panel__competence-field">
					<span>Nouvelle competence</span>
					<input type="text" name="name" value="" maxlength="190" required>
				</label>

				<label class="profile-panel__competence-field">
					<span>Descriptif</span>
					<input type="text" name="description" value="" maxlength="500" placeholder="Ex: PHP / MySQL">
				</label>

				<label class="profile-panel__competence-field">
					<span>Type</span>
					<select name="category" class="generic-form-control">
						<?php omoRenderCompetenceTypeOptions('technical'); ?>
					</select>
				</label>

				<label class="profile-panel__competence-field">
					<span>Votre niveau</span>
					<select name="level" class="generic-form-control" required>
						<?php omoRenderCompetenceLevelOptions(0, true); ?>
					</select>
				</label>
			</div>

			<div class="profile-panel__competence-actions">
				<button type="submit" class="generic-action-button generic-action-button--main">Ajouter</button>
			</div>
		</form>

		<div class="profile-panel__competence-feedback" data-profile-competence-feedback="1"></div>
	</section>

	<script>
	(function () {
		var fragment = document.querySelector('.profile-panel__scope-fragment[data-profile-loaded-scope="<?= htmlspecialchars($scope, ENT_QUOTES, 'UTF-8') ?>"]');
		if (!fragment) {
			return;
		}

		var feedback = fragment.querySelector('[data-profile-competence-feedback="1"]');

		function setFeedback(message, type) {
			if (!feedback) {
				return;
			}

			feedback.textContent = message || '';
			feedback.className = 'profile-panel__competence-feedback';
			if (type === 'success') {
				feedback.classList.add('is-success');
			} else if (type === 'error') {
				feedback.classList.add('is-error');
			}
		}

		function executeEmbeddedScripts(container) {
			Array.prototype.forEach.call(container.querySelectorAll('script'), function (script) {
				var replacement = document.createElement('script');

				Array.prototype.forEach.call(script.attributes, function (attribute) {
					replacement.setAttribute(attribute.name, attribute.value);
				});

				if (!replacement.src) {
					replacement.textContent = script.textContent || '';
				}

				script.parentNode.replaceChild(replacement, script);
			});
		}

		function reloadScope() {
			var scopeName = fragment.getAttribute('data-profile-loaded-scope') || 'general';
			var container = document.getElementById('profileScopeContent');
			if (!container) {
				return;
			}

			fetch('/popup/profil_scope.php?scope=' + encodeURIComponent(scopeName), {
				credentials: 'same-origin',
				headers: {
					'X-Requested-With': 'XMLHttpRequest'
				}
			})
				.then(function (response) {
					if (!response.ok) {
						throw new Error('load');
					}

					return response.text();
				})
				.then(function (html) {
					container.innerHTML = html;
					executeEmbeddedScripts(container);
				})
				.catch(function () {
					setFeedback('Impossible de recharger les competences.', 'error');
				});
		}

		function parseResponse(response) {
			return response.text().then(function (text) {
				try {
					return JSON.parse(text);
				} catch (error) {
					return {
						status: false,
						message: 'Reponse serveur invalide.'
					};
				}
			});
		}

		fragment.querySelectorAll('[data-profile-competence-form="1"]').forEach(function (form) {
			form.addEventListener('submit', function (event) {
				event.preventDefault();
				setFeedback('', '');

				var formData = new FormData(form);

				fetch('/ajax/user_competence_save.php', {
					method: 'POST',
					credentials: 'same-origin',
					body: formData,
					headers: {
						'X-Requested-With': 'XMLHttpRequest'
					}
				})
					.then(parseResponse)
					.then(function (result) {
						if (!result || !result.status) {
							setFeedback(result && result.message ? result.message : "Impossible d'enregistrer cette competence.", 'error');
							return;
						}

						setFeedback(result.message || 'Competence enregistree.', 'success');
						reloadScope();
					})
					.catch(function () {
						setFeedback("Impossible d'enregistrer cette competence.", 'error');
					});
			});

			var deleteButton = form.querySelector('[data-profile-competence-delete="1"]');
			if (!deleteButton) {
				return;
			}

			deleteButton.addEventListener('click', function () {
				var identifier = form.querySelector('input[name="id"]');
				if (!identifier || !identifier.value) {
					return;
				}

				if (!confirm('Supprimer cette competence ?')) {
					return;
				}

				setFeedback('', '');

				var formData = new FormData();
				formData.append('id', identifier.value);

				fetch('/ajax/user_competence_delete.php', {
					method: 'POST',
					credentials: 'same-origin',
					body: formData,
					headers: {
						'X-Requested-With': 'XMLHttpRequest'
					}
				})
					.then(parseResponse)
					.then(function (result) {
						if (!result || !result.status) {
							setFeedback(result && result.message ? result.message : "Impossible de supprimer cette competence.", 'error');
							return;
						}

						setFeedback(result.message || 'Competence supprimee.', 'success');
						reloadScope();
					})
					.catch(function () {
						setFeedback("Impossible de supprimer cette competence.", 'error');
					});
			});
		});
	})();
	</script>
</div>
