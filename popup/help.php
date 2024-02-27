<?
	require_once("../config.php");
	require_once("../shared_functions.php");
?>
<html>
<script>

  $( function() {
     $( "#accordion" ).accordion({heightStyle: "fill"});
    // Le met à jour lorsque la fenêtre change
    window.onresize = function() {
		$( "#accordion" ).accordion( "refresh" );
	};
	 } );

</script>
<div id="accordion">
  <h3><?=T_("Comment ça marche ?")?></h3>
  <div>
    <p>
    <?=T_("Une image valant 100 mots, voici une petite présentation vidéo de comment ça marche.")?>
    </p>
    <iframe width="560" height="315" src="https://www.youtube.com/embed/dIG_Y5NX5iQ?si=FtYRbaVXo1rowbpP" title="YouTube video player" frameborder="0" allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture; web-share" allowfullscreen></iframe>
  </div>
  <h3><?=T_("Où sont stoquées les données ?")?></h3>
  <div>
    <?=T_("<p>Dans la version tout public, les données sont stoquées sur votre ordinateur. Aucune information n'est envoyée sur le réseau. En conséquent, si vous changer d'ordinateur (et même si vous changez de navigateur, par exemple en passant de Firefox à Edge), vous ne retrouverez pas le PV en cours.</p><p>Cela signifie que vous pouvez travailler sur plusieurs documents en même temps, si vous avez plusieurs navigateurs. Mais cela signifie également que pour un navigateur donné, vous ne pouvez travailler que sur un seul PV à la fois: vous devez le terminer, l'imprimer (par exemple en PDF), puis en recommencer un nouveau sans pouvoir revenir en arrière.</p>")?>
  </div>
  <h3><?=T_("A quoi ça sert ?")?></h3>
  <div>
    <p>
    <?=T_("En gouvernance partagée, une des particularité des réunions dite opérationnelles est de fonctionner avec un ordre du jour à la volée: il est construit en début de réunion avec ce que chaque personne amène, et parfois se complète même en cours de réunion.")?>
    </p>
   <p>
    <?=T_("C'est pourquoi il est nécessire d'avoir une bonne vision de l'ordre du jour et de l'avancement de l'équipe dans celui-ci. Grâce à l'affichage dans la partie de droite de celui-ci, et avec le rappel du temps estimé de chaque point ainsi que de l'heure de fin qui en découle permet de mieux piloter la réunion.")?>
    </p> 
   <p>
    <?=T_("Dans la version de base, le système est prévu pour être aux mains du rôle mémoire, et son contenu projeté sur un écran à la vue de l'équipe et du rôle facilitation.")?>
    </p>
 </div>
  <h3><?=T_("Qui a développé ce programme ?")?></h3>
  <div>
    <p>
<?=T_("David Dräyer, concepteur d'OpenMyOrganization, forme et accompagne les organisations qui souhaitent faire le pas vers la Gouvernance Partagée. Au cours de ses formations, il est fréquemment confronté à la nécessité de disposer d'un système simple permettant une prise de notes efficace. Un simple document Word s'avère souvent peu adapté, car il devient rapidement difficile de maintenir une bonne visibilité de l'ordre du jour et de suivre l'avancement de la réunion.")?>
    </p>
    <p>
<?=T_("C'est la raison pour laquelle il a développé ce programme, le mettant à disposition de ses clients ainsi que de toute personne ayant besoin d'un outil facile à prendre en main pour la rédaction de comptes rendus.")?>
    </p>
    <p>
	<?=T_("Pour en savoir plus:")?>
	<ul>
	<li><a href='https://www.linkedin.com/in/daviddraeyer/' target='blank'><?=T_("David Dräyer sur Linkedin")?></a></li>
	<li><a href='https://instantz.org' target='blank'><?=T_("L'instant Z, formation et accompagnement en gouvernance partagée")?>
	<li><a href='https://openmyorganization.org' target='blank'><?=T_("OpenMyOrganization, le logiciel soutenant la gouvernance partagée")?></a></li>
	</ul>
    </p>
  </div>
</div>
</html>
