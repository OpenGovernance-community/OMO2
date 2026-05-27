<?php
	namespace dbObject;

	class UserOrganization extends DbObject
	{
	    public static function tableName()
		{
			return 'user_organization';
		}

		public static function rules()
		{
			return [
				[['IDuser', 'IDorganization'], 'required'],
				[['id'], 'integer'],
				[['IDuser', 'IDorganization'], 'fk'],
				[['username', 'email'], 'string'],
				[['presentation'], 'text'],
				[['image'], 'sizedimage'],
				[['parameters'], 'parameters'],
				[['datecreation', 'dateconnexion'], 'datetime'],
				[['active'], 'boolean'],
				[['id'], 'safe'],
			];
		}

		public static function attributeLabels()
		{
			return [
				'id' => 'ID',
				'IDuser' => 'Personne',
				'IDorganization' => 'Organisation',
				'username' => 'Identifiant',
				'email' => 'E-mail',
				'presentation' => 'Presentation',
				'image' => 'Photo',
				'parameters' => 'Parametres',
				'datecreation' => 'Creation',
				'dateconnexion' => 'Derniere connexion',
				'active' => 'Actif',
			];
		}

		public static function attributeDescriptions()
		{
			return [
				'IDuser' => 'Utilisateur associe a cette organisation.',
				'IDorganization' => 'Organisation concernee par ce lien.',
				'username' => 'Identifiant affiche specifiquement dans cette organisation. Laissez vide pour utiliser la valeur generale.',
				'email' => 'Adresse e-mail affichee specifiquement dans cette organisation. Laissez vide pour utiliser la valeur generale.',
				'presentation' => 'Presentation visible uniquement dans cette organisation. Laissez vide pour reutiliser la presentation generale.',
				'image' => 'Photo de profil specifique a cette organisation. Si elle est vide, la photo generale est utilisee.',
				'parameters' => 'Parametres specifiques au role de cette personne dans l organisation.',
				'datecreation' => 'Date de creation du lien avec l organisation.',
				'dateconnexion' => 'Derniere activite connue dans cette organisation.',
			];
		}

		public static function attributeLength()
		{
			return [
				'image' => [320, 320],
				'username' => 250,
				'email' => 250,
				'presentation' => 2000,
			];
		}

		public static function getOrder()
		{
			return "dateconnexion DESC, datecreation DESC, id DESC";
		}

		public function getUserDisplayName()
		{
			$user = $this->get('user');
			if (!$user || (int)$user->getId() <= 0) {
				return 'Profil';
			}

			$firstname = trim((string)$user->get('firstname'));
			$lastname = trim((string)$user->get('lastname'));
			$fullName = trim($firstname . ' ' . $lastname);

			if ($fullName !== '') {
				return $fullName;
			}

			$username = $this->getScopedUsername();
			if ($username !== '') {
				return $username;
			}

			$email = $this->getScopedEmail();
			if ($email !== '') {
				return $email;
			}

			return 'Profil';
		}

		public function getUserSecondaryLabel()
		{
			$username = $this->getScopedUsername();
			if ($username !== '') {
				return '@' . $username;
			}

			return $this->getScopedEmail();
		}

		public function getUserInitials()
		{
			$user = $this->get('user');
			if ($user && (int)$user->getId() > 0) {
				$parts = array_filter([
					trim((string)$user->get('firstname')),
					trim((string)$user->get('lastname')),
				], static function ($value) {
					return $value !== '';
				});

				if (count($parts) > 0) {
					$initials = '';
					foreach ($parts as $part) {
						$initials .= mb_substr($part, 0, 1, 'UTF-8');
						if (mb_strlen($initials, 'UTF-8') >= 2) {
							break;
						}
					}

					if ($initials !== '') {
						return mb_strtoupper($initials, 'UTF-8');
					}
				}
			}

			$label = $this->getUserDisplayName();
			$words = preg_split('/\s+/u', $label) ?: [];
			$initials = '';

			foreach ($words as $word) {
				$word = trim((string)$word);
				if ($word === '') {
					continue;
				}

				$initials .= mb_substr($word, 0, 1, 'UTF-8');
				if (mb_strlen($initials, 'UTF-8') >= 2) {
					break;
				}
			}

			if ($initials === '') {
				$initials = mb_substr($label, 0, 1, 'UTF-8');
			}

			return mb_strtoupper($initials !== '' ? $initials : 'P', 'UTF-8');
		}

		public function isOrganizationAdmin()
		{
			return (bool)$this->getParameter('isAdmin');
		}

		public function setOrganizationAdmin($isAdmin)
		{
			$parameters = json_decode((string)$this->get('parameters'), true);
			if (!is_array($parameters)) {
				$parameters = array();
			}

			if ($isAdmin) {
				$parameters['isAdmin'] = true;
			} else {
				unset($parameters['isAdmin']);
			}

			$this->set('parameters', $parameters);
			return $this->save();
		}

		public function getProfilePhotoUrl()
		{
			$photoUrl = trim((string)$this->get('image'));
			if ($photoUrl !== '') {
				return $photoUrl;
			}

			$photoUrl = trim((string)$this->getParameter('photo'));
			if ($photoUrl !== '') {
				return $photoUrl;
			}

			$photoUrl = trim((string)$this->getParameter('photoUrl'));
			if ($photoUrl !== '') {
				return $photoUrl;
			}

			$user = $this->get('user');
			if ($user && method_exists($user, 'getProfilePhotoUrl')) {
				return (string)$user->getProfilePhotoUrl();
			}

			return '';
		}

		public function getScopedUsername()
		{
			$username = trim((string)$this->get('username'));
			if ($username !== '') {
				return $username;
			}

			$user = $this->get('user');
			if (!$user || (int)$user->getId() <= 0) {
				return '';
			}

			return trim((string)$user->get('username'));
		}

		public function getScopedEmail()
		{
			$email = trim((string)$this->get('email'));
			if ($email !== '') {
				return $email;
			}

			$user = $this->get('user');
			if (!$user || (int)$user->getId() <= 0) {
				return '';
			}

			return trim((string)$user->get('email'));
		}

		public function getScopedPresentation()
		{
			$presentation = trim((string)$this->get('presentation'));
			if ($presentation !== '') {
				return $presentation;
			}

			$user = $this->get('user');
			if ($user && method_exists($user, 'getScopedPresentation')) {
				return (string)$user->getScopedPresentation();
			}

			return '';
		}

		public function getGlobalCreatedAt()
		{
			$user = $this->get('user');
			if (!$user || (int)$user->getId() <= 0) {
				return null;
			}

			$value = $user->get('datecreation');
			return $value instanceof \DateTimeInterface ? $value : null;
		}

		public function getGlobalLastConnectionAt()
		{
			$user = $this->get('user');
			if (!$user || (int)$user->getId() <= 0) {
				return null;
			}

			$value = $user->get('dateconnexion');
			return $value instanceof \DateTimeInterface ? $value : null;
		}

		public function canEdit()
		{
			$currentUserId = (int)\commonGetCurrentUserId();
			if ($currentUserId <= 0) {
				return false;
			}

			return $currentUserId === (int)$this->get('IDuser');
		}
	}

?>
