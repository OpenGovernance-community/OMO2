<?php
	namespace dbObject;

	class Application extends DbObject
	{
	    public static function tableName()
		{
			return 'application';
		}

		public static function rules()
		{
			return [
				[['label'], 'required'],
				[['id', 'position'], 'integer'],
				[['label', 'hash', 'directory', 'icon', 'drawer', 'url', 'navigationmode'], 'string'],
				[['requires_login', 'active'], 'boolean'],
				[['id'], 'safe'],
			];
		}

		public static function attributeLabels()
		{
			return [
				'id' => 'ID',
				'label' => 'Libellé',
				'hash' => 'Hash',
				'directory' => 'Répertoire',
				'icon' => 'Icône',
				'drawer' => 'Drawer',
				'url' => 'URL',
				'navigationmode' => 'Mode de navigation',
				'position' => 'Position',
				'requires_login' => 'Connexion requise',
				'active' => 'Actif',
			];
		}

		public static function attributeDescriptions()
		{
			return [
				'label' => 'Texte visible dans la barre latérale',
				'hash' => 'Hash utilisé pour le routage OMO',
				'directory' => 'Répertoire du module dans /omo/api',
				'icon' => 'Chemin de l’icône à afficher',
				'drawer' => 'Identifiant du drawer à ouvrir',
				'url' => 'URL du contenu à charger pour ce module',
				'navigationmode' => 'panel pour revenir à la structure, drawer pour ouvrir un module',
				'position' => 'Ordre d’affichage',
				'requires_login' => 'Masque le module aux visiteurs non connectés',
				'active' => 'Permet de désactiver globalement le module',
			];
		}

		public static function attributeLength()
		{
			return [
				'label' => 100,
				'hash' => 100,
				'directory' => 100,
				'icon' => 255,
				'drawer' => 100,
				'url' => 255,
				'navigationmode' => 20,
			];
		}

		public static function getOrder()
		{
			return "position ASC, label ASC";
		}

		public function getRouteHash()
		{
			return trim((string)$this->get('hash'));
		}

		public function getNavigationMode()
		{
			$mode = strtolower(trim((string)$this->get('navigationmode')));
			return in_array($mode, ['panel', 'drawer'], true) ? $mode : 'drawer';
		}

		public function getResolvedUrl()
		{
			$url = trim((string)$this->get('url'));
			if ($url !== '') {
				return $url;
			}

			$directory = trim((string)$this->get('directory'), "/ \t\n\r\0\x0B");
			if ($directory !== '') {
				return 'api/' . $directory . '/index.php';
			}

			return '';
		}

		public function getResolvedDrawer()
		{
			$drawer = trim((string)$this->get('drawer'));
			if ($drawer !== '') {
				return $drawer;
			}

			$hash = $this->getRouteHash();
			if ($hash !== '') {
				return 'drawer_' . $hash;
			}

			return '';
		}

		public function requiresLogin()
		{
			return (bool)$this->get('requires_login');
		}
	}

?>
