<?
	require_once("../config.php");
	require_once("../shared_functions.php");
?>
<style>
	.ui-accordion-header {background-color:#DDD; border:2px solid #AAA; padding:10px; margin-bottom:0px; margin-top:5px;}
	.ui-accordion-content {border: 1px solid #DDD; border-top:0px;padding:10px;position:relative;}
	#rememberbtn {text-decoration:underline;}
</style>
<div id="accordion">
<?	
	// Initialise le login
	$connected=checklogin();
	if ($connected) {
?>
  <h3><?=T_("Abonnement mensuel")?></h3>
  <div>
<h1>Soutenez le développement des outils de System D2!</h1>
<p>Nous développons un ensemble de logiciels interconnectés soutenant les organisations de façon efficace et humaniste, dans la philosophie de la gouvernance partagée.</p>

<p>Soutenez-nous en vous abonnant pour un franc par mois, avec la possibilité de résilier à tout moment!</p>
<div id="paypal-button-container-P-2RA96585G5695464VMXHXTMA"></div>
<script>
	$(function () {
	  paypal_sdk.Buttons({
		  style: {
			  shape: 'rect',
			  color: 'gold',
			  layout: 'vertical',
			  label: 'subscribe'
		  },
		  createSubscription: function(data, actions) {
			return actions.subscription.create({

				"plan_id": "P-2RA96585G5695464VMXHXTMA",
				
				"subscriber": 
				{

					"name": 

					{
						"given_name": "John",
						"surname": "Doe"
					},
					"email_address": "customer@example.com",
					

				},
				"application_context": 
				{

					"brand_name": "System D2",
					"locale": "fr-CH",
					"shipping_preference": "NO_SHIPPING",
					"user_action": "SUBSCRIBE_NOW"
					}

				});
		  },
		  onApprove: function(data, actions) {
			alert(data.subscriptionID); // You can add optional success message for the subscriber here
		  }
	  }).render('#paypal-button-container-P-2RA96585G5695464VMXHXTMA'); // Renders the PayPal button
	});
</script>
</div>
<h3><?=T_("Je préfère faire un don unique")?></h3><div>
	<?=T_("Si vous préférez donner une seule fois, c'est parfait également. Cependant, vous n'accèderez pas ainsi à certaines fonctionnalités réservées aux donnateurs réguliers, comme toutes les fonctions associiées à l'IA ou à des services tiers payants, comme le téléchargement au format Word.")?>
<?
	} else {
?>
<h3><?=T_("Faire un don")?></h3><div>
	
Vous pouvez faire un don unique.
<?
	}
?>
<div id="donate-button-container">
<div id="donate-button"></div>

</div></div>
</div>
<script>
	
PayPal.Donation.Button({
env:'production',
hosted_button_id:'58QWBGDQVPWWW',
image: {
src:'https://www.paypalobjects.com/fr_FR/CH/i/btn/btn_donateCC_LG.gif',
alt:'Bouton Faites un don avec PayPal',
title:'PayPal - The safer, easier way to pay online!',
}
}).render('#donate-button');



  $( function() {

	  
    // Crée l'accordeon
    $( "#accordion" ).accordion({heightStyle: "fill"});
    // Le met à jour lorsque la fenêtre change
    window.onresize = function() {
		$( "#accordion" ).accordion( "refresh" );
	};
	
});
</script>
